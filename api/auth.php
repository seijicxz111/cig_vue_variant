<?php
/**
 * api/auth.php — Admin login / logout / session check
 * Admin users have role = 'admin' in users table
 */
session_start();
header('Content-Type: application/json');
require_once '../db/config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── LOGOUT ────────────────────────────────────────────────────────────────────
if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

// ── SESSION CHECK ─────────────────────────────────────────────────────────────
if ($action === 'check') {
    echo json_encode(['logged_in' => !empty($_SESSION['admin_logged_in'])]);
    exit;
}

// ── LOGIN ─────────────────────────────────────────────────────────────────────
$data  = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$email = trim($data['email'] ?? '');
$pass  = trim($data['password'] ?? '');

if (!$email || !$pass) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and password required']);
    exit;
}

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) { echo json_encode(['success' => false, 'message' => 'DB error']); exit; }

$stmt = mysqli_prepare($conn,
    "SELECT user_id, username, full_name, email, password_hash, role
     FROM users WHERE email = ? AND status = 'active' LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user   = mysqli_fetch_assoc($result);

if (!$user || $user['role'] !== 'admin' || !password_verify($pass, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid credentials or not an admin account']);
    mysqli_close($conn);
    exit;
}

// Update last_login
$uid = $user['user_id'];
mysqli_query($conn, "UPDATE users SET last_login = NOW() WHERE user_id = $uid");

$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id']        = $user['user_id'];
$_SESSION['admin_email']     = $user['email'];
$_SESSION['admin_name']      = $user['full_name'];

mysqli_close($conn);
echo json_encode([
    'success'   => true,
    'user_id'   => $user['user_id'],
    'full_name' => $user['full_name'],
    'email'     => $user['email'],
]);
