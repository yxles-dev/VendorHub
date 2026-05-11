<?php
require_once __DIR__ . '/admin_auth.php';
admin_require_login();
require_once __DIR__ . '/connect_db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_files.php');
    exit;
}

try {
    // delete order matters because of possible foreign keys
    $pdo->beginTransaction();

    $pdo->exec('DELETE FROM attachments');
    $pdo->exec('DELETE FROM health_declarations');
    $pdo->exec('DELETE FROM businesses');
    $pdo->exec('DELETE FROM registrations');

    // reset auto increment (MySQL)
    $pdo->exec('ALTER TABLE attachments AUTO_INCREMENT = 1');
    $pdo->exec('ALTER TABLE health_declarations AUTO_INCREMENT = 1');
    $pdo->exec('ALTER TABLE businesses AUTO_INCREMENT = 1');
    $pdo->exec('ALTER TABLE registrations AUTO_INCREMENT = 1');

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
}

header('Location: admin_files.php');
exit;
