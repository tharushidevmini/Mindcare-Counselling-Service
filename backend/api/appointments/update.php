<?php
// FILE: backend/api/appointments/update.php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireLogin();

$id      = (int)($_POST['appointment_id'] ?? 0);
$status  = trim($_POST['status']          ?? '');
$reason  = trim($_POST['reason']          ?? '');
$newDate = trim($_POST['new_date']        ?? '');
$newTime = trim($_POST['new_time']        ?? '');

if (!$id || !in_array($status, ['accepted','postponed','completed','cancelled'])) {
    echo json_encode(['error'=>'Invalid data']); exit;
}

// Postponing without saying WHEN the session will actually happen isn't
// useful to the student/guardian waiting on it — require both fields.
if ($status === 'postponed' && (!$newDate || !$newTime)) {
    echo json_encode(['error' => 'Please give a new date and time for the postponed session.']); exit;
}

// Load the appointment first so we can check ownership — also pull the
// student's and (if any) guardian's email/name here so we don't need a
// second lookup later just to send the notification email.
$stmt = $pdo->prepare("
    SELECT a.*, u.full_name AS student_name, u.email AS student_email,
           gs.guardian_phone AS guardian_email
    FROM appointments a
    JOIN users u ON a.student_id = u.id
    LEFT JOIN guardian_sessions gs ON a.guardian_id = gs.id
    WHERE a.id = ?
");
$stmt->execute([$id]);
$apt = $stmt->fetch();
if (!$apt) { echo json_encode(['error' => 'Appointment not found']); exit; }

$uid  = $_SESSION['user_id'];
$role = $_SESSION['role'];

$isOwnerStudent   = ($role === 'student')   && ((int)$apt['student_id']   === (int)$uid);
$isOwnerCounselor = ($role === 'counselor') && ((int)$apt['counselor_id'] === (int)$uid);
$isAdmin          = ($role === 'admin');

// Students may only cancel their own appointment — EXCEPT for a
// counselor-assigned session that's still pending, where the student is
// the one who needs to confirm it (the counselor proposed the slot; the
// student accepts it, mirroring the normal flow in reverse).
$studentMayAccept = $isOwnerStudent
    && $apt['booking_source'] === 'counselor_assigned'
    && $apt['status'] === 'pending'
    && $status === 'accepted';

if ($isOwnerStudent && $status !== 'cancelled' && !$studentMayAccept) {
    echo json_encode(['error' => 'Students may only cancel an appointment']); exit;
}

if (!$isOwnerStudent && !$isOwnerCounselor && !$isAdmin) {
    echo json_encode(['error' => 'Not authorized to update this appointment']); exit;
}

// new_date/new_time only mean anything for a postponed session — clear
// them for every other status so a stale proposed date never lingers.
$saveNewDate = ($status === 'postponed') ? $newDate : null;
$saveNewTime = ($status === 'postponed') ? $newTime : null;

$stmt = $pdo->prepare("UPDATE appointments SET status=?, reschedule_reason=?, new_date=?, new_time=? WHERE id=?");
$stmt->execute([$status, $reason, $saveNewDate, $saveNewTime, $id]);

// Note: guardian-booked emergency slots no longer consume/restore a
// blocked_slots row — their availability is now derived dynamically from
// this counselor's permanent guardian_daily_slots time plus whatever's in
// the appointments table for that date (see backend/api/guardian/emergency_slots.php),
// so cancelling one here already frees it up automatically with nothing
// further to do.

// Best-effort email to whoever booked the session (student, or the
// guardian if this was a guardian-booked emergency slot) when a counselor
// accepts or postpones. This NEVER blocks the status update itself —
// if the mail fails (network, SMTP, whatever), the appointment status
// change above has already been saved either way.
if (in_array($status, ['accepted', 'postponed']) && ($isOwnerCounselor || $isAdmin)) {
    $recipientEmail = $apt['guardian_id'] ? $apt['guardian_email'] : $apt['student_email'];
    $recipientLabel = $apt['guardian_id'] ? 'Guardian' : $apt['student_name'];

    if ($recipientEmail && file_exists(__DIR__ . '/../../vendor/autoload.php')) {
        try {
            require_once __DIR__ . '/../../helpers/mailer.php';

            if ($status === 'accepted') {
                $subject = 'MindCare — Your Session Has Been Accepted';
                $bodyMsg = "
                    <p style='color:#8C7060; font-size:14px; margin:0 0 20px;'>Your counseling session has been <strong style='color:#2D9B6A;'>accepted</strong>.</p>
                    <div style='background:#F0FBF4; border:2px solid #5CC48A; border-radius:12px; padding:18px 20px; margin-bottom:20px;'>
                        <p style='margin:0; color:#1C1410; font-size:15px;'><strong>Date:</strong> " . htmlspecialchars($apt['preferred_date'], ENT_QUOTES) . "</p>
                        <p style='margin:6px 0 0; color:#1C1410; font-size:15px;'><strong>Time:</strong> " . htmlspecialchars($apt['preferred_time'], ENT_QUOTES) . "</p>
                        <p style='margin:6px 0 0; color:#1C1410; font-size:15px;'><strong>Mode:</strong> " . htmlspecialchars($apt['session_type'], ENT_QUOTES) . "</p>
                    </div>";
            } else { // postponed
                $subject = 'MindCare — Your Session Has Been Postponed';
                $bodyMsg = "
                    <p style='color:#8C7060; font-size:14px; margin:0 0 20px;'>Your counselor needed to <strong style='color:#D4841A;'>postpone</strong> your session. Here's the new proposed time:</p>
                    <div style='background:#FEF3DF; border:2px solid #F5A846; border-radius:12px; padding:18px 20px; margin-bottom:20px;'>
                        <p style='margin:0; color:#1C1410; font-size:15px;'><strong>New Date:</strong> " . htmlspecialchars($newDate, ENT_QUOTES) . "</p>
                        <p style='margin:6px 0 0; color:#1C1410; font-size:15px;'><strong>New Time:</strong> " . htmlspecialchars($newTime, ENT_QUOTES) . "</p>
                    </div>" .
                    ($reason ? "<p style='color:#8C7060; font-size:13px;'><strong>Note from your counselor:</strong> " . htmlspecialchars($reason, ENT_QUOTES) . "</p>" : "");
            }

            $mail = mindcareNewMailer();
            $mail->addAddress($recipientEmail, $recipientLabel);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = "
                <div style='font-family:Arial,sans-serif;max-width:480px;margin:0 auto;'>
                  <div style='background:linear-gradient(135deg,#F5A846,#F5C846);padding:28px 32px;border-radius:16px 16px 0 0;'>
                    <h1 style='color:#1C1410;margin:0;font-size:22px;'>MindCare</h1>
                    <p style='color:#4A3728;margin:6px 0 0;font-size:13px;'>LNBTI Campus Mental Health Support</p>
                  </div>
                  <div style='background:#fff;padding:32px;border-radius:0 0 16px 16px;'>
                    {$bodyMsg}
                  </div>
                </div>";

            mindcareSendMail($mail);
        } catch (\Throwable $e) {
            // Swallow silently — the appointment status change already
            // succeeded above, so a mail failure shouldn't surface as an
            // error to the counselor. It's logged for troubleshooting.
            error_log('MindCare: appointment notification email failed — ' . $e->getMessage());
        }
    }
}

echo json_encode(['success'=>true]);
?>