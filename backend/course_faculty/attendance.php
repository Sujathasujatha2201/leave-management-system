<?php

header('Content-Type: application/json');

require_once '../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Session expired. Please login again.'
    ]);
    exit;
}

$faculty_id = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$timetable_id = isset($data['timetable_id'])
    ? (int) $data['timetable_id']
    : 0;

$attendance_date = $data['attendance_date'] ?? '';

$attendance = $data['attendance'] ?? [];

if ($timetable_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Timetable ID is required.'
    ]);
    exit;
}

if (!$attendance_date) {
    echo json_encode([
        'success' => false,
        'message' => 'Attendance date is required.'
    ]);
    exit;
}

if (!is_array($attendance) || count($attendance) === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'No attendance data received.'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Verify timetable belongs to logged-in faculty
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT id
    FROM timetable
    WHERE id = ?
      AND faculty_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $timetable_id, $faculty_id);
$stmt->execute();

$result = $stmt->get_result();

if (!$result->fetch_assoc()) {
    echo json_encode([
        'success' => false,
        'message' => 'You are not assigned to this timetable.'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Insert / Update Attendance
|--------------------------------------------------------------------------
*/

$conn->begin_transaction();

try {

    $sql_insert = "
        INSERT INTO attendance
        (
            timetable_id,
            student_id,
            attendance_date,
            status,
            marked_by
        )
        VALUES (?, ?, ?, ?, ?)

        ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            marked_by = VALUES(marked_by)
    ";

    $stmt_insert = $conn->prepare($sql_insert);

    foreach ($attendance as $student_id => $status) {

        $student_id = (int) $student_id;

        if (!in_array($status, ['Present', 'Absent'], true)) {
            throw new Exception(
                "Invalid attendance status for student ID " . $student_id
            );
        }

        if ($student_id <= 0) {
            throw new Exception('Invalid student ID.');
        }

        $stmt_insert->bind_param(
            "iissi",
            $timetable_id,
            $student_id,
            $attendance_date,
            $status,
            $faculty_id
        );

        if (!$stmt_insert->execute()) {
            throw new Exception(
                'Failed to save attendance.'
            );
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Attendance saved successfully.'
    ]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

exit;