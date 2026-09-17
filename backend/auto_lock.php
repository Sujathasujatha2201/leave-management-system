<?php
/*
 * Scheduler endpoint for the faculty 48-hour auto-lock.
 * For exact automatic timing, call this URL periodically using
 * Windows Task Scheduler / cron.
 */
require_once __DIR__ . '/shared/bootstrap.php';
require_once __DIR__ . '/shared/auto_lock.php';

$result = autoLockExpiredLeaves($conn);

json_response([
    'success' => true,
    'message' => 'Automatic faculty leave-lock check completed.',
    'locked' => $result['locked'],
    'emails' => $result['emails']
]);
