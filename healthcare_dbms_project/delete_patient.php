<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
include 'db.php';

if (isset($_GET['id'])) {
    $id = $conn->real_escape_string($_GET['id']);
    
    // Auto-heal ASSISTS table if it was dropped manually to prevent fatal error on DELETE
    $conn->query("CREATE TABLE IF NOT EXISTS ASSISTS (Nurse_ID int, Visit_ID int, PRIMARY KEY (Nurse_ID, Visit_ID))");
    
    // 1. Delete all deep child dependencies for this patient's visits
    $v_res = $conn->query("SELECT Visit_ID FROM VISIT WHERE Patient_ID = '$id'");
    if ($v_res && $v_res->num_rows > 0) {
        while ($v_row = $v_res->fetch_assoc()) {
            $vid = $v_row['Visit_ID'];
            
            // Delete from ASSISTS, TEST, VISIT_SYMPTOMS
            $conn->query("DELETE FROM ASSISTS WHERE Visit_ID = '$vid'");
            $conn->query("DELETE FROM TEST WHERE Visit_ID = '$vid'");
            $conn->query("DELETE FROM VISIT_SYMPTOMS WHERE Visit_ID = '$vid'");
            
            // Find Prescriptions for this visit to delete their medicines first
            $p_res = $conn->query("SELECT Prescription_ID FROM PRESCRIPTION WHERE Visit_ID = '$vid'");
            if ($p_res && $p_res->num_rows > 0) {
                while ($p_row = $p_res->fetch_assoc()) {
                    $pid = $p_row['Prescription_ID'];
                    $conn->query("DELETE FROM PRESCRIPTION_MEDICINE WHERE Prescription_ID = '$pid'");
                }
            }
            // Delete Prescriptions
            $conn->query("DELETE FROM PRESCRIPTION WHERE Visit_ID = '$vid'");
        }
    }
    
    // 2. Delete the Visits
    $conn->query("DELETE FROM VISIT WHERE Patient_ID = '$id'");
    
    // 3. Delete Patient dependencies
    $conn->query("DELETE FROM PATIENT_PHONE WHERE Patient_ID = '$id'");
    
    // 4. Finally, Delete the Patient safely!
    $conn->query("DELETE FROM PATIENT WHERE Patient_ID = '$id'");
}

header("Location: view_patient.php");
exit();
?>