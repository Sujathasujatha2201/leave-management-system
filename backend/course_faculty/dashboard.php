<?php
require_once __DIR__.'/../shared/bootstrap.php';
$id=require_role('course_faculty');
$u=$conn->prepare("SELECT id,full_name,designation,department,username FROM users WHERE id=? AND role='course_faculty' AND is_active=1");
$u->bind_param('i',$id);
$u->execute();
$faculty=$u->get_result()->fetch_assoc();
$u->close();
if(!$faculty)json_response(['success'=>false,'message'=>'Course Faculty details not found.'],404);
$q=$conn->prepare("SELECT cfa.course_name,cfa.subject_slot,ca.department,ca.section,ca.batch,ca.semester,ca.academic_year,adv.full_name advisor_name,adv.designation advisor_designation FROM course_faculty_assignments cfa JOIN class_assignments ca ON ca.id=cfa.class_assignment_id JOIN users adv ON adv.id=ca.advisor_id WHERE cfa.faculty_id=? ORDER BY ca.section,cfa.subject_slot");
$q->bind_param('i',$id);
$q->execute();
$rs=$q->get_result();
$assignments=[];
while($r=$rs->fetch_assoc())$assignments[]=$r;
$q->close();
json_response(['success'=>true,'faculty'=>$faculty,'assignments'=>$assignments]);
