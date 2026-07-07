<?php
$success_message = "";

/* ================= SAVE PERSONAL ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_personal'])) {

    // Check if row exists
    $checkStmt = $conn->prepare("SELECT id FROM pr_clients WHERE lead_id = ?");
    $checkStmt->bind_param("i", $lead_id);
    $checkStmt->execute();
    $existing = $checkStmt->get_result()->fetch_assoc();

    if ($existing) {

        $updatePersonal = $conn->prepare("
            UPDATE pr_clients
            SET full_name_passport = ?,
                date_of_birth = ?,
                gender = ?,
                passport_number = ?,
                passport_expiry = ?,
                address = ?,
                marital_status = ?,
                dependents = ?
            WHERE lead_id = ?
        ");

        $updatePersonal->bind_param(
            "ssssssssi",
            $_POST['full_name_passport'],
            $_POST['date_of_birth'],
            $_POST['gender'],
            $_POST['passport_number'],
            $_POST['passport_expiry'],
            $_POST['address'],
            $_POST['marital_status'],
            $_POST['dependents'],
            $lead_id
        );

        $updatePersonal->execute();

    } else {

        $insertPersonal = $conn->prepare("
            INSERT INTO pr_clients
            (lead_id, full_name_passport, date_of_birth, gender,
             passport_number, passport_expiry, address,
             marital_status, dependents)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $insertPersonal->bind_param(
            "issssssss",
            $lead_id,
            $_POST['full_name_passport'],
            $_POST['date_of_birth'],
            $_POST['gender'],
            $_POST['passport_number'],
            $_POST['passport_expiry'],
            $_POST['address'],
            $_POST['marital_status'],
            $_POST['dependents']
        );

        $insertPersonal->execute();
    }

    header("Location: manage-case.php?lead_id=".$lead_id."&tab=personal&success=1");
    exit;
}

/* ================= LOAD PERSONAL DATA ================= */
$personalStmt = $conn->prepare("
    SELECT *
    FROM pr_clients
    WHERE lead_id = ?
");
$personalStmt->bind_param("i", $lead_id);
$personalStmt->execute();
$personal = $personalStmt->get_result()->fetch_assoc();

/* ================= SUCCESS MESSAGE ================= */
if (isset($_GET['success'])) {
    $success_message = "Personal details saved successfully.";
}
?>

<div class="container-fluid py-4">

<?php if (!empty($success_message)) : ?>
<div class="alert alert-success">
    <?= $success_message ?>
</div>
<?php endif; ?>

<form method="POST" action="manage-case.php?lead_id=<?= $lead_id ?>&tab=personal">

<div class="row g-4">

    <!-- ================= PERSONAL DETAILS ================= -->
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="mb-3">Personal & Identity</h5>

                <div class="mb-3">
                    <label>Full Name (As per Passport)</label>
                    <input type="text" name="full_name_passport" class="form-control"
                        value="<?= htmlspecialchars($personal['full_name_passport'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label>Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control"
                        value="<?= $personal['date_of_birth'] ?? '' ?>">
                </div>

                <div class="mb-3">
                    <label>Gender</label>
                    <select name="gender" class="form-control">
                        <option value="">Select</option>
                        <option value="Male" <?= ($personal['gender'] ?? '') == 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= ($personal['gender'] ?? '') == 'Female' ? 'selected' : '' ?>>Female</option>
                        <option value="Other" <?= ($personal['gender'] ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label>Passport Number</label>
                    <input type="text" name="passport_number" class="form-control"
                        value="<?= htmlspecialchars($personal['passport_number'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label>Passport Expiry</label>
                    <input type="date" name="passport_expiry" class="form-control"
                        value="<?= $personal['passport_expiry'] ?? '' ?>">
                </div>

            </div>
        </div>
    </div>

    <!-- ================= FAMILY DETAILS ================= -->
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="mb-3">Family & Address</h5>

                <div class="mb-3">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($personal['address'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <label>Marital Status</label>
                    <select name="marital_status" class="form-control">
                        <option value="">Select</option>
                        <option value="Single" <?= ($personal['marital_status'] ?? '') == 'Single' ? 'selected' : '' ?>>Single</option>
                        <option value="Married" <?= ($personal['marital_status'] ?? '') == 'Married' ? 'selected' : '' ?>>Married</option>
                        <option value="Divorced" <?= ($personal['marital_status'] ?? '') == 'Divorced' ? 'selected' : '' ?>>Divorced</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label>Dependents (Spouse / Children Details)</label>
                    <textarea name="dependents" class="form-control" rows="3"><?= htmlspecialchars($personal['dependents'] ?? '') ?></textarea>
                </div>

            </div>
        </div>
    </div>

</div>

<div class="text-end mt-4">
    <button type="submit" name="save_personal" class="btn btn-primary px-5">
        Save Personal Details
    </button>
</div>

</form>
</div>