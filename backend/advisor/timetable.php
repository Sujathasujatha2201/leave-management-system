<?php

require_once __DIR__.'/../shared/bootstrap.php';

$id = require_role('advisor');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $q = $conn->prepare("
        SELECT
            t.id,
            t.class_assignment_id,
            t.day,
            t.period,
            t.subject_slot,
            t.course_name,
            t.faculty_id,
            u.full_name AS faculty_name,
            ca.department,
            ca.batch,
            ca.semester,
            ca.academic_year,
            ca.section
        FROM timetable t
        JOIN class_assignments ca
            ON ca.id = t.class_assignment_id
        JOIN users u
            ON u.id = t.faculty_id
        WHERE ca.advisor_id = ?
        ORDER BY
            ca.id,
            FIELD(t.day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'),
            t.period
    ");

    $q->bind_param('i', $id);
    $q->execute();

    $res = $q->get_result();

    $rows = [];

    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }

    $q->close();

    json_response([
        'success' => true,
        'timetable' => $rows
    ]);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $classId = (int)($_POST['class_assignment_id'] ?? 0);
    $day = trim($_POST['day'] ?? '');
    $period = (int)($_POST['period'] ?? 0);
    $subjectSlot = (int)($_POST['subject_slot'] ?? 0);
    $facultyId = (int)($_POST['faculty_id'] ?? 0);
    $courseName = trim($_POST['course_name'] ?? '');

    if (!$classId || !$day || !$period || !$subjectSlot || !$facultyId || !$courseName) {
        json_response([
            'success' => false,
            'message' => 'All timetable fields are required.'
        ], 400);
    }

    if (!in_array($day, [
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday'
    ], true)) {
        json_response([
            'success' => false,
            'message' => 'Invalid day.'
        ], 400);
    }

    if ($period < 1 || $period > 7) {
        json_response([
            'success' => false,
            'message' => 'Period must be between 1 and 7.'
        ], 400);
    }

    /* Make sure this class belongs to the logged-in advisor */
    $check = $conn->prepare("
        SELECT id
        FROM class_assignments
        WHERE id = ?
        AND advisor_id = ?
        LIMIT 1
    ");

    $check->bind_param('ii', $classId, $id);
    $check->execute();

    if (!$check->get_result()->fetch_assoc()) {
        $check->close();

        json_response([
            'success' => false,
            'message' => 'You are not authorized to modify this class timetable.'
        ], 403);
    }

    $check->close();


    /* Check whether the same section already has a class in this period */
    $check = $conn->prepare("
        SELECT id
        FROM timetable
        WHERE class_assignment_id = ?
        AND day = ?
        AND period = ?
        LIMIT 1
    ");

    $check->bind_param('isi', $classId, $day, $period);
    $check->execute();

    if ($check->get_result()->fetch_assoc()) {
        $check->close();

        json_response([
            'success' => false,
            'message' => 'This class already has a subject in this period.'
        ], 409);
    }

    $check->close();


    /* Check whether the faculty is already assigned elsewhere */
    $check = $conn->prepare("
        SELECT t.id, ca.department, ca.section
        FROM timetable t
        JOIN class_assignments ca
            ON ca.id = t.class_assignment_id
        WHERE t.faculty_id = ?
        AND t.day = ?
        AND t.period = ?
        LIMIT 1
    ");

    $check->bind_param('isi', $facultyId, $day, $period);
    $check->execute();

    $existing = $check->get_result()->fetch_assoc();

    if ($existing) {
        $check->close();

        json_response([
            'success' => false,
            'message' => 'This Course Faculty is already assigned to another class in this period.'
        ], 409);
    }

    $check->close();


    /* Save timetable */
    $stmt = $conn->prepare("
        INSERT INTO timetable
        (
            class_assignment_id,
            day,
            period,
            subject_slot,
            course_name,
            faculty_id
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        'isiisi',
        $classId,
        $day,
        $period,
        $subjectSlot,
        $courseName,
        $facultyId
    );

    if (!$stmt->execute()) {

        $error = $stmt->error;
        $stmt->close();

        json_response([
            'success' => false,
            'message' => $error
        ], 500);
    }

    $stmt->close();

    json_response([
        'success' => true,
        'message' => 'Timetable entry assigned successfully.'
    ]);
}


json_response([
    'success' => false,
    'message' => 'Invalid request method.'
], 405);