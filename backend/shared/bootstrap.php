<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../db.php';

function require_role(string $role): int {
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== $role)
{
        http_response_code(401);
        echo json_encode(['success'=>false,'message'=>'Unauthorized']);
        exit;
    }
    return (int)$_SESSION['user_id'];
}

function json_response(array $data, int $status=200): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}
