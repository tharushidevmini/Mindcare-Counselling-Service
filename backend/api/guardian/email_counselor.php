<?php
// FILE: backend/api/guardian/email_counselor.php
// Sent from the guardian emergency page when a counselor has NO free reserved
// slot. Emails that counselor directly with the student + reason.
ob_start();
require_once '../../config/db.php';
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['guardian_verified']) || !$_SESSION['guardian_verified']) {
    http_response_code(403);
    echo json_encode(['error' => 'Guardian session not verified']); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST required']); exit; }

$counselorId = (int)($_POST['counselor_id'] ?? 0);
$reason      = trim($_POST['reason'] ?? '');
if (!$counselorId) { echo json_encode(['error'=>'Please select a counselor']); exit; }
if (!$reason)      { echo json_encode(['error'=>'Please describe the emergency briefly']); exit; }

// Counselor must be real + active, and we need their email
$c = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ? AND role='counselor' AND is_active=1");
$c->execute([$counselorId]);
$counselor = $c->fetch();
if (!$counselor) { echo json_encode(['error'=>'Counselor not found']); exit; }

// Student the guardian is booking for
$studentId = $_SESSION['guardian_student_id'] ?? 0;
$s = $pdo->prepare("SELECT full_name, student_id FROM users WHERE id = ?");
$s->execute([$studentId]);
$student = $s->fetch();
$studentLine = $student ? ($student['full_name'] . ' (' . ($student['student_id'] ?: '—') . ')') : 'Unknown student';

require_once __DIR__ . '/../../helpers/mailer.php';

$safeReason  = htmlspecialchars($reason, ENT_QUOTES);
$safeStudent = htmlspecialchars($studentLine, ENT_QUOTES);

try {
    $mail = mindcareNewMailer();
    $mail->addAddress($counselor['email'], $counselor['full_name']);
    $mail->isHTML(true);
    $mail->Subject = 'URGENT — Guardian Emergency Request (no slots free)';
    $mail->Body = "
    <div style='font-family:Arial,sans-serif;max-width:480px;margin:0 auto;'>
      <div style='background:linear-gradient(135deg,#F5A846,#F5C846);padding:28px 32px;border-radius:16px 16px 0 0;'>
        <h1 style='color:#1C1410;margin:0;font-size:22px;'>MindCare</h1>
        <p style='color:#4A3728;margin:6px 0 0;font-size:13px;'>Guardian Emergency Request</p>
      </div>
      <div style='background:#fff;padding:32px;border-radius:0 0 16px 16px;'>
        <p style='color:#8C7060;font-size:14px;margin:0 0 18px;'>A guardian tried to book an emergency session but <strong>no reserved slots were free</strong>. Please reach out as soon as you can.</p>
        <p style='color:#1C1410;font-size:14px;margin:0 0 8px;'><strong>Student:</strong> $safeStudent</p>
        <div style='background:#FEF3DF;border:2px solid #F5A846;border-radius:12px;padding:16px;margin-top:12px;'>
          <p style='color:#8C7060;font-size:12px;margin:0 0 6px;text-transform:uppercase;'>Reason given</p>
          <p style='color:#1C1410;font-size:14px;margin:0;line-height:1.6;'>$safeReason</p>
        </div>
        <p style='color:#8C7060;font-size:12px;margin-top:20px;'>Sent automatically from the Guardian Emergency Booking page.</p>
      </div>
    </div>";

    mindcareSendMail($mail);
    $log = $pdo->prepare("INSERT INTO system_logs (action, ip_address) VALUES (?,?)");
    $log->execute(['guardian_emergency_email counselor:' . $counselorId, $_SERVER['REMOTE_ADDR'] ?? '']);
    ob_end_clean();
    echo json_encode(['success' => true, 'message' => 'Urgent email sent to ' . $counselor['full_name']]);
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['error' => 'Could not send email: ' . $e->getMessage()]);
}
?>