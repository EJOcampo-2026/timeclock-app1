<?php
// ============================================
// Auth API - login, logout, session check
// Self-registration is off (see config.php ALLOW_REGISTRATION).
// Add accounts manually via phpMyAdmin -> users table.
// ============================================

require_once 'config.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
$pdo = getDBConnection();

switch ($action) {

    case 'login':
        if ($method !== 'POST') { http_response_code(405); exit(json_encode(['success' => false, 'message' => 'Method not allowed.'])); }

        $input = json_decode(file_get_contents('php://input'), true);
        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';

        if ($username === '' || $password === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Username and password are required.']);
            break;
        }

        $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        // Plain-text comparison (kept simple for manual phpMyAdmin entry - see README).
        if (!$user || $password !== $user['password_hash']) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Incorrect username or password.']);
            break;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        echo json_encode(['success' => true, 'username' => $user['username'], 'role' => $user['role']]);
        break;

    case 'logout':
        $_SESSION = [];
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logged out.']);
        break;

    case 'check':
        if (isLoggedIn()) {
            echo json_encode([
                'success' => true,
                'loggedIn' => true,
                'username' => $_SESSION['username'],
                'role' => $_SESSION['role'],
            ]);
        } else {
            echo json_encode(['success' => true, 'loggedIn' => false]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        break;
}
