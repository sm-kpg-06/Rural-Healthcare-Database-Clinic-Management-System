<?php
session_start();
include 'db.php';
if(!isset($_SESSION['user'])){ header("Location: login.php"); exit(); }
include 'header.php';
?>

<div class="card" style="max-width: 600px; margin-bottom: 40px;">
    <h1>🏥 Search Clinic</h1>
    <p class="subtitle">Search for a clinic by Name or Location</p>

    <form method="GET" action="search_clinic.php" style="flex-direction: row; flex-wrap: wrap;">
        <input type="text" name="query" placeholder="Enter Clinic Name or Location..." style="flex: 1; min-width: 200px;" value="<?php echo isset($_GET['query']) ? htmlspecialchars($_GET['query']) : ''; ?>">
        <button type="submit" class="btn" style="width: auto; margin:0;">Search</button>
    </form>
</div>

<?php
if(isset($_GET['query']) && !empty(trim($_GET['query']))){
    $search = $conn->real_escape_string(trim($_GET['query']));

    // Using GROUP_CONCAT to handle the multivalued attribute (CLINIC_CONTACT) correctly
    $sql = "SELECT c.Clinic_ID, c.Clinic_Name, c.Location, GROUP_CONCAT(cc.Contact_No SEPARATOR ', ') AS Contact_Numbers
            FROM CLINIC c
            LEFT JOIN CLINIC_CONTACT cc ON c.Clinic_ID = cc.Clinic_ID
            WHERE c.Clinic_Name LIKE '%$search%' OR c.Location LIKE '%$search%'
            GROUP BY c.Clinic_ID, c.Clinic_Name, c.Location";

    $result = $conn->query($sql);

    echo "<h2 style='margin-bottom:20px; text-align:center;'>Search Results</h2>";

    if($result && $result->num_rows > 0){
        echo "
        <div class='table-container'>
            <table>
                <tr>
                    <th>Clinic ID</th>
                    <th>Clinic Name</th>
                    <th>Location</th>
                    <th>Contact Numbers</th>
                    <th>Action</th>
                </tr>";

        while($row = $result->fetch_assoc()){
            $contacts = $row['Contact_Numbers'] ? htmlspecialchars($row['Contact_Numbers']) : 'N/A';
            echo "<tr>
            <td>{$row['Clinic_ID']}</td>
            <td>{$row['Clinic_Name']}</td>
            <td>{$row['Location']}</td>
            <td>{$contacts}</td>
            <td>
                <a href='clinic_staff.php?clinic_id={$row['Clinic_ID']}' style='color: var(--accent-color);'>View Staff</a>
            </td>
            </tr>";
        }

        echo "</table></div>";
    } else {
        echo "<p style='text-align:center; color: var(--danger-color);'>No matching clinics found.</p>";
    }
}
?>

<?php include 'footer.php'; ?>
