<?php
include 'db.php';
header('Content-Type: application/json');

$vid = isset($_GET['visit_id']) ? intval($_GET['visit_id']) : 0;

if ($vid <= 0) {
    echo json_encode(['success' => false]);
    exit;
}

$sql = "SELECT v.Visit_ID, v.Visit_Date,
               p.Name AS patient_name,
               c.Clinic_Name AS clinic_name
        FROM VISIT v
        JOIN PATIENT p ON v.Patient_ID = p.Patient_ID
        LEFT JOIN CLINIC  c ON v.Clinic_ID  = c.Clinic_ID
        WHERE v.Visit_ID = $vid
        LIMIT 1";

$res = $conn->query($sql);
if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    echo json_encode([
        'success'      => true,
        'visit_date'   => $row['Visit_Date'],
        'patient_name' => $row['patient_name'],
        'clinic_name'  => $row['clinic_name'] ?? '',
    ]);
} else {
    echo json_encode(['success' => false]);
}
