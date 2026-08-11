<?php
// ============================================
// Database Configuration
// Update these values to match your phpMyAdmin / MySQL setup
// ============================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'timeclock_db');
define('DB_USER', 'root');      // default XAMPP/WAMP user
define('DB_PASS', '');          // default XAMPP/WAMP password (empty)

// Self-registration is off - accounts are added manually via phpMyAdmin.
define('ALLOW_REGISTRATION', false);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

// Call at the top of any endpoint that requires a logged-in user.
function requireLogin() {
    if (!isLoggedIn()) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Not authenticated. Please log in.']);
        exit;
    }
}

// Call at the top of any endpoint that requires an admin account.
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Admin access only.']);
        exit;
    }
}

function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
        exit;
    }
}
