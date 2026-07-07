<?php
/**
 * migrate.php
 * Run this ONCE via browser to alter the VISIT table.
 * Visit: http://localhost/healthcare_dbms_project/migrate.php
 */
include 'db.php';

echo "<pre style='font-family:monospace; padding:30px; color:#0f172a; background:#f0fdf4; min-height:100vh;'>";
echo "=== Healthcare DB Migration ===\n\n";

$steps = [
    "ADD Doctor_ID column" => "ALTER TABLE VISIT ADD COLUMN Doctor_ID INT DEFAULT NULL",
    "ADD Symptoms column"  => "ALTER TABLE VISIT ADD COLUMN Symptoms TEXT DEFAULT NULL",
    "ADD FK Doctor_ID"     =>
        "ALTER TABLE VISIT ADD CONSTRAINT fk_visit_doctor
         FOREIGN KEY (Doctor_ID) REFERENCES DOCTOR(Doctor_ID) ON DELETE SET NULL",
];

foreach ($steps as $label => $sql) {
    $result = $conn->query($sql);
    if ($result) {
        echo "✅  $label — OK\n";
    } else {
        $err = $conn->error;
        // Duplicate column / key = already done — treat as OK
        if (str_contains($err, 'Duplicate') || str_contains($err, 'already exists')) {
            echo "ℹ️   $label — Already exists (skipped)\n";
        } else {
            echo "❌  $label — ERROR: $err\n";
        }
    }
}

echo "\n=== Done. You may now delete migrate.php. ===\n";
echo "</pre>";
