<?php
// start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rural Healthcare System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php if(isset($_SESSION['user'])): ?>
    <div class="navbar">
        <a href="dashboard.php">Dashboard</a>
        <a href="index.php">Home</a>
        <a href="view_patient.php">Patients</a>
        <a href="add_patient.php">Add Patient</a>
        <a href="search_patient.php">Search</a>
        <a href="visit_details.php">📋 Visits</a>
        <a href="generate_bill.php">Billing</a>
        <a href="search_clinic.php">Clinics</a>
        <a href="search_doctor.php">Doctors</a>
        <a href="clinic_staff.php">Staff Directory</a>
        <a href="add_prescription.php">💊 Prescription</a>
        <a href="add_test.php">🔬 Test</a>
        <a href="logout.php" style="color: var(--danger-color);">Logout</a>
    </div>
<?php endif; ?>

<div class="container">
