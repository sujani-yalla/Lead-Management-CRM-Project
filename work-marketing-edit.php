<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: work-visa-list.php");
    exit;
}

$id = intval($_GET['id']);

/* FETCH RECORD */
$stmt = $conn->prepare("SELECT * FROM work_visa_marketing WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    header("Location: work-visa-list.php");
    exit;
}

$stmt->close();

/* HANDLE UPDATE */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $country            = $_POST['country'];
    $job_role           = $_POST['job_role'];
    $contact_number     = $_POST['contact_number'];
    $application_status = $_POST['application_status'];
    $documents_required = $_POST['documents_required'];
    $jobs_applied       = $_POST['jobs_applied'];
    $time_period        = $_POST['time_period'];
    $documents_details = $_POST['documents_details'] ?? NULL;

    $update = $conn->prepare("
    UPDATE work_visa_marketing SET
        country = ?,
        job_role = ?,
        contact_number = ?,
        application_status = ?,
        documents_required = ?,
        documents_details = ?,
        jobs_applied = ?,
        time_period = ?
    WHERE id = ?
");

   $update->bind_param(
    "ssssssisi",
    $country,
    $job_role,
    $contact_number,
    $application_status,
    $documents_required,
    $documents_details,
    $jobs_applied,
    $time_period,
    $id
);

    $update->execute();
    $update->close();

    header("Location: work-visa-view.php?id=" . $data['visa_id']);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Marketing Job</title>
    <link rel="stylesheet" href="assets/css/vendors/bootstrap.css">
</head>
<body>

<div class="container mt-5">
<div class="card shadow-sm">
<div class="card-header">
    <h4>Edit Marketing Job</h4>
</div>

<div class="card-body">
<form method="POST">

<div class="row g-3">

<div class="col-md-4">
<label>Country</label>
<input type="text" name="country" class="form-control"
value="<?= htmlspecialchars($data['country']) ?>">
</div>

<div class="col-md-4">
<label>Job Role</label>
<input type="text" name="job_role" class="form-control"
value="<?= htmlspecialchars($data['job_role']) ?>">
</div>

<div class="col-md-4">
<label>Contact Number</label>
<input type="text" name="contact_number" class="form-control"
value="<?= htmlspecialchars($data['contact_number']) ?>">
</div>

<div class="col-md-4">
<label>Application Status</label>
<input type="text" name="application_status" class="form-control"
value="<?= htmlspecialchars($data['application_status']) ?>">
</div>

<div class="col-md-4">
<label>Documents Required</label>
<select name="documents_required" id="documents_required" class="form-select">
    <option value="Yes" <?=  strtolower($data['documents_required'] ?? '') == 'yes' ? 'selected' : '' ?>>Yes</option>
    <option value="No" <?= strtolower($data['documents_required'] ?? '') == 'no' ? 'selected' : '' ?>>No</option>
</select>
</div>
<div class="col-md-4" id="documents_text_div"
     style="<?= strtolower($data['documents_required'] ?? '') == 'yes' ? '' : 'display:none;' ?>">
<label>Mention Required Documents</label>
<input type="text" name="documents_details" class="form-control"
value="<?= htmlspecialchars($data['documents_details']) ?>">
</div>

<div class="col-md-4">
<label>No. of Jobs Applied</label>
<input type="number" name="jobs_applied" class="form-control"
value="<?= htmlspecialchars($data['jobs_applied']) ?>">
</div>

<div class="col-md-4">
<label>Time Period</label>
<input type="text" name="time_period" class="form-control"
value="<?= htmlspecialchars($data['time_period']) ?>">
</div>

</div>

<div class="mt-4">
<button class="btn btn-primary">Update</button>
<a href="work-visa-view.php?id=<?= $data['visa_id'] ?>" 
class="btn btn-outline-secondary">Cancel</a>
</div>

</form>
</div>
</div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function () {

    var select = document.getElementById("documents_required");
    var docDiv = document.getElementById("documents_text_div");

    function toggleDocs() {
        if (select.value.toLowerCase() === "yes") {
            docDiv.style.display = "block";
        } else {
            docDiv.style.display = "none";
        }
    }

    toggleDocs(); // run once on page load
    select.addEventListener("change", toggleDocs);

});
</script>
</body>
</html>