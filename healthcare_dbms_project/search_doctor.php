<?php 
session_start();
include 'db.php'; 
if(!isset($_SESSION['user'])){ header("Location: login.php"); exit(); }
include 'header.php';
?>

<div class="card" style="max-width: 600px; margin-bottom: 40px;">
    <h1>👨‍⚕️ Search Doctor</h1>
    <p class="subtitle">Find a doctor by ID, Name, or Specialization</p>

    <form method="GET" action="search_doctor.php" style="flex-direction: row; flex-wrap: wrap;">
        <input type="text" name="query" placeholder="Enter ID, Name, or Specialty..." style="flex: 1; min-width: 200px;" value="<?php echo isset($_GET['query']) ? htmlspecialchars($_GET['query']) : ''; ?>">
        <button type="submit" class="btn" style="width: auto; margin:0;">Search</button>
    </form>
</div>

<?php
if(isset($_GET['query']) && !empty(trim($_GET['query']))){
    $search = $conn->real_escape_string(trim($_GET['query']));

    // JOIN DOCTOR to EMPLOYEE and EMPLOYEE_PHONE
    $sql = "SELECT d.Doctor_ID, e.Name, d.Specialization, d.Qualification, GROUP_CONCAT(ep.Phone_No SEPARATOR ', ') as Phone_Numbers
            FROM DOCTOR d
            JOIN EMPLOYEE e ON d.Employee_ID = e.Employee_ID
            LEFT JOIN EMPLOYEE_PHONE ep ON e.Employee_ID = ep.Employee_ID
            WHERE d.Doctor_ID = '$search' OR e.Name LIKE '%$search%' OR d.Specialization LIKE '%$search%'
            GROUP BY d.Doctor_ID, e.Name, d.Specialization, d.Qualification";

    $result = $conn->query($sql);

    echo "<h2 style='margin-bottom:20px; text-align:center;'>Search Results</h2>";

    if($result && $result->num_rows > 0){
        echo "<div class='stats-grid'>";

        while($doctor = $result->fetch_assoc()){
            $did = $doctor['Doctor_ID'];
            $phones = $doctor['Phone_Numbers'] ? htmlspecialchars($doctor['Phone_Numbers']) : 'N/A';
            
            echo "<div class='card' style='max-width: 400px; text-align: left; margin: 10px; border: 1px solid var(--accent-color);'>";
            echo "<h3 style='color: var(--accent-color); margin-bottom: 15px;'>Dr. {$doctor['Name']}</h3>";
            echo "<p><strong>ID:</strong> {$did}</p>";
            echo "<p><strong>Specialization:</strong> {$doctor['Specialization']}</p>";
            echo "<p><strong>Qualification:</strong> {$doctor['Qualification']}</p>";
            echo "<p><strong>Contact:</strong> {$phones}</p>";
            
            echo "<hr style='border: 1px solid rgba(255,255,255,0.1); margin: 15px 0;'>";
            echo "<h5 style='color: #4ade80; margin-bottom: 10px;'>🏥 Affiliated Clinics</h5>";
            
            // Subquery: Which clinics does this doctor attend via VISIT?
            $c_sql = "SELECT DISTINCT c.Clinic_Name, c.Location
                      FROM VISIT v
                      JOIN CLINIC c ON v.Clinic_ID = c.Clinic_ID
                      WHERE v.Doctor_ID = '$did'";
                      
            $c_res = $conn->query($c_sql);
            if($c_res && $c_res->num_rows > 0) {
                echo "<ul style='margin-left: 20px; color: var(--text-secondary);'>";
                while($clinic = $c_res->fetch_assoc()){
                    echo "<li>{$clinic['Clinic_Name']} ({$clinic['Location']})</li>";
                }
                echo "</ul>";
            } else {
                echo "<p style='color: var(--text-secondary); font-size: 14px;'>No affiliated clinics found.</p>";
            }
            
            echo "</div>";
        }

        echo "</div>";
    } else {
        echo "<p style='text-align:center; color: var(--danger-color);'>No matching doctors found.</p>";
    }
}
?>

<?php include 'footer.php'; ?>
