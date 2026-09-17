<?php
// FILE: backend/api/advisor/email_counselor.php
// Lets the learning advisor send a direct email to a counselor from the
// Academic Accommodations dashboard (e.g. to flag an urgent request).
// CSP MODULE — the advisor never sees the counselor's email address here;
// it is looked up and used server-side only.
ob_start();
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['learning_advisor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); exit; }

$counselorId = (int)($_POST['counselor_id'] ?? 0);
$subject     = trim($_POST['subject'] ?? '');
$message     = trim($_POST['message'] ?? '');
$advisorId   = $_SESSION['user_id'];

if (!$counselorId) { echo json_encode(['error' => 'Please select a counselor']); exit; }
if (!$subject)     { echo json_encode(['error' => 'Please add a subject']); exit; }
if (!$message)     { echo json_encode(['error' => 'Please write a message']); exit; }

// Counselor must be real + active, and we need their email
$c = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ? AND role = 'counselor' AND is_active = 1");
$c->execute([$counselorId]);
$counselor = $c->fetch();
if (!$counselor) { echo json_encode(['error' => 'Counselor not found']); exit; }

// Advisor's own name + email, for the "From" line and reply-to
$a = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
$a->execute([$advisorId]);
$advisor = $a->fetch();
$advisorName  = $advisor ? $advisor['full_name'] : 'Learning Advisor';
$advisorEmail = $advisor ? $advisor['email'] : 'projectmindcare7@gmail.com';

require_once __DIR__ . '/../../helpers/mailer.php';
use PHPMailer\PHPMailer\Exception;

$safeSubject = htmlspecialchars($subject, ENT_QUOTES);
$safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES));
$safeAdvisor = htmlspecialchars($advisorName, ENT_QUOTES);

$mail = mindcareNewMailer();
try {
    $mail->addAddress($counselor['email'], $counselor['full_name']);
    $mail->isHTML(true);
    $mail->Subject = 'MindCare — Message from Learning Advisor: ' . $subject;
    $mail->Body = "
    <div style='font-family:Arial,sans-serif;max-width:480px;margin:0 auto;'>
      <div style='background:linear-gradient(135deg,#F5A846,#F5C846);padding:28px 32px;border-radius:16px 16px 0 0;'>
        <h1 style='color:#1C1410;margin:0;font-size:22px;'>MindCare</h1>
        <p style='color:#4A3728;margin:6px 0 0;font-size:13px;'>Message from Learning Advisor</p>
      </div>
      <div style='background:#fff;padding:32px;border-radius:0 0 16px 16px;'>
        <p style='color:#1C1410;font-size:14px;margin:0 0 4px;'><strong>From:</strong> $safeAdvisor (Learning Advisor)</p>
        <p style='color:#1C1410;font-size:14px;margin:0 0 18px;'><strong>Subject:</strong> $safeSubject</p>
        <div style='background:#FEF3DF;border:2px solid #F5A846;border-radius:12px;padding:16px;'>
          <p style='color:#1C1410;font-size:14px;margin:0;line-height:1.7;'>$safeMessage</p>
        </div>
        <p style='color:#8C7060;font-size:12px;margin-top:20px;'>Sent from the Learning Advisor dashboard. Reply directly to this email to respond.</p>
      </div>
    </div>";
    $mail->addReplyTo($advisorEmail, $advisorName);

    mindcareSendMail($mail);
    $log = $pdo->prepare("INSERT INTO system_logs (action, ip_address) VALUES (?,?)");
    $log->execute(["advisor_email_counselor advisor:$advisorId counselor:$counselorId", $_SERVER['REMOTE_ADDR'] ?? '']);
    $hist = $pdo->prepare("INSERT INTO advisor_email_log (advisor_id, counselor_id, subject, message) VALUES (?,?,?,?)");
    $hist->execute([$advisorId, $counselorId, $subject, $message]);
    ob_end_clean();
    echo json_encode(['success' => true, 'message' => 'Email sent to ' . $counselor['full_name']]);
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['error' => 'Could not send email: ' . $mail->ErrorInfo]);
}
?>