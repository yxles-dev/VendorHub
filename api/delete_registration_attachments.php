<?php
require_once __DIR__ . '/admin_auth.php';
admin_require_login();
require_once __DIR__ . '/connect_db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_files.php');
    exit;
}

$registrationId = (int)($_POST['registration_id'] ?? 0);
if ($registrationId <= 0) {
    header('Location: admin_files.php');
    exit;
}

try {
    $stmt = $pdo->prepare('DELETE FROM attachments WHERE registration_id = :rid');
    $stmt->execute([':rid' => $registrationId]);
} catch (Throwable $e) {
    // ignore
}

header('Location: admin_files.php');
exit;
