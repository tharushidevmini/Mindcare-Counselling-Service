<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function requireLogin() {
    global $pdo;

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Not logged in', 'redirect' => '/mindcare_final/frontend/pages/login.html']);
        exit;
    }

    // Re-check the database on every request — if an admin deletes or
    // disables this account, or changes its role, the change takes effect
    // immediately, even if the browser still has an old session cookie.
    $stmt = $pdo->prepare("SELECT role, is_active, full_name, photo_url FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || !$user['is_active']) {
        session_unset();
        session_destroy();
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Your account is no longer active. Please contact admin.', 'redirect' => '/mindcare_final/frontend/pages/login.html']);
        exit;
    }

    // Keep the session in sync with the database (role/name/photo may have changed)
    $_SESSION['role']      = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['photo_url'] = $user['photo_url'];
}

function requireRole(array $roles) {
    requireLogin();
    if (!in_array($_SESSION['role'], $roles)) {
        $map = [
            'student'          => '/mindcare_final/frontend/pages/student/dashboard.html',
            'counselor'        => '/mindcare_final/frontend/pages/counselor/dashboard.html',
            'learning_advisor' => '/mindcare_final/frontend/pages/advisor/dashboard.html',
            'admin'            => '/mindcare_final/frontend/pages/admin/dashboard.html',
        ];
        $role = $_SESSION['role'] ?? 'student';
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Not authorized for this page', 'redirect' => ($map[$role] ?? '/mindcare_final/frontend/pages/login.html')]);
        exit;
    }
}
?>