<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}
include 'header.php';

// Total patients
$p_result = $conn->query("SELECT COUNT(*) as total FROM PATIENT");
$p = $p_result ? $p_result->fetch_assoc() : ['total' => 0];

// Total visits
$v_result = $conn->query("SELECT COUNT(*) as total FROM VISIT");
$v = $v_result ? $v_result->fetch_assoc() : ['total' => 0];
?>

<h1 style="text-align: center; margin-bottom: 40px;">📊 Dashboard</h1>

<div class="stats-grid">
    <div class="stat-box">
        <h3>Total Patients</h3>
        <div class="count"><?php echo $p['total']; ?></div>
    </div>
    
    <div class="stat-box">
        <h3>Total Visits</h3>
        <div class="count"><?php echo $v['total']; ?></div>
    </div>
</div>

<div style="text-align: center; margin-top: 50px;">
    <a href="index.php" class="btn">🏠 Go to Main Menu</a>
</div>

<?php include 'footer.php'; ?>