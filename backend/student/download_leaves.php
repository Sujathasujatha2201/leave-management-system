<?php

session_start();

if(!isset($_SESSION['user_id'])||($_SESSION['role']??'')!=='student')
{
    http_response_code(401);
    exit('Unauthorized');
}

require_once __DIR__.'/../../db.php';

$id=(int)$_SESSION['user_id'];

$stmt=$conn->prepare('SELECT id,reg_no,date_from,date_to,reason,advisor_status,hod_status,created_at FROM leave_requests WHERE student_id=? ORDER BY id DESC');
$stmt->bind_param('i',$id);
$stmt->execute();
$result=$stmt->get_result();

header('Content-Type:application/vnd.ms-excel');
header('Content-Disposition:attachment; filename=My_Leave_Requests.xls');
header('Pragma:no-cache');
header('Expires:0');

echo '<table border="1"><tr><th>Leave ID</th><th>Register No</th><th>From Date</th><th>To Date</th><th>Reason</th><th>Advisor Status</th><th>HOD Status</th><th>Final Status</th><th>Applied Date</th></tr>';

while($r=$result->fetch_assoc())
{ 
    $a=$r['advisor_status']==1?'Approved':($r['advisor_status']==3?'Rejected':($r['advisor_status']==2?'Locked':'Pending'));

    $h=$r['advisor_status']==2?'Not Forwarded':($r['hod_status']==2?'Approved':($r['hod_status']==5?'Rejected':'Pending'));

    $f=$r['advisor_status']==2?'Locked':(($r['advisor_status']==3||$r['hod_status']==5)?'Rejected':(($r['advisor_status']==1&&$r['hod_status']==2)?'Approved':'Pending'));

    echo '<tr><td>'.htmlspecialchars($r['id']).'</td><td>'.htmlspecialchars($r['reg_no']).'</td><td>'.htmlspecialchars($r['date_from']).'</td><td>'.htmlspecialchars($r['date_to']).'</td><td>'.htmlspecialchars($r['reason']).'</td><td>'.$a.'</td><td>'.$h.'</td><td>'.$f.'</td><td>'.htmlspecialchars($r['created_at']).'</td></tr>';
}

echo '</table>';

exit;