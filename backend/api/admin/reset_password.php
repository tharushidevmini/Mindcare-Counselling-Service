<?php
// FILE: backend/api/admin/reset_password.php
// Resets a user's password to a new random temporary one, and emails it
// DIRECTLY to that user's own campus email — the admin never sees the
// plaintext password, so an admin resetting a password cannot use it
// themselves to log into that account.
ob_start();
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST required']); exit; }

$userId = (int)($_POST['user_id'] ?? 0);
if (!$userId) { echo json_encode(['error'=>'User ID required']); exit; }

$u = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
$u->execute([$userId]);
$user = $u->fetch();
if (!$user) { echo json_encode(['error'=>'User not found']); exit; }

$tempPassword = 'Mind' . rand(1000, 9999) . '!';
$hashed       = password_hash($tempPassword, PASSWORD_BCRYPT);

$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->execute([$hashed, $userId]);

require_once __DIR__ . '/../../helpers/mailer.php';
use PHPMailer\PHPMailer\Exception;

$safeName = htmlspecialchars($user['full_name'], ENT_QUOTES);

$mail = mindcareNewMailer();
try {
    $mail->addAddress($user['email']);
    $mail->isHTML(true);
    $mail->Subject = 'MindCare — Your Password Has Been Reset';
    $mail->Body = "
    <div style='font-family:Arial,sans-serif;max-width:480px;margin:0 auto;'>
      <div style='background:linear-gradient(135deg,#F5A846,#F5C846);padding:28px 32px;border-radius:16px 16px 0 0;'>
        <h1 style='color:#1C1410;margin:0;font-size:22px;'>MindCare</h1>
        <p style='color:#4A3728;margin:6px 0 0;font-size:13px;'>Password Reset</p>
      </div>
      <div style='background:#fff;padding:32px;border-radius:0 0 16px 16px;'>
        <p style='color:#1C1410;font-size:14px;margin:0 0 8px;'>Hi $safeName,</p>
        <p style='color:#8C7060;font-size:14px;margin:0 0 20px;'>An admin has reset your MindCare password. Here is your new temporary password:</p>
        <div style='background:#FEF3DF;border:2px solid #F5A846;border-radius:12px;padding:18px;text-align:center;margin-bottom:20px;'>
          <span style='font-size:22px;font-weight:800;color:#D4841A;letter-spacing:2px;'>$tempPassword</span>
        </div>
        <p style='color:#8C7060;font-size:13px;'>Please log in with this password, then change it to something only you know as soon as possible.</p>
      </div>
    </div>";

    mindcareSendMail($mail);
    $log = $pdo->prepare("INSERT INTO system_logs (user_id, action, ip_address) VALUES (?,?,?)");
    $log->execute([$_SESSION['user_id'], "admin_reset_password for_user:$userId", $_SERVER['REMOTE_ADDR'] ?? '']);
    ob_end_clean();
    echo json_encode(['success' => true, 'message' => "New password emailed to {$user['full_name']}."]);
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['error' => 'Password was reset, but the email could not be sent: ' . $mail->ErrorInfo]);
}
?>