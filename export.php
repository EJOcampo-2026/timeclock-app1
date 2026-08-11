<?php
// ============================================
// export.php - Admin-only CSV export of time logs
// Accepts the same filters as admin_api.php?action=logs:
//   user_id, date_from, date_to
//
// USAGE (from the admin dashboard's Export button):
//   export.php?user_id=3&date_from=2026-08-01&date_to=2026-08-31
// ============================================

require_once 'config.php';
requireAdmin();

$pdo = getDBConnection();

$employeeId = $_GET['user_id'] ?? null;
$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;

$sql = "SELECT u.username, u.email, t.time_in, t.time_out,
               TIMESTAMPDIFF(MINUTE, t.time_in, IFNULL(t.time_out, NOW())) AS minutes_worked
        FROM time_logs t
        JOIN users u ON u.id = t.user_id
        WHERE 1=1";
$params = [];

if ($employeeId) {
    $sql .= " AND t.user_id = ?";
    $params[] = $employeeId;
}
if ($dateFrom) {
    $sql .= " AND DATE(t.time_in) >= ?";
    $params[] = $dateFrom;
}
if ($dateTo) {
    $sql .= " AND DATE(t.time_in) <= ?";
    $params[] = $dateTo;
}

$sql .= " ORDER BY u.username, t.time_in";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Build a filename that reflects the filters applied
$filenameParts = ['timelogs'];
if ($dateFrom) $filenameParts[] = $dateFrom;
if ($dateTo) $filenameParts[] = $dateTo;
$filename = implode('_', $filenameParts) . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Header row
fputcsv($output, ['Employee', 'Email', 'Date', 'Time In', 'Time Out', 'Hours Worked', 'Status']);

foreach ($rows as $r) {
    $timeIn = new DateTime($r['time_in']);
    $timeOut = $r['time_out'] ? new DateTime($r['time_out']) : null;
    $hours = round($r['minutes_worked'] / 60, 2);
    $status = $timeOut ? 'Done' : 'Still clocked in';

    fputcsv($output, [
        $r['username'],
        $r['email'],
        $timeIn->format('Y-m-d'),
        $timeIn->format('h:i A'),
        $timeOut ? $timeOut->format('h:i A') : '',
        $hours,
        $status,
    ]);
}

fclose($output);
