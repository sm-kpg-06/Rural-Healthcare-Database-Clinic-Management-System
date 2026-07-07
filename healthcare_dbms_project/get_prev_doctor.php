<?php
/**
 * get_prev_doctor.php
 * AJAX endpoint — returns the most recent doctor consulted by a patient.
 */
include 'db.php';
header('Content-Type: application/json');

$pid = isset($_GET['patient_id']) ? $conn->real_escape_string($_GET['patient_id']) : '';

if ($pid === '') {
    echo json_encode(['found' => false]);
    exit;
}

$sql = "SELECT d.Doctor_ID, e.Name AS doctor_name
        FROM VISIT v
        JOIN DOCTOR d ON v.Doctor_ID = d.Doctor_ID
        JOIN EMPLOYEE e ON d.Employee_ID = e.Employee_ID
        WHERE v.Patient_ID = '$pid'
        ORDER BY v.Visit_Date DESC
        LIMIT 1";

$res = $conn->query($sql);

if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    echo json_encode([
        'found'       => true,
        'doctor_id'   => $row['Doctor_ID'],
        'doctor_name' => $row['doctor_name'],
    ]);
} else {
    echo json_encode(['found' => false]);
}
