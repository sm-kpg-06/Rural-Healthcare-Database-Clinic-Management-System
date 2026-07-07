<?php 
session_start();
include 'db.php'; 
if(!isset($_SESSION['user'])){ header("Location: login.php"); exit(); }
include 'header.php';

$preVisitId   = isset($_GET['visit_id'])   ? intval($_GET['visit_id'])                          : '';
$prePatientId = isset($_GET['patient_id']) ? $conn->real_escape_string($_GET['patient_id'])      : '';
?>

<div class="card" style="max-width: 640px; margin-bottom: 40px;">
    <h1>🔬 Add Test</h1>
    <p class="subtitle">Assign a diagnostic test and record its result for a patient visit</p>

    <form method="POST" id="testForm">
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

        <input name="test_name" id="test_name" placeholder="Test Name (e.g. CBC, MRI, X-Ray)" required>

        <select name="result_status" id="result_status" style="margin-bottom:0;">
            <option value="" disabled selected>Result Status (optional)</option>
            <option value="Pending">Pending</option>
            <option value="Normal">Normal</option>
            <option value="Abnormal">Abnormal</option>
            <option value="Critical">Critical</option>
        </select>

        <input name="result_detail" id="result_detail" placeholder="Result Detail (e.g. Hemoglobin: 13.2 g/dL)">

        <button type="submit" name="add_test" class="btn" style="width:100%; margin-top:8px;">
            ➕ Save Test
        </button>
    </form>

    <?php
    // ── Handle form submission ──────────────────────────────────────────────
    if (isset($_POST['add_test'])) {
        $vid           = intval($_POST['visit_id']);
        $testName      = $conn->real_escape_string(trim($_POST['test_name']));
        $resultStatus  = $conn->real_escape_string(trim($_POST['result_status'] ?? 'Pending'));
        $resultDetail  = $conn->real_escape_string(trim($_POST['result_detail'] ?? ''));

        // Combined result string
        $result = $resultStatus . ($resultDetail ? ': ' . $resultDetail : '');

        // Validate visit exists
        $check = $conn->query("SELECT Visit_ID FROM VISIT WHERE Visit_ID = $vid");
        if (!$check || $check->num_rows === 0) {
            echo "<p style='color:var(--danger-color); margin-top:16px; font-weight:600;'>❌ Invalid Visit ID. Please select a valid visit.</p>";
        } else {
            // Auto-generate Test_ID
            $tidRes = $conn->query("SELECT COALESCE(MAX(Test_ID),0)+1 AS nid FROM TEST");
            $newTid = $tidRes->fetch_assoc()['nid'];

            $insTest = "INSERT INTO TEST (Test_ID, Test_Name, Result, Visit_ID)
                        VALUES ($newTid, '$testName', '$result', $vid)";

            if ($conn->query($insTest)) {
                echo "
                <div style='margin-top:20px; padding:20px; background:rgba(251,146,60,0.1); border:1px solid rgba(251,146,60,0.3); border-radius:12px;'>
                    <p style='color:#fb923c; font-weight:700; font-size:18px;'>✅ Test Recorded!</p>
                    <p style='color:var(--text-secondary); margin-top:8px;'>Test #$newTid saved for Visit #$vid</p>
                    <div style='display:flex; gap:10px; justify-content:center; margin-top:16px; flex-wrap:wrap;'>
                        <a href='add_test.php?visit_id=$vid' class='btn' style='max-width:220px; background:linear-gradient(45deg,#fb923c,#f97316); color:#0f172a;'>➕ Add Another Test</a>
                        <a href='add_prescription.php?visit_id=$vid' class='btn' style='max-width:220px;'>💊 Add Prescription for This Visit</a>
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

<!-- Existing Tests Panel -->
<?php
$filterVid = $preVisitId ?: (isset($_POST['visit_id']) ? intval($_POST['visit_id']) : null);
if ($filterVid): ?>
<div class="card" style="max-width: 800px; margin-bottom: 40px; text-align:left;">
    <h2 style="text-align:center; margin-bottom:20px;">📋 Tests for Visit #<?php echo $filterVid; ?></h2>
    <?php
    $existSql = "SELECT Test_ID, Test_Name, Result FROM TEST WHERE Visit_ID = $filterVid ORDER BY Test_ID";
    $existRes = $conn->query($existSql);
    if ($existRes && $existRes->num_rows > 0):
    ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>#ID</th>
                    <th>Test Name</th>
                    <th>Result</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $existRes->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['Test_ID']; ?></td>
                    <td><?php echo htmlspecialchars($row['Test_Name']); ?></td>
                    <td><?php echo htmlspecialchars($row['Result'] ?? 'Pending'); ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <p style="color:var(--text-secondary); text-align:center;">No tests yet for this visit.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>
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
