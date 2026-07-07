<?php 
session_start();
include 'db.php'; 
if(!isset($_SESSION['user'])){ header("Location: login.php"); exit(); }
include 'header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1>👨‍⚕️ Patient Records</h1>
    <div>
        <a href="view_patient.php" class="btn">All Records</a>
        <a href="view_patient.php?view=first" class="btn">First Record</a>
        <a href="view_patient.php?view=last" class="btn">Last Record</a>
    </div>
</div>

<div class="table-container">
    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Gender</th>
            <th>Blood Group</th>
            <th>Age</th>
            <th>City</th>
            <th>Action</th>
        </tr>

        <?php
        $sql = "SELECT * FROM PATIENT";
        
        if (isset($_GET['view'])) {
            if ($_GET['view'] == 'first') {
                $sql .= " ORDER BY Patient_ID ASC LIMIT 1";
            } else if ($_GET['view'] == 'last') {
                $sql .= " ORDER BY Patient_ID DESC LIMIT 1";
            }
        }

        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()){
                $bg = $row['Blood_Group'] ?? $row['Blood_group'] ?? $row['blood_group'] ?? 'N/A';
                $age = $row['Age'] ?? $row['age'] ?? 'N/A';
                echo "<tr>
                    <td>{$row['Patient_ID']}</td>
                    <td>{$row['Name']}</td>
                    <td>{$row['Gender']}</td>
                    <td>{$bg}</td>
                    <td>{$age}</td>
                    <td>{$row['City']}</td>
                    <td>
                        <a href='update_patient.php?id={$row['Patient_ID']}' style='color: var(--accent-color);'>Edit</a>
                        <a href='delete_patient.php?id={$row['Patient_ID']}' style='color: var(--danger-color);' onclick='return confirm(\"Are you sure you want to delete this patient?\")'>Delete</a>
                    </td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='7' style='text-align:center;'>No records found</td></tr>";
        }
        ?>
    </table>
</div>

<?php include 'footer.php'; ?>