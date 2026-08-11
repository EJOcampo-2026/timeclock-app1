<?php
// ============================================
// Time Clock API - for the logged-in employee
// Actions: status, clock_in, clock_out, history
// ============================================

require_once 'config.php';
requireLogin();
header('Content-Type: application/json');

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

switch ($action) {

    case 'status':
        // Is there an open (still clocked-in) entry for this user?
        $stmt = $pdo->prepare("SELECT id, time_in FROM time_logs WHERE user_id = ? AND time_out IS NULL ORDER BY time_in DESC LIMIT 1");
        $stmt->execute([$userId]);
        $open = $stmt->fetch();

        echo json_encode([
            'success' => true,
            'clockedIn' => (bool)$open,
            'sinceTime' => $open['time_in'] ?? null,
        ]);
        break;

    case 'clock_in':
        if ($method !== 'POST') { http_response_code(405); exit(json_encode(['success' => false, 'message' => 'Method not allowed.'])); }

        // Prevent double clock-in
        $stmt = $pdo->prepare("SELECT id FROM time_logs WHERE user_id = ? AND time_out IS NULL");
        $stmt->execute([$userId]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'You are already clocked in.']);
            break;
        }

        $stmt = $pdo->prepare("INSERT INTO time_logs (user_id, time_in) VALUES (?, NOW())");
        $stmt->execute([$userId]);

        echo json_encode(['success' => true, 'message' => 'Clocked in.']);
        break;

    case 'clock_out':
        if ($method !== 'POST') { http_response_code(405); exit(json_encode(['success' => false, 'message' => 'Method not allowed.'])); }

        $stmt = $pdo->prepare("SELECT id FROM time_logs WHERE user_id = ? AND time_out IS NULL ORDER BY time_in DESC LIMIT 1");
        $stmt->execute([$userId]);
        $open = $stmt->fetch();

        if (!$open) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'You are not currently clocked in.']);
            break;
        }

        $stmt = $pdo->prepare("UPDATE time_logs SET time_out = NOW() WHERE id = ?");
        $stmt->execute([$open['id']]);

        echo json_encode(['success' => true, 'message' => 'Clocked out.']);
        break;

    case 'history':
        // This user's own recent logs
        $stmt = $pdo->prepare(
            "SELECT id, time_in, time_out,
                    TIMESTAMPDIFF(MINUTE, time_in, IFNULL(time_out, NOW())) AS minutes_worked
             FROM time_logs
             WHERE user_id = ?
             ORDER BY time_in DESC
             LIMIT 30"
        );
        $stmt->execute([$userId]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        break;
}
