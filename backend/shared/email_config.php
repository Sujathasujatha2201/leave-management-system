<?php
/* Gmail SMTP configuration for Leave Management System */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../uploads/PHPMailer-7.1.1/src/Exception.php';
require_once __DIR__ . '/../../uploads/PHPMailer-7.1.1/src/PHPMailer.php';
require_once __DIR__ . '/../../uploads/PHPMailer-7.1.1/src/SMTP.php';

/*
 * IMPORTANT:
 * Keep the App Password only on your own laptop.
 * Do NOT share it in chat or commit it to GitHub.
 */
const MAIL_USERNAME = 'sujathasambasivam01@gmail.com';
const MAIL_APP_PASSWORD = 'rrlwaquxpfuliwjn';

function sendLeaveStatusEmail($toEmail, $studentName, $regNo, $dateFrom, $dateTo, $reason, $authority, $action)
{
    if (empty($toEmail) || MAIL_APP_PASSWORD === 'PASTE_YOUR_16_CHARACTER_APP_PASSWORD_HERE')
{
        error_log('Leave email skipped: student email or Gmail App Password is not configured.');
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_APP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_USERNAME, 'M. Kumarasamy College of Engineering');
        $mail->addAddress($toEmail, $studentName);
        $mail->isHTML(true);

        $isApproved = ($action === 'approve');

        $statusText = $isApproved ? 'LEAVE APPROVED' : 'LEAVE REJECTED';
        $statusColor = $isApproved ? '#16a34a' : '#dc2626';
        $statusBg = $isApproved ? '#f0fdf4' : '#fef2f2';

        $safeName = htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8');
        $safeRegNo = htmlspecialchars($regNo, ENT_QUOTES, 'UTF-8');
        $safeFrom = htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8');
        $safeTo = htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8');
        $safeReason = nl2br(htmlspecialchars($reason, ENT_QUOTES, 'UTF-8'));
        $safeAuthority = htmlspecialchars($authority, ENT_QUOTES, 'UTF-8');

        $mail->Subject = $isApproved
            ? 'Leave Request Approved'
            : 'Leave Request Rejected';

        $rejectionNote = '';

        if (!$isApproved)
{
            $rejectionNote = "
                <div style='
                    margin-top:24px;
                    padding:16px;
                    background:#fef2f2;
                    border-left:4px solid #dc2626;
                    border-radius:6px;
                '>
                    <div style='
                        font-size:14px;
                        font-weight:bold;
                        color:#b91c1c;
                        margin-bottom:6px;
                    '>
                        Rejection Reason
                    </div>

                    <div style='font-size:14px;color:#7f1d1d;'>
                        {$safeReason}
                    </div>
                </div>
            ";
        }

        $mail->Body = "
<!DOCTYPE html>
<html>
<head>
<meta charset='UTF-8'>
<meta name='viewport' content='width=device-width, initial-scale=1.0'>
</head>

<body style='
    margin:0;
    padding:0;
    background:#f4f6f8;
    font-family:Arial,Helvetica,sans-serif;
    color:#1f2937;
'>

<table width='100%' cellpadding='0' cellspacing='0' style='background:#f4f6f8;padding:30px 10px;'>
<tr>
<td align='center'>

<table width='600' cellpadding='0' cellspacing='0' style='
    max-width:600px;
    width:100%;
    background:#ffffff;
    border-radius:12px;
    overflow:hidden;
    border:1px solid #e5e7eb;
'>

<!-- COLLEGE HEADER -->
<tr>
<td style='
    background:#172033;
    padding:24px 30px;
    text-align:center;
'>
    <div style='
        color:#ffffff;
        font-size:21px;
        font-weight:bold;
    '>
        M. Kumarasamy College of Engineering
    </div>

    <div style='
        color:#cbd5e1;
        font-size:12px;
        margin-top:6px;
    '>
        Leave Management System
    </div>
</td>
</tr>

<!-- STATUS -->
<tr>
<td style='padding:30px 30px 10px;text-align:center;'>

    <div style='
        display:inline-block;
        padding:10px 20px;
        background:{$statusBg};
        color:{$statusColor};
        border-radius:20px;
        font-size:18px;
        font-weight:bold;
        letter-spacing:0.5px;
    '>
        {$statusText}
    </div>

</td>
</tr>

<!-- CONTENT -->
<tr>
<td style='padding:15px 30px 30px;'>

    <p style='font-size:15px;margin:0 0 16px;'>
        Dear <strong>{$safeName}</strong>,
    </p>

    <p style='
        font-size:14px;
        line-height:1.7;
        margin:0 0 20px;
        color:#4b5563;
    '>
        Your leave request has been
        <strong style='color:{$statusColor};'>
            " . ($isApproved ? 'approved' : 'rejected') . "
        </strong>
        by the <strong>{$safeAuthority}</strong>.
    </p>

    <!-- DETAILS TABLE -->
    <table width='100%' cellpadding='0' cellspacing='0' style='
        border-collapse:collapse;
        border:1px solid #e5e7eb;
        border-radius:8px;
        overflow:hidden;
    '>

        <tr>
            <td style='
                padding:12px;
                background:#f8fafc;
                border-bottom:1px solid #e5e7eb;
                font-size:13px;
                font-weight:bold;
                width:40%;
            '>
                Student Name
            </td>

            <td style='
                padding:12px;
                border-bottom:1px solid #e5e7eb;
                font-size:13px;
            '>
                {$safeName}
            </td>
        </tr>

        <tr>
            <td style='
                padding:12px;
                background:#f8fafc;
                border-bottom:1px solid #e5e7eb;
                font-size:13px;
                font-weight:bold;
            '>
                Register No
            </td>

            <td style='
                padding:12px;
                border-bottom:1px solid #e5e7eb;
                font-size:13px;
            '>
                {$safeRegNo}
            </td>
        </tr>

        <tr>
            <td style='
                padding:12px;
                background:#f8fafc;
                border-bottom:1px solid #e5e7eb;
                font-size:13px;
                font-weight:bold;
            '>
                Leave From
            </td>

            <td style='
                padding:12px;
                border-bottom:1px solid #e5e7eb;
                font-size:13px;
            '>
                {$safeFrom}
            </td>
        </tr>

        <tr>
            <td style='
                padding:12px;
                background:#f8fafc;
                border-bottom:1px solid #e5e7eb;
                font-size:13px;
                font-weight:bold;
            '>
                Leave To
            </td>

            <td style='
                padding:12px;
                border-bottom:1px solid #e5e7eb;
                font-size:13px;
            '>
                {$safeTo}
            </td>
        </tr>

        <tr>
            <td style='
                padding:12px;
                background:#f8fafc;
                font-size:13px;
                font-weight:bold;
            '>
                Reason
            </td>

            <td style='
                padding:12px;
                font-size:13px;
            '>
                {$safeReason}
            </td>
        </tr>

    </table>

    {$rejectionNote}

    <p style='
        margin:24px 0 0;
        font-size:14px;
        line-height:1.7;
        color:#4b5563;
    '>
        " . ($isApproved
            ? 'Please make sure to follow the approved leave dates. You can log in to the Leave Management System to view your leave status.'
            : 'If you have any questions regarding this decision, please contact your Advisor.'
        ) . "
    </p>

</td>
</tr>

<!-- FOOTER -->
<tr>
<td style='
    background:#f8fafc;
    padding:20px 30px;
    text-align:center;
    border-top:1px solid #e5e7eb;
'>

    <div style='
        font-size:13px;
        font-weight:bold;
        color:#374151;
    '>
        Leave Management System
    </div>

    <div style='
        font-size:12px;
        color:#9ca3af;
        margin-top:5px;
    '>
        M. Kumarasamy College of Engineering
    </div>

    <div style='
        font-size:11px;
        color:#9ca3af;
        margin-top:10px;
    '>
        This is an automated notification. Please do not reply to this email.
    </div>

</td>
</tr>

</table>

</td>
</tr>
</table>

</body>
</html>
        ";

        $mail->AltBody =
            "Dear {$studentName}, your leave request from {$dateFrom} to {$dateTo} has been "
            . ($isApproved ? 'approved' : 'rejected')
            . " by {$authority}. "
            . "Register No: {$regNo}. Reason: {$reason}";

        $mail->send();
        return true;

    } catch (Exception $e)
{
        error_log('Leave status email failed: ' . $mail->ErrorInfo);
        return false;
    }
}

/** Send an automatic-lock notification only to the assigned faculty/advisor. */
function sendLeaveLockEmail($toEmail, $advisorName, $studentName, $regNo, $dateFrom, $dateTo, $reason)
{
    if (empty($toEmail) || MAIL_APP_PASSWORD === 'PASTE_YOUR_16_CHARACTER_APP_PASSWORD_HERE')
{
        error_log('Leave lock email skipped: faculty email or Gmail App Password is not configured.');
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_APP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_USERNAME, 'M. Kumarasamy College of Engineering');
        $mail->addAddress($toEmail, $advisorName ?: 'Faculty');
        $mail->isHTML(true);

        $safeAdvisor = htmlspecialchars($advisorName ?: 'Faculty', ENT_QUOTES, 'UTF-8');
        $safeStudent = htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8');
        $safeReg     = htmlspecialchars($regNo, ENT_QUOTES, 'UTF-8');
        $safeFrom    = htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8');
        $safeTo      = htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8');
        $safeReason  = nl2br(htmlspecialchars($reason, ENT_QUOTES, 'UTF-8'));

        $mail->Subject = 'Leave Request Automatically Locked - 48 Hour Deadline';

        $mail->Body = "
<!DOCTYPE html>
<html>
<head>
<meta charset='UTF-8'>
<meta name='viewport' content='width=device-width, initial-scale=1.0'>
</head>

<body style='
    margin:0;
    padding:0;
    background:#f4f6f8;
    font-family:Arial,Helvetica,sans-serif;
    color:#1f2937;
'>

<table width='100%' cellpadding='0' cellspacing='0'
style='background:#f4f6f8;padding:30px 10px;'>
<tr>
<td align='center'>

<table width='600' cellpadding='0' cellspacing='0'
style='
    max-width:600px;
    width:100%;
    background:#ffffff;
    border-radius:12px;
    overflow:hidden;
    border:1px solid #e5e7eb;
'>

<!-- HEADER -->
<tr>
<td style='
    background:#172033;
    padding:24px 30px;
    text-align:center;
'>

    <div style='
        color:#ffffff;
        font-size:21px;
        font-weight:bold;
    '>
        M. Kumarasamy College of Engineering
    </div>

    <div style='
        color:#cbd5e1;
        font-size:12px;
        margin-top:6px;
    '>
        Leave Management System
    </div>

</td>
</tr>

<!-- LOCK STATUS -->
<tr>
<td style='padding:30px 30px 10px;text-align:center;'>

    <div style='
        display:inline-block;
        padding:10px 20px;
        background:#fff7ed;
        color:#ea580c;
        border-radius:20px;
        font-size:18px;
        font-weight:bold;
        letter-spacing:0.5px;
    '>
        LEAVE AUTO-LOCKED
    </div>

</td>
</tr>

<!-- CONTENT -->
<tr>
<td style='padding:15px 30px 30px;'>

    <p style='font-size:15px;margin:0 0 16px;'>
        Dear <strong>{$safeAdvisor}</strong>,
    </p>

    <p style='
        font-size:14px;
        line-height:1.7;
        margin:0 0 20px;
        color:#4b5563;
    '>
        The following leave request has been
        <strong style='color:#ea580c;'>
            automatically locked
        </strong>
        because no approval or rejection was recorded within the
        <strong>48-hour faculty review period</strong>.
    </p>

    <!-- DETAILS TABLE -->
    <table width='100%' cellpadding='0' cellspacing='0'
    style='
        border-collapse:collapse;
        border:1px solid #e5e7eb;
    '>

        <tr>
            <td style='
                padding:12px;
                background:#f8fafc;
                border-bottom:1px solid #e5e7eb;
                font-size:13px;
                font-weight:bold;
                width:40%;
            '>
                Student Name
            </td>

            <td style='
                padding:12px;
                border-bottom:1px solid #e5e7eb;
                font-size:13px;
            '>
                {$safeStudent}
            </td>
        </tr>

        <tr>
            <td style='
                padding:12px;
                background:#f8fafc;
                border-bottom:1px solid #e5e7eb;
                font-size:13px;
                font-weight:bold;
            '>
                Register No
            </td>

            <td style='
                padding:12px;
                border-bottom:1px solid #e5e7eb;
                font-size:13px;
            '>
                {$safeReg}
            </td>
        </tr>

        <tr>
            <td style='
                padding:12px;
                background:#f8fafc;
                border-bottom:1px solid #e5e7eb;
                font-size:13px;
                font-weight:bold;
            '>
                Leave From
            </td>

            <td style='
                padding:12px;
                border-bottom:1px solid #e5e7eb;
                font-size:13px;
            '>
                {$safeFrom}
            </td>
        </tr>

        <tr>
            <td style='
                padding:12px;
                background:#f8fafc;
                border-bottom:1px solid #e5e7eb;
                font-size:13px;
                font-weight:bold;
            '>
                Leave To
            </td>

            <td style='
                padding:12px;
                border-bottom:1px solid #e5e7eb;
                font-size:13px;
            '>
                {$safeTo}
            </td>
        </tr>

        <tr>
            <td style='
                padding:12px;
                background:#f8fafc;
                font-size:13px;
                font-weight:bold;
            '>
                Reason
            </td>

            <td style='
                padding:12px;
                font-size:13px;
            '>
                {$safeReason}
            </td>
        </tr>

    </table>

    <!-- ACTION MESSAGE -->
    <div style='
        margin-top:24px;
        padding:16px;
        background:#fff7ed;
        border-left:4px solid #ea580c;
        border-radius:6px;
    '>

        <div style='
            font-size:14px;
            font-weight:bold;
            color:#c2410c;
            margin-bottom:6px;
        '>
            Action Required
        </div>

        <div style='
            font-size:14px;
            line-height:1.6;
            color:#7c2d12;
        '>
            This request has been forwarded to the
            <strong>HOD</strong> for review.
            Please check the Leave Management System for further action.
        </div>

    </div>

    <p style='
        margin:24px 0 0;
        font-size:14px;
        line-height:1.7;
        color:#4b5563;
    '>
        The system automatically locked this request after the
        48-hour review deadline.
    </p>

</td>
</tr>

<!-- FOOTER -->
<tr>
<td style='
    background:#f8fafc;
    padding:20px 30px;
    text-align:center;
    border-top:1px solid #e5e7eb;
'>

    <div style='
        font-size:13px;
        font-weight:bold;
        color:#374151;
    '>
        Leave Management System
    </div>

    <div style='
        font-size:12px;
        color:#9ca3af;
        margin-top:5px;
    '>
        M. Kumarasamy College of Engineering
    </div>

    <div style='
        font-size:11px;
        color:#9ca3af;
        margin-top:10px;
    '>
        This is an automated notification. Please do not reply to this email.
    </div>

</td>
</tr>

</table>

</td>
</tr>
</table>

</body>
</html>
        ";

        $mail->AltBody =
            "Dear {$advisorName}, the leave request for {$studentName} ({$regNo}) "
            . "has been automatically locked after 48 hours and forwarded to the HOD. "
            . "Leave: {$dateFrom} to {$dateTo}. Reason: {$reason}";

        $mail->send();
        return true;

    } catch (Exception $e)
{
        error_log('Leave lock email failed: ' . $mail->ErrorInfo);
        return false;
    }
}
