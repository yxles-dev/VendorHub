<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/connect_db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'register':
        handleRegister();
        break;
    case 'health_declaration':
        handleHealthDeclaration();
        break;
    case 'requirements':
        handleRequirements();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid or missing action parameter']);
}

function handleRegister() {
    global $pdo;

    $fullname = trim($_POST['fullname'] ?? '');
    $sex = $_POST['sex'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $contact_number = trim($_POST['contact_number'] ?? '');
    $emergency_contact = trim($_POST['emergency_contact'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $business_name = trim($_POST['business_name'] ?? '');
    $category = trim($_POST['category'] ?? '');

    // validate the data submitted before uploading to database
    $errors = [];
    if ($fullname === '') $errors[] = 'Fullname is required';
    if (!in_array($sex, ['Male','Female'], true)) $errors[] = 'Invalid sex';
    if ($date_of_birth === '') $errors[] = 'Date of birth is required';
    if ($business_name === '') $errors[] = 'Business name is required';
    if ($category === '') $errors[] = 'Business category is required';

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    $registration_id = null;

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('INSERT INTO registrations
            (fullname, sex, date_of_birth, contact_number, emergency_contact, address)
            VALUES (:fullname, :sex, :dob, :contact, :emergency, :address)');
        $stmt->execute([
            ':fullname' => $fullname,
            ':sex' => $sex,
            ':dob' => $date_of_birth,
            ':contact' => $contact_number,
            ':emergency' => $emergency_contact,
            ':address' => $address
        ]);
        $registration_id = (int)$pdo->lastInsertId();

        $stmt2 = $pdo->prepare('INSERT INTO businesses
            (registration_id, business_name, category)
            VALUES (:rid, :bname, :cat)');
        $stmt2->execute([
            ':rid' => $registration_id,
            ':bname' => $business_name,
            ':cat' => $category
        ]);

        $pdo->commit();

        echo json_encode(['success' => true, 'registration_id' => $registration_id]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        // if an error occurred while inserting data from the database, remove the uploaded data
        if ($registration_id !== null) {
            try {
                $cleanup = $pdo->prepare('DELETE FROM registrations WHERE id = :id');
                $cleanup->execute([':id' => $registration_id]);
            } catch (Throwable $cleanupError) {
                
            }
        }

        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to save registration and business details.',
            'detail' => $e->getMessage()
        ]);
    }
}

function handleHealthDeclaration() {
    global $pdo;

    $registration_id = (int)($_POST['registration_id'] ?? 0);
    $q1 = $_POST['q1'] ?? '';
    $q2 = $_POST['q2'] ?? '';
    $q3 = $_POST['q3'] ?? '';
    $q4 = $_POST['q4'] ?? '';
    $q5 = $_POST['q5'] ?? '';
    $q6 = $_POST['q6'] ?? '';

    $agree_health_certificates = isset($_POST['agree_health_certificates']) ? (int)$_POST['agree_health_certificates'] : 0;
    $agree_clean_stall = isset($_POST['agree_clean_stall']) ? (int)$_POST['agree_clean_stall'] : 0;
    $agree_proper_waste_disposal = isset($_POST['agree_proper_waste_disposal']) ? (int)$_POST['agree_proper_waste_disposal'] : 0;
    $agree_noncompliance_termination = isset($_POST['agree_noncompliance_termination']) ? (int)$_POST['agree_noncompliance_termination'] : 0;
    $agree_products_fresh_legal = isset($_POST['agree_products_fresh_legal']) ? (int)$_POST['agree_products_fresh_legal'] : 0;
    $agree_fit_to_work = isset($_POST['agree_fit_to_work']) ? (int)$_POST['agree_fit_to_work'] : 0;

    $errors = [];
    $allowed_yes_no = ['Yes', 'No'];

    if ($registration_id <= 0) $errors[] = 'Missing or invalid registration ID';
    if (!in_array($q1, $allowed_yes_no, true)) $errors[] = 'Question 1 must be Yes or No';
    if (!in_array($q2, $allowed_yes_no, true)) $errors[] = 'Question 2 must be Yes or No';
    if (!in_array($q3, $allowed_yes_no, true)) $errors[] = 'Question 3 must be Yes or No';
    if (!in_array($q4, $allowed_yes_no, true)) $errors[] = 'Question 4 must be Yes or No';
    if (!in_array($q5, $allowed_yes_no, true)) $errors[] = 'Question 5 must be Yes or No';
    if (!in_array($q6, $allowed_yes_no, true)) $errors[] = 'Question 6 must be Yes or No';

    $agreement_values = [
        $agree_health_certificates,
        $agree_clean_stall,
        $agree_proper_waste_disposal,
        $agree_noncompliance_termination,
        $agree_products_fresh_legal,
        $agree_fit_to_work
    ];

    foreach ($agreement_values as $value) {
        if (!in_array($value, [0, 1], true)) {
            $errors[] = 'Agreement values must be 0 or 1';
            break;
        }
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    try {
        $checkRegistration = $pdo->prepare('SELECT id FROM registrations WHERE id = :id LIMIT 1');
        $checkRegistration->execute([':id' => $registration_id]);

        if (!$checkRegistration->fetchColumn()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Registration not found']);
            exit;
        }

        $stmt = $pdo->prepare('INSERT INTO health_declarations (
                registration_id,
                vaccinated,
                handwashing_station,
                color_coded_trash_bin,
                wear_protective_gear,
                clean_stall_before_after,
                no_smoking_in_area,
                agree_health_certificates,
                agree_clean_stall,
                agree_proper_waste_disposal,
                agree_noncompliance_termination,
                agree_products_fresh_legal,
                agree_fit_to_work
            ) VALUES (
                :registration_id,
                :vaccinated,
                :handwashing_station,
                :color_coded_trash_bin,
                :wear_protective_gear,
                :clean_stall_before_after,
                :no_smoking_in_area,
                :agree_health_certificates,
                :agree_clean_stall,
                :agree_proper_waste_disposal,
                :agree_noncompliance_termination,
                :agree_products_fresh_legal,
                :agree_fit_to_work
            ) ON DUPLICATE KEY UPDATE
                vaccinated = VALUES(vaccinated),
                handwashing_station = VALUES(handwashing_station),
                color_coded_trash_bin = VALUES(color_coded_trash_bin),
                wear_protective_gear = VALUES(wear_protective_gear),
                clean_stall_before_after = VALUES(clean_stall_before_after),
                no_smoking_in_area = VALUES(no_smoking_in_area),
                agree_health_certificates = VALUES(agree_health_certificates),
                agree_clean_stall = VALUES(agree_clean_stall),
                agree_proper_waste_disposal = VALUES(agree_proper_waste_disposal),
                agree_noncompliance_termination = VALUES(agree_noncompliance_termination),
                agree_products_fresh_legal = VALUES(agree_products_fresh_legal),
                agree_fit_to_work = VALUES(agree_fit_to_work)');

        $stmt->execute([
            ':registration_id' => $registration_id,
            ':vaccinated' => $q1,
            ':handwashing_station' => $q2,
            ':color_coded_trash_bin' => $q3,
            ':wear_protective_gear' => $q4,
            ':clean_stall_before_after' => $q5,
            ':no_smoking_in_area' => $q6,
            ':agree_health_certificates' => $agree_health_certificates,
            ':agree_clean_stall' => $agree_clean_stall,
            ':agree_proper_waste_disposal' => $agree_proper_waste_disposal,
            ':agree_noncompliance_termination' => $agree_noncompliance_termination,
            ':agree_products_fresh_legal' => $agree_products_fresh_legal,
            ':agree_fit_to_work' => $agree_fit_to_work
        ]);

        echo json_encode(['success' => true]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to save health declaration.',
            'detail' => $e->getMessage()
        ]);
    }
}

function handleRequirements() {
    global $pdo;

    $registration_id = (int)($_POST['registration_id'] ?? 0);
    if ($registration_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid registration_id']);
        exit;
    }

    // Ensure registration exists
    $chk = $pdo->prepare('SELECT id FROM registrations WHERE id = :id LIMIT 1');
    $chk->execute([':id' => $registration_id]);
    if (!$chk->fetchColumn()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Registration not found']);
        exit;
    }

    $fields = [
        'barangay_clearance',
        'dti_registration',
        'government_id',
        'health_certificate',
        'sanitary_permit',
        'proof_of_stall'
    ];

    $maxFileSize = 10 * 1024 * 1024; // 10 MB per file
    $allowedMimes = [
        'image/jpeg', 'image/png', 'application/pdf', 'image/jpg'
    ];

    try {
        $insert = $pdo->prepare('INSERT INTO attachments (registration_id, field_name, filename, mime, content) VALUES (:rid, :field, :filename, :mime, :content)');

        foreach ($fields as $field) {
            if (!isset($_FILES[$field])) {
                throw new Exception('Missing file: ' . $field);
            }
            $f = $_FILES[$field];
            if ($f['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Upload error for ' . $field . ': ' . $f['error']);
            }
            if ($f['size'] > $maxFileSize) {
                throw new Exception('File too large for ' . $field);
            }
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($f['tmp_name']) ?: '';

            if ($mime === 'application/x-empty' || $mime === 'application/octet-stream' || $mime === '') {
                $extension = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                $extensionMap = [
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'pdf' => 'application/pdf',
                ];

                if (isset($extensionMap[$extension])) {
                    $mime = $extensionMap[$extension];
                }
            }

            if (!in_array($mime, $allowedMimes, true)) {
                throw new Exception('Invalid file type for ' . $field . ': ' . $mime);
            }

            $orig = basename($f['name']);
            $content = file_get_contents($f['tmp_name']);

            $insert->execute([
                ':rid' => $registration_id,
                ':field' => $field,
                ':filename' => $orig,
                ':mime' => $mime,
                ':content' => $content
            ]);
        }

        echo json_encode(['success' => true]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Upload failed', 'detail' => $e->getMessage()]);
    }
}
?>