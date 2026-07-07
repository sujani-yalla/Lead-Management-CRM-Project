<?php
$success_message = "";

/* ================= HANDLE SAVE ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_language'])) {

    // First check if record exists
    $checkStmt = $conn->prepare("SELECT id FROM pr_case_language WHERE case_id = ?");
    $checkStmt->bind_param("i", $case_id);
    $checkStmt->execute();
    $existing = $checkStmt->get_result()->fetch_assoc();

    if ($existing) {

        // UPDATE
        $update = $conn->prepare("
            UPDATE pr_case_language
            SET test_type = ?,
                trf_number = ?,
                listening = ?,
                reading = ?,
                writing = ?,
                speaking = ?,
                overall_score = ?,
                valid_till = ?
            WHERE case_id = ?
        ");

        $update->bind_param(
            "ssssssssi",
            $_POST['test_type'],
            $_POST['trf_number'],
            $_POST['listening'],
            $_POST['reading'],
            $_POST['writing'],
            $_POST['speaking'],
            $_POST['overall_score'],
            $_POST['valid_till'],
            $case_id
        );

        $update->execute();

    } else {

        // INSERT
        $insert = $conn->prepare("
            INSERT INTO pr_case_language
            (case_id, test_type, trf_number, listening, reading, writing, speaking, overall_score, valid_till)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $insert->bind_param(
            "issssssss",
            $case_id,
            $_POST['test_type'],
            $_POST['trf_number'],
            $_POST['listening'],
            $_POST['reading'],
            $_POST['writing'],
            $_POST['speaking'],
            $_POST['overall_score'],
            $_POST['valid_till']
        );

        $insert->execute();
    }

    // Redirect to avoid resubmission
    header("Location: manage-case.php?lead_id=".$lead_id."&tab=language&success=1");
    exit;
}

/* ================= FETCH RECORD ================= */
$stmt = $conn->prepare("
    SELECT *
    FROM pr_case_language
    WHERE case_id = ?
");
$stmt->bind_param("i", $case_id);
$stmt->execute();
$language = $stmt->get_result()->fetch_assoc();

/* ================= SUCCESS MESSAGE ================= */
if (isset($_GET['success'])) {
    $success_message = "Language details saved successfully.";
}
?>

<div class="container-fluid py-4">

<?php if (!empty($success_message)) : ?>
<div class="alert alert-success">
    <?= $success_message ?>
</div>
<?php endif; ?>

<div class="card shadow-sm border-0">
<div class="card-body">

<h5 class="mb-4">Language Test Details</h5>

<form method="POST" action="manage-case.php?lead_id=<?= $lead_id ?>&tab=language">

<div class="row g-4">

<div class="col-md-6">
    <label>Test Type</label>
    <select name="test_type" class="form-selecting">
        <option value="">Select</option>
        <option value="IELTS" <?= ($language['test_type'] ?? '') == 'IELTS' ? 'selected' : '' ?>>IELTS</option>
        <option value="PTE" <?= ($language['test_type'] ?? '') == 'PTE' ? 'selected' : '' ?>>PTE</option>
        <option value="TOEFL" <?= ($language['test_type'] ?? '') == 'TOEFL' ? 'selected' : '' ?>>TOEFL</option>
        <option value="CELPIP" <?= ($language['test_type'] ?? '') == 'CELPIP' ? 'selected' : '' ?>>CELPIP</option>
    </select>
</div>

<div class="col-md-6">
    <label>TRF / Profile Number</label>
    <input type="text" name="trf_number" class="form-control"
        value="<?= htmlspecialchars($language['trf_number'] ?? '') ?>">
</div>

<div class="col-md-3">
    <label>Listening</label>
    <input type="text" name="listening" class="form-control"
        value="<?= htmlspecialchars($language['listening'] ?? '') ?>">
</div>

<div class="col-md-3">
    <label>Reading</label>
    <input type="text" name="reading" class="form-control"
        value="<?= htmlspecialchars($language['reading'] ?? '') ?>">
</div>

<div class="col-md-3">
    <label>Writing</label>
    <input type="text" name="writing" class="form-control"
        value="<?= htmlspecialchars($language['writing'] ?? '') ?>">
</div>

<div class="col-md-3">
    <label>Speaking</label>
    <input type="text" name="speaking" class="form-control"
        value="<?= htmlspecialchars($language['speaking'] ?? '') ?>">
</div>

<div class="col-md-6">
    <label>Overall Score</label>
    <input type="text" name="overall_score" class="form-control"
        value="<?= htmlspecialchars($language['overall_score'] ?? '') ?>">
</div>

<div class="col-md-6">
    <label>Valid Till</label>
    <input type="date" name="valid_till" class="form-control"
        value="<?= $language['valid_till'] ?? '' ?>">
</div>

</div>

<div class="text-end mt-4">
    <button type="submit" name="save_language" class="btn btn-primary px-5">
        Save Language
    </button>
</div>

</form>

</div>
</div>

</div>