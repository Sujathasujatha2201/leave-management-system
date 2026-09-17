<?php

require_once __DIR__.'/../shared/bootstrap.php';

$studentId = require_role('student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response([
        'success' => false,
        'message' => 'Invalid request.'
    ], 405);
}


/* ---------------------------------------------------------
   Student details
--------------------------------------------------------- */

$stmt = $conn->prepare("
    SELECT
        full_name,
        reg_no,
        department,
        section,
        branch,
        batch,
        semester,
        academic_year
    FROM users
    WHERE id = ?
      AND role = 'student'
");

$stmt->bind_param('i', $studentId);
$stmt->execute();

$student = $stmt->get_result()->fetch_assoc();

$stmt->close();


if (!$student) {
    json_response([
        'success' => false,
        'message' => 'Student details not found.'
    ], 404);
}


/* ---------------------------------------------------------
   Form data
--------------------------------------------------------- */

$applicationType = trim($_POST['application_type'] ?? 'Leave');

$dateFrom = $_POST['date_from'] ?? '';
$dateTo   = $_POST['date_to'] ?? '';
$reason   = trim($_POST['reason'] ?? '');


/* ---------------------------------------------------------
   Validate Application Type
--------------------------------------------------------- */

if (!in_array($applicationType, ['Leave', 'OD'], true)) {
    json_response([
        'success' => false,
        'message' => 'Invalid application type.'
    ], 400);
}


/* ---------------------------------------------------------
   Validate required fields
--------------------------------------------------------- */

if (!$dateFrom || !$dateTo || !$reason) {
    json_response([
        'success' => false,
        'message' => 'Please fill all required fields.'
    ]);
}


if ($dateFrom > $dateTo) {
    json_response([
        'success' => false,
        'message' => 'To Date must be on or after From Date.'
    ]);
}


/* ---------------------------------------------------------
   Find Class Advisor
--------------------------------------------------------- */

$section = $student['section'] ?? '';

$advisor = null;

if ($section !== '') {

    $a = $conn->prepare("
        SELECT u.id
        FROM class_assignments ca
        JOIN users u
            ON u.id = ca.advisor_id
        WHERE ca.department = ?
          AND ca.section = ?
          AND u.role = 'advisor'
          AND u.is_active = 1
        LIMIT 1
    ");

    $a->bind_param(
        'ss',
        $student['department'],
        $section
    );

    $a->execute();

    $advisor = $a->get_result()->fetch_assoc();

    $a->close();
}


if (!$advisor) {
    json_response([
        'success' => false,
        'message' =>
            'Your Class Advisor has not been assigned yet. Please contact your HOD.'
    ], 409);
}


/* ---------------------------------------------------------
   Find HOD
--------------------------------------------------------- */

$h = $conn->prepare("
    SELECT id
    FROM users
    WHERE role = 'hod'
      AND department = ?
      AND is_active = 1
    ORDER BY id ASC
    LIMIT 1
");

$h->bind_param(
    's',
    $student['department']
);

$h->execute();

$hod = $h->get_result()->fetch_assoc();

$h->close();


if (!$advisor || !$hod) {
    json_response([
        'success' => false,
        'message' =>
            'Advisor/HOD is not configured for your department.'
    ]);
}


/* ---------------------------------------------------------
   Proof upload
--------------------------------------------------------- */

$proofName = null;

if (
    isset($_FILES['proof']) &&
    $_FILES['proof']['error'] !== UPLOAD_ERR_NO_FILE
) {

    if ($_FILES['proof']['error'] !== UPLOAD_ERR_OK) {
        json_response([
            'success' => false,
            'message' => 'Proof upload failed.'
        ]);
    }


    if ($_FILES['proof']['size'] > 5 * 1024 * 1024) {
        json_response([
            'success' => false,
            'message' => 'Proof file must be 5 MB or smaller.'
        ]);
    }


    $allowed = [
        'pdf',
        'jpg',
        'jpeg',
        'png'
    ];

    $ext = strtolower(
        pathinfo(
            $_FILES['proof']['name'],
            PATHINFO_EXTENSION
        )
    );


    if (!in_array($ext, $allowed, true)) {
        json_response([
            'success' => false,
            'message' =>
                'Only PDF, JPG, JPEG and PNG files are allowed.'
        ]);
    }


    $proofName =
        bin2hex(random_bytes(12)) . '.' . $ext;


    $uploadDir =
        __DIR__ . '/../../uploads/student/proofs/';


    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }


    if (
        !move_uploaded_file(
            $_FILES['proof']['tmp_name'],
            $uploadDir . $proofName
        )
    ) {
        json_response([
            'success' => false,
            'message' => 'Could not save proof file.'
        ]);
    }
}


/* ---------------------------------------------------------
   Insert Leave / OD application
--------------------------------------------------------- */

$advisorId = (int) $advisor['id'];
$hodId     = (int) $hod['id'];


$stmt = $conn->prepare("
    INSERT INTO leave_requests
    (
        student_id,
        application_type,
        reg_no,
        date_from,
        date_to,
        batch,
        semester,
        academic_year,
        reason,
        proof,
        advisor_id,
        advisor_status,
        hod_id,
        hod_status,
        forwarded_to_hod,
        advisor_deadline_at
    )
    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        4,
        ?,
        4,
        0,
        DATE_ADD(NOW(), INTERVAL 48 HOUR)
    )
");


$stmt->bind_param(
    'isssssssssii',
    $studentId,
    $applicationType,
    $student['reg_no'],
    $dateFrom,
    $dateTo,
    $student['batch'],
    $student['semester'],
    $student['academic_year'],
    $reason,
    $proofName,
    $advisorId,
    $hodId
);


$ok = $stmt->execute();

$stmt->close();


if (!$ok) {
    json_response([
        'success' => false,
        'message' => 'Could not submit application.'
    ]);
}


$typeText = ($applicationType === 'OD')
    ? 'OD application'
    : 'Leave application';


json_response([
    'success' => true,
    'message' => $typeText . ' submitted successfully.'
]);