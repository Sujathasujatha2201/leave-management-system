<?php

require_once __DIR__ . '/../../db.php';

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
    die('Session expired. Please login again.');
}


/*
|--------------------------------------------------------------------------
| Check Student Role
|--------------------------------------------------------------------------
*/

if (($_SESSION['role'] ?? '') !== 'student')
{
    die('Access denied.');
}


$student_id = (int)$_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| Load Dompdf
|--------------------------------------------------------------------------
*/

$autoload = __DIR__ . '/../../vendor/autoload.php';

if (!file_exists($autoload))
{
    die('Dompdf is not installed. Please install Dompdf first.');
}

require_once $autoload;

use Dompdf\Dompdf;
use Dompdf\Options;


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
| Query
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        reg_no,
        date_from,
        date_to,
        reason,
        advisor_status,
        hod_status,
        forwarded_to_hod
    FROM leave_requests
    WHERE student_id = ?
";

$params = [$student_id];
$types = "i";


/*
|--------------------------------------------------------------------------
| Date Filters
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


$sql .= " ORDER BY date_from DESC, id DESC";


/*
|--------------------------------------------------------------------------
| Execute Query
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare($sql);

if (!$stmt)
{
    die('Failed to prepare report query.');
}

$stmt->bind_param($types, ...$params);

if (!$stmt->execute())
{
    die('Failed to execute report query.');
}

$result = $stmt->get_result();


/*
|--------------------------------------------------------------------------
| Student Details
|--------------------------------------------------------------------------
*/

$studentName = 'Student';
$studentReg = '';

$userStmt = $conn->prepare("
    SELECT full_name, reg_no
    FROM users
    WHERE id = ?
    LIMIT 1
");

$userStmt->bind_param("i", $student_id);
$userStmt->execute();

$userResult = $userStmt->get_result();

if ($userRow = $userResult->fetch_assoc())
{

    $studentName = $userRow['full_name'];
    $studentReg = $userRow['reg_no'];

}

$userStmt->close();


/*
|--------------------------------------------------------------------------
| Status Helpers
|--------------------------------------------------------------------------
*/

function getAdvisorStatus($row)
{
    if ((int)$row['advisor_status'] === 1)
{
        return 'Approved';
    }

    if ((int)$row['advisor_status'] === 3)
{
        return 'Rejected';
    }

    if ((int)$row['advisor_status'] === 2)
{
        return 'Locked';
    }

    return 'Pending';
}


function getHodStatus($row)
{
    if (
        (int)$row['advisor_status'] === 2 &&
        (int)$row['forwarded_to_hod'] === 1
    )
{
        return 'Locked - HOD Review';
    }

    if ((int)$row['advisor_status'] === 2)
{
        return 'Locked';
    }

    if ((int)$row['hod_status'] === 2)
{
        return 'Approved';
    }

    if ((int)$row['hod_status'] === 5)
{
        return 'Rejected';
    }

    if ((int)$row['forwarded_to_hod'] === 1)
{
        return 'Pending';
    }

    return 'Not Forwarded';
}


function getFinalStatus($row)
{
    if ((int)$row['advisor_status'] === 2)
{
        return 'Locked';
    }

    if (
        (int)$row['advisor_status'] === 3 ||
        (int)$row['hod_status'] === 5
    )
{
        return 'Rejected';
    }

    if ((int)$row['hod_status'] === 2)
{
        return 'Approved';
    }

    return 'Pending';
}


/*
|--------------------------------------------------------------------------
| Build HTML
|--------------------------------------------------------------------------
*/

function h($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


$html = '
<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">

<style>

body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 11px;
    color: #222;
}

.header {
    text-align: center;
    margin-bottom: 20px;
}

.college {
    font-size: 18px;
    font-weight: bold;
}

.title {
    font-size: 15px;
    margin-top: 5px;
}

.student {
    margin-bottom: 15px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: #eeeeee;
    border: 1px solid #999;
    padding: 7px;
    text-align: left;
}

td {
    border: 1px solid #999;
    padding: 7px;
}

.footer {
    margin-top: 20px;
    text-align: center;
    font-size: 9px;
    color: #777;
}

</style>

</head>

<body>

<div class="header">

    <div class="college">
        M. Kumarasamy College of Engineering
    </div>

    <div class="title">
        Student Leave Report
    </div>

</div>

<div class="student">

    <b>Student Name:</b> ' . h($studentName) . '<br>

    <b>Register No:</b> ' . h($studentReg) . '<br>

    <b>Generated On:</b> ' . date('d-m-Y H:i') . '

</div>

<table>

<thead>

<tr>
    <th>#</th>
    <th>Leave From</th>
    <th>Leave To</th>
    <th>Reason</th>
    <th>Advisor</th>
    <th>HOD</th>
    <th>Final Status</th>
</tr>

</thead>

<tbody>
';


$count = 0;


while ($row = $result->fetch_assoc())
{

    $count++;

    $html .= '
    <tr>

        <td>' . $count . '</td>

        <td>' . h($row['date_from']) . '</td>

        <td>' . h($row['date_to']) . '</td>

        <td>' . h($row['reason']) . '</td>

        <td>' . h(getAdvisorStatus($row)) . '</td>

        <td>' . h(getHodStatus($row)) . '</td>

        <td>' . h(getFinalStatus($row)) . '</td>

    </tr>
    ';
}


if ($count === 0)
{

    $html .= '
    <tr>

        <td colspan="7" style="text-align:center;">
            No leave records found.
        </td>

    </tr>
    ';

}


$html .= '

</tbody>

</table>

<div class="footer">

    Leave Management System<br>
    M. Kumarasamy College of Engineering

</div>

</body>
</html>
';


$stmt->close();


/*
|--------------------------------------------------------------------------
| Generate PDF
|--------------------------------------------------------------------------
*/

$options = new Options();

$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);


$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);

$dompdf->setPaper('A4', 'landscape');

$dompdf->render();


/*
|--------------------------------------------------------------------------
| Download PDF
|--------------------------------------------------------------------------
*/

$dompdf->stream(
    'Student_Leave_Report.pdf',
    [
        'Attachment' => true
    ]
);

exit;

?>
