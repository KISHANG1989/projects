<?php
/**
 * Pincode Importer Script
 * Usage: Place this in a folder accessible via web or CLI.
 * Recommended: Run via CLI `php import.php` to avoid timeouts.
 */

// Configuration
$csv_file = __DIR__ . '/../data/india_pincodes_sample.csv';
$db_host = 'localhost';
$db_name = 'wordpress';
$db_user = 'root';
$db_pass = '';
$table_prefix = 'wp_'; // CHANGE THIS if your WP prefix is different
$table_name = $table_prefix . 'pincode_directory';

// Try to load WP config if available (optional)
if (file_exists(__DIR__ . '/../../wp-config.php')) {
    // This is a naive way to get creds, but for this standalone tool we might just ask user to edit above.
}

echo "Starting Import Process...\n";

if (!file_exists($csv_file)) {
    die("Error: CSV file not found at $csv_file\n");
}

try {
    // Connect to DB
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE '$table_name'");
    if ($stmt->rowCount() == 0) {
        die("Error: Table '$table_name' does not exist. Run setup_schema.sql first.\n");
    }

    $handle = fopen($csv_file, 'r');
    if (!$handle) {
        die("Error: Could not open CSV.\n");
    }

    // Get Headers
    $headers = fgetcsv($handle);
    // Expected map: CSV Header -> DB Column
    // CSV: pincode,officename,pincode_type,deliverystatus,divisionname,regionname,circlename,taluk,districtname,statename,telephone,related_suboffice,related_headoffice,longitude,latitude
    // DB matches these mostly.

    $batch_size = 100;
    $rows = [];
    $count = 0;

    $insert_sql = "INSERT INTO $table_name
    (pincode, officename, pincode_type, deliverystatus, divisionname, regionname, circlename, taluk, districtname, statename, telephone, related_suboffice, related_headoffice, longitude, latitude, slug)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($insert_sql);

    while (($data = fgetcsv($handle)) !== FALSE) {
        // Map CSV data to named array for safety
        $row = array_combine($headers, $data);

        // Slug generation: pincode-officename-district
        $slug_raw = $row['pincode'] . '-' . $row['officename'] . '-' . $row['districtname'];
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $slug_raw)));

        // Prepare execution params
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

        if ($count % 100 == 0) {
            echo "Imported $count rows...\n";
        }
    }

    fclose($handle);
    echo "Success! Total imported: $count\n";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage() . "\n");
}
