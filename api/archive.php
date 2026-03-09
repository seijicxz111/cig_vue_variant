<?php
/**
 * api/archive.php — Rejected submissions for ArchiveView
 */
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_logged_in'])) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }

require_once '../db/config.php';
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) { echo json_encode(['submissions'=>[], 'orgs'=>[]]); exit; }

// Filter by org
$org_id = isset($_GET['org_id']) ? (int)$_GET['org_id'] : 0;
$where  = $org_id ? "AND s.org_id = $org_id" : '';

$res = mysqli_query($conn, "
    SELECT s.submission_id, s.title, s.description, s.status,
           s.file_name, s.file_path, s.submitted_at, s.updated_at,
           u.org_name, u.full_name,
           r.feedback AS review_feedback, r.reviewed_at
    FROM submissions s
    JOIN users u ON s.org_id = u.user_id
    LEFT JOIN reviews r ON r.submission_id = s.submission_id
    WHERE s.status = 'rejected'
    $where
    ORDER BY s.updated_at DESC");

$submissions = [];
while ($row = mysqli_fetch_assoc($res)) $submissions[] = $row;

// Org list for filter dropdown
$orgs_res = mysqli_query($conn, "
    SELECT DISTINCT u.user_id AS org_id, u.org_name
    FROM submissions s
    JOIN users u ON s.org_id = u.user_id
    WHERE s.status = 'rejected' AND u.org_code IS NOT NULL
    ORDER BY u.org_name ASC");
$orgs = [];
while ($r = mysqli_fetch_assoc($orgs_res)) $orgs[] = $r;

mysqli_close($conn);
echo json_encode(['submissions' => $submissions, 'orgs' => $orgs]);
