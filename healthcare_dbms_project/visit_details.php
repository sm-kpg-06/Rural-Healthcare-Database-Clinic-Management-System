<?php
session_start();
include 'db.php';
if(!isset($_SESSION['user'])){ header("Location: login.php"); exit(); }


include 'header.php';


$patient_filter = '';
$filterLabel    = '';
if (isset($_GET['patient_id']) && $_GET['patient_id'] !== '') {
    $fpid          = intval($_GET['patient_id']);
    $patient_filter = "WHERE v.Patient_ID = $fpid";
    $pr = $conn->query("SELECT Name FROM PATIENT WHERE Patient_ID=$fpid");
    if ($pr && $pr->num_rows > 0) {
        $filterLabel = ' — ' . $pr->fetch_assoc()['Name'];
    }
}
?>

<!-- Header Row -->
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:28px; flex-wrap:wrap; gap:12px;">
    <div>
        <h1 style="margin-bottom:4px;">📋 Visit Details<?php echo htmlspecialchars($filterLabel); ?></h1>
        <p class="subtitle" style="margin:0; font-size:15px;">Full relational view — Patient · Doctor · Clinic · Nurse · Rx · Tests</p>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <?php if ($patient_filter): ?>
            <a href="visit_details.php" class="btn" style="max-width:160px; font-size:14px; background:rgba(255,255,255,0.1); color:#fff;">
                ✖ Clear Filter
            </a>
        <?php endif; ?>
        <a href="add_visit.php" class="btn" style="max-width:180px; font-size:14px;">
            ➕ Add New Visit
        </a>
    </div>
</div>

<?php
/* ─── Master Query: Visit + Patient + Doctor + Clinic ─────────────────── */
$masterSQL = "
    SELECT
        v.Visit_ID,
        v.Visit_Date,
        vs.Symptoms,
        p.Patient_ID,
        p.Name          AS PatientName,
        p.Blood_Group,
        p.City,
        d.Doctor_ID,
        de.Name         AS DoctorName,
        d.Specialization,
        c.Clinic_ID,
        c.Clinic_Name,
        c.Location      AS ClinicLocation
    FROM VISIT v
    JOIN PATIENT p ON v.Patient_ID  = p.Patient_ID
    LEFT JOIN DOCTOR d  ON v.Doctor_ID   = d.Doctor_ID
    LEFT JOIN EMPLOYEE de ON d.Employee_ID = de.Employee_ID
    LEFT JOIN CLINIC  c  ON v.Clinic_ID   = c.Clinic_ID
    LEFT JOIN VISIT_SYMPTOMS vs ON v.Visit_ID = vs.Visit_ID
    $patient_filter
    ORDER BY v.Visit_Date DESC, v.Visit_ID DESC
";

$visits = $conn->query($masterSQL);

if (!$visits || $visits->num_rows === 0):
?>
    <div style="text-align:center; padding:60px 20px; color:var(--text-secondary);">
        <p style="font-size:48px;">🏥</p>
        <p style="font-size:20px; margin-top:16px;">No visits found.</p>
        <a href="add_visit.php" class="btn" style="max-width:200px; margin-top:20px;">Add First Visit</a>
    </div>

<?php else: ?>

<?php while($v = $visits->fetch_assoc()):
    $vid = $v['Visit_ID'];

    /* Nurses assigned to this visit (Gracefully handle if VISIT_NURSE table doesn't exist yet) */
    $nurses = [];
    try {
        $nurseQ = $conn->query(
            "SELECT n.Nurse_ID, e.Name AS NurseName
             FROM NURSE n
             JOIN EMPLOYEE e ON n.Employee_ID = e.Employee_ID
             JOIN VISIT_NURSE vn ON n.Nurse_ID = vn.Nurse_ID
             WHERE vn.Visit_ID = $vid"
        );
        if ($nurseQ) {
            while ($nr = $nurseQ->fetch_assoc()) $nurses[] = $nr['NurseName'];
        }
    } catch (Exception $e) {
        // Safe fallback if VISIT_NURSE junction table is missing
    }

    /* Prescription count */
    $rxQ   = $conn->query("SELECT COUNT(*) AS cnt FROM PRESCRIPTION WHERE Visit_ID = $vid");
    $rxCnt = ($rxQ) ? $rxQ->fetch_assoc()['cnt'] : 0;

    /* Test count */
    $testQ   = $conn->query("SELECT COUNT(*) AS cnt FROM TEST WHERE Visit_ID = $vid");
    $testCnt = ($testQ) ? $testQ->fetch_assoc()['cnt'] : 0;

    $incomplete = ($rxCnt == 0 || $testCnt == 0);
?>

<div style="
    background: var(--glass-bg);
    backdrop-filter: blur(16px);
    border: 1px solid <?php echo $incomplete ? 'rgba(251,146,60,0.4)' : 'rgba(74,222,128,0.3)'; ?>;
    border-radius: 20px;
    padding: 28px 32px;
    margin-bottom: 24px;
    max-width: 900px;
    margin-left: auto;
    margin-right: auto;
    position: relative;
">

    <?php if ($incomplete): ?>
    <div style="
        position:absolute; top:16px; right:16px;
        background:rgba(251,146,60,0.15); border:1px solid rgba(251,146,60,0.4);
        border-radius:8px; padding:4px 12px; font-size:12px; font-weight:700; color:#fb923c;">
        ⚠️ Incomplete
    </div>
    <?php else: ?>
    <div style="
        position:absolute; top:16px; right:16px;
        background:rgba(74,222,128,0.12); border:1px solid rgba(74,222,128,0.35);
        border-radius:8px; padding:4px 12px; font-size:12px; font-weight:700; color:#4ade80;">
        ✅ Complete
    </div>
    <?php endif; ?>

    <!-- Visit Header -->
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:18px;">
        <div style="
            background:linear-gradient(135deg,#00f2fe,#4facfe);
            color:#0f172a; font-weight:800; font-size:13px;
            padding:6px 14px; border-radius:20px;">
            Visit #<?php echo $vid; ?>
        </div>
        <span style="color:var(--text-secondary); font-size:14px;">
            📅 <?php echo htmlspecialchars($v['Visit_Date']); ?>
        </span>
    </div>

    <!-- Info Grid -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px; margin-bottom:18px;">

        <!-- Patient -->
        <div style="background:rgba(0,0,0,0.2); border-radius:12px; padding:14px;">
            <p style="color:var(--text-secondary); font-size:12px; margin-bottom:4px; text-transform:uppercase; letter-spacing:1px;">👤 Patient</p>
            <p style="font-weight:700; font-size:16px; color:#fff;"><?php echo htmlspecialchars($v['PatientName']); ?></p>
            <p style="color:var(--text-secondary); font-size:13px;">
                ID: <?php echo $v['Patient_ID']; ?>
                <?php if($v['City']) echo " | " . htmlspecialchars($v['City']); ?>
                <?php if($v['Blood_Group']) echo " | 🩸 " . htmlspecialchars($v['Blood_Group']); ?>
            </p>
        </div>

        <!-- Doctor -->
        <div style="background:rgba(0,0,0,0.2); border-radius:12px; padding:14px;
                    border-left: 3px solid var(--accent-color);">
            <p style="color:var(--text-secondary); font-size:12px; margin-bottom:4px; text-transform:uppercase; letter-spacing:1px;">🩺 Doctor</p>
            <?php if ($v['DoctorName']): ?>
                <p style="font-weight:700; font-size:16px; color:var(--accent-color);">
                    <?php echo htmlspecialchars($v['DoctorName']); ?>
                </p>
                <p style="color:var(--text-secondary); font-size:13px;">
                    ID: <?php echo $v['Doctor_ID']; ?>
                    <?php if($v['Specialization']) echo " | " . htmlspecialchars($v['Specialization']); ?>
                </p>
            <?php else: ?>
                <p style="color:#fb923c; font-size:14px; font-weight:600;">⚠️ No Doctor Assigned</p>
            <?php endif; ?>
        </div>

        <!-- Clinic -->
        <div style="background:rgba(0,0,0,0.2); border-radius:12px; padding:14px;">
            <p style="color:var(--text-secondary); font-size:12px; margin-bottom:4px; text-transform:uppercase; letter-spacing:1px;">🏥 Clinic</p>
            <?php if ($v['Clinic_Name']): ?>
                <p style="font-weight:700; font-size:16px; color:#fff;"><?php echo htmlspecialchars($v['Clinic_Name']); ?></p>
                <p style="color:var(--text-secondary); font-size:13px;">
                    <?php if($v['ClinicLocation']) echo htmlspecialchars($v['ClinicLocation']); ?>
                </p>
            <?php else: ?>
                <p style="color:var(--text-secondary); font-size:14px;">—</p>
            <?php endif; ?>
        </div>

        <!-- Symptoms -->
        <?php if ($v['Symptoms']): ?>
        <div style="background:rgba(0,0,0,0.2); border-radius:12px; padding:14px;">
            <p style="color:var(--text-secondary); font-size:12px; margin-bottom:4px; text-transform:uppercase; letter-spacing:1px;">🤒 Symptoms</p>
            <p style="font-size:14px; color:#fff;"><?php echo htmlspecialchars($v['Symptoms']); ?></p>
        </div>
        <?php endif; ?>

        <!-- Nurses -->
        <div style="background:rgba(0,0,0,0.2); border-radius:12px; padding:14px;">
            <p style="color:var(--text-secondary); font-size:12px; margin-bottom:4px; text-transform:uppercase; letter-spacing:1px;">👩‍⚕️ Nurses</p>
            <?php if (count($nurses) > 0): ?>
                <p style="font-size:14px; color:#fff;"><?php echo implode(', ', array_map('htmlspecialchars', $nurses)); ?></p>
            <?php else: ?>
                <p style="color:var(--text-secondary); font-size:13px;">None assigned</p>
            <?php endif; ?>
        </div>

    </div>

    <!-- Prescription + Test Status Row -->
    <div style="display:flex; gap:12px; margin-bottom:18px; flex-wrap:wrap;">

        <div style="
            flex:1; min-width:160px;
            background:<?php echo $rxCnt > 0 ? 'rgba(74,222,128,0.1)' : 'rgba(239,68,68,0.08)'; ?>;
            border:1px solid <?php echo $rxCnt > 0 ? 'rgba(74,222,128,0.3)' : 'rgba(239,68,68,0.25)'; ?>;
            border-radius:12px; padding:12px 16px; text-align:center;">
            <p style="font-size:22px; margin-bottom:4px;"><?php echo $rxCnt > 0 ? '💊' : '❌'; ?></p>
            <p style="font-weight:700; color:<?php echo $rxCnt > 0 ? '#4ade80' : 'var(--danger-color)'; ?>; font-size:14px;">
                Prescription
            </p>
            <p style="color:var(--text-secondary); font-size:13px;">
                <?php echo $rxCnt > 0 ? "$rxCnt added" : 'Not added'; ?>
            </p>
        </div>

        <div style="
            flex:1; min-width:160px;
            background:<?php echo $testCnt > 0 ? 'rgba(251,146,60,0.1)' : 'rgba(239,68,68,0.08)'; ?>;
            border:1px solid <?php echo $testCnt > 0 ? 'rgba(251,146,60,0.35)' : 'rgba(239,68,68,0.25)'; ?>;
            border-radius:12px; padding:12px 16px; text-align:center;">
            <p style="font-size:22px; margin-bottom:4px;"><?php echo $testCnt > 0 ? '🔬' : '❌'; ?></p>
            <p style="font-weight:700; color:<?php echo $testCnt > 0 ? '#fb923c' : 'var(--danger-color)'; ?>; font-size:14px;">
                Tests
            </p>
            <p style="color:var(--text-secondary); font-size:13px;">
                <?php echo $testCnt > 0 ? "$testCnt recorded" : 'Not added'; ?>
            </p>
        </div>

    </div>

    <!-- Action Buttons -->
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a href="add_prescription.php?visit_id=<?php echo $vid; ?>"
           style="display:inline-flex;align-items:center;gap:6px;text-decoration:none;
                  background:linear-gradient(45deg,#00f2fe,#4facfe);color:#0f172a;
                  font-weight:700;font-size:13px;padding:9px 18px;border-radius:10px;
                  box-shadow:0 3px 10px rgba(0,242,254,0.25);">
            💊 <?php echo $rxCnt > 0 ? 'Update Rx' : 'Add Prescription'; ?>
        </a>
        <a href="add_test.php?visit_id=<?php echo $vid; ?>"
           style="display:inline-flex;align-items:center;gap:6px;text-decoration:none;
                  background:linear-gradient(45deg,#fb923c,#f97316);color:#0f172a;
                  font-weight:700;font-size:13px;padding:9px 18px;border-radius:10px;
                  box-shadow:0 3px 10px rgba(251,146,60,0.25);">
            🔬 <?php echo $testCnt > 0 ? 'Update Test' : 'Add Test'; ?>
        </a>
        <a href="generate_bill.php?visit_id=<?php echo $vid; ?>"
           style="display:inline-flex;align-items:center;gap:6px;text-decoration:none;
                  background:rgba(255,255,255,0.08);color:var(--accent-color);
                  font-weight:600;font-size:13px;padding:9px 18px;border-radius:10px;
                  border:1px solid rgba(0,242,254,0.2);">
            🧾 Bill
        </a>
        <a href="visit_details.php?patient_id=<?php echo $v['Patient_ID']; ?>"
           style="display:inline-flex;align-items:center;gap:6px;text-decoration:none;
                  background:rgba(255,255,255,0.05);color:var(--text-secondary);
                  font-weight:600;font-size:13px;padding:9px 18px;border-radius:10px;
                  border:1px solid rgba(255,255,255,0.1);">
            👤 Patient Visits
        </a>
    </div>

</div>

<?php endwhile; ?>
<?php endif; ?>

<?php include 'footer.php'; ?>