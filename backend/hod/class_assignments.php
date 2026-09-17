<?php
require_once __DIR__.'/../shared/bootstrap.php';
$hid=require_role('hod');
$h=$conn->prepare("SELECT department FROM users WHERE id=? AND role='hod' AND is_active=1");$h->bind_param('i',$hid);$h->execute();$hod=$h->get_result()->fetch_assoc();$h->close();
if(!$hod) json_response(['success'=>false,'message'=>'HOD details not found.'],404);
$dept=$hod['department'];
if($_SERVER['REQUEST_METHOD']==='GET')
{
 $a=$conn->prepare("SELECT id,username,full_name,designation FROM users WHERE role='advisor' AND department=? AND is_active=1 AND (username LIKE 'advisor_a_%' OR username LIKE 'advisor_b_%') ORDER BY full_name");
 $a->bind_param('s',$dept);
 $a->execute();
 $ar=[];
 $rr=$a->get_result();
 while($r=$rr->fetch_assoc())$ar[]=$r;
 $a->close();
 $c=$conn->prepare("SELECT ca.*,u.full_name advisor_name,u.username advisor_username,u.designation FROM class_assignments ca JOIN users u ON u.id=ca.advisor_id WHERE ca.department=? ORDER BY ca.section");
 $c->bind_param('s',$dept);
 $c->execute();
 $cr=$c->get_result();
 $classes=[];
 while($r=$cr->fetch_assoc())$classes[]=$r;
 $c->close();
 json_response(['success'=>true,'department'=>$dept,'advisors'=>$ar,'classes'=>$classes]);
}
if($_SERVER['REQUEST_METHOD']!=='POST') json_response(['success'=>false,'message'=>'Invalid request.'],405);
$section=strtoupper(trim($_POST['section']??''));$advisorId=(int)($_POST['advisor_id']??0);
if(!in_array($section,['A','B'],true)||$advisorId<=0) json_response(['success'=>false,'message'=>'Select a valid section and Class Advisor.']);
$x=$conn->prepare("SELECT id FROM users WHERE id=? AND role='advisor' AND department=? AND is_active=1");
$x->bind_param('is',$advisorId,$dept);
$x->execute();
$ok=$x->get_result()->fetch_assoc();
$x->close();
if(!$ok)json_response(['success'=>false,'message'=>'Selected advisor is not from your department.'],403);
$st=$conn->prepare("SELECT batch,semester,academic_year FROM users WHERE role='student' AND department=? AND section=? AND is_active=1 ORDER BY id LIMIT 1");
$st->bind_param('ss',$dept,$section);
$st->execute();
$student=$st->get_result()->fetch_assoc();
$st->close();
if(!$student) json_response(['success'=>false,'message'=>'No student is configured for Section '.$section.' in '.$dept.'. Add the student section first.'],409);
$q=$conn->prepare("INSERT INTO class_assignments(department,batch,semester,academic_year,section,advisor_id) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE batch=VALUES(batch),semester=VALUES(semester),academic_year=VALUES(academic_year),advisor_id=VALUES(advisor_id)");
$q->bind_param('sssssi',$dept,$student['batch'],$student['semester'],$student['academic_year'],$section,$advisorId);
if(!$q->execute())
{ $e=$q->error;$q->close();json_response(['success'=>false,'message'=>'Unable to save class assignment: '.$e],500);} $q->close();json_response(['success'=>true,'message'=>'Section '.$section.' assigned successfully.']);
