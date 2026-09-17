<?php
require_once __DIR__.'/../shared/bootstrap.php';
$hid = require_role('hod');

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

if (!in_array($action, ['approve','reject','release'], true))
{
    json_response(['success'=>false,'message'=>'Invalid action.']);
}

/* HOD can release an auto-locked request, or approve/reject a normal forwarded request. */
if ($action === 'release')
{
    $check = $conn->prepare("SELECT lr.id FROM leave_requests lr JOIN users hu ON hu.id=lr.hod_id AND hu.role='hod' AND hu.is_active=1 JOIN users su ON su.id=lr.student_id AND su.department=hu.department WHERE lr.id=? AND lr.hod_id=? AND lr.advisor_status=2 AND lr.forwarded_to_hod=1 AND lr.hod_status=4 LIMIT 1");
}
else
{
    $check = $conn->prepare("SELECT lr.id FROM leave_requests lr JOIN users hu ON hu.id=lr.hod_id AND hu.role='hod' AND hu.is_active=1 JOIN users su ON su.id=lr.student_id AND su.department=hu.department WHERE lr.id=? AND lr.hod_id=? AND lr.advisor_status=1 AND lr.forwarded_to_hod=1 AND lr.hod_status=4 LIMIT 1");
}
if (!$check)
{
    json_response(['success'=>false,'message'=>'Database error while checking the request: '.$conn->error],500);
}
$check->bind_param('ii', $id, $hid);
if (!$check->execute())
{
    $err = $check->error;
    $check->close();
    json_response(['success'=>false,'message'=>'Database error while checking the request: '.$err],500);
}
$found = $check->get_result()->num_rows > 0;
$check->close();

if (!$found)
{
    json_response(['success'=>false,'message'=>'This leave request is not forwarded to you or has already been processed.']);
}

if ($action === 'release')
{
    $q = $conn->prepare("UPDATE leave_requests SET advisor_status=4, forwarded_to_hod=0, hod_status=4, advisor_deadline_at=DATE_ADD(NOW(), INTERVAL 48 HOUR), advisor_lock_notified=0 WHERE id=? AND hod_id=? AND advisor_status=2 AND forwarded_to_hod=1 AND hod_status=4");
    if (!$q) json_response(['success'=>false,'message'=>'Database error while preparing lock release: '.$conn->error],500);
    $q->bind_param('ii',$id,$hid);
    if (!$q->execute())
{ $err=$q->error; $q->close(); json_response(['success'=>false,'message'=>'Unable to release lock. Database error: '.$err],500); }
    if ($q->affected_rows < 1)
{ $q->close(); json_response(['success'=>false,'message'=>'The locked request could not be released. Please refresh the page.'],409); }
    $q->close();
    json_response(['success'=>true,'message'=>'Lock released. The leave request has been returned to the Advisor for a new 48-hour review window.']);
}

$newStatus = ($action === 'approve') ? 2 : 5;

/* The request was verified above. Use a simpler update condition so a valid
   forwarded HOD request is not rejected because of a redundant status check. */
$q = $conn->prepare('UPDATE leave_requests SET hod_status=?, student_notified=0 WHERE id=? AND hod_id=?');
if (!$q)
{
    json_response(['success'=>false,'message'=>'Database error while preparing HOD update: '.$conn->error],500);
}
$q->bind_param('iii', $newStatus, $id, $hid);
if (!$q->execute())
{
    $err = $q->error;
    $q->close();
    json_response(['success'=>false,'message'=>'Unable to update request. Database error: '.$err],500);
}
if ($q->affected_rows < 1)
{
    $q->close();
    json_response(['success'=>false,'message'=>'Unable to update request. No matching HOD request was changed. Please refresh the page and try again.'],409);
}
$q->close();

$studentEmail = '';
$studentName = '';
$regNo = '';
$dateFrom = '';
$dateTo = '';
$reason = '';

$info = $conn->prepare('SELECT u.email,u.full_name,lr.reg_no,lr.date_from,lr.date_to,lr.reason FROM leave_requests lr INNER JOIN users u ON u.id=lr.student_id WHERE lr.id=? LIMIT 1');
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
$emailSent = sendLeaveStatusEmail($studentEmail,$studentName,$regNo,$dateFrom,$dateTo,$reason,'HOD',$action);

$message = $action === 'approve' ? 'Leave approved successfully.' : 'Leave rejected successfully.';
if ($emailSent)
{
    $message .= ' Email notification sent to the student.';
}
else
{
    $message .= ' Email notification could not be sent. Check the student email and Gmail App Password.';
}

json_response(['success'=>true,'message'=>$message,'email_sent'=>$emailSent]);
