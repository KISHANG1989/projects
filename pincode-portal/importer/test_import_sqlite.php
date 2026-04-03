<?php
/**
 * Test Importer using SQLite
 */

$csv_file = __DIR__ . '/../data/india_pincodes_sample.csv';
$db_file = __DIR__ . '/test_db.sqlite';

if (file_exists($db_file)) unlink($db_file);

try {
    $pdo = new PDO("sqlite:$db_file");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create Table (SQLite compatible)
    $pdo->exec("CREATE TABLE wp_pincode_directory (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        pincode TEXT,
        officename TEXT,
        pincode_type TEXT,
        deliverystatus TEXT,
        divisionname TEXT,
        regionname TEXT,
        circlename TEXT,
        taluk TEXT,
        districtname TEXT,
        statename TEXT,
        telephone TEXT,
        related_suboffice TEXT,
        related_headoffice TEXT,
        longitude REAL,
        latitude REAL,
        slug TEXT
    )");

    echo "Table created.\n";

    $handle = fopen($csv_file, 'r');
    $headers = fgetcsv($handle);

    $insert_sql = "INSERT INTO wp_pincode_directory
    (pincode, officename, pincode_type, deliverystatus, divisionname, regionname, circlename, taluk, districtname, statename, telephone, related_suboffice, related_headoffice, longitude, latitude, slug)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($insert_sql);
    $count = 0;

    while (($data = fgetcsv($handle)) !== FALSE) {
        $row = array_combine($headers, $data);

        $slug_raw = $row['pincode'] . '-' . $row['officename'] . '-' . $row['districtname'];
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $slug_raw)));

        $params = [
            $row['pincode'],
            $row['officename'],
            $row['pincode_type'],
            $row['deliverystatus'],
            $row['divisionname'],
            $row['regionname'],
            $row['circlename'],
            $row['taluk'],
            $row['districtname'],
            $row['statename'],
            $row['telephone'],
            $row['related_suboffice'],
            $row['related_headoffice'],
            $row['longitude'] ?: null,
            $row['latitude'] ?: null,
            $slug
        ];

        $stmt->execute($params);
        $count++;
    }

    echo "Imported $count rows.\n";

    // Verify Data
    $check = $pdo->query("SELECT * FROM wp_pincode_directory WHERE pincode='110001'")->fetch(PDO::FETCH_ASSOC);
    print_r($check);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
