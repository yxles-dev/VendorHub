<?php
require_once __DIR__ . '/admin_auth.php';
admin_require_login();
require_once __DIR__ . '/connect_db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_files.php');
    exit;
}

$attachmentId = (int)($_POST['attachment_id'] ?? 0);
if ($attachmentId <= 0) {
    header('Location: admin_files.php');
    exit;
}

try {
    $stmt = $pdo->prepare('DELETE FROM attachments WHERE id = :id');
    $stmt->execute([':id' => $attachmentId]);
} catch (Throwable $e) {
    // ignore and continue
}

header('Location: admin_files.php');
exit;
