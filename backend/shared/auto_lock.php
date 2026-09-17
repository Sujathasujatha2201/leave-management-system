<?php
/*
 * Faculty-only automatic leave lock.
 * advisor_status:
 *   4 = Pending
 *   1 = Approved
 *   3 = Rejected
 *   2 = Locked
 *
 * Only advisor/faculty pending requests are checked.
 * Expired requests are locked and forwarded to the same-department HOD.
 * advisor_deadline_at is reset when HOD releases a lock, so the next 48-hour window starts from release.
 */
require_once __DIR__ . '/email_config.php';

function autoLockExpiredLeaves(mysqli $conn): array
{
    $locked = 0;
    $emails = 0;

    $q = $conn->prepare("
        SELECT lr.id, lr.reg_no, lr.date_from, lr.date_to, lr.reason,
               lr.advisor_status, lr.advisor_lock_notified, lr.advisor_deadline_at,
               lr.advisor_id,
               au.full_name AS advisor_name, au.email AS advisor_email,
               su.full_name AS student_name
        FROM leave_requests lr
        LEFT JOIN users au ON au.id = lr.advisor_id
        LEFT JOIN users su ON su.id = lr.student_id
        WHERE lr.advisor_status = 4
          AND (lr.advisor_deadline_at IS NOT NULL AND lr.advisor_deadline_at <= NOW()
               OR lr.advisor_deadline_at IS NULL AND lr.created_at <= DATE_SUB(NOW(), INTERVAL 48 HOUR))
        ORDER BY lr.id ASC
    ");

    if (!$q || !$q->execute())
{
        if ($q) $q->close();
        return ['locked' => 0, 'emails' => 0];
    }

    $result = $q->get_result();

    while ($r = $result->fetch_assoc())
{
        $id = (int)$r['id'];

        /* Atomic status change: only a still-pending request can be locked. */
        $u = $conn->prepare("
            UPDATE leave_requests
            SET advisor_status = 2, forwarded_to_hod = 1, hod_status = 4
            WHERE id = ? AND advisor_status = 4
        ");
        if (!$u) continue;

        $u->bind_param('i', $id);
        $u->execute();
        $changed = ($u->affected_rows === 1);
        $u->close();

        if (!$changed) continue;

        $locked++;

        /* Send the lock email only to the assigned faculty/advisor. */
        if ((int)$r['advisor_lock_notified'] === 0 && !empty($r['advisor_email']))
{
            $sent = sendLeaveLockEmail(
                $r['advisor_email'],
                $r['advisor_name'],
                $r['student_name'],
                $r['reg_no'],
                $r['date_from'],
                $r['date_to'],
                $r['reason']
            );

            if ($sent)
{
                $m = $conn->prepare("
                    UPDATE leave_requests
                    SET advisor_lock_notified = 1
                    WHERE id = ?
                ");
                if ($m)
{
                    $m->bind_param('i', $id);
                    $m->execute();
                    $m->close();
                }
                $emails++;
            }
        }
    }

    $q->close();
    return ['locked' => $locked, 'emails' => $emails];
}
