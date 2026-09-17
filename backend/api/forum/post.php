<?php
require_once '../../config/db.php';
require_once '../../middleware/auth_check.php';
header('Content-Type: application/json');
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
    echo json_encode(['error'=>'POST required']); exit; 
}

$uid      = $_SESSION['user_id'];
$content  = trim($_POST['content']  ?? '');
$category = trim($_POST['category'] ?? 'General');

if (!$content) { echo json_encode(['error'=>'Content cannot be empty']); exit; }

$chk = $pdo->prepare("SELECT anon_key FROM forum_posts WHERE user_id=? LIMIT 1");
$chk->execute([$uid]);
$row     = $chk->fetch();
$anonKey = $row ? $row['anon_key'] : 'User#' . rand(1000,9999);

$stmt = $pdo->prepare("INSERT INTO forum_posts (user_id,anon_key,content,category) VALUES (?,?,?,?)");
$stmt->execute([$uid, $anonKey, $content, $category]);
echo json_encode(['success'=>true]);
?>