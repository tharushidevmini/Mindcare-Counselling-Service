<?php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');

// Only counselors and admins may manage relaxation content
requireRole(['counselor', 'admin']);

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$table  = $_POST['table']  ?? $_GET['table']  ?? '';

$allowed_tables = ['relaxation_exercises', 'music_tracks', 'meditation_types'];
if (!in_array($table, $allowed_tables)) {
    echo json_encode(['error' => 'Invalid table']); exit;
}

$uid = $_SESSION['user_id'] ?? null;

// Build a 3 or 4-phase breathing cycle JSON from simple second counts.
function buildPhases($inhale, $hold1, $exhale, $hold2) {
    $phases = [];
    if ($inhale > 0) $phases[] = ['text' => 'Breathe In',  'voice' => 'Breathe in slowly through your nose', 'dur' => $inhale, 'scale' => '1.35', 'bg' => '#98E3B4'];
    if ($hold1  > 0) $phases[] = ['text' => 'Hold',        'voice' => 'Hold gently',                          'dur' => $hold1,  'scale' => '1.35', 'bg' => '#E9DBC4'];
    if ($exhale > 0) $phases[] = ['text' => 'Breathe Out', 'voice' => 'Exhale completely through your mouth', 'dur' => $exhale, 'scale' => '1.0',  'bg' => '#d0e8f5'];
    if ($hold2  > 0) $phases[] = ['text' => 'Hold',        'voice' => 'Hold again',                           'dur' => $hold2,  'scale' => '1.0',  'bg' => '#f5e0d0'];
    return json_encode($phases);
}

// Turn a textarea (one item per line) into a JSON array of strings.
function linesToJsonArray($raw) {
    $lines = array_filter(array_map('trim', explode("\n", $raw)), fn($l) => $l !== '');
    return json_encode(array_values($lines));
}

switch ($action) {

    case 'list':
        $stmt = $pdo->query("SELECT * FROM {$table} ORDER BY is_active DESC, id DESC");
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            if (isset($r['phases']))  $r['phases']  = json_decode($r['phases'], true);
            if (isset($r['steps']))   $r['steps']   = json_decode($r['steps'], true);
            if (isset($r['guidance'])) $r['guidance'] = json_decode($r['guidance'], true);
        }
        echo json_encode(['data' => $rows]);
        break;

    case 'delete':
        $id   = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE {$table} SET is_active=0 WHERE id=?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    case 'restore':
        $id   = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE {$table} SET is_active=1 WHERE id=?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    // ── MUSIC ─────────────────────────────────────
    case 'add_music':
        $title    = trim($_POST['title']    ?? '');
        $category = trim($_POST['category'] ?? 'calm');
        $cover    = trim($_POST['cover_url'] ?? '');
        $audio    = trim($_POST['audio_url'] ?? '');

        if (!$title || !$audio) { echo json_encode(['error' => 'Title and audio URL required']); exit; }

        $stmt = $pdo->prepare("INSERT INTO music_tracks (title, category, cover_url, audio_url, created_by) VALUES (?,?,?,?,?)");
        $stmt->execute([$title, $category, $cover, $audio, $uid]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        break;

    case 'update_music':
        $id       = (int)($_POST['id'] ?? 0);
        $title    = trim($_POST['title']    ?? '');
        $category = trim($_POST['category'] ?? 'calm');
        $cover    = trim($_POST['cover_url'] ?? '');
        $audio    = trim($_POST['audio_url'] ?? '');

        if (!$id || !$title || !$audio) { echo json_encode(['error' => 'Title and audio URL required']); exit; }

        $stmt = $pdo->prepare("UPDATE music_tracks SET title=?, category=?, cover_url=?, audio_url=? WHERE id=?");
        $stmt->execute([$title, $category, $cover, $audio, $id]);
        echo json_encode(['success' => true]);
        break;

    // ── BREATHING EXERCISES ─────────────────────────
    case 'add_exercise':
        $name   = trim($_POST['name'] ?? '');
        $desc   = trim($_POST['description'] ?? '');
        $icon   = trim($_POST['icon'] ?? '🌬');
        $inhale = (int)($_POST['inhale_sec'] ?? 4);
        $hold1  = (int)($_POST['hold_sec'] ?? 0);
        $exhale = (int)($_POST['exhale_sec'] ?? 6);
        $hold2  = (int)($_POST['hold2_sec'] ?? 0);

        if (!$name) { echo json_encode(['error' => 'Name required']); exit; }
        if ($inhale < 1 || $exhale < 1) { echo json_encode(['error' => 'Inhale and exhale seconds are required']); exit; }

        $phases = buildPhases($inhale, $hold1, $exhale, $hold2);
        $stmt = $pdo->prepare("INSERT INTO relaxation_exercises (type, name, description, icon, phases, created_by) VALUES ('breathing',?,?,?,?,?)");
        $stmt->execute([$name, $desc, $icon, $phases, $uid]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        break;

    case 'update_exercise':
        $id     = (int)($_POST['id'] ?? 0);
        $name   = trim($_POST['name'] ?? '');
        $desc   = trim($_POST['description'] ?? '');
        $icon   = trim($_POST['icon'] ?? '🌬');
        $inhale = (int)($_POST['inhale_sec'] ?? 4);
        $hold1  = (int)($_POST['hold_sec'] ?? 0);
        $exhale = (int)($_POST['exhale_sec'] ?? 6);
        $hold2  = (int)($_POST['hold2_sec'] ?? 0);

        if (!$id || !$name || $inhale < 1 || $exhale < 1) { echo json_encode(['error' => 'Name, inhale and exhale seconds are required']); exit; }

        $phases = buildPhases($inhale, $hold1, $exhale, $hold2);
        $stmt = $pdo->prepare("UPDATE relaxation_exercises SET name=?, description=?, icon=?, phases=? WHERE id=? AND type='breathing'");
        $stmt->execute([$name, $desc, $icon, $phases, $id]);
        echo json_encode(['success' => true]);
        break;

    // ── MEDITATION TYPES ─────────────────────────────
    case 'add_meditation':
        $name   = trim($_POST['name'] ?? '');
        $icon   = trim($_POST['icon'] ?? '🧘');
        $desc   = trim($_POST['description'] ?? '');
        $steps  = linesToJsonArray($_POST['steps_raw'] ?? '');
        $sound  = trim($_POST['sound_url'] ?? '');
        $color  = trim($_POST['stroke_color'] ?? '#2D9B6A');

        if (!$name) { echo json_encode(['error' => 'Name required']); exit; }

        $stmt = $pdo->prepare("INSERT INTO meditation_types (name, icon, description, steps, guidance, sound_url, stroke_color, created_by) VALUES (?,?,?,?,'[]',?,?,?)");
        $stmt->execute([$name, $icon, $desc, $steps, $sound, $color, $uid]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        break;

    case 'update_meditation':
        $id     = (int)($_POST['id'] ?? 0);
        $name   = trim($_POST['name'] ?? '');
        $icon   = trim($_POST['icon'] ?? '🧘');
        $desc   = trim($_POST['description'] ?? '');
        $steps  = linesToJsonArray($_POST['steps_raw'] ?? '');
        $sound  = trim($_POST['sound_url'] ?? '');
        $color  = trim($_POST['stroke_color'] ?? '#2D9B6A');

        if (!$id || !$name) { echo json_encode(['error' => 'Name required']); exit; }

        $stmt = $pdo->prepare("UPDATE meditation_types SET name=?, icon=?, description=?, steps=?, sound_url=?, stroke_color=? WHERE id=?");
        $stmt->execute([$name, $icon, $desc, $steps, $sound, $color, $id]);
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
?>