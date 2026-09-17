<?php

require_once __DIR__.'/../shared/bootstrap.php';

$id = require_role('course_faculty');


/*
|--------------------------------------------------------------------------
| GET only
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

    json_response([
        'success' => false,
        'message' => 'Invalid request method.'
    ], 405);

}


/*
|--------------------------------------------------------------------------
| Get Timetable ID
|--------------------------------------------------------------------------
*/

$timetable_id = isset($_GET['timetable_id'])
    ? (int) $_GET['timetable_id']
    : 0;


if ($timetable_id <= 0) {

    json_response([
        'success' => false,
        'message' => 'Timetable ID is required.'
    ], 400);

}


/*
|--------------------------------------------------------------------------
| Get Attendance Date
|--------------------------------------------------------------------------
*/

$attendance_date = $_GET['attendance_date'] ?? date('Y-m-d');


if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $attendance_date)) {

    json_response([
        'success' => false,
        'message' => 'Invalid attendance date.'
    ], 400);

}


/*
|--------------------------------------------------------------------------
| Verify timetable belongs to logged-in Course Faculty
|--------------------------------------------------------------------------
*/

$q = $conn->prepare("
    SELECT
        t.id,
        t.class_assignment_id,
        t.day,
        t.period,
        t.subject_slot,
        t.course_name,
        ca.department,
        ca.batch,
        ca.semester,
        ca.academic_year,
        ca.section
    FROM timetable t
    JOIN class_assignments ca
        ON ca.id = t.class_assignment_id
    WHERE t.id = ?
      AND t.faculty_id = ?
    LIMIT 1
");

$q->bind_param('ii', $timetable_id, $id);

$q->execute();

$timetable = $q->get_result()->fetch_assoc();

$q->close();


if (!$timetable) {

    json_response([
        'success' => false,
        'message' => 'You are not assigned to this timetable.'
    ], 403);

}


/*
|--------------------------------------------------------------------------
| Get Students
|--------------------------------------------------------------------------
*/

$q = $conn->prepare("
    SELECT
        u.id,
        u.full_name,
        u.username,
        u.reg_no,

        CASE
            WHEN lr.id IS NOT NULL
                THEN lr.application_type
            ELSE NULL
        END AS attendance_type

    FROM users u

    LEFT JOIN leave_requests lr
        ON lr.student_id = u.id
        AND lr.date_from <= ?
        AND lr.date_to >= ?

    WHERE u.role = 'student'
      AND u.department = ?
      AND u.batch = ?
      AND u.semester = ?
      AND u.section = ?
      AND u.academic_year = ?
      AND u.is_active = 1

    ORDER BY
        u.reg_no ASC,
        u.full_name ASC
");


$q->bind_param(
    'ssssiss',
    $attendance_date,
    $attendance_date,
    $timetable['department'],
    $timetable['batch'],
    $timetable['semester'],
    $timetable['section'],
    $timetable['academic_year']
);


$q->execute();

$rs = $q->get_result();

$students = [];


while ($r = $rs->fetch_assoc()) {

    $students[] = $r;

}


$q->close();


/*
|--------------------------------------------------------------------------
| Response
|--------------------------------------------------------------------------
*/

json_response([
    'success' => true,
    'attendance_date' => $attendance_date,
    'timetable' => $timetable,
    'students' => $students
]);