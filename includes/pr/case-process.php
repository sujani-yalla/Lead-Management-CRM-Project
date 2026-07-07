<?php
// Load core case data
$coreStmt = $conn->prepare("SELECT * FROM pr_case_details WHERE id = ?");
$coreStmt->bind_param("i", $case_id);
$coreStmt->execute();
$core = $coreStmt->get_result()->fetch_assoc();

// Load process data
$processStmt = $conn->prepare("SELECT * FROM pr_case_process WHERE case_id = ?");
$processStmt->bind_param("i", $case_id);
$processStmt->execute();
$process = $processStmt->get_result()->fetch_assoc();

// If process row doesn't exist, create one
if (!$process) {
    $createProcess = $conn->prepare("INSERT INTO pr_case_process (case_id) VALUES (?)");
    $createProcess->bind_param("i", $case_id);
    $createProcess->execute();
    $processStmt->execute();
    $process = $processStmt->get_result()->fetch_assoc();
}
$success_message = "";
if (isset($_GET['success'])) {
    $success_message = "Process details saved successfully.";
}


/* ================= SAVE PROCESS ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_process'])) {

    // Update core case
    $updateCore = $conn->prepare("
        UPDATE pr_case_details
        SET eligibility_result = ?,
            points = ?,
            agreement_signed = ?,
            current_stage = ?
        WHERE id = ?
    ");
    $updateCore->bind_param(
        "sissi",
        $_POST['eligibility_result'],
        $_POST['points'],
        $_POST['agreement_signed'],
        $_POST['current_stage'],
        $case_id
    );
    $updateCore->execute();

    // Update process table
    $updateProcess = $conn->prepare("
        UPDATE pr_case_process
        SET program_stream = ?,
            state_nomination = ?,
            eoi_profile_id = ?,
            profile_submission_date = ?,
            invitation_received_date = ?,
            medical_status = ?,
            pcc_india_status = ?,
            pcc_other_status = ?,
            application_submitted_date = ?,
            application_reference_number = ?,
            decision_status = ?,
            visa_grant_date = ?,
            visa_expiry_date = ?,
            stage_updated_at = NOW(),
            stage_updated_by = ?
        WHERE case_id = ?
    ");

    $updateProcess->bind_param(
        "sssssssssssssii",
        $_POST['program_stream'],
        $_POST['state_nomination'],
        $_POST['eoi_profile_id'],
        $_POST['profile_submission_date'],
        $_POST['invitation_received_date'],
        $_POST['medical_status'],
        $_POST['pcc_india_status'],
        $_POST['pcc_other_status'],
        $_POST['application_submitted_date'],
        $_POST['application_reference_number'],
        $_POST['decision_status'],
        $_POST['visa_grant_date'],
        $_POST['visa_expiry_date'],
        $_SESSION['user_id'],
        $case_id
    );

    $updateProcess->execute();

    if (in_array($_POST['current_stage'], ['Approved', 'Rejected'])) {

    $syncDecision = $conn->prepare("
        UPDATE pr_case_process
        SET decision_status = ?
        WHERE case_id = ?
    ");

    $syncDecision->bind_param("si", $_POST['current_stage'], $case_id);
    $syncDecision->execute();
}

    header("Location: manage-case.php?lead_id=".$lead_id."&tab=process&success=1");
    exit;
}
?>

    <div class="container-fluid py-4">

    <?php if (!empty($success_message)) : ?>
<div class="alert alert-success">
    <?= $success_message ?>
</div>
<?php endif; ?>

<form method="POST" action="manage-case.php?lead_id=<?= $lead_id ?>&tab=process">

<div class="row g-4">

    <!-- ================= ELIGIBILITY ================= -->
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="mb-3">Eligibility & Agreement</h5>

                <div class="mb-3">
                    <label>Eligibility Result</label>
                    <select name="eligibility_result" class="form-control">
                        <option value="">Select</option>
                        <option value="Eligible" <?= ($core['eligibility_result'] ?? '') == 'Eligible' ? 'selected' : '' ?>>Eligible</option>
                        <option value="Not Eligible" <?= ($core['eligibility_result'] ?? '') == 'Not Eligible' ? 'selected' : '' ?>>Not Eligible</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label>Points</label>
                    <input type="number" name="points" class="form-control"
                        value="<?= htmlspecialchars($core['points'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label>Agreement Signed</label>
                    <select name="agreement_signed" class="form-control">
                        <option value="Yes" <?= ($core['agreement_signed'] ?? '') == 'Yes' ? 'selected' : '' ?>>Yes</option>
                        <option value="No" <?= ($core['agreement_signed'] ?? '') == 'No' ? 'selected' : '' ?>>No</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label>Current Stage</label>
                    <select name="current_stage" class="form-control">
                        <?php
                        $stages = [
                            "Eligibility Check",
                            "Documents Pending",
                            "Documents Verified",
                            "Language Test Completed",
                            "Skill Assessment / ECA",
                            "Profile / EOI Submitted",
                            "State Nomination / PNP",
                            "Invitation Received",
                            "PR Application Filed",
                            "Medical & PCC",
                            "Decision Awaited",
                            "Approved",
                            "Rejected",
                            "Closed"
                        ];
                        foreach ($stages as $stage) {
                            $selected = ($core['current_stage'] ?? '') == $stage ? 'selected' : '';
                            echo "<option value=\"$stage\" $selected>$stage</option>";
                        }
                        ?>
                    </select>
                </div>

            </div>
        </div>
    </div>

    <!-- ================= PROGRAM & PROFILE ================= -->
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="mb-3">Program & Profile</h5>

                <div class="mb-3">
                    <label>Program / Stream</label>
                    <input type="text" name="program_stream" class="form-control"
                        value="<?= htmlspecialchars($process['program_stream'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label>State Nomination / PNP</label>
                    <input type="text" name="state_nomination" class="form-control"
                        value="<?= htmlspecialchars($process['state_nomination'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label>EOI / Profile ID</label>
                    <input type="text" name="eoi_profile_id" class="form-control"
                        value="<?= htmlspecialchars($process['eoi_profile_id'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label>Profile Submission Date</label>
                    <input type="date" name="profile_submission_date" class="form-control"
                        value="<?= $process['profile_submission_date'] ?? '' ?>">
                </div>

                <div class="mb-3">
                    <label>Invitation Received Date</label>
                    <input type="date" name="invitation_received_date" class="form-control"
                        value="<?= $process['invitation_received_date'] ?? '' ?>">
                </div>

            </div>
        </div>
    </div>

    <!-- ================= MEDICAL & PCC ================= -->
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="mb-3">Medical & PCC</h5>

                <div class="mb-3">
                    <label>Medical Status</label>
                    <select name="medical_status" class="form-control">
                        <option value="Pending" <?= ($process['medical_status'] ?? '') == 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Done" <?= ($process['medical_status'] ?? '') == 'Done' ? 'selected' : '' ?>>Done</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label>PCC India Status</label>
                    <input type="text" name="pcc_india_status" class="form-control"
                        value="<?= htmlspecialchars($process['pcc_india_status'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label>PCC Other Country Status</label>
                    <input type="text" name="pcc_other_status" class="form-control"
                        value="<?= htmlspecialchars($process['pcc_other_status'] ?? '') ?>">
                </div>

            </div>
        </div>
    </div>

    <!-- ================= APPLICATION & DECISION ================= -->
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="mb-3">Application & Decision</h5>

                <div class="mb-3">
                    <label>Application Submitted Date</label>
                    <input type="date" name="application_submitted_date" class="form-control"
                        value="<?= $process['application_submitted_date'] ?? '' ?>">
                </div>

                <div class="mb-3">
                    <label>Application Reference Number</label>
                    <input type="text" name="application_reference_number" class="form-control"
                        value="<?= htmlspecialchars($process['application_reference_number'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label>Decision Status</label>
                    <select name="decision_status" class="form-control">
                        <option value="">Select</option>
                        <option value="Approved" <?= ($process['decision_status'] ?? '') == 'Approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="Rejected" <?= ($process['decision_status'] ?? '') == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label>Visa Grant Date</label>
                    <input type="date" name="visa_grant_date" class="form-control"
                        value="<?= $process['visa_grant_date'] ?? '' ?>">
                </div>

                <div class="mb-3">
                    <label>Visa Expiry Date</label>
                    <input type="date" name="visa_expiry_date" class="form-control"
                        value="<?= $process['visa_expiry_date'] ?? '' ?>">
                </div>

            </div>
        </div>
    </div>

</div>

<div class="text-end mt-4">
    <button type="submit" name="save_process" class="btn btn-primary px-5">
        Save Process
    </button>
</div>

</form>
</div>

