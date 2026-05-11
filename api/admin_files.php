<?php
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
    <style>
        :root {
            color-scheme: light;
            --bg: #f5f1e8;
            --panel: #ffffff;
            --text: #1e1e1e;
            --muted: #666;
            --accent: #2f5d50;
            --accent-2: #d08c3f;
            --border: #ddd4c3;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: linear-gradient(135deg, #f3ede1 0%, #f8f6f1 50%, #efe6d6 100%);
            color: var(--text);
        }
        .page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 20px 48px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: end;
            gap: 20px;
            margin-bottom: 24px;
        }
        .header h1 {
            margin: 0;
            font-size: 32px;
            letter-spacing: 0.5px;
        }
        .header p {
            margin: 6px 0 0;
            color: var(--muted);
        }
        .card {
            background: rgba(255,255,255,0.92);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 18px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.06);
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
            font-size: 22px;
        }
        .registration-head .meta {
            color: var(--muted);
            font-size: 14px;
            margin-top: 4px;
        }
        .badge {
            display: inline-block;
            background: #eef5f1;
            color: var(--accent);
            border: 1px solid #d8e8df;
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            overflow: hidden;
            border-radius: 12px;
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
            color: var(--accent);
            text-decoration: none;
            font-weight: 700;
        }
        .file-link:hover { text-decoration: underline; }
        .empty {
            background: rgba(255,255,255,0.8);
            border: 1px dashed var(--border);
            padding: 20px;
            border-radius: 14px;
            color: var(--muted);
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }
        .topbar a {
            color: var(--accent-2);
            text-decoration: none;
            font-weight: 700;
        }
        .topbar a:hover { text-decoration: underline; }
        @media (max-width: 700px) {
            .header, .registration-head, .topbar { flex-direction: column; align-items: start; }
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
    <div class="page">
        <div class="topbar">
            <div>
                <div class="badge">Admin</div>
                <div style="margin-top:10px; font-size:14px; color:#666;">Browse uploaded form files by vendor registration.</div>
            </div>
            <div style="display: flex; gap: 12px;">
                <a href="export_csv.php" style="background: var(--accent); color: white; padding: 8px 16px; border-radius: 6px; font-weight: 700; text-decoration: none;">Export CSV</a>
                <a href="../index.html">Back to site</a>
            </div>
        </div>

        <div class="header">
            <div>
                <h1>Submitted Files</h1>
                <p>Each file is stored in the database and linked to the registration record.</p>
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