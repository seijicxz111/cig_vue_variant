<?php
/**
 * api/create_user.php — List orgs/users, create org user, activate/deactivate/delete
 * Organizations are users with org_code IS NOT NULL
 */
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_logged_in'])) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }

require_once '../db/config.php';
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) { echo json_encode(['error'=>'DB error']); exit; }

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── LIST users/orgs ────────────────────────────────────────────────────────────
if ($method === 'GET' && (!$action || $action === 'list')) {
    $type = $_GET['type'] ?? 'orgs'; // 'orgs' | 'users' | 'all'
    if ($type === 'orgs') {
        $where = "WHERE org_code IS NOT NULL";
    } elseif ($type === 'users') {
        $where = "WHERE role = 'user' AND org_code IS NULL";
    } else {
        $where = "WHERE role != 'admin'";
    }
    $res  = mysqli_query($conn, "
        SELECT user_id, username, email, full_name, role, status,
               org_name, org_code, description, contact_person, phone, created_at, last_login
        FROM users $where ORDER BY created_at DESC");
    $rows = [];
    while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
    echo json_encode(['users' => $rows]);
    mysqli_close($conn);
    exit;
}

// ── CREATE ─────────────────────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'create') {
    $d = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $username  = trim($d['username'] ?? '');
    $email     = trim($d['email'] ?? '');
    $password  = trim($d['password'] ?? '');
    $full_name = trim($d['full_name'] ?? '');
    $org_name  = trim($d['org_name'] ?? '');
    $org_code  = strtoupper(trim($d['org_code'] ?? ''));
    $desc      = trim($d['description'] ?? '');
    $contact   = trim($d['contact_person'] ?? '');
    $phone     = trim($d['phone'] ?? '');

    if (!$username || !$email || !$password || !$full_name) {
        echo json_encode(['success'=>false,'message'=>'Required fields missing']); exit;
    }

    // Check duplicate email / org_code
    $check = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email=? OR (org_code IS NOT NULL AND org_code=?) LIMIT 1");
    mysqli_stmt_bind_param($check, 'ss', $email, $org_code);
    mysqli_stmt_execute($check);
    if (mysqli_stmt_get_result($check)->num_rows > 0) {
        echo json_encode(['success'=>false,'message'=>'Email or org code already exists']); exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = mysqli_prepare($conn,
        "INSERT INTO users (username,email,full_name,role,password_hash,status,org_name,org_code,description,contact_person,phone)
         VALUES (?,?,?,'user',?,'active',?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt,'sssssssss',
        $username,$email,$full_name,$hash,$org_name,$org_code,$desc,$contact,$phone);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success'=>true,'id'=>mysqli_insert_id($conn)]);
    } else {
        echo json_encode(['success'=>false,'message'=>mysqli_error($conn)]);
    }
    mysqli_close($conn);
    exit;
}

// ── ACTIVATE / DEACTIVATE / DELETE ────────────────────────────────────────────
if ($method === 'POST' && in_array($action, ['activate','deactivate','delete'])) {
    $user_id = (int)($_POST['user_id'] ?? 0);
    if (!$user_id) { echo json_encode(['success'=>false,'message'=>'Invalid user_id']); exit; }

    if ($action === 'delete') {
        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE user_id = ? AND role != 'admin'");
        mysqli_stmt_bind_param($stmt,'i',$user_id);
    } else {
        $status = $action === 'activate' ? 'active' : 'inactive';
        $stmt   = mysqli_prepare($conn, "UPDATE users SET status=? WHERE user_id=? AND role != 'admin'");
        mysqli_stmt_bind_param($stmt,'si',$status,$user_id);
    }

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success'=>true]);
    } else {
        echo json_encode(['success'=>false,'message'=>mysqli_error($conn)]);
    }
    mysqli_close($conn);
    exit;
}

http_response_code(400);
echo json_encode(['error'=>'Invalid request']);
