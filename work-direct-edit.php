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
$stmt = $conn->prepare("SELECT * FROM work_visa_direct WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    header("Location: work-visa-list.php");
    exit;
}

$stmt->close();

/* UPDATE */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $employer_name     = $_POST['employer_name'];
$country           = $_POST['country'];
$job_type          = $_POST['job_type'];
$job_role          = $_POST['job_role'];
$salary            = $_POST['salary'];
$applied_company   = $_POST['applied_company'];
$job_status        = $_POST['job_status'];
$documents_required = $_POST['documents_required'];
$documents_details  = $_POST['documents_details'] ?? NULL;
$comments           = $_POST['comments'];

    $update = $conn->prepare("
    UPDATE work_visa_direct SET
        employer_name = ?,
        country = ?,
        job_type = ?,
        job_role = ?,
        salary = ?,
        applied_company = ?,
        job_status = ?,
        documents_required = ?,
        documents_details = ?,
        comments = ?
    WHERE id = ?
");

    $update->bind_param(
    "ssssssssssi",
    $employer_name,
    $country,
    $job_type,
    $job_role,
    $salary,
    $applied_company,
    $job_status,
    $documents_required,
    $documents_details,
    $comments,
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
    <title>Edit Direct Employer Job</title>
    <link rel="stylesheet" href="assets/css/vendors/bootstrap.css">
    <style>
        body { background:#f8f9fa; }
        .form-card {
            border-radius:12px;
            box-shadow:0 4px 20px rgba(0,0,0,0.06);
        }
        .form-header {
            font-weight:600;
            font-size:18px;
        }
        .form-label {
            font-weight:500;
            font-size:14px;
        }
    </style>
</head>

<body>

<div class="container mt-5 mb-5">

<div class="card form-card">
<div class="card-body p-4">

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="form-header">
        Edit Direct Employer Job
    </div>
    <a href="work-visa-view.php?id=<?= $data['visa_id'] ?>" 
       class="btn btn-outline-secondary btn-sm">
       Back
    </a>
</div>

<form method="POST">

<div class="row g-4">

<div class="col-md-6">
<label class="form-label">Employer Name</label>
<input type="text" name="employer_name" 
class="form-control form-control-lg"
value="<?= htmlspecialchars($data['employer_name']) ?>" required>
</div>

<div class="col-md-6">
<label class="form-label">Country</label>
<input type="text" name="country"
class="form-control form-control-lg"
value="<?= htmlspecialchars($data['country']) ?>" required>
</div>

<div class="col-md-6">
<label class="form-label">Job Status</label>
<input type="text" name="job_status"
class="form-control form-control-lg"
value="<?= htmlspecialchars($data['job_status']) ?>" required>
</div>

<div class="col-md-6">
<label class="form-label">Job Type</label>
<input type="text" name="job_type"
class="form-control form-control-lg"
value="<?= htmlspecialchars($data['job_type']) ?>">
</div>

<div class="col-md-6">
<label class="form-label">Job Role</label>
<input type="text" name="job_role"
class="form-control form-control-lg"
value="<?= htmlspecialchars($data['job_role']) ?>">
</div>

<div class="col-md-6">
<label class="form-label">Salary</label>
<input type="text" name="salary"
class="form-control form-control-lg"
value="<?= htmlspecialchars($data['salary']) ?>">
</div>

<div class="col-md-6">
<label class="form-label">Applied Company</label>
<input type="text" name="applied_company"
class="form-control form-control-lg"
value="<?= htmlspecialchars($data['applied_company']) ?>">
</div>

<div class="col-md-6">
<label class="form-label">Documents Required</label>
<select name="documents_required" id="documents_required"
class="form-select form-select-lg">
<option value="Yes" <?= $data['documents_required']=="Yes"?"selected":"" ?>>Yes</option>
<option value="No" <?= $data['documents_required']=="No"?"selected":"" ?>>No</option>
</select>
</div>

<div class="col-md-6" id="documents_text_div"
style="<?= $data['documents_required']=="Yes" ? "" : "display:none;" ?>">
<label class="form-label">Mention Required Documents</label>
<input type="text" name="documents_details"
class="form-control form-control-lg"
value="<?= htmlspecialchars($data['documents_details']) ?>">
</div>

<div class="col-md-12">
<label class="form-label">Comments</label>
<textarea name="comments"
class="form-control form-control-lg"
rows="4"><?= htmlspecialchars($data['comments']) ?></textarea>
</div>

<div class="mt-5 d-flex justify-content-end gap-3">
    <a href="work-visa-view.php?id=<?= $data['visa_id'] ?>" 
       class="btn btn-light border px-4">
       Cancel
    </a>
    <button type="submit" class="btn btn-primary px-4">
        Update Job
    </button>
</div>

</form>

</div>
</div>

</div>

<script>
document.getElementById("documents_required").addEventListener("change", function () {
    var docDiv = document.getElementById("documents_text_div");

    if (this.value === "Yes") {
        docDiv.style.display = "block";
    } else {
        docDiv.style.display = "none";
    }
});
</script>

</body>
</html>