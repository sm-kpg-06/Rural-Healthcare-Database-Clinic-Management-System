<?php 
session_start();
include 'db.php'; 
if(!isset($_SESSION['user'])){ header("Location: login.php"); exit(); }
include 'header.php';
?>

<div class="card" style="max-width: 600px; margin-bottom: 40px;">
    <h1>➕ Add Patient</h1>
    <p class="subtitle">Register a new patient to the system</p>

    <form method="POST">
        <input name="id" placeholder="Patient ID (Unique)" required>
        <input name="name" placeholder="Full Name" required>
        
        <select name="gender" required>
            <option value="" disabled selected>Select Gender</option>
            <option value="M">Male</option>
            <option value="F">Female</option>
        </select>
        
        <input type="date" name="dob" title="Date of Birth" required>
        <input name="age" placeholder="Age" required>
        <input name="bg" placeholder="Blood Group (e.g. O+, A-)">
        <input name="address" placeholder="Address">
        <input name="city" placeholder="City">
        <input name="state" placeholder="State">
        
        <button type="submit" name="add" class="btn" style="width: 100%; margin-top: 10px;">Add Patient</button>
    </form>

    <?php
    if(isset($_POST['add'])){
        $id = $conn->real_escape_string($_POST['id']);
        $name = $conn->real_escape_string($_POST['name']);
        $gender = $conn->real_escape_string($_POST['gender']);
        $dob = $conn->real_escape_string($_POST['dob']);
        $age = $conn->real_escape_string($_POST['age']);
        $bg = $conn->real_escape_string($_POST['bg']);
        $address = $conn->real_escape_string($_POST['address']);
        $city = $conn->real_escape_string($_POST['city']);
        $state = $conn->real_escape_string($_POST['state']);

        $patient_query = "INSERT INTO PATIENT 
        (Patient_ID, Name, Gender, DOB, Age, Blood_Group, Address, City, State)
        VALUES ('$id', '$name', '$gender', '$dob', '$age', '$bg', '$address', '$city', '$state')";

        if($conn->query($patient_query)){
            echo "
            <div style='margin-top:20px; padding:24px; background:rgba(74,222,128,0.08); border:1px solid rgba(74,222,128,0.3); border-radius:14px;'>
                <p style='color:#4ade80; font-weight:700; font-size:18px;'>&#x2705; Patient Added Successfully!</p>
                <p style='color:var(--text-secondary); margin-top:8px;'>
                    Patient ID: <strong style='color:#fff'>$id</strong> &nbsp;|&nbsp;
                    Name: <strong style='color:#fff'>$name</strong>
                </p>
                <p style='color:var(--text-secondary); margin-top:6px; font-size:14px;'>
                    You can now create a visit for this patient and assign a doctor:
                </p>
                <div style='display:flex; gap:12px; justify-content:center; margin-top:16px; flex-wrap:wrap;'>
                    <a href='add_visit.php?patient_id=$id'
                       style='display:inline-flex; align-items:center; gap:8px; text-decoration:none;
                              background:linear-gradient(45deg,#00f2fe,#4facfe); color:#0f172a;
                              font-weight:700; padding:12px 22px; border-radius:12px;
                              box-shadow:0 4px 15px rgba(0,242,254,0.3); transition:all .3s;'>
                        &#128197; Create Visit
                    </a>
                    <a href='visit_details.php?patient_id=$id'
                       style='display:inline-flex; align-items:center; gap:8px; text-decoration:none;
                              background:rgba(255,255,255,0.1); color:#fff;
                              font-weight:600; padding:12px 22px; border-radius:12px; transition:all .3s;'>
                        👤 View History
                    </a>
                </div>
            </div>";
        } else {
            echo "<p style='color: var(--danger-color); margin-top: 20px; font-weight: 600;'>&#x274C; Failed: " . $conn->error . "</p>";
        }
    }
    ?>
</div>

<?php include 'footer.php'; ?>