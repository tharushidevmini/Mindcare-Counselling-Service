<?php
// FILE: backend/api/appointments/assign_emergency.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireRole(['counselor', 'admin']);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// List all active students for the "Assign Student" dropdown
if ($action === 'students') {
    $stmt = $pdo->query("SELECT id, full_name, student_id FROM users WHERE role = 'student' AND is_active = 1 ORDER BY full_name");
    echo json_encode(['students' => $stmt->fetchAll()]);
    exit;
}

if ($action === 'assign') {
    $cid       = $_SESSION['user_id'];
    $blockId   = (int)($_POST['block_id']   ?? 0);
    $studentId = (int)($_POST['student_id'] ?? 0);
    $mode      = trim($_POST['session_type'] ?? 'physical');
    $notes     = trim($_POST['notes'] ?? 'Emergency walk-in — assigned by counselor.');

    if (!$blockId || !$studentId) { echo json_encode(['error' => 'Missing data']); exit; }

    // Confirm the reserved slot belongs to this counselor
    $stmt = $pdo->prepare("SELECT * FROM blocked_slots WHERE id = ? AND counselor_id = ?");
    $stmt->execute([$blockId, $cid]);
    $block = $stmt->fetch();
    if (!$block) { echo json_encode(['error' => 'Reserved slot not found']); exit; }

    // Create the appointment as PENDING, not auto-accepted — the counselor
    // is proposing this slot to the student, but the student still needs
    // to confirm they can actually make it (same courtesy as the normal
    // booking flow, just initiated from the other side). booking_source
    // marks it as counselor-assigned so the UI can label it clearly and
    // route the confirmation step to the student instead of the counselor.
    $stmt = $pdo->prepare("INSERT INTO appointments (student_id, counselor_id, session_type, preferred_date, preferred_time, notes, status, booking_source)
                            VALUES (?,?,?,?,?,?, 'pending', 'counselor_assigned')");
    $stmt->execute([$studentId, $cid, $mode, $block['block_date'], $block['block_time'], $notes]);
    $appointmentId = $pdo->lastInsertId();

    // Remove the reservation — the real booking now takes its place and will show as "Booked", not "Reserved"
    $del = $pdo->prepare("DELETE FROM blocked_slots WHERE id = ?");
    $del->execute([$blockId]);

    // Best-effort email letting the student know a session was set up for
    // them and needs their confirmation. Never blocks the assignment itself.
    if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
        try {
            $s = $pdo->prepare("SELECT email, full_name FROM users WHERE id = ?");
            $s->execute([$studentId]);
            $student = $s->fetch();

            if ($student && $student['email']) {
                require_once __DIR__ . '/../../helpers/mailer.php';

                $mail = mindcareNewMailer();
                $mail->addAddress($student['email'], $student['full_name']);
                $mail->isHTML(true);
                $mail->Subject = 'MindCare — A Session Was Set Up For You, Please Confirm';
                $mail->Body = "
                    <div style='font-family:Arial,sans-serif;max-width:480px;margin:0 auto;'>
                      <div style='background:linear-gradient(135deg,#F5A846,#F5C846);padding:28px 32px;border-radius:16px 16px 0 0;'>
                        <h1 style='color:#1C1410;margin:0;font-size:22px;'>MindCare</h1>
                        <p style='color:#4A3728;margin:6px 0 0;font-size:13px;'>LNBTI Campus Mental Health Support</p>
                      </div>
                      <div style='background:#fff;padding:32px;border-radius:0 0 16px 16px;'>
                        <p style='color:#8C7060; font-size:14px; margin:0 0 20px;'>Your counselor set up a counseling session for you. Please confirm you can make it by logging into MindCare and pressing <strong>Accept</strong>.</p>
                        <div style='background:#FEF3DF; border:2px solid #F5A846; border-radius:12px; padding:18px 20px; margin-bottom:20px;'>
                            <p style='margin:0; color:#1C1410; font-size:15px;'><strong>Date:</strong> " . htmlspecialchars($block['block_date'], ENT_QUOTES) . "</p>
                            <p style='margin:6px 0 0; color:#1C1410; font-size:15px;'><strong>Time:</strong> " . htmlspecialchars($block['block_time'], ENT_QUOTES) . "</p>
                            <p style='margin:6px 0 0; color:#1C1410; font-size:15px;'><strong>Mode:</strong> " . htmlspecialchars($mode, ENT_QUOTES) . "</p>
                        </div>
                      </div>
                    </div>";
                mindcareSendMail($mail);
            }
        } catch (\Throwable $e) {
            error_log('MindCare: counselor-assigned session email failed — ' . $e->getMessage());
        }
    }

    echo json_encode(['success' => true, 'appointment_id' => $appointmentId]);
    exit;
}

echo json_encode(['error' => 'Unknown action']);
?>