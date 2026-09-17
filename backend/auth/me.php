<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'],$_SESSION['role']))
{ 
    echo json_encode(['success'=>false,'authenticated'=>false]); 
    exit; 
}

echo json_encode([
    'success'=>true,
    'authenticated'=>true,
    'user_id'=>(int)$_SESSION['user_id'],
    'username'=>$_SESSION['username']??'',
    'full_name'=>$_SESSION['full_name']??'',
    'role'=>$_SESSION['role']
]);