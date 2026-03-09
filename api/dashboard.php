<?php
/**
 * api/dashboard.php — Chart data for DashboardView
 * All orgs = users WHERE org_code IS NOT NULL
 * submissions.org_id references users.user_id
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../db/config.php';
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) { echo json_encode(['error' => 'DB connect failed']); exit; }

$range = $_GET['range'] ?? '6months';
switch ($range) {
    case '30days':  $interval = 'INTERVAL 30 DAY';  $group = '%Y-%m-%d'; break;
    case '1year':   $interval = 'INTERVAL 1 YEAR';  $group = '%Y-%m'; break;
    case 'alltime': $interval = 'INTERVAL 10 YEAR'; $group = '%Y-%m'; break;
    default:        $interval = 'INTERVAL 6 MONTH'; $group = '%Y-%m'; break;
}

// ── KPI stats ───────────────────────────────────────────────────────────────
$stats_res = mysqli_query($conn, "
    SELECT
        COUNT(*) AS total,
        SUM(status='pending')   AS pending,
        SUM(status='approved')  AS approved,
        SUM(status='rejected')  AS rejected,
        SUM(status='in_review') AS in_review,
        ROUND(SUM(status='approved')/NULLIF(COUNT(*),0)*100, 1) AS approval_rate
    FROM submissions
    WHERE submitted_at >= NOW() - $interval");
$stats = mysqli_fetch_assoc($stats_res) ?: [];

// ── Monthly trend ────────────────────────────────────────────────────────────
$trend_res = mysqli_query($conn, "
    SELECT DATE_FORMAT(submitted_at, '$group') AS period, COUNT(*) AS cnt
    FROM submissions
    WHERE submitted_at >= NOW() - $interval
    GROUP BY period ORDER BY period ASC");
$trend = [];
while ($r = mysqli_fetch_assoc($trend_res)) $trend[] = $r;

// ── Approved vs Rejected trend ───────────────────────────────────────────────
$approval_res = mysqli_query($conn, "
    SELECT DATE_FORMAT(submitted_at, '$group') AS period,
           SUM(status='approved') AS approved,
           SUM(status='rejected') AS rejected
    FROM submissions
    WHERE submitted_at >= NOW() - $interval
    GROUP BY period ORDER BY period ASC");
$approval_trend = [];
while ($r = mysqli_fetch_assoc($approval_res)) $approval_trend[] = $r;

// ── Per-org bar chart ────────────────────────────────────────────────────────
$org_res = mysqli_query($conn, "
    SELECT u.org_name, COUNT(s.submission_id) AS cnt
    FROM submissions s
    JOIN users u ON s.org_id = u.user_id
    WHERE s.submitted_at >= NOW() - $interval
      AND u.org_code IS NOT NULL
    GROUP BY s.org_id, u.org_name
    ORDER BY cnt DESC LIMIT 10");
$by_org = [];
while ($r = mysqli_fetch_assoc($org_res)) $by_org[] = $r;

// ── Smart insights ───────────────────────────────────────────────────────────
$active_res = mysqli_query($conn, "
    SELECT u.org_name, COUNT(*) AS cnt
    FROM submissions s JOIN users u ON s.org_id = u.user_id
    WHERE u.org_code IS NOT NULL
    GROUP BY s.org_id ORDER BY cnt DESC LIMIT 1");
$most_active = ($r = mysqli_fetch_assoc($active_res)) ? $r['org_name'] : null;

$oldest_res = mysqli_query($conn, "
    SELECT DATEDIFF(NOW(), MIN(submitted_at)) AS days
    FROM submissions WHERE status='pending'");
$oldest_row    = mysqli_fetch_assoc($oldest_res);
$oldest_pending = $oldest_row['days'] ?? null;

$high_res = mysqli_query($conn, "
    SELECT DATE_FORMAT(submitted_at,'%b %Y') AS period, COUNT(*) AS cnt
    FROM submissions GROUP BY period ORDER BY cnt DESC LIMIT 1");
$highest_month = ($r = mysqli_fetch_assoc($high_res)) ? $r['period'] : null;

$low_res = mysqli_query($conn, "
    SELECT DATE_FORMAT(submitted_at,'%b %Y') AS period, COUNT(*) AS cnt
    FROM submissions GROUP BY period ORDER BY cnt ASC LIMIT 1");
$lowest_month = ($r = mysqli_fetch_assoc($low_res)) ? $r['period'] : null;

// ── Recent submissions (last 5) ───────────────────────────────────────────────
$recent_res = mysqli_query($conn, "
    SELECT s.submission_id, s.title, s.status, s.submitted_at,
           u.org_name, u.full_name
    FROM submissions s
    JOIN users u ON s.org_id = u.user_id
    ORDER BY s.submitted_at DESC LIMIT 5");
$recent = [];
while ($r = mysqli_fetch_assoc($recent_res)) $recent[] = $r;

mysqli_close($conn);
echo json_encode([
    'stats'          => $stats,
    'trend'          => $trend,
    'approval_trend' => $approval_trend,
    'by_org'         => $by_org,
    'recent'         => $recent,
    'insights'       => [
        'most_active'    => $most_active,
        'oldest_pending' => $oldest_pending,
        'highest_month'  => $highest_month,
        'lowest_month'   => $lowest_month,
    ],
]);
