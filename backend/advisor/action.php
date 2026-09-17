<?php
require_once __DIR__.'/../shared/bootstrap.php';
require_once __DIR__.'/../shared/auto_lock.php';

$fid = require_role('advisor');

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    json_response(['success'=>false,'message'=>'Invalid request.'],405);
}

$id = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($id <= 0)
{
    json_response(['success'=>false,'message'=>'Invalid leave request.']);
}

if (!in_array($action, ['approve','reject'], true))
{
    json_response(['success'=>false,'message'=>'Invalid action.']);
}

/* Lock any overdue faculty requests before allowing an action. */
autoLockExpiredLeaves($conn);

/* Check ownership/status and capture created_at for a second deadline check. */
$check = $conn->prepare("
    SELECT lr.id, lr.advisor_status, lr.created_at
    FROM leave_requests lr
    JOIN users au ON au.id=lr.advisor_id AND au.role='advisor' AND au.is_active=1
    JOIN users su ON su.id=lr.student_id AND su.department=au.department
    WHERE lr.id=? AND lr.advisor_id=?
    LIMIT 1
");
if (!$check)
{
    json_response(['success'=>false,'message'=>'Database error while checking the request: '.$conn->error],500);
}

$check->bind_param('ii', $id, $fid);
if (!$check->execute())
{
    $err = $check->error;
    $check->close();
    json_response(['success'=>false,'message'=>'Database error while checking the request: '.$err],500);
}

$row = $check->get_result()->fetch_assoc();
$check->close();

if (!$row)
{
    json_response(['success'=>false,'message'=>'This leave request is not assigned to you.'],404);
}

$currentStatus = (int)$row['advisor_status'];

if ($currentStatus === 2)
{
    json_response([
        'success'=>false,
        'message'=>'This leave request is locked because the 48-hour faculty approval deadline has expired.'
    ],409);
}

if ($currentStatus !== 4)
{
    json_response([
        'success'=>false,
        'message'=>'This leave request is not pending and cannot be changed.'
    ],409);
}

/* Final race-safe deadline check. */
$deadlineCheck = $conn->prepare("
    SELECT lr.id
    FROM leave_requests lr
    JOIN users au ON au.id=lr.advisor_id AND au.role='advisor' AND au.is_active=1
    JOIN users su ON su.id=lr.student_id AND su.department=au.department
    WHERE lr.id=? AND lr.advisor_id=? AND lr.advisor_status=4
      AND ((advisor_deadline_at IS NOT NULL AND advisor_deadline_at <= NOW()) OR (advisor_deadline_at IS NULL AND created_at <= DATE_SUB(NOW(), INTERVAL 48 HOUR)))
    LIMIT 1
");
if ($deadlineCheck)
{
    $deadlineCheck->bind_param('ii',$id,$fid);
    $deadlineCheck->execute();
    $expired = $deadlineCheck->get_result()->num_rows > 0;
    $deadlineCheck->close();

    if ($expired)
{
        autoLockExpiredLeaves($conn);
        json_response([
            'success'=>false,
            'message'=>'This leave request has reached the 48-hour deadline and is now locked.'
        ],409);
    }
}

if ($action === 'approve')
{
    $q = $conn->prepare("
        UPDATE leave_requests
        SET advisor_status=1, forwarded_to_hod=1, hod_status=4
        WHERE id=? AND advisor_id=? AND advisor_status=4
    ");
}
else
{
    $q = $conn->prepare("
        UPDATE leave_requests
        SET advisor_status=3, forwarded_to_hod=0
        WHERE id=? AND advisor_id=? AND advisor_status=4
    ");
}

if (!$q)
{
    json_response(['success'=>false,'message'=>'Database error while preparing update: '.$conn->error],500);
}

$q->bind_param('ii', $id, $fid);

if (!$q->execute())
{
    $err = $q->error;
    $q->close();
    json_response(['success'=>false,'message'=>'Unable to update request. Database error: '.$err],500);
}

if ($q->affected_rows < 1)
{
    $q->close();
    json_response([
        'success'=>false,
        'message'=>'The request could not be changed. It may have expired or already been processed.'
    ],409);
}
$q->close();

/* Get student details after the status update for the existing email notification. */
$studentEmail = '';
$studentName = '';
$regNo = '';
$dateFrom = '';
$dateTo = '';
$reason = '';

$info = $conn->prepare("
    SELECT u.email,u.full_name,lr.reg_no,lr.date_from,lr.date_to,lr.reason
    FROM leave_requests lr
    INNER JOIN users u ON u.id=lr.student_id
    WHERE lr.id=? LIMIT 1
");
if ($info)
{
    $info->bind_param('i',$id);
    if ($info->execute())
{
        $info->bind_result($studentEmail,$studentName,$regNo,$dateFrom,$dateTo,$reason);
        $info->fetch();
    }
    $info->close();
}

require_once __DIR__.'/../shared/email_config.php';
$emailSent = sendLeaveStatusEmail(
    $studentEmail,$studentName,$regNo,$dateFrom,$dateTo,$reason,'Advisor',$action
);

$message = $action === 'approve'
    ? 'Leave approved successfully.'
    : 'Leave rejected successfully.';

if ($emailSent)
{
    $message .= ' Email notification sent to the student.';
}
else
{
    $message .= ' Email notification could not be sent. Check the student email and Gmail App Password.';
}

json_response(['success'=>true,'message'=>$message,'email_sent'=>$emailSent]);