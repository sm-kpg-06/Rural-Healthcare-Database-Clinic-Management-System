<?php 
session_start();
include 'db.php'; 
if(!isset($_SESSION['user'])){ header("Location: login.php"); exit(); }
include 'header.php';
?>

<div class="card" style="max-width: 600px; margin-bottom: 40px;">
    <h1>🔍 Advanced Patient Search</h1>
    <p class="subtitle">Retrieve deep details including nested queries for Visits, Tests, and Prescriptions</p>

    <form method="GET" action="search_patient.php" style="flex-direction: column;">
        <div style="display: flex; gap: 10px; width: 100%;">
            <input type="text" name="search" placeholder="Enter ID or Name..." style="flex: 1;" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit" class="btn" style="width: auto; margin:0;">Search</button>
        </div>
        
        <div style="display: flex; gap: 20px; justify-content: center; margin-top: 15px;">
            <label style="color: var(--text-primary); cursor: pointer;">
                <input type="radio" name="filter" value="all" <?php echo (!isset($_GET['filter']) || $_GET['filter'] == 'all') ? 'checked' : ''; ?>> All Details
            </label>
            <label style="color: var(--text-primary); cursor: pointer;">
                <input type="radio" name="filter" value="prescription" <?php echo (isset($_GET['filter']) && $_GET['filter'] == 'prescription') ? 'checked' : ''; ?>> Prescription Only
            </label>
            <label style="color: var(--text-primary); cursor: pointer;">
                <input type="radio" name="filter" value="test" <?php echo (isset($_GET['filter']) && $_GET['filter'] == 'test') ? 'checked' : ''; ?>> Test Only
            </label>
        </div>
    </form>
</div>

<?php
if(isset($_GET['search']) && !empty(trim($_GET['search']))){
    $search = $conn->real_escape_string($_GET['search']);
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

    $sql = "SELECT * FROM PATIENT WHERE Patient_ID='$search' OR Name LIKE '%$search%'";
    $result = $conn->query($sql);

    echo "<h2 style='margin-bottom:20px; text-align:center;'>Search Results</h2>";

    if($result && $result->num_rows > 0){
        
        while($patient = $result->fetch_assoc()){
            $bg = $patient['Blood_Group'] ?? $patient['Blood_group'] ?? $patient['blood_group'] ?? 'N/A';
            $pid = $patient['Patient_ID'];
            
            echo "<div class='card' style='max-width: 800px; text-align: left; margin-bottom: 30px; border: 1px solid var(--accent-color);'>";
            echo "<h3 style='color: var(--accent-color); margin-bottom: 10px;'>{$patient['Name']} </h3>";
            echo "<p><strong>ID:</strong> {$pid} | <strong>Gender:</strong> {$patient['Gender']} | <strong>Blood:</strong> {$bg} | <strong>City:</strong> {$patient['City']}</p>";
            echo "<hr style='border: 1px solid rgba(255,255,255,0.1); margin: 15px 0;'>";
            
            // Query Visits
            $v_sql = "SELECT * FROM VISIT WHERE Patient_ID = '$pid' ORDER BY Visit_Date DESC";
            $v_res = $conn->query($v_sql);
            
            if($v_res && $v_res->num_rows > 0) {
                while($visit = $v_res->fetch_assoc()) {
                    $vid = $visit['Visit_ID'];
                    echo "<div style='background: rgba(0,0,0,0.3); padding: 15px; border-radius: 8px; margin-bottom: 15px;'>";
                    echo "<h4>📅 Visit Date: {$visit['Visit_Date']} (Visit ID: {$vid})</h4>";
                    
                    // Show Prescriptions if filter allows
                    if($filter == 'all' || $filter == 'prescription') {
                        $p_sql = "SELECT p.Dosage, p.Duration, GROUP_CONCAT(pm.Medicines SEPARATOR ', ') as Meds 
                                  FROM PRESCRIPTION p 
                                  LEFT JOIN PRESCRIPTION_MEDICINE pm ON p.Prescription_ID = pm.Prescription_ID 
                                  WHERE p.Visit_ID = '$vid' 
                                  GROUP BY p.Prescription_ID";
                        $p_res = $conn->query($p_sql);
                        
                        echo "<h5 style='color: #4ade80; margin-top: 10px;'>💊 Prescriptions</h5>";
                        if($p_res && $p_res->num_rows > 0) {
                            echo "<ul style='margin-left: 20px; color: var(--text-secondary);'>";
                            while($presc = $p_res->fetch_assoc()) {
                                $meds = $presc['Meds'] ? htmlspecialchars($presc['Meds']) : 'None listed';
                                echo "<li><strong>Medicines:</strong> {$meds} | <strong>Dosage:</strong> {$presc['Dosage']} | <strong>Duration:</strong> {$presc['Duration']}</li>";
                            }
                            echo "</ul>";
                        } else {
                            echo "<p style='margin-left: 20px; color: var(--text-secondary); font-size: 14px;'>No prescriptions found.</p>";
                        }
                    }
                    
                    // Show Tests if filter allows
                    if($filter == 'all' || $filter == 'test') {
                        $t_sql = "SELECT Test_Name, Result FROM TEST WHERE Visit_ID = '$vid'";
                        $t_res = $conn->query($t_sql);
                        
                        echo "<h5 style='color: #fca5a5; margin-top: 10px;'>🔬 Tests</h5>";
                        if($t_res && $t_res->num_rows > 0) {
                            echo "<ul style='margin-left: 20px; color: var(--text-secondary);'>";
                            while($test = $t_res->fetch_assoc()) {
                                echo "<li><strong>Test:</strong> {$test['Test_Name']} | <strong>Result:</strong> {$test['Result']}</li>";
                            }
                            echo "</ul>";
                        } else {
                            echo "<p style='margin-left: 20px; color: var(--text-secondary); font-size: 14px;'>No tests found.</p>";
                        }
                    }
                    
                    echo "</div>"; // End Visit Block
                }
            } else {
                echo "<p style='color: var(--text-secondary);'>No visits logged for this patient.</p>";
            }
            
            echo "</div>"; // End Patient Card
        }
    } else {
        echo "<p style='text-align:center; color: var(--danger-color);'>No matching patients found.</p>";
    }
}
?>

<?php include 'footer.php'; ?>