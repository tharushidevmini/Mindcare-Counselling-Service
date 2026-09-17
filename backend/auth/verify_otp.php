<?php
require_once '../config/db.php';
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'POST required']); exit;
}

$email    = strtolower(trim($_POST['email']    ?? ''));
$fullName = trim($_POST['full_name']           ?? '');
$otp      = trim($_POST['otp']                 ?? '');
$password = trim($_POST['password']            ?? '');
$role     = trim($_POST['role']                ?? 'student');

if (!$email || !$fullName || !$otp || !$password) {
    echo json_encode(['error' => 'All fields are required.']); exit;
}
if (strlen($password) < 6) {
    echo json_encode(['error' => 'Password must be at least 6 characters.']); exit;
}

$stmt = $pdo->prepare("
    SELECT * FROM guardian_sessions
    WHERE guardian_phone = ?
    AND otp_code = ?
    AND otp_expires > NOW()
    AND is_verified = 0
    ORDER BY created_at DESC LIMIT 1
");
$stmt->execute([$email, $otp]);
$session = $stmt->fetch();

if (!$session) {
    echo json_encode(['error' => 'Invalid or expired OTP. Please request a new one.']); exit;
}

$pdo->prepare("UPDATE guardian_sessions SET is_verified = 1 WHERE id = ?")
    ->execute([$session['id']]);

$studentId = null;
$localPart = strstr($email, '@', true);
if (preg_match('/[a-zA-Z]{2,5}\d{2,6}/', $localPart, $m)) {
    $studentId = strtoupper($m[0]);
}

// Handle optional profile picture upload — JPG/PNG only, 2MB max, safe filename
$photoUrl = null;
if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    $mime = mime_content_type($_FILES['photo']['tmp_name']);
    if (isset($allowedTypes[$mime]) && $_FILES['photo']['size'] <= 2 * 1024 * 1024) {
        $ext      = $allowedTypes[$mime];
        $filename = 'user_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $destDir  = __DIR__ . '/../../frontend/assets/images/profiles/';
        if (!is_dir($destDir)) mkdir($destDir, 0755, true);
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $destDir . $filename)) {
            $photoUrl = $filename;
        }
    }
}

$hashed = password_hash($password, PASSWORD_BCRYPT);

// Students self-activate instantly (verified campus email is proof enough —
// they only ever access their own data). Staff roles (counselor, advisor,
// admin) get elevated access to other people's sensitive data, so those
// still require a human admin to approve them before they can log in —
// anyone with a campus email could otherwise self-grant staff access.
$isStaffRole = in_array($role, ['counselor', 'learning_advisor', 'admin']);
$isActive    = $isStaffRole ? 0 : 1;

$ins = $pdo->prepare("
    INSERT INTO users (full_name, email, password, role, student_id, photo_url, is_active)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$ins->execute([$fullName, $email, $hashed, $role, $studentId, $photoUrl, $isActive]);
$newId = $pdo->lastInsertId();

if ($isStaffRole) {
    $log = $pdo->prepare("INSERT INTO system_logs (user_id, action, ip_address) VALUES (?,?,?)");
    $log->execute([$newId, 'staff_account_registered_pending_approval', $_SERVER['REMOTE_ADDR'] ?? '']);
    // Do NOT log staff in yet — their account needs admin approval first.
    echo json_encode(['success' => true, 'pending' => true, 'full_name' => $fullName]);
    exit;
}

$_SESSION['user_id']   = $newId;
$_SESSION['role']      = $role;
$_SESSION['full_name'] = $fullName;
$_SESSION['photo_url'] = $photoUrl;

$log = $pdo->prepare("INSERT INTO system_logs (user_id, action, ip_address) VALUES (?,?,?)");
$log->execute([$newId, 'account_registered_and_activated', $_SERVER['REMOTE_ADDR'] ?? '']);

// Log the student straight in — no waiting on admin approval.
echo json_encode(['success' => true, 'pending' => false, 'role' => $role, 'full_name' => $fullName]);
?>