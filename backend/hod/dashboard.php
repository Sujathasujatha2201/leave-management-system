<?php

require_once __DIR__.'/../shared/bootstrap.php';

$id=require_role('hod');

$s=$conn->prepare("SELECT full_name,department FROM users WHERE id=? AND role='hod' AND is_active=1");
$s->bind_param('i',$id);
$s->execute();
$hod=$s->get_result()->fetch_assoc();
$s->close();

if(!$hod)
    json_response(['success'=>false,'message'=>'HOD details not found.'],404);

$c=$conn->prepare("SELECT COUNT(*) total, SUM(advisor_status=2 AND forwarded_to_hod=1 AND hod_status=4) locked, SUM(forwarded_to_hod=1 AND hod_status=4 AND advisor_status=1) pending, SUM(hod_status=2) approved, SUM(hod_status=5) rejected FROM leave_requests WHERE hod_id=?");
$c->bind_param('i',$id);
$c->execute();
$counts=$c->get_result()->fetch_assoc();
$c->close();

foreach(['total','pending','approved','rejected','locked'] as $k)
    $counts[$k]=(int)($counts[$k]??0);

$q=$conn->prepare("SELECT lr.id,lr.reg_no,lr.date_from,lr.date_to,lr.reason,lr.proof,lr.advisor_status,lr.hod_status,lr.forwarded_to_hod,lr.created_at,lr.advisor_deadline_at,u.full_name FROM leave_requests lr JOIN users u ON u.id=lr.student_id WHERE lr.hod_id=? AND ((lr.forwarded_to_hod=1 AND lr.hod_status=4) OR lr.hod_status IN (2,5)) ORDER BY lr.id DESC");
$q->bind_param('i',$id);
$q->execute();
$res=$q->get_result();
$rows=[];

while($r=$res->fetch_assoc())
    $rows[]=$r;

$q->close();

json_response(['success'=>true,'hod'=>$hod,'counts'=>$counts,'leaves'=>$rows]);