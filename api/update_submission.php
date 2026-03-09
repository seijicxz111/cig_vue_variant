<?php
/**
 * api/update_submission.php — Set submission to in_review when admin opens it
 */
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_logged_in'])) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }

require_once '../db/config.php';
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$sid  = (int)($data['submission_id'] ?? 0);
$status = $data['status'] ?? '';
$allowed = ['pending','in_review','approved','rejected'];

if (!$sid || !in_array($status, $allowed)) {
    echo json_encode(['success'=>false,'message'=>'Invalid params']); exit;
}

$stmt = mysqli_prepare($conn,"UPDATE submissions SET status=?, updated_at=NOW() WHERE submission_id=?");
mysqli_stmt_bind_param($stmt,'si',$status,$sid);
echo json_encode(['success' => mysqli_stmt_execute($stmt)]);
mysqli_close($conn);
