<?php 
session_start();
include 'db.php'; 
if(!isset($_SESSION['user'])){ header("Location: login.php"); exit(); }
include 'header.php';

// Collect visit_id from GET (pre-fill) or leave empty
$preVisitId = isset($_GET['visit_id']) ? intval($_GET['visit_id']) : '';
$prePatientId = isset($_GET['patient_id']) ? $conn->real_escape_string($_GET['patient_id']) : '';
?>

<div class="card" style="max-width: 640px; margin-bottom: 40px;">
    <h1>💊 Add Prescription</h1>
    <p class="subtitle">Assign medicines, dosage & duration to a patient visit</p>

    <form method="POST" id="prescForm">
        <!-- Visit selector -->
        <select name="visit_id" id="visit_id" required onchange="fetchVisitInfo(this.value)">
            <option value="" disabled <?php echo $preVisitId === '' ? 'selected' : ''; ?>>Select Visit</option>
            <?php
            $vsql = "SELECT v.Visit_ID, v.Visit_Date, p.Name AS PatientName
                     FROM VISIT v
                     JOIN PATIENT p ON v.Patient_ID = p.Patient_ID
                     ORDER BY v.Visit_Date DESC";
            $vres = $conn->query($vsql);
            if ($vres) {
                while ($vrow = $vres->fetch_assoc()) {
                    $sel = ($vrow['Visit_ID'] == $preVisitId) ? 'selected' : '';
                    echo "<option value='{$vrow['Visit_ID']}' $sel>Visit #{$vrow['Visit_ID']} — {$vrow['PatientName']} ({$vrow['Visit_Date']})</option>";
                }
            }
            ?>
        </select>

        <!-- Info box shown after visit selection -->
        <div id="visitInfo" style="
            background: rgba(0,242,254,0.07);
            border: 1px solid rgba(0,242,254,0.2);
            border-radius: 12px;
            padding: 12px 18px;
            width: 100%;
            text-align: left;
            font-size: 14px;
            color: var(--text-secondary);
            display: none;
        ">
            <span id="visitInfoText"></span>
        </div>

        <input name="medicines" id="medicines" placeholder="Medicines (e.g. Paracetamol, Ibuprofen)" required>
        <input name="dosage"    id="dosage"    placeholder="Dosage (e.g. 500mg twice daily)" required>
        <input name="duration"  id="duration"  placeholder="Duration (e.g. 7 days)" required>

        <button type="submit" name="add_prescription" class="btn" style="width:100%; margin-top:8px;">
            ➕ Save Prescription
        </button>
    </form>

    <?php
    // ── Handle form submission ──────────────────────────────────────────────
    if (isset($_POST['add_prescription'])) {
        $vid      = intval($_POST['visit_id']);
        $meds     = $conn->real_escape_string(trim($_POST['medicines']));
        $dosage   = $conn->real_escape_string(trim($_POST['dosage']));
        $duration = $conn->real_escape_string(trim($_POST['duration']));

        // Validate visit exists
        $check = $conn->query("SELECT Visit_ID FROM VISIT WHERE Visit_ID = $vid");
        if (!$check || $check->num_rows === 0) {
            echo "<p style='color:var(--danger-color); margin-top:16px; font-weight:600;'>❌ Invalid Visit ID. Please select a valid visit.</p>";
        } else {
            // Auto-generate Prescription_ID
            $pidRes = $conn->query("SELECT COALESCE(MAX(Prescription_ID),0)+1 AS nid FROM PRESCRIPTION");
            $newPid = $pidRes->fetch_assoc()['nid'];

            $insPresc = "INSERT INTO PRESCRIPTION (Prescription_ID, Dosage, Duration, Visit_ID)
                         VALUES ($newPid, '$dosage', '$duration', $vid)";

            if ($conn->query($insPresc)) {
                // Insert into PRESCRIPTION_MEDICINE junction table
                $medList = array_map('trim', explode(',', $meds));
                foreach ($medList as $med) {
                    $med = $conn->real_escape_string($med);
                    $conn->query("INSERT INTO PRESCRIPTION_MEDICINE (Prescription_ID, Medicines) VALUES ($newPid, '$med')");
                }

                echo "
                <div style='margin-top:20px; padding:20px; background:rgba(74,222,128,0.1); border:1px solid rgba(74,222,128,0.3); border-radius:12px;'>
                    <p style='color:#4ade80; font-weight:700; font-size:18px;'>✅ Prescription Added!</p>
                    <p style='color:var(--text-secondary); margin-top:8px;'>Prescription #$newPid saved for Visit #$vid</p>
                    <div style='display:flex; gap:10px; justify-content:center; margin-top:16px; flex-wrap:wrap;'>
                        <a href='add_prescription.php?visit_id=$vid' class='btn' style='max-width:220px; background:linear-gradient(45deg,#4ade80,#22c55e); color:#0f172a;'>➕ Add Another Prescription</a>
                        <a href='add_test.php?visit_id=$vid' class='btn' style='max-width:220px;'>🔬 Add Test for This Visit</a>
                        <a href='search_patient.php' class='btn' style='max-width:220px; background:rgba(255,255,255,0.1); color:#fff;'>🔍 View Patient Records</a>
                    </div>
                </div>";
            } else {
                echo "<p style='color:var(--danger-color); margin-top:16px; font-weight:600;'>❌ Failed: " . $conn->error . "</p>";
            }
        }
    }
    ?>
</div>

<!-- Existing Prescriptions Panel -->
<?php
$filterVid = $preVisitId ?: (isset($_POST['visit_id']) ? intval($_POST['visit_id']) : null);
if ($filterVid): ?>
<div class="card" style="max-width: 800px; margin-bottom: 40px; text-align:left;">
    <h2 style="text-align:center; margin-bottom:20px;">📋 Prescriptions for Visit #<?php echo $filterVid; ?></h2>
    <?php
    $existSql = "SELECT p.Prescription_ID, p.Dosage, p.Duration,
                        GROUP_CONCAT(pm.Medicines SEPARATOR ', ') AS Meds
                 FROM PRESCRIPTION p
                 LEFT JOIN PRESCRIPTION_MEDICINE pm ON p.Prescription_ID = pm.Prescription_ID
                 WHERE p.Visit_ID = $filterVid
                 GROUP BY p.Prescription_ID";
    $existRes = $conn->query($existSql);
    if ($existRes && $existRes->num_rows > 0):
    ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>#ID</th>
                    <th>Medicines</th>
                    <th>Dosage</th>
                    <th>Duration</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $existRes->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['Prescription_ID']; ?></td>
                    <td><?php echo htmlspecialchars($row['Meds'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($row['Dosage']); ?></td>
                    <td><?php echo htmlspecialchars($row['Duration']); ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <p style="color:var(--text-secondary); text-align:center;">No prescriptions yet for this visit.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>
// Auto-trigger info fetch if visit pre-selected
document.addEventListener('DOMContentLoaded', function(){
    const sel = document.getElementById('visit_id');
    if(sel.value) fetchVisitInfo(sel.value);
});

function fetchVisitInfo(vid) {
    if(!vid) return;
    fetch('get_visit_info.php?visit_id=' + vid)
        .then(r => r.json())
        .then(data => {
            if(data.success){
                document.getElementById('visitInfoText').innerHTML =
                    '📅 Date: <strong style="color:#fff">' + data.visit_date + '</strong> &nbsp;|&nbsp; ' +
                    '👤 Patient: <strong style="color:var(--accent-color)">' + data.patient_name + '</strong> &nbsp;|&nbsp; ' +
                    '🏥 Clinic: <strong style="color:#fff">' + (data.clinic_name || '—') + '</strong>';
                document.getElementById('visitInfo').style.display = 'block';
            }
        }).catch(()=>{});
}
</script>

<?php include 'footer.php'; ?>
