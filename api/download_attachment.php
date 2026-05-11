<?php
require_once __DIR__ . '/connect_db.php';

$attachment_id = (int)($_GET['id'] ?? 0);
if ($attachment_id <= 0) {
    http_response_code(400);
    echo 'Invalid attachment id';
    exit;
}

$stmt = $pdo->prepare('
    SELECT a.filename, a.mime, a.content, r.id AS registration_id, r.fullname
    FROM attachments a
    INNER JOIN registrations r ON r.id = a.registration_id
    WHERE a.id = :id
    LIMIT 1
');
$stmt->execute([':id' => $attachment_id]);
$attachment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$attachment) {
    http_response_code(404);
    echo 'File not found';
    exit;
}

$filename = $attachment['filename'] ?: 'download.bin';
$mime = $attachment['mime'] ?: 'application/octet-stream';
$content = $attachment['content'];

header('Content-Type: ' . $mime);
header('Content-Length: ' . strlen($content));
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
header('X-Content-Type-Options: nosniff');

echo $content;