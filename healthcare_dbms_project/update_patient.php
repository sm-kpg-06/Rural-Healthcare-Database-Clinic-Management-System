<?php
session_start();
include 'db.php';
if(!isset($_SESSION['user'])){ header("Location: login.php"); exit(); }

$id = $conn->real_escape_string($_GET['id']);
$result = $conn->query("SELECT * FROM PATIENT WHERE Patient_ID='$id'");
$row = $result->fetch_assoc();

include 'header.php';
?>

<div class="card" style="max-width: 500px; margin-bottom: 40px;">
    <h1>✏️ Update Patient</h1>
    <p class="subtitle">Modify details for patient ID: <?php echo htmlspecialchars($id); ?></p>

    <form method="POST">
        <label style="display:block; margin-bottom: 5px; color: var(--text-secondary);">Name</label>
        <input name="name" value="<?php echo htmlspecialchars($row['Name'] ?? ''); ?>" placeholder="Name" required>
        
        <label style="display:block; margin-bottom: 5px; color: var(--text-secondary);">Gender</label>
        <select name="gender" required>
            <option value="M" <?php if(($row['Gender'] ?? '')=='M') echo 'selected'; ?>>Male</option>
            <option value="F" <?php if(($row['Gender'] ?? '')=='F') echo 'selected'; ?>>Female</option>
            <option value="Other" <?php if(($row['Gender'] ?? '')=='Other') echo 'selected'; ?>>Other</option>
        </select>
        
        <label style="display:block; margin-bottom: 5px; color: var(--text-secondary);">Date of Birth</label>
        <input type="date" name="dob" value="<?php echo htmlspecialchars($row['DOB'] ?? ''); ?>">
        
        <div style="display:flex; gap: 10px;">
            <div style="flex:1;">
                <label style="display:block; margin-bottom: 5px; color: var(--text-secondary);">Age</label>
                <input type="number" name="age" value="<?php echo htmlspecialchars($row['Age'] ?? ''); ?>" placeholder="Age">
            </div>
            <div style="flex:1;">
                <label style="display:block; margin-bottom: 5px; color: var(--text-secondary);">Blood Group</label>
                <?php $bg = $row['Blood_Group'] ?? $row['Blood_group'] ?? $row['blood_group'] ?? ''; ?>
                <input type="text" name="blood_group" value="<?php echo htmlspecialchars($bg); ?>" placeholder="e.g. O+">
            </div>
        </div>

        <label style="display:block; margin-bottom: 5px; color: var(--text-secondary);">Address</label>
        <input type="text" name="address" value="<?php echo htmlspecialchars($row['Address'] ?? ''); ?>" placeholder="Street Address">
        
        <div style="display:flex; gap: 10px;">
            <div style="flex:1;">
                <label style="display:block; margin-bottom: 5px; color: var(--text-secondary);">City</label>
                <input type="text" name="city" value="<?php echo htmlspecialchars($row['City'] ?? ''); ?>" placeholder="City">
            </div>
            <div style="flex:1;">
                <label style="display:block; margin-bottom: 5px; color: var(--text-secondary);">State</label>
                <input type="text" name="state" value="<?php echo htmlspecialchars($row['State'] ?? ''); ?>" placeholder="State">
            </div>
        </div>
        
        <button type="submit" name="update" class="btn" style="width: 100%; margin-top: 20px;">Update All Details</button>
    </form>

    <?php
    if(isset($_POST['update'])){
        $name = $conn->real_escape_string($_POST['name']);
        $gender = $conn->real_escape_string($_POST['gender']);
        $dob = $conn->real_escape_string($_POST['dob']);
        $age = (int)$_POST['age'];
        $blood_group = $conn->real_escape_string($_POST['blood_group']);
        $address = $conn->real_escape_string($_POST['address']);
        $city = $conn->real_escape_string($_POST['city']);
        $state = $conn->real_escape_string($_POST['state']);

        $sql = "UPDATE PATIENT SET Name='$name', Gender='$gender', DOB='$dob', Age='$age', Blood_Group='$blood_group', Address='$address', City='$city', State='$state' WHERE Patient_ID='$id'";

        if($conn->query($sql)){
            echo "<script>window.location.href = 'view_patient.php';</script>";
        } else {
            echo "<p style='color: var(--danger-color); margin-top: 20px;'>❌ Failed: " . $conn->error . "</p>";
        }
    }
    ?>
</div>

<?php include 'footer.php'; ?>