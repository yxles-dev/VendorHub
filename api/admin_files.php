<?php
require_once __DIR__ . '/admin_auth.php';
if (isset($_GET['logout'])) {
    admin_logout();
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

admin_require_login();

require_once __DIR__ . '/connect_db.php';

$stmt = $pdo->query('
    SELECT
        r.id AS registration_id,
        r.fullname,
        a.id AS attachment_id,
        a.field_name,
        a.filename,
        a.mime,
        a.created_at
    FROM attachments a
    INNER JOIN registrations r ON r.id = a.registration_id
    ORDER BY r.id DESC, a.created_at DESC, a.id DESC
');

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$registrations = [];

foreach ($rows as $row) {
    $registrationId = (int)$row['registration_id'];

    if (!isset($registrations[$registrationId])) {
        $registrations[$registrationId] = [
            'registration_id' => $registrationId,
            'fullname' => $row['fullname'],
            'attachments' => []
        ];
    }

    $registrations[$registrationId]['attachments'][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VendorHub Admin Files</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #f6f2e8 0%, #f8f6f1 42%, #efe6d6 100%);
            color: #1e1e1e;
        }
        .page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 20px 48px;
        }
        .hero-strip {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            padding: 18px 20px;
            border-radius: 24px;
            background: rgba(255, 250, 242, 0.92);
            border: 1px solid rgba(208, 194, 167, 0.55);
            box-shadow: 0 18px 40px rgba(52, 44, 26, 0.08);
            margin-bottom: 24px;
        }
        .hero-copy {
            display: grid;
            gap: 4px;
        }
        .hero-title {
            margin: 0;
            font-family: var(--font-display);
            font-size: 36px;
            letter-spacing: 0.04em;
        }
        .hero-text {
            margin: 0;
            color: rgba(0, 0, 0, 0.68);
            font-family: var(--font-sans);
        }
        .hero-actions {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        .action-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            border-radius: 999px;
            padding: 10px 16px;
            font-family: var(--font-sans);
            font-weight: 700;
            transition: transform 0.2s ease, background-color 0.2s ease;
        }
        .action-link:hover { transform: translateY(-1px); }
        .action-primary {
            background: #a4b87e;
            color: #fff;
            box-shadow: 0 12px 24px rgba(164, 184, 126, 0.24);
        }
        .action-secondary {
            background: #f3ece0;
            color: #2f5d50;
            border: 1px solid #decfb6;
        }
        .card {
            background: rgba(255, 250, 242, 0.94);
            border: 1px solid rgba(208, 194, 167, 0.55);
            border-radius: 24px;
            padding: 20px;
            box-shadow: 0 18px 36px rgba(52, 44, 26, 0.07);
            margin-bottom: 18px;
        }
        .registration-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: start;
            margin-bottom: 14px;
        }
        .registration-head h2 {
            margin: 0;
            font-family: var(--font-display);
            font-size: 24px;
            letter-spacing: 0.03em;
        }
        .registration-head .meta {
            color: rgba(0, 0, 0, 0.58);
            font-size: 14px;
            margin-top: 4px;
            font-family: var(--font-sans);
        }
        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #edf4e5;
            color: #36553d;
            border: 1px solid #d8e8df;
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
        .top-header .badge {
            margin-left: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            overflow: hidden;
            border-radius: 16px;
        }
        th, td {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid #eee5d7;
            vertical-align: top;
        }
        th {
            background: #faf7f0;
            color: #4a4a4a;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        tr:last-child td { border-bottom: none; }
        .file-link {
            color: #2f5d50;
            text-decoration: none;
            font-weight: 700;
        }
        .file-link:hover { text-decoration: underline; }
        .empty {
            background: rgba(255, 250, 242, 0.88);
            border: 1px dashed #d9ccb5;
            padding: 20px;
            border-radius: 16px;
            color: rgba(0, 0, 0, 0.58);
            font-family: var(--font-sans);
        }
        @media (max-width: 820px) {
            .hero-strip, .registration-head { flex-direction: column; align-items: start; }
            .hero-title { font-size: 28px; }
            .page { padding: 20px 14px 36px; }
            table, thead, tbody, th, td, tr { display: block; }
            thead { display: none; }
            tr { border: 1px solid #eee5d7; border-radius: 12px; margin-bottom: 12px; overflow: hidden; }
            td { border-bottom: 1px solid #f2eadb; }
            td::before {
                content: attr(data-label) ": ";
                font-weight: 700;
                color: #555;
            }
            td:last-child { border-bottom: none; }
        }
    </style>
</head>
<body>
    <div class="top-header">
        <img src="../assets/logo.svg" alt="VendorHub logo" class="logo-placeholder">
        <h2>VendorHub</h2>
        <div class="badge">Admin Dashboard</div>
    </div>
    <div class="page">
        <div class="hero-strip">
            <div class="hero-copy">
                <h1 class="hero-title">Submitted Files</h1>
                <p class="hero-text">List of all submitted files on registration:</p>
            </div>
            <div class="hero-actions">
                <a class="action-link action-primary" href="export_csv.php">Export CSV</a>
                <a class="action-link action-secondary" href="?logout=1">Log Out</a>
                <a class="action-link action-secondary" href="../index.html">Back to site</a>
            </div>
        </div>

        <?php if (empty($registrations)): ?>
            <div class="empty">No uploaded files found yet.</div>
        <?php else: ?>
            <?php foreach ($registrations as $registration): ?>
                <div class="card">
                    <div class="registration-head">
                        <div>
                            <h2><?php echo htmlspecialchars($registration['fullname']); ?></h2>
                            <div class="meta">Registration ID: <?php echo (int)$registration['registration_id']; ?></div>
                        </div>
                        <div class="badge"><?php echo count($registration['attachments']); ?> file(s)</div>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>File Type</th>
                                <th>Filename</th>
                                <th>MIME</th>
                                <th>Uploaded</th>
                                <th>Download</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registration['attachments'] as $attachment): ?>
                                <tr>
                                    <td data-label="File Type"><?php echo htmlspecialchars($attachment['field_name']); ?></td>
                                    <td data-label="Filename"><?php echo htmlspecialchars($attachment['filename']); ?></td>
                                    <td data-label="MIME"><?php echo htmlspecialchars($attachment['mime']); ?></td>
                                    <td data-label="Uploaded"><?php echo htmlspecialchars($attachment['created_at']); ?></td>
                                    <td data-label="Download">
                                        <a class="file-link" href="download_attachment.php?id=<?php echo (int)$attachment['attachment_id']; ?>">Download</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>