<?php
require_once '../config/db.php';
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'POST required', 'error' => 'POST required']); exit;
}

// Support both form-encoded (POST) and JSON body input
$rawInput  = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true);
if (!is_array($jsonInput)) {
    $jsonInput = [];
}

$email    = strtolower(trim($_POST['email']    ?? $jsonInput['email']    ?? ''));
$password = trim($_POST['password']            ?? $jsonInput['password'] ?? '');
$role     = trim($_POST['role']                ?? $jsonInput['role']     ?? '');

if (!$email || !$password) {
    echo json_encode(['status' => 'error', 'message' => 'Email and password are required.', 'error' => 'Email and password are required.']); exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'No account found. Please register first.', 'error' => 'No account found. Please register first.']); exit;
}
if (!$user['is_active']) {
    echo json_encode(['status' => 'error', 'message' => 'This account is disabled. Contact admin.', 'error' => 'This account is disabled. Contact admin.']); exit;
}

// Match the submitted password with the database (supporting plain text or matching the stored format)
$isMatch = ($password === $user['password']) || (password_verify($password, $user['password']));
if (!$isMatch) {
    echo json_encode(['status' => 'error', 'message' => 'Incorrect password.', 'error' => 'Incorrect password.']); exit;
}

// If role is specified and does not match, log in with user's actual role
$userRole = $user['role'] ?? 'student';

$_SESSION['user_id']   = (int)$user['id'];
$_SESSION['role']      = $userRole;
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['photo_url'] = $user['photo_url'];

try {
    $log = $pdo->prepare("INSERT INTO system_logs (user_id, action, ip_address) VALUES (?,?,?)");
    $log->execute([$user['id'], 'login', $_SERVER['REMOTE_ADDR'] ?? '']);
} catch (Exception $e) {
    // Suppress log failure
}

$redirectMap = [
    'student'          => '../pages/student_dashboard.html',
    'counselor'        => '../pages/counselor/dashboard.html',
    'learning_advisor' => '../pages/advisor/dashboard.html',
    'admin'            => '../pages/admin/dashboard.html',
];
$redirect = $redirectMap[$userRole] ?? '../pages/student_dashboard.html';

echo json_encode([
    'status'    => 'success',
    'success'   => true,
    'redirect'  => $redirect,
    'message'   => 'Login successful',
    'role'      => $userRole,
    'full_name' => $user['full_name']
]);
?>