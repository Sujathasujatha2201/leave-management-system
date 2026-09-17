<?php

require_once __DIR__.'/../shared/bootstrap.php';

$id=require_role('student');

$s=$conn->prepare("SELECT full_name,reg_no,department,branch,batch,semester,academic_year FROM users WHERE id=? AND role='student' AND is_active=1");
$s->bind_param('i',$id);
$s->execute();
$student=$s->get_result()->fetch_assoc();
$s->close();

if(!$student)
    json_response(['success'=>false,'message'=>'Student details not found.'],404);

$n=$conn->prepare('SELECT id,date_from,date_to FROM leave_requests WHERE student_id=? AND hod_status=2 AND student_notified=0 ORDER BY id DESC LIMIT 1');
$n->bind_param('i',$id);
$n->execute();
$notification=$n->get_result()->fetch_assoc();
$n->close();

if($notification)
{ 
    $u=$conn->prepare('UPDATE leave_requests SET student_notified=1 WHERE id=? AND student_id=?'); 
    $u->bind_param('ii',$notification['id'],$id); 
    $u->execute(); 
    $u->close(); 
}

$c=$conn->prepare('SELECT COUNT(*) total,SUM(advisor_status=4 OR (advisor_status=1 AND forwarded_to_hod=1 AND hod_status=4)) pending,SUM(advisor_status=1 AND hod_status=2) approved,SUM(advisor_status=3 OR hod_status=5) rejected FROM leave_requests WHERE student_id=?');
$c->bind_param('i',$id);
$c->execute();
$counts=$c->get_result()->fetch_assoc();
$c->close();

foreach(['total','pending','approved','rejected'] as $k)
    $counts[$k]=(int)($counts[$k]??0);

$q=$conn->prepare('SELECT id,date_from,date_to,reason,proof,advisor_status,hod_status,forwarded_to_hod,created_at FROM leave_requests WHERE student_id=? ORDER BY id DESC LIMIT 10');
$q->bind_param('i',$id);
$q->execute();
$res=$q->get_result();
$leaves=[];

while($r=$res->fetch_assoc())
    $leaves[]=$r;

$q->close();

json_response(['success'=>true,'student'=>$student,'counts'=>$counts,'leaves'=>$leaves,'notification'=>$notification]);