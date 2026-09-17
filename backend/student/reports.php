<?php

require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE)
{
    session_start();
}

/*
|--------------------------------------------------------------------------
| Check Login
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id']))
{
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Session expired. Please login again.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Check Student Role
|--------------------------------------------------------------------------
*/

if (($_SESSION['role'] ?? '') !== 'student')
{
    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'Access denied.'
    ]);

    exit;
}


$student_id = (int)$_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$from_date = trim($_GET['from_date'] ?? '');
$to_date   = trim($_GET['to_date'] ?? '');
$status    = strtolower(trim($_GET['status'] ?? 'all'));


/*
|--------------------------------------------------------------------------
| Base Query
|--------------------------------------------------------------------------
|
| IMPORTANT:
| student_id comes from SESSION.
| Student cannot send another student's ID.
|
*/

$sql = "
    SELECT
        id,
        student_id,
        reg_no,
        date_from,
        date_to,
        reason,
        advisor_status,
        hod_status,
        forwarded_to_hod,
        created_at,
        updated_at
    FROM leave_requests
    WHERE student_id = ?
";

$params = [$student_id];
$types  = "i";


/*
|--------------------------------------------------------------------------
| Date Filter
|--------------------------------------------------------------------------
*/

if ($from_date !== '')
{

    $sql .= " AND date_from >= ?";

    $params[] = $from_date;
    $types .= "s";
}


if ($to_date !== '')
{

    $sql .= " AND date_to <= ?";

    $params[] = $to_date;
    $types .= "s";
}


/*
|--------------------------------------------------------------------------
| Status Filter
|--------------------------------------------------------------------------
*/

if ($status !== 'all' && $status !== '')
{

    if ($status === 'approved')
{

        $sql .= "
            AND hod_status = 2
            AND advisor_status != 2
            AND advisor_status != 3
        ";

    }
elseif ($status === 'rejected')
{

        $sql .= "
            AND (
                advisor_status = 3
                OR hod_status = 5
            )
        ";

    }
elseif ($status === 'locked')
{

        $sql .= "
            AND advisor_status = 2
        ";

    }
elseif ($status === 'pending')
{

        $sql .= "
            AND advisor_status != 2
            AND advisor_status != 3
            AND hod_status != 5
            AND hod_status != 2
        ";

    }

}


/*
|--------------------------------------------------------------------------
| Latest Leaves First
|--------------------------------------------------------------------------
*/

$sql .= " ORDER BY date_from DESC, id DESC";


/*
|--------------------------------------------------------------------------
| Prepare
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare($sql);

if (!$stmt)
{

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to prepare report query.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Bind Parameters
|--------------------------------------------------------------------------
*/

$stmt->bind_param($types, ...$params);


/*
|--------------------------------------------------------------------------
| Execute
|--------------------------------------------------------------------------
*/

if (!$stmt->execute())
{

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to load leave report.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Get Results
|--------------------------------------------------------------------------
*/

$result = $stmt->get_result();

$leaves = [];


while ($row = $result->fetch_assoc())
{

    $leaves[] = [
        'id' => (int)$row['id'],
        'student_id' => (int)$row['student_id'],
        'reg_no' => $row['reg_no'],
        'date_from' => $row['date_from'],
        'date_to' => $row['date_to'],
        'reason' => $row['reason'],
        'advisor_status' => (int)$row['advisor_status'],
        'hod_status' => (int)$row['hod_status'],
        'forwarded_to_hod' => (int)$row['forwarded_to_hod'],
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at']
    ];

}


$stmt->close();


/*
|--------------------------------------------------------------------------
| Response
|--------------------------------------------------------------------------
*/

echo json_encode([
    'success' => true,
    'leaves' => $leaves,
    'count' => count($leaves)
]);

?>
