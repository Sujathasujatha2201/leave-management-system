<?php
session_start();
require_once __DIR__ . '/../../db.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Invalid request method.']);
    exit;
}

$username = trim((string)($_POST['username'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$role = trim((string)($_POST['role'] ?? ''));
$allowed = ['student','advisor','hod','course_faculty'];

if ($username === '' || $password === '' || !in_array($role,$allowed,true))
{
    echo json_encode(['success'=>false,'message'=>'Please enter username, password and select a role.']);
    exit;
}

$stmt = $conn->prepare('SELECT id, username, password, role, full_name, is_active FROM users WHERE username = ? AND role = ? LIMIT 1');
if (!$stmt)
{
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Database error while preparing login.']);
    exit;
}
$stmt->bind_param('ss',$username,$role);
if (!$stmt->execute())
{
    $stmt->close();
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Database error while checking login.']);
    exit;
}
$result = $stmt->get_result();
$user = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$user || (int)$user['is_active'] !== 1 || !password_verify($password,$user['password']))
{
    echo json_encode(['success'=>false,'message'=>'Invalid username, password or selected role.']);
    exit;
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];
$_SESSION['full_name'] = $user['full_name'];

$redirects = [
    'student' => 'student/dashboard.html',
    'advisor' => 'advisor/dashboard.html',
    'hod' => 'hod/dashboard.html',
    'course_faculty' => 'course_faculty/dashboard.html'
];

echo json_encode(['success'=>true,'redirect'=>$redirects[$user['role']]]);
