<?php
session_start();
include 'db.php';
if(!isset($_SESSION['user'])){ header("Location: login.php"); exit(); }
include 'header.php';

$bill_generated = false;
$patient_name = "";
$visit_date = "";
$doctor_fee = 500; // default consultation fee
$tests_cost = 0;
$medicines_cost = 0;
$total_bill = 0;

if(isset($_GET['visit_id'])){
    $vid = $conn->real_escape_string($_GET['visit_id']);
    
    // Get Visit Info
    $v_query = $conn->query("SELECT p.Name, v.Visit_Date FROM VISIT v JOIN PATIENT p ON v.Patient_ID = p.Patient_ID WHERE v.Visit_ID = '$vid'");
    if($v_query && $v_query->num_rows > 0) {
        $v_data = $v_query->fetch_assoc();
        $patient_name = $v_data['Name'];
        $visit_date = $v_data['Visit_Date'];
        
        // Mock processing: List tests for this visit
        $tests_list = [];
        $t_query = $conn->query("SELECT Test_Name FROM TEST WHERE Visit_ID = '$vid'");
        if($t_query && $t_query->num_rows > 0) {
            while($t = $t_query->fetch_assoc()) {
                $tests_list[] = $t['Test_Name'];
                $tests_cost += 300; // 300 rupees per test
            }
        }
        
        // Mock processing: List prescriptions 
        $meds_list = [];
        $p_query = $conn->query("SELECT pm.Medicines FROM PRESCRIPTION p JOIN PRESCRIPTION_MEDICINE pm ON p.Prescription_ID = pm.Prescription_ID WHERE p.Visit_ID = '$vid'");
        if($p_query && $p_query->num_rows > 0) {
            while($p = $p_query->fetch_assoc()) {
                $meds_list[] = $p['Medicines'];
                $medicines_cost += 150; // 150 rupees per medicine
            }
        }
        
        $total_bill = $doctor_fee + $tests_cost + $medicines_cost;
        $bill_generated = true;
    } else {
        $error = "Visit ID not found.";
    }
}
?>

<div class="card" style="max-width: 600px; margin-bottom: 40px; text-align: left;">
    <h1 style="text-align: center;">💸 Bill Generation</h1>
    <p class="subtitle">Calculate procedure and consultation costs</p>

    <form method="GET" style="flex-direction: row; margin-bottom: 30px;">
        <input type="text" name="visit_id" placeholder="Enter Visit ID (e.g., 1)" style="flex:1;" required>
        <button type="submit" class="btn" style="width: auto; margin:0;">Generate</button>
    </form>

    <?php if(isset($error)): ?>
        <p style="color: var(--danger-color); text-align: center;"><?php echo $error; ?></p>
    <?php endif; ?>

    <?php if($bill_generated): ?>
        <div style="background: rgba(0,0,0,0.2); padding: 20px; border-radius: 12px; margin-top: 20px;">
            <h3 style="color: var(--accent-color); margin-bottom: 15px; text-align: center;">Invoice Details</h3>
            <p><strong>Patient Name:</strong> <?php echo $patient_name; ?></p>
            <p><strong>Visit Date:</strong> <?php echo $visit_date; ?></p>
            <hr style="border-color: rgba(255,255,255,0.1); margin: 15px 0;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span><strong>Consultation Fee:</strong></span>
                <span>₹<?php echo $doctor_fee; ?></span>
            </div>
            
            <?php if(!empty($tests_list)): ?>
                <div style="margin-top: 15px;">
                    <strong>Tests Performed (₹300 each):</strong>
                    <ul style="margin-left: 20px; font-size: 14px; color: var(--text-secondary);">
                        <?php foreach($tests_list as $test) echo "<li>$test - ₹300</li>"; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if(!empty($meds_list)): ?>
                <div style="margin-top: 15px;">
                    <strong>Medicines Prescribed (₹150 each):</strong>
                    <ul style="margin-left: 20px; font-size: 14px; color: var(--text-secondary);">
                        <?php foreach($meds_list as $med) echo "<li>$med - ₹150</li>"; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <hr style="border-color: rgba(255,255,255,0.1); margin: 15px 0;">
            <div style="display: flex; justify-content: space-between; font-size: 20px; font-weight: 800; color: var(--accent-color);">
                <span>Total Amount:</span>
                <span>₹<?php echo $total_bill; ?></span>
            </div>
            
            <button class="btn" style="width: 100%; margin-top: 25px;" onclick="window.print()">🖨️ Print Invoice</button>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
