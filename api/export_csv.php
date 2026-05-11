<?php
require_once __DIR__ . '/admin_auth.php';
admin_require_login();

require_once __DIR__ . '/connect_db.php';

// Fetch all registration data with related information
$stmt = $pdo->query('
    SELECT
        r.id,
        r.fullname,
        r.sex,
        r.date_of_birth,
        r.contact_number,
        r.emergency_contact,
        r.address,
        r.status,
        r.created_at as registration_date,
        b.business_name,
        b.category as business_category,
        hd.vaccinated,
        hd.handwashing_station,
        hd.color_coded_trash_bin,
        hd.wear_protective_gear,
        hd.clean_stall_before_after,
        hd.no_smoking_in_area,
        hd.agree_health_certificates,
        hd.agree_clean_stall,
        hd.agree_proper_waste_disposal,
        hd.agree_noncompliance_termination,
        hd.agree_products_fresh_legal,
        hd.agree_fit_to_work
    FROM registrations r
    LEFT JOIN businesses b ON r.id = b.registration_id
    LEFT JOIN health_declarations hd ON r.id = hd.registration_id
    ORDER BY r.id DESC
');

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="registrations_export_' . date('Y-m-d_H-i-s') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for Excel UTF-8 compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Define CSV headers
$headers = [
    'Registration ID',
    'Full Name',
    'Sex',
    'Date of Birth',
    'Contact Number',
    'Emergency Contact',
    'Address',
    'Status',
    'Registration Date',
    'Business Name',
    'Business Category',
    'Vaccinated',
    'Handwashing Station',
    'Color Coded Trash Bin',
    'Wear Protective Gear',
    'Clean Stall Before After',
    'No Smoking in Area',
    'Agree Health Certificates',
    'Agree Clean Stall',
    'Agree Proper Waste Disposal',
    'Agree Noncompliance Termination',
    'Agree Products Fresh Legal',
    'Agree Fit to Work'
];

// Write headers
fputcsv($output, $headers);

// Write data rows
foreach ($rows as $row) {
    $csvRow = [
        $row['id'],
        $row['fullname'],
        $row['sex'],
        $row['date_of_birth'],
        $row['contact_number'],
        $row['emergency_contact'],
        $row['address'],
        $row['status'],
        $row['registration_date'],
        $row['business_name'],
        $row['business_category'],
        $row['vaccinated'] ?? '',
        $row['handwashing_station'] ?? '',
        $row['color_coded_trash_bin'] ?? '',
        $row['wear_protective_gear'] ?? '',
        $row['clean_stall_before_after'] ?? '',
        $row['no_smoking_in_area'] ?? '',
        ($row['agree_health_certificates'] ? 'Yes' : 'No'),
        ($row['agree_clean_stall'] ? 'Yes' : 'No'),
        ($row['agree_proper_waste_disposal'] ? 'Yes' : 'No'),
        ($row['agree_noncompliance_termination'] ? 'Yes' : 'No'),
        ($row['agree_products_fresh_legal'] ? 'Yes' : 'No'),
        ($row['agree_fit_to_work'] ? 'Yes' : 'No')
    ];
    fputcsv($output, $csvRow);
}

fclose($output);
exit;
?>