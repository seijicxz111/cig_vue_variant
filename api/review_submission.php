<?php
/**
 * api/review_submission.php — Fetch submissions for ReviewView
 * Returns: folder list (distinct orgs), per-org submissions, single submission detail
 */
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_logged_in'])) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }

require_once '../db/config.php';
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) { echo json_encode(['error'=>'DB error']); exit; }

$action = $_GET['action'] ?? 'folders';

// ── FOLDER LIST: one entry per org that has pending/in_review submissions ─────
if ($action === 'folders') {
    $res = mysqli_query($conn, "
        SELECT u.user_id AS org_id, u.org_name, u.org_code,
               COUNT(s.submission_id) AS total,
               SUM(s.status='pending')   AS pending,
               SUM(s.status='in_review') AS in_review
        FROM submissions s
        JOIN users u ON s.org_id = u.user_id
        WHERE s.status IN ('pending','in_review')
          AND u.org_code IS NOT NULL
        GROUP BY s.org_id, u.user_id, u.org_name, u.org_code
        ORDER BY pending DESC, u.org_name ASC");
    $folders = [];
    while ($r = mysqli_fetch_assoc($res)) $folders[] = $r;
    echo json_encode(['folders' => $folders]);
    mysqli_close($conn);
    exit;
}

// ── ORG SUBMISSIONS: list for a specific org ───────────────────────────────────
if ($action === 'by_org' && isset($_GET['org_id'])) {
    $org_id = (int)$_GET['org_id'];
    $res = mysqli_query($conn, "
        SELECT s.submission_id, s.title, s.description, s.status,
               s.file_name, s.file_path, s.submitted_at, s.updated_at,
               u.org_name, u.full_name
        FROM submissions s
        JOIN users u ON s.org_id = u.user_id
        WHERE s.org_id = $org_id AND s.status IN ('pending','in_review')
        ORDER BY s.submitted_at DESC");
    $subs = [];
    while ($r = mysqli_fetch_assoc($res)) $subs[] = $r;
    echo json_encode(['submissions' => $subs]);
    mysqli_close($conn);
    exit;
}

// ── SINGLE SUBMISSION DETAIL ──────────────────────────────────────────────────
if ($action === 'detail' && isset($_GET['submission_id'])) {
    $sid = (int)$_GET['submission_id'];
    $res = mysqli_query($conn, "
        SELECT s.*, u.org_name, u.org_code, u.full_name, u.email,
               r.feedback AS review_feedback, r.reviewed_at, r.rating
        FROM submissions s
        JOIN users u ON s.org_id = u.user_id
        LEFT JOIN reviews r ON r.submission_id = s.submission_id
        WHERE s.submission_id = $sid LIMIT 1");
    $sub = mysqli_fetch_assoc($res);
    echo json_encode(['submission' => $sub]);
    mysqli_close($conn);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
