<?php
require_once __DIR__ . '/db.php';

// Explicit setup/reset page. Demo password for every account: 123456
$users = [
 ['ragavarshini','Ragavarshini','24BEE01','student','EEE','Electrical and Electronics Engineering','2024-2028','5','2026-2027'],
 ['sowmiya','Sowmiya','24BEE02','student','EEE','Electrical and Electronics Engineering','2024-2028','5','2026-2027'],
 ['sujatha','Sujatha','24BCB01','student','CSBS','Computer Science and Business Systems','2024-2028','5','2026-2027'],
 ['libika','Libika','24BCB02','student','CSBS','Computer Science and Business Systems','2024-2028','5','2026-2027'],
 ['theshitha','Theshitha','24BIT01','student','IT','Information Technology','2024-2028','5','2026-2027'],
 ['tharanya','Tharanya','24BIT02','student','IT','Information Technology','2024-2028','5','2026-2027'],
 ['advisor_eee','EEE Advisor',null,'advisor','EEE',null,null,null,null],
 ['advisor_csbs','CSBS Advisor',null,'advisor','CSBS',null,null,null,null],
 ['advisor_it','IT Advisor',null,'advisor','IT',null,null,null,null],
 ['hod_eee','EEE HOD',null,'hod','EEE',null,null,null,null],
 ['hod_csbs','CSBS HOD',null,'hod','CSBS',null,null,null,null],
 ['hod_it','IT HOD',null,'hod','IT',null,null,null,null]
];

foreach ($users as $u)
{
    $hash=password_hash('123456', PASSWORD_DEFAULT);
    $stmt=$conn->prepare("INSERT INTO users (username,password,role,full_name,reg_no,email,department,branch,batch,semester,academic_year,is_active) VALUES (?,?,?,?,?,NULL,?,?,?,?,?,1) ON DUPLICATE KEY UPDATE password=VALUES(password),role=VALUES(role),full_name=VALUES(full_name),reg_no=VALUES(reg_no),department=VALUES(department),branch=VALUES(branch),batch=VALUES(batch),semester=VALUES(semester),academic_year=VALUES(academic_year),is_active=1");
    $username=$u[0]; $fullName=$u[1]; $regNo=$u[2]; $role=$u[3]; $department=$u[4];
    $branch=$u[5]; $batch=$u[6]; $sem=$u[7]; $ay=$u[8];
    $stmt->bind_param('ssssssssss',$username,$hash,$role,$fullName,$regNo,$department,$branch,$batch,$sem,$ay);
    $stmt->execute(); $stmt->close();
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Setup Complete</title><link rel="stylesheet" href="style.css"></head><body class="setup-page"><div class="setup-card"><h2>Leave Management System Setup Complete</h2><p>All 12 demo accounts are ready.</p><p><strong>Password for all accounts:</strong> 123456</p><table><tr><th>Department</th><th>Students</th><th>Advisor</th><th>HOD</th></tr><tr><td>EEE</td><td>Ragavarshini (24BEE01), Sowmiya (24BEE02)</td><td>advisor_eee</td><td>hod_eee</td></tr><tr><td>CSBS</td><td>Sujatha (24BCB01), Libika (24BCB02)</td><td>advisor_csbs</td><td>hod_csbs</td></tr><tr><td>IT</td><td>Theshitha (24BIT01), Tharanya (24BIT02)</td><td>advisor_it</td><td>hod_it</td></tr></table><p><a class="btn" href="login.php">Go to Login</a></p></div></body></html>
