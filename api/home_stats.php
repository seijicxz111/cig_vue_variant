<?php
/**
 * api/home_stats.php — Quick stats for HomeView header cards
 */
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_logged_in'])) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }

require_once '../db/config.php';
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) { echo json_encode(['orgs'=>0,'submissions'=>0,'approval_rate'=>0]); exit; }

$r = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT
        (SELECT COUNT(*) FROM users WHERE org_code IS NOT NULL AND status='active') AS orgs,
        (SELECT COUNT(*) FROM submissions) AS submissions,
        (SELECT ROUND(SUM(status='approved')/NULLIF(COUNT(*),0)*100,1) FROM submissions) AS approval_rate"));

mysqli_close($conn);
echo json_encode(['orgs' => (int)$r['orgs'], 'submissions' => (int)$r['submissions'], 'approval_rate' => (float)$r['approval_rate']]);
