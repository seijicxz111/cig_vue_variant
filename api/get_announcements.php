<?php
/**
 * api/get_announcements.php — Returns all active announcements for the home page
 */
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_logged_in'])) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }

require_once '../db/config.php';
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) { echo json_encode(['announcements'=>[]]); exit; }

$result = mysqli_query($conn,
    "SELECT announcement_id, title, content, priority, category, audience,
            is_pinned, is_active, expires_at, created_at
     FROM announcements
     WHERE is_active = 1
       AND (expires_at IS NULL OR expires_at >= CURDATE())
     ORDER BY is_pinned DESC, created_at DESC");

$rows = [];
while ($r = mysqli_fetch_assoc($result)) $rows[] = $r;
mysqli_close($conn);
echo json_encode(['announcements' => $rows]);
