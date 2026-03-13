#!/usr/bin/env php
<?php
// ============================================================
// cron/api_logs_cleanup.php
// Run daily: 0 2 * * * php /path/to/cron/api_logs_cleanup.php
//
// Deletes:
//   - Error logs (response_code != 200) older than 30 days
//   - Success logs (response_code = 200) older than 60 days
// ============================================================

define('CRON_RUN', true);
require_once __DIR__ . '/../include/config.php';

$success_days = (int)(get_config('api_log_retention_success') ?? 60);
$error_days   = (int)(get_config('api_log_retention_error')   ?? 30);

$now = date('Y-m-d H:i:s');
echo "[{$now}] API Log Cleanup started\n";

// ── Delete error logs older than N days ───────────────────────
$error_cutoff = date('Y-m-d H:i:s', strtotime("-{$error_days} days"));
$conn->query("DELETE FROM api_logs WHERE response_code != 200 AND created_at < '$error_cutoff'");
$error_deleted = $conn->affected_rows;
echo "[{$now}] Deleted {$error_deleted} error log(s) older than {$error_days} days (before {$error_cutoff})\n";

// ── Delete success logs older than N days ─────────────────────
$success_cutoff = date('Y-m-d H:i:s', strtotime("-{$success_days} days"));
$conn->query("DELETE FROM api_logs WHERE response_code = 200 AND created_at < '$success_cutoff'");
$success_deleted = $conn->affected_rows;
echo "[{$now}] Deleted {$success_deleted} success log(s) older than {$success_days} days (before {$success_cutoff})\n";

// ── Stats ─────────────────────────────────────────────────────
$remaining = $conn->query("SELECT COUNT(*) FROM api_logs")->fetch_row()[0];
echo "[{$now}] Logs remaining in DB: {$remaining}\n";
echo "[{$now}] Cleanup complete. Total deleted: " . ($error_deleted + $success_deleted) . "\n";

$conn->close();
?>
