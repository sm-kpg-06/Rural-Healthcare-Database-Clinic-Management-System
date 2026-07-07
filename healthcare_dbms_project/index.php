<?php
session_start();
if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}
include 'header.php';
?>

<div style="text-align: center; margin-bottom: 50px;">
    <h1>🏥 Rural Healthcare System</h1>
    <p class="subtitle">Manage patient records, visits and healthcare data with ease.</p>
</div>

<div class="card">
    <p>Welcome to the portal</p>
    <a href="view_patient.php" class="btn-link">👨‍⚕️ View All Patients</a>
    <a href="search_patient.php" class="btn-link">🔍 Search Patient</a>
    <a href="visit_details.php" class="btn-link">📋 View Visit Details</a>
    <a href="generate_bill.php" class="btn-link">💸 Generate Bill</a>
</div>

<?php include 'footer.php'; ?>