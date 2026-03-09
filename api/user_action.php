<?php
/**
 * api/user_action.php — Activate / Deactivate / Delete user
 * Thin wrapper — delegates to create_user.php logic
 */
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_logged_in'])) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }

require_once '../db/config.php';
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$data    = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action  = $data['action'] ?? '';
$user_id = (int)($data['user_id'] ?? 0);

if (!$user_id || !in_array($action, ['activate','deactivate','delete'])) {
    echo json_encode(['success'=>false,'message'=>'Invalid params']); exit;
}

if ($action === 'delete') {
    $stmt = mysqli_prepare($conn,"DELETE FROM users WHERE user_id=? AND role!='admin'");
    mysqli_stmt_bind_param($stmt,'i',$user_id);
} else {
    $status = $action === 'activate' ? 'active' : 'inactive';
    $stmt   = mysqli_prepare($conn,"UPDATE users SET status=? WHERE user_id=? AND role!='admin'");
    mysqli_stmt_bind_param($stmt,'si',$status,$user_id);
}

echo json_encode(['success' => mysqli_stmt_execute($stmt)]);
mysqli_close($conn);
