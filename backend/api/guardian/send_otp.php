<?php
// FILE: backend/api/guardian/send_otp.php
ob_start();
require_once '../../config/db.php';
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST required']); exit; }

$studentId = trim($_POST['student_id']     ?? '');
$email     = strtolower(trim($_POST['guardian_email'] ?? ''));

if (!$studentId || !$email) { echo json_encode(['error'=>'Student ID and email are required']); exit; }
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { echo json_encode(['error'=>'Please enter a valid email address']); exit; }

$chk = $pdo->prepare("SELECT id FROM users WHERE student_id = ? AND is_active = 1");
$chk->execute([$studentId]);
$student = $chk->fetch();
if (!$student) { echo json_encode(['error'=>'Student ID not found in the system']); exit; }
$otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

$stmt = $pdo->prepare("INSERT INTO guardian_sessions (guardian_phone, student_id, otp_code, otp_expires, is_verified) VALUES (?,?,?, DATE_ADD(NOW(), INTERVAL 5 MINUTE), 0)");
$stmt->execute([$email, $student['id'], $otp]);
$_SESSION['guardian_session_id'] = $pdo->lastInsertId();
$_SESSION['guardian_student_id'] = $student['id'];

require_once __DIR__ . '/../../helpers/mailer.php';

try {
    $mail = mindcareNewMailer();
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'MindCare — Guardian Emergency Access Code';
    $mail->Body    = "
    <div style='font-family:Arial,sans-serif;max-width:480px;margin:0 auto;'>
      <div style='background:linear-gradient(135deg,#F5A846,#F5C846);padding:28px 32px;border-radius:16px 16px 0 0;'>
        <h1 style='color:#1C1410;margin:0;font-size:22px;'>MindCare</h1>
        <p style='color:#4A3728;margin:6px 0 0;font-size:13px;'>Guardian Emergency Access</p>
      </div>
      <div style='background:#fff;padding:32px;border-radius:0 0 16px 16px;'>
        <h2 style='color:#1C1410;font-size:18px;margin:0 0 8px;'>Your Verification Code</h2>
        <p style='color:#8C7060;font-size:14px;margin:0 0 24px;'>Use this code to access emergency counseling booking for your student.</p>
        <div style='background:#FEF3DF;border:2px solid #F5A846;border-radius:12px;padding:20px;text-align:center;margin-bottom:24px;'>
          <span style='font-size:42px;font-weight:800;color:#D4841A;letter-spacing:10px;'>$otp</span>
        </div>
        <p style='color:#8C7060;font-size:13px;'>Expires in <strong>5 minutes</strong>.</p>
        <p style='color:#8C7060;font-size:13px;'>If you did not request this, please ignore this email — your access remains secure.</p>
      </div>
    </div>";

    mindcareSendMail($mail);
    ob_end_clean();
    $res = ['success' => true, 'message' => 'OTP sent to ' . $email];
    if (empty(SMTP_PASS) || SMTP_PASS === 'YOUR_GMAIL_APP_PASSWORD_HERE') {
        $res['dev_otp'] = $otp;
    }
    echo json_encode($res);
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['error' => 'Could not send email: ' . $e->getMessage()]);
}
?>