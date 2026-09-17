<?php
require_once __DIR__.'/../shared/bootstrap.php';
$id=require_role('advisor');
$me=$conn->prepare("SELECT department,full_name FROM users WHERE id=? AND role='advisor' AND is_active=1");
$me->bind_param('i',$id);
$me->execute();
$advisor=$me->get_result()->fetch_assoc();
$me->close();
if(!$advisor)json_response(['success'=>false,'message'=>'Advisor details not found.'],404);
$subjects=[1=>'Computer Networks',2=>'Machine Learning',3=>'Internet of Things',4=>'Design Thinking',5=>'Design & Analysis of Algorithms'];
if($_SERVER['REQUEST_METHOD']==='GET')
{
 $c=$conn->prepare("SELECT ca.id,ca.department,ca.section,ca.batch,ca.semester,ca.academic_year FROM class_assignments ca WHERE ca.advisor_id=? ORDER BY ca.section");
 $c->bind_param('i',$id);
 $c->execute();
 $rs=$c->get_result();
 $classes=[];
 while($r=$rs->fetch_assoc())$classes[]=$r;
 $c->close();
 $f=$conn->prepare("SELECT id,full_name,designation,username,department FROM users WHERE role='course_faculty' AND is_active=1 ORDER BY full_name");
 $f->execute();
 $fr=$f->get_result();
 $faculty=[];
 while($r=$fr->fetch_assoc())$faculty[]=$r;
 $f->close();
 $a=$conn->prepare("SELECT cfa.id,cfa.class_assignment_id,cfa.subject_slot,cfa.course_name,cfa.faculty_id,uf.full_name faculty_name,uf.designation FROM course_faculty_assignments cfa JOIN class_assignments ca ON ca.id=cfa.class_assignment_id JOIN users uf ON uf.id=cfa.faculty_id WHERE ca.advisor_id=? ORDER BY cfa.class_assignment_id,cfa.subject_slot");
 $a->bind_param('i',$id);
 $a->execute();
 $ar=$a->get_result();
 $assign=[];
 while($r=$ar->fetch_assoc())$assign[]=$r;
 $a->close();
 json_response(['success'=>true,'subjects'=>$subjects,'classes'=>$classes,'faculty'=>$faculty,'assignments'=>$assign]);
}
if($_SERVER['REQUEST_METHOD']!=='POST')json_response(['success'=>false,'message'=>'Invalid request.'],405);
$classId=(int)($_POST['class_assignment_id']??0);
$slot=(int)($_POST['subject_slot']??0);
$facultyId=(int)($_POST['faculty_id']??0);
if(!$classId||!isset($subjects[$slot])||!$facultyId)json_response(['success'=>false,'message'=>'Invalid course faculty assignment.']);
$x=$conn->prepare("SELECT id FROM class_assignments WHERE id=? AND advisor_id=?");
$x->bind_param('ii',$classId,$id);
$x->execute();
$owned=$x->get_result()->fetch_assoc();
$x->close();
if(!$owned)json_response(['success'=>false,'message'=>'This class is not assigned to you.'],403);
$f=$conn->prepare("SELECT id FROM users WHERE id=? AND role='course_faculty' AND is_active=1");
$f->bind_param('i',$facultyId);
$f->execute();
$valid=$f->get_result()->fetch_assoc();
$f->close();
if(!$valid)json_response(['success'=>false,'message'=>'Selected Course Faculty is invalid.'],403);
$q=$conn->prepare("SELECT id FROM course_faculty_assignments WHERE class_assignment_id=? AND subject_slot=? LIMIT 1");$q->bind_param('ii',$classId,$slot);$q->execute();$old=$q->get_result()->fetch_assoc();$q->close();
if($old)
{$u=$conn->prepare("UPDATE course_faculty_assignments SET course_name=?,faculty_id=? WHERE id=?");$name=$subjects[$slot];$u->bind_param('sii',$name,$facultyId,$old['id']);$u->execute();$u->close();}
else
{$u=$conn->prepare("INSERT INTO course_faculty_assignments(class_assignment_id,subject_slot,course_name,faculty_id) VALUES(?,?,?,?)");
$name=$subjects[$slot];
$u->bind_param('iisi',$classId,$slot,$name,$facultyId);
$u->execute();
$u->close();
}
json_response(['success'=>true,'message'=>$subjects[$slot].' assigned successfully.']);
