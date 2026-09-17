<?php

require_once __DIR__.'/../shared/bootstrap.php';

$id=require_role('student');

$stmt=$conn->prepare('SELECT id,date_from,date_to,reason,proof,advisor_status,hod_status,forwarded_to_hod,created_at FROM leave_requests WHERE student_id=? ORDER BY id DESC');
$stmt->bind_param('i',$id);
$stmt->execute();
$res=$stmt->get_result();

$rows=[];

while($r=$res->fetch_assoc())
    $rows[]=$r;

$stmt->close();

json_response(['success'=>true,'leaves'=>$rows]);