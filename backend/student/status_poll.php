<?php

require_once __DIR__.'/../shared/bootstrap.php';

$id=require_role('student');

$stmt=$conn->prepare('SELECT id,advisor_status,hod_status,forwarded_to_hod FROM leave_requests WHERE student_id=? ORDER BY id DESC');
$stmt->bind_param('i',$id);
$stmt->execute();
$res=$stmt->get_result();

$data=[];

while($r=$res->fetch_assoc())
    $data[]=$r;

$stmt->close();

json_response(['success'=>true,'requests'=>$data]);