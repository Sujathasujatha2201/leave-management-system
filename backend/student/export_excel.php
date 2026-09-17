<?php

require_once __DIR__ . '/../../db.php';

if (session_status() === PHP_SESSION_NONE)
{
    session_start();
}

if (!isset($_SESSION['user_id']))
{
    die('Session expired. Please login again.');
}

if (($_SESSION['role'] ?? '') !== 'student')
{
    die('Access denied.');
}

$student_id = (int)$_SESSION['user_id'];

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

$from_date = trim($_GET['from_date'] ?? '');
$to_date   = trim($_GET['to_date'] ?? '');
$status    = strtolower(trim($_GET['status'] ?? 'all'));

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

    $sql .= " AND advisor_status = 2 ";

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

$userStmt = $conn->prepare("
    SELECT full_name, reg_no
    FROM users
    WHERE id = ?
    LIMIT 1
");

$userStmt->bind_param("i", $student_id);
$userStmt->execute();

$userResult = $userStmt->get_result();

$studentName = 'Student';
$studentReg = '';

if ($userRow = $userResult->fetch_assoc())
{
    $studentName = $userRow['full_name'];
    $studentReg = $userRow['reg_no'];
}

$userStmt->close();

function getAdvisorStatusExcel($row)
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

function getHodStatusExcel($row)
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

function getFinalStatusExcel($row)
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
| Create Excel
|--------------------------------------------------------------------------
*/

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle('Leave Report');


/*
|--------------------------------------------------------------------------
| Header
|--------------------------------------------------------------------------
*/

$sheet->mergeCells('A1:G1');
$sheet->setCellValue(
    'A1',
    'M. Kumarasamy College of Engineering'
);

$sheet->mergeCells('A2:G2');
$sheet->setCellValue(
    'A2',
    'Student Leave Report'
);

$sheet->mergeCells('A3:G3');
$sheet->setCellValue(
    'A3',
    'Student: ' . $studentName . ' | Register No: ' . $studentReg
);

$sheet->mergeCells('A4:G4');
$sheet->setCellValue(
    'A4',
    'Generated On: ' . date('d-m-Y H:i')
);


/*
|--------------------------------------------------------------------------
| Table Headers
|--------------------------------------------------------------------------
*/

$headers = [
    'S.No',
    'Leave From',
    'Leave To',
    'Reason',
    'Advisor Status',
    'HOD Status',
    'Final Status'
];

$column = 'A';

foreach ($headers as $header)
{

    $sheet->setCellValue(
        $column . '6',
        $header
    );

    $column++;
}


/*
|--------------------------------------------------------------------------
| Leave Data
|--------------------------------------------------------------------------
*/

$rowNumber = 7;
$count = 1;

while ($row = $result->fetch_assoc())
{

    $sheet->setCellValue('A' . $rowNumber, $count);
    $sheet->setCellValue('B' . $rowNumber, $row['date_from']);
    $sheet->setCellValue('C' . $rowNumber, $row['date_to']);
    $sheet->setCellValue('D' . $rowNumber, $row['reason']);

    $sheet->setCellValue(
        'E' . $rowNumber,
        getAdvisorStatusExcel($row)
    );

    $sheet->setCellValue(
        'F' . $rowNumber,
        getHodStatusExcel($row)
    );

    $sheet->setCellValue(
        'G' . $rowNumber,
        getFinalStatusExcel($row)
    );

    $rowNumber++;
    $count++;
}


/*
|--------------------------------------------------------------------------
| Formatting
|--------------------------------------------------------------------------
*/

$sheet->getStyle('A1:G4')->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->getStyle('A1:G1')->getFont()
    ->setBold(true)
    ->setSize(16);

$sheet->getStyle('A2:G2')->getFont()
    ->setBold(true)
    ->setSize(13);

$sheet->getStyle('A6:G6')->getFont()
    ->setBold(true);

$sheet->getStyle('A6:G6')->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->getStyle('A6:G' . max(6, $rowNumber - 1))
    ->getAlignment()
    ->setVertical(Alignment::VERTICAL_CENTER);

$sheet->getColumnDimension('A')->setWidth(8);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(15);
$sheet->getColumnDimension('D')->setWidth(40);
$sheet->getColumnDimension('E')->setWidth(18);
$sheet->getColumnDimension('F')->setWidth(22);
$sheet->getColumnDimension('G')->setWidth(18);


/*
|--------------------------------------------------------------------------
| Download
|--------------------------------------------------------------------------
*/

$filename = 'Student_Leave_Report.xlsx';

header(
    'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
);

header(
    'Content-Disposition: attachment; filename="' . $filename . '"'
);

header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);

$writer->save('php://output');

exit;
