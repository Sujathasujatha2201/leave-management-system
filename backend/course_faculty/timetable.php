<?php

require_once __DIR__.'/../shared/bootstrap.php';

$id = require_role('course_faculty');


/*
|--------------------------------------------------------------------------
| GET - Course Faculty Timetable
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

    json_response([
        'success' => false,
        'message' => 'Invalid request method.'
    ], 405);

}


$q = $conn->prepare("
    SELECT
        t.id,
        t.day,
        t.period,
        t.subject_slot,
        t.course_name,
        t.class_assignment_id,
        ca.department,
        ca.batch,
        ca.semester,
        ca.academic_year,
        ca.section
    FROM timetable t
    JOIN class_assignments ca
        ON ca.id = t.class_assignment_id
    WHERE t.faculty_id = ?
    ORDER BY
        FIELD(
            t.day,
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday'
        ),
        t.period
");


$q->bind_param('i', $id);

$q->execute();

$rs = $q->get_result();

$timetable = [];


while ($r = $rs->fetch_assoc()) {

    $timetable[] = $r;

}


$q->close();


json_response([
    'success' => true,
    'data' => $timetable
]);