<?php
// ============================================
// Admin API - view all employees' time logs
// Actions: logs (optionally filtered), employees
// ============================================

require_once 'config.php';
requireAdmin();
header('Content-Type: application/json');

$pdo = getDBConnection();
$action = $_GET['action'] ?? null;

switch ($action) {

    case 'employees':
        // For the filter dropdown
        $stmt = $pdo->query("SELECT id, username FROM users ORDER BY username");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'logs':
        $employeeId = $_GET['user_id'] ?? null;
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;

        $sql = "SELECT t.id, t.time_in, t.time_out, u.username,
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

        $sql .= " ORDER BY t.time_in DESC LIMIT 300";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        break;
}
