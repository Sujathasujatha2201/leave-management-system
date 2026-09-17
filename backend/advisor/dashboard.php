<?php
require_once __DIR__.'/../shared/bootstrap.php';
require_once __DIR__.'/../shared/auto_lock.php';

$id = require_role('advisor');

/* Check and lock expired faculty-pending requests whenever the faculty dashboard loads. */
autoLockExpiredLeaves($conn);

$s = $conn->prepare("SELECT full_name,department FROM users WHERE id=? AND role='advisor' AND is_active=1");
$s->bind_param('i',$id);
$s->execute();
$faculty = $s->get_result()->fetch_assoc();
$s->close();

if (!$faculty)
{
    json_response(['success'=>false,'message'=>'Advisor details not found.'],404);
}

$c = $conn->prepare("
    SELECT
        COUNT(*) total,
        SUM(advisor_status=4) pending,
        SUM(advisor_status=1) approved,
        SUM(advisor_status=3) rejected,
        SUM(advisor_status=2) locked
    FROM leave_requests
    WHERE advisor_id=?
");
$c->bind_param('i',$id);
$c->execute();
$counts = $c->get_result()->fetch_assoc();
$c->close();

foreach(['total','pending','approved','rejected','locked'] as $k)
{
    $counts[$k] = (int)($counts[$k] ?? 0);
}

$q = $conn->prepare("
    SELECT lr.id,lr.reg_no,lr.date_from,lr.date_to,lr.reason,lr.proof,
           lr.advisor_status,lr.created_at,lr.advisor_deadline_at,lr.forwarded_to_hod,lr.hod_status,u.full_name,u.department
    FROM leave_requests lr
    JOIN users u ON u.id=lr.student_id
    WHERE lr.advisor_id=?
    ORDER BY lr.id DESC
");
$q->bind_param('i',$id);
$q->execute();
$res=$q->get_result();
$rows=[];
while($r=$res->fetch_assoc()) $rows[]=$r;
$q->close();

json_response([
    'success'=>true,
    'faculty'=>$faculty,
    'counts'=>$counts,
    'leaves'=>$rows
]);
