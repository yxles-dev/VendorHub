<?php
header('Content-Type: application/json');

require_once __DIR__ . '/connect_db.php';

function fetchSingleValue(PDO $pdo, string $sql, array $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function fetchAllAssoc(PDO $pdo, string $sql, array $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function normalizeLabel(string $value): string {
    $clean = trim($value);
    return $clean === '' ? 'Uncategorized' : $clean;
}

function monthLabels(int $months = 8): array {
    $labels = [];
    $date = new DateTimeImmutable('first day of this month');

    for ($index = $months - 1; $index >= 0; $index--) {
        $labels[] = $date->modify("-$index months")->format('M');
    }

    return $labels;
}

function monthKeys(int $months = 8): array {
    $keys = [];
    $date = new DateTimeImmutable('first day of this month');

    for ($index = $months - 1; $index >= 0; $index--) {
        $keys[] = $date->modify("-$index months")->format('Y-m');
    }

    return $keys;
}

try {
    $totalRegisteredVendors = (int)fetchSingleValue($pdo, 'SELECT COUNT(*) FROM registrations');

    $businessCategoriesCount = (int)fetchSingleValue(
        $pdo,
        "SELECT COUNT(DISTINCT CASE WHEN category IS NULL OR TRIM(category) = '' THEN NULL ELSE category END) FROM businesses"
    );

    $genderRows = fetchAllAssoc(
        $pdo,
        'SELECT sex, COUNT(*) AS total FROM registrations GROUP BY sex'
    );
    $genderMap = ['Male' => 0, 'Female' => 0];
    foreach ($genderRows as $row) {
        $sex = $row['sex'] ?? '';
        if (array_key_exists($sex, $genderMap)) {
            $genderMap[$sex] = (int)$row['total'];
        }
    }

    $ageBuckets = [
        '18-25' => 0,
        '26-35' => 0,
        '36-45' => 0,
        '46-55' => 0,
        '56-65' => 0,
        '65+' => 0,
    ];

    $ageRows = fetchAllAssoc(
        $pdo,
        "SELECT TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) AS age, COUNT(*) AS total
         FROM registrations
         WHERE date_of_birth IS NOT NULL
         GROUP BY age"
    );

    foreach ($ageRows as $row) {
        $age = (int)$row['age'];
        $count = (int)$row['total'];

        if ($age <= 25) {
            $ageBuckets['18-25'] += $count;
        } elseif ($age <= 35) {
            $ageBuckets['26-35'] += $count;
        } elseif ($age <= 45) {
            $ageBuckets['36-45'] += $count;
        } elseif ($age <= 55) {
            $ageBuckets['46-55'] += $count;
        } elseif ($age <= 65) {
            $ageBuckets['56-65'] += $count;
        } else {
            $ageBuckets['65+'] += $count;
        }
    }

    $ageValues = array_values($ageBuckets);
    $averageVendorAge = (float)fetchSingleValue(
        $pdo,
        'SELECT COALESCE(AVG(TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE())), 0) FROM registrations WHERE date_of_birth IS NOT NULL'
    );

    $topCategoryRow = fetchAllAssoc(
        $pdo,
        "SELECT normalized_category, COUNT(*) AS total
         FROM (
             SELECT CASE WHEN category IS NULL OR TRIM(category) = '' THEN 'Uncategorized' ELSE category END AS normalized_category
             FROM businesses
         ) AS category_source
         GROUP BY normalized_category
         ORDER BY total DESC, normalized_category ASC
         LIMIT 1"
    );
    $topCategory = $topCategoryRow[0] ?? ['normalized_category' => 'N/A', 'total' => 0];

    $categoryRows = fetchAllAssoc(
        $pdo,
        "SELECT normalized_category, COUNT(*) AS total
         FROM (
             SELECT CASE WHEN category IS NULL OR TRIM(category) = '' THEN 'Uncategorized' ELSE category END AS normalized_category
             FROM businesses
         ) AS category_source
         GROUP BY normalized_category
         ORDER BY total DESC, normalized_category ASC"
    );
    $categoryLabels = [];
    $categoryValues = [];
    foreach ($categoryRows as $row) {
        $categoryLabels[] = normalizeLabel($row['normalized_category']);
        $categoryValues[] = (int)$row['total'];
    }

    $monthLabels = monthLabels(8);
    $monthKeys = monthKeys(8);
    $growthCounts = array_fill(0, count($monthKeys), 0);

    $growthRows = fetchAllAssoc(
        $pdo,
        'SELECT DATE_FORMAT(created_at, "%Y-%m") AS month_key, COUNT(*) AS total
         FROM registrations
         GROUP BY month_key
         ORDER BY month_key ASC'
    );

    $growthMap = [];
    foreach ($growthRows as $row) {
        $growthMap[$row['month_key']] = (int)$row['total'];
    }

    foreach ($monthKeys as $index => $key) {
        $growthCounts[$index] = $growthMap[$key] ?? 0;
    }

    $lastMonthCount = $growthCounts[count($growthCounts) - 1] ?? 0;
    $previousMonthCount = $growthCounts[count($growthCounts) - 2] ?? 0;
    $monthlyGrowthRate = 0.0;
    if ($previousMonthCount > 0) {
        $monthlyGrowthRate = (($lastMonthCount - $previousMonthCount) / $previousMonthCount) * 100;
    } elseif ($lastMonthCount > 0) {
        $monthlyGrowthRate = 100.0;
    }

    $lastTwoMonths = array_slice($growthCounts, -2);
    $monthlyGrowthDetail = 'Compared to the previous month';
    if (count($lastTwoMonths) === 2) {
        $monthlyGrowthDetail = sprintf('Last month: %d, previous month: %d', $lastTwoMonths[1], $lastTwoMonths[0]);
    }

    $healthRows = fetchAllAssoc(
        $pdo,
        'SELECT
            COUNT(*) AS total,
            SUM(vaccinated = "Yes") AS vaccinated_yes,
            SUM(vaccinated = "No") AS vaccinated_no,
            SUM(handwashing_station = "Yes") AS handwashing_yes,
            SUM(handwashing_station = "No") AS handwashing_no,
            SUM(color_coded_trash_bin = "Yes") AS trash_yes,
            SUM(color_coded_trash_bin = "No") AS trash_no,
            SUM(wear_protective_gear = "Yes") AS gear_yes,
            SUM(wear_protective_gear = "No") AS gear_no,
            SUM(clean_stall_before_after = "Yes") AS stall_yes,
            SUM(clean_stall_before_after = "No") AS stall_no,
            SUM(no_smoking_in_area = "Yes") AS smoking_yes,
            SUM(no_smoking_in_area = "No") AS smoking_no
         FROM health_declarations'
    );
    $healthStats = $healthRows[0] ?? [];
    $healthTotal = (int)($healthStats['total'] ?? 0);
    $fullyCompliant = (int)fetchSingleValue(
        $pdo,
        'SELECT COUNT(*) FROM health_declarations
         WHERE vaccinated = "Yes"
           AND handwashing_station = "Yes"
           AND color_coded_trash_bin = "Yes"
           AND wear_protective_gear = "Yes"
           AND clean_stall_before_after = "Yes"
           AND no_smoking_in_area = "Yes"'
    );
    $healthComplianceRate = $healthTotal > 0 ? ($fullyCompliant / $healthTotal) * 100 : 0;

    $complianceLabels = [
        'Hand Washing Stations',
        'Color-Coded Items',
        'Protective Gear',
        'Stall Cleaning',
        'No Smoking'
    ];
    $complianceCompliant = [
        (int)($healthStats['handwashing_yes'] ?? 0),
        (int)($healthStats['trash_yes'] ?? 0),
        (int)($healthStats['gear_yes'] ?? 0),
        (int)($healthStats['stall_yes'] ?? 0),
        (int)($healthStats['smoking_yes'] ?? 0),
    ];
    $complianceNonCompliant = [
        (int)($healthStats['handwashing_no'] ?? 0),
        (int)($healthStats['trash_no'] ?? 0),
        (int)($healthStats['gear_no'] ?? 0),
        (int)($healthStats['stall_no'] ?? 0),
        (int)($healthStats['smoking_no'] ?? 0),
    ];

    echo json_encode([
        'totalRegisteredVendors' => $totalRegisteredVendors,
        'businessCategoriesCount' => $businessCategoriesCount,
        'healthComplianceRate' => round($healthComplianceRate, 1),
        'topCategory' => [
            'name' => normalizeLabel($topCategory['normalized_category']),
            'count' => (int)$topCategory['total'],
        ],
        'averageVendorAge' => round($averageVendorAge, 1),
        'ageRangeDetail' => 'Distribution across age bands from registrations',
        'monthlyGrowthRate' => round($monthlyGrowthRate, 1),
        'monthlyGrowthDetail' => $monthlyGrowthDetail,
        'growthTrend' => [
            'labels' => $monthLabels,
            'values' => $growthCounts,
        ],
        'genderDistribution' => [
            'labels' => ['Male', 'Female'],
            'values' => array_values($genderMap),
        ],
        'categoryDistribution' => [
            'labels' => $categoryLabels,
            'values' => $categoryValues,
        ],
        'ageDistribution' => [
            'labels' => array_keys($ageBuckets),
            'values' => $ageValues,
        ],
        'complianceOverview' => [
            'labels' => $complianceLabels,
            'compliant' => $complianceCompliant,
            'nonCompliant' => $complianceNonCompliant,
        ],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load dashboard statistics.',
        'detail' => $e->getMessage()
    ]);
}