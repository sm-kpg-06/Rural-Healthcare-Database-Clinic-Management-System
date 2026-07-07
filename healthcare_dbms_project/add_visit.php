<?php 
session_start();
include 'db.php'; 
if(!isset($_SESSION['user'])){ header("Location: login.php"); exit(); }
include 'header.php';

/* ─── Auto-Generate Visit ID ────────────────────────────────────────────── */
$nextVidQuery = $conn->query("SELECT MAX(Visit_ID) AS max_vid FROM VISIT");
$nextVidRow = $nextVidQuery->fetch_assoc();
$nextVisitId = ($nextVidRow['max_vid'] !== null) ? $nextVidRow['max_vid'] + 1 : 1;

/* ─── Smart Doctor Suggestion ───────────────────────────────────────────── */
$prevDoctor     = null;
$prevDoctorName = '';
$patientHasVisits = false;

if (isset($_GET['patient_id']) && $_GET['patient_id'] !== '') {
    $gPid = $conn->real_escape_string($_GET['patient_id']);
    $prevQ = $conn->query(
        "SELECT d.Doctor_ID, e.Name AS DName
         FROM VISIT v
         JOIN DOCTOR d ON v.Doctor_ID = d.Doctor_ID
         JOIN EMPLOYEE e ON d.Employee_ID = e.Employee_ID
         WHERE v.Patient_ID = '$gPid'
         ORDER BY v.Visit_Date DESC LIMIT 1"
    );
    if ($prevQ && $prevQ->num_rows > 0) {
        $prevDoc         = $prevQ->fetch_assoc();
        $prevDoctor      = $prevDoc['Doctor_ID'];
        $prevDoctorName  = $prevDoc['DName'];
        $patientHasVisits = true;
    }
}
?>

<div class="card" style="max-width: 700px; margin-bottom: 40px;">
    <h1>📅 Add Visit</h1>
    <p class="subtitle">Record a new patient visit — link Doctor, Clinic & Symptoms</p>

    <form method="POST" id="visitForm">

        <!-- Visit ID -->
        <input name="visit_id" id="visit_id" value="<?php echo $nextVisitId; ?>" readonly required title="Auto-Generated Visit ID"
               style="width:100%; cursor:not-allowed; opacity:0.8;">

        <!-- Visit Date -->
        <input type="date" name="visit_date" id="visit_date" required title="Visit Date" style="width:100%;">

        <!-- Patient Dropdown -->
        <select name="patient_id" id="patient_id" required onchange="loadDoctorSuggestion(this.value)">
            <option value="" disabled selected>👤 Select Patient</option>
            <?php
            $pres = $conn->query("SELECT Patient_ID, Name FROM PATIENT ORDER BY Name");
            if ($pres) {
                while ($pr = $pres->fetch_assoc()) {
                    $sel = (isset($_GET['patient_id']) && $_GET['patient_id'] == $pr['Patient_ID']) ? 'selected' : '';
                    echo "<option value='{$pr['Patient_ID']}' $sel>{$pr['Patient_ID']} — {$pr['Name']}</option>";
                }
            }
            ?>
        </select>

        <!-- Smart Doctor Suggestion Banner -->
        <div id="doctorBanner" style="
            display:<?php echo $patientHasVisits ? 'block' : 'none'; ?>;
            background:rgba(0,242,254,0.07);
            border:1px solid rgba(0,242,254,0.25);
            border-radius:12px; padding:12px 16px;
            width:100%; text-align:left; font-size:14px;">
            <span style="color:var(--accent-color); font-weight:600;">💡 Previous Doctor Found:</span>
            <span id="prevDocName" style="color:#fff; margin-left:6px;">
                <?php echo htmlspecialchars($prevDoctorName); ?>
            </span><br>
            <label style="margin-top:8px; display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
                <input type="checkbox" id="usePrevDoc" onchange="togglePrevDoc(this)"
                       style="width:auto; accent-color:var(--accent-color);">
                Use previous doctor
                <strong id="prevDocLabel" style="color:var(--accent-color);">
                    <?php echo htmlspecialchars($prevDoctorName); ?>
                </strong>
            </label>
        </div>

        <!-- Doctor Dropdown -->
        <div style="width:100%;">
            <select name="doctor_id" id="doctor_id" required style="width:100%;">
                <option value="" disabled selected>🩺 Select Doctor (Required)</option>
                <?php
                $dres = $conn->query("SELECT d.Doctor_ID, e.Name, d.Specialization 
                                      FROM DOCTOR d 
                                      JOIN EMPLOYEE e ON d.Employee_ID = e.Employee_ID 
                                      ORDER BY e.Name");
                if ($dres) {
                    while ($dr = $dres->fetch_assoc()) {
                        $sel = ($prevDoctor && $prevDoctor == $dr['Doctor_ID']) ? '' : '';
                        $spec = $dr['Specialization'] ? " [{$dr['Specialization']}]" : '';
                        echo "<option value='{$dr['Doctor_ID']}' $sel>{$dr['Doctor_ID']} — {$dr['Name']}{$spec}</option>";
                    }
                }
                ?>
            </select>
            <p id="newPatientMsg" style="
                color:var(--text-secondary); font-size:13px;
                margin-top:6px; text-align:left;
                display:<?php echo (!$patientHasVisits && isset($_GET['patient_id'])) ? 'block' : 'none'; ?>;">
                🆕 New patient — please select a doctor to consult.
            </p>
        </div>

        <!-- Clinic Dropdown -->
        <select name="clinic_id" id="clinic_id" required style="width:100%;">
            <option value="" disabled selected>🏥 Select Clinic</option>
            <?php
            $cres = $conn->query("SELECT Clinic_ID, Clinic_Name, Location FROM CLINIC ORDER BY Clinic_Name");
            if ($cres) {
                while ($cr = $cres->fetch_assoc()) {
                    $loc = $cr['Location'] ? " — {$cr['Location']}" : '';
                    echo "<option value='{$cr['Clinic_ID']}'>{$cr['Clinic_ID']} — {$cr['Clinic_Name']}{$loc}</option>";
                }
            }
            ?>
        </select>

        <!-- Symptoms -->
        <textarea name="symptoms" id="symptoms" placeholder="Symptoms (e.g. Fever, Headache, Cough...)"
                  rows="3" style="
                    width:100%; padding:14px 20px; border-radius:12px;
                    border:1px solid var(--glass-border); background:rgba(0,0,0,0.2);
                    color:var(--text-primary); font-size:16px;
                    font-family:'Outfit',sans-serif; outline:none; resize:vertical;
                    transition:border-color .3s;"></textarea>

        <button type="submit" name="add_visit" class="btn" style="width:100%; margin-top:8px;">
            ➕ Add Visit
        </button>
    </form>

    <?php
    /* ─── Handle Submission ─────────────────────────────────────────────── */
    if (isset($_POST['add_visit'])) {
        $vid      = intval($_POST['visit_id']);
        $date     = $conn->real_escape_string($_POST['visit_date']);
        $pid      = $conn->real_escape_string($_POST['patient_id']);
        $did      = intval($_POST['doctor_id']);
        $cid      = intval($_POST['clinic_id']);
        $symptoms = $conn->real_escape_string(trim($_POST['symptoms'] ?? ''));

        // Validate all FKs
        $p = $conn->query("SELECT Patient_ID FROM PATIENT WHERE Patient_ID='$pid'");
        $d = $conn->query("SELECT Doctor_ID  FROM DOCTOR  WHERE Doctor_ID=$did");
        $c = $conn->query("SELECT Clinic_ID  FROM CLINIC  WHERE Clinic_ID=$cid");

        if (!$p || $p->num_rows === 0) {
            echo "<p style='color:var(--danger-color); margin-top:16px; font-weight:600;'>❌ Invalid Patient selected.</p>";
        } elseif (!$d || $d->num_rows === 0) {
            echo "<p style='color:var(--danger-color); margin-top:16px; font-weight:600;'>❌ Invalid Doctor selected.</p>";
        } elseif (!$c || $c->num_rows === 0) {
            echo "<p style='color:var(--danger-color); margin-top:16px; font-weight:600;'>❌ Invalid Clinic selected.</p>";
        } else {
            $sql = "INSERT INTO VISIT (Visit_ID, Visit_Date, Patient_ID, Doctor_ID, Clinic_ID)
                    VALUES ($vid, '$date', '$pid', $did, $cid)";
            if ($conn->query($sql)) {
                if (!empty($symptoms)) {
                    $conn->query("INSERT INTO VISIT_SYMPTOMS (Visit_ID, Symptoms) VALUES ($vid, '$symptoms')");
                }
                echo "
                <div style='margin-top:20px; padding:24px; background:rgba(79,172,254,0.08);
                            border:1px solid rgba(79,172,254,0.3); border-radius:14px;'>
                    <p style='color:#4facfe; font-weight:700; font-size:18px;'>&#x2705; Visit Added!</p>
                    <p style='color:var(--text-secondary); margin-top:8px;'>
                        Visit ID: <strong style='color:var(--accent-color)'>$vid</strong> &nbsp;|&nbsp;
                        Patient ID: <strong style='color:#fff'>$pid</strong> &nbsp;|&nbsp;
                        Date: <strong style='color:#fff'>$date</strong>
                    </p>
                    <div style='display:flex; gap:12px; justify-content:center; margin-top:16px; flex-wrap:wrap;'>
                        <a href='add_prescription.php?visit_id=$vid'
                           style='display:inline-flex;align-items:center;gap:8px;text-decoration:none;
                                  background:linear-gradient(45deg,#00f2fe,#4facfe);color:#0f172a;
                                  font-weight:700;padding:12px 22px;border-radius:12px;
                                  box-shadow:0 4px 15px rgba(0,242,254,0.3);'>
                            &#128138; Add Prescription
                        </a>
                        <a href='add_test.php?visit_id=$vid'
                           style='display:inline-flex;align-items:center;gap:8px;text-decoration:none;
                                  background:linear-gradient(45deg,#fb923c,#f97316);color:#0f172a;
                                  font-weight:700;padding:12px 22px;border-radius:12px;
                                  box-shadow:0 4px 15px rgba(251,146,60,0.3);'>
                            &#128300; Add Test
                        </a>
                        <a href='visit_details.php'
                           style='display:inline-flex;align-items:center;gap:8px;text-decoration:none;
                                  background:rgba(255,255,255,0.1);color:#fff;
                                  font-weight:600;padding:12px 22px;border-radius:12px;'>
                            &#128203; View All Visits
                        </a>
                    </div>
                </div>";
            } else {
                echo "<p style='color:var(--danger-color); margin-top:16px; font-weight:600;'>❌ Failed: " . $conn->error . "</p>";
            }
        }
    }
    ?>
</div>

<script>
/* ── Auto-set today's date ─────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function(){
    const d = document.getElementById('visit_date');
    if (!d.value) d.value = new Date().toISOString().substring(0,10);

    // If patient already pre-selected via GET (from add_patient redirect)
    const sel = document.getElementById('patient_id');
    if(sel.value) loadDoctorSuggestion(sel.value);
});

/* ── Smart Doctor Suggestion via AJAX ─────────────────────────────── */
function loadDoctorSuggestion(patientId) {
    if (!patientId) return;
    fetch('get_prev_doctor.php?patient_id=' + encodeURIComponent(patientId))
        .then(r => r.json())
        .then(data => {
            const banner  = document.getElementById('doctorBanner');
            const newMsg  = document.getElementById('newPatientMsg');
            const docSel  = document.getElementById('doctor_id');
            const chk     = document.getElementById('usePrevDoc');

            chk.checked = false;

            if (data.found) {
                document.getElementById('prevDocName').textContent  = data.doctor_name;
                document.getElementById('prevDocLabel').textContent = data.doctor_name;
                banner.style.display  = 'block';
                newMsg.style.display  = 'none';
            } else {
                banner.style.display  = 'none';
                newMsg.style.display  = 'block';
                // Reset dropdown
                docSel.value = '';
            }
        }).catch(()=>{});
}

/* ── "Use previous doctor" checkbox ───────────────────────────────── */
function togglePrevDoc(checkbox) {
    fetch('get_prev_doctor.php?patient_id=' +
          encodeURIComponent(document.getElementById('patient_id').value))
        .then(r => r.json())
        .then(data => {
            const docSel = document.getElementById('doctor_id');
            if (checkbox.checked && data.found) {
                docSel.value = data.doctor_id;
            } else {
                docSel.value = '';
            }
        });
}
</script>

<?php include 'footer.php'; ?>