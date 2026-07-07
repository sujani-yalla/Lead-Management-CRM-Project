<?php
session_start();
include "db.php";
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('work_visa')) {
    http_response_code(403);
    die("Access Denied");
}

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: work-visa-list.php");
    exit;
}

$id = intval($_GET['id']);
$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];

/* FETCH CASE WITH ROLE SECURITY */
if ($role === 'admin') {

    $stmt = $conn->prepare("
        SELECT 
            wv.*,
            l.lead_name,
            l.mobile,
            l.email,
            u.name AS employee_name
        FROM work_visas wv
        JOIN leads l ON l.id = wv.lead_id
        LEFT JOIN users u ON u.id = wv.assigned_to
        WHERE wv.id = ?
    ");

    $stmt->bind_param("i", $id);

} else {

    $stmt = $conn->prepare("
        SELECT 
            wv.*,
            l.lead_name,
            l.mobile,
            l.email,
            u.name AS employee_name
        FROM work_visas wv
        JOIN leads l ON l.id = wv.lead_id
        LEFT JOIN users u ON u.id = wv.assigned_to
        WHERE wv.id = ?
        AND wv.assigned_to = ?
    ");

    $stmt->bind_param("ii", $id, $userId);
}

$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    header("Location: work-visa-list.php");
    exit;
}

$stmt->close();

/* FETCH MARKETING JOBS */
$marketingStmt = $conn->prepare("
    SELECT * FROM work_visa_marketing
    WHERE visa_id = ?
    ORDER BY id DESC
");
$marketingStmt->bind_param("i", $id);
$marketingStmt->execute();
$marketingJobs = $marketingStmt->get_result();

/* FETCH DIRECT JOBS */
$directStmt = $conn->prepare("
    SELECT * FROM work_visa_direct
    WHERE visa_id = ?
    ORDER BY id DESC
");
$directStmt->bind_param("i", $id);
$directStmt->execute();
$directJobs = $directStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Work Visa Case Details</title>
    <link rel="stylesheet" href="assets/css/vendors/bootstrap.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/vendors/feather-icon.css">

</head>

<body>

<div class="container-fluid mt-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">Work Visa Case Details</h4>
            <small class="text-muted">
                Case ID: WV<?= str_pad($data['id'], 3, "0", STR_PAD_LEFT) ?>
            </small>
        </div>

        <a href="work-visa-list.php" class="btn btn-outline-secondary btn-sm">
            ← Back to List
        </a>
    </div>

    <!-- MAIN CASE CARD -->
    <div class="card shadow-sm mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">

            <div>
                <h4 class="mb-1">
                    Work Visa – WV<?= str_pad($data['id'], 3, "0", STR_PAD_LEFT) ?>
                   
                </h4>

                <p class="mb-0 text-muted">
                    <?= htmlspecialchars($data['lead_name']) ?>
                </p>
            </div>

            <?php
            $badgeClass = "bg-secondary";

            if ($data['status'] == "Approved" || $data['status'] == "Travel Completed") {
                $badgeClass = "bg-success";
            } elseif ($data['status'] == "Docs Pending" || $data['status'] == "Decision Awaited") {
                $badgeClass = "bg-warning";
            } elseif ($data['status'] == "Interview Scheduled" || $data['status'] == "Interview Cleared") {
                $badgeClass = "bg-info";
            }
            ?>

            <span class="badge <?= $badgeClass ?> fs-6 px-3 py-2">
                <?= htmlspecialchars($data['status']) ?>
            </span>

        </div>
    </div>

    <!-- APPLICANT INFO -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0">Applicant Information</h5>
        </div>
        <div class="card-body row">
            <div class="col-md-4">
                <strong>Name:</strong><br>
                <?= htmlspecialchars($data['lead_name']) ?>
            </div>
            <div class="col-md-4">
                <strong>Mobile:</strong><br>
                <?= htmlspecialchars($data['mobile']) ?>
            </div>
            <div class="col-md-4">
                <strong>Email:</strong><br>
                <?= htmlspecialchars($data['email']) ?>
            </div>
        </div>
    </div>

    <!-- WORK PROFILE -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0">Work Profile Details</h5>
        </div>
        <div class="card-body row">

            <div class="col-md-4">
                <strong>Job Category:</strong><br>
                <?= htmlspecialchars($data['job_category']) ?>
            </div>

            <div class="col-md-4">
                <strong>Experience:</strong><br>
                <?= htmlspecialchars($data['experience_years']) ?> Years
            </div>

            <div class="col-md-4">
                <strong>Qualification:</strong><br>
                <?= htmlspecialchars($data['qualification']) ?>
            </div>

            <div class="col-md-4 mt-3">
                <strong>English Test:</strong><br>
                <?= htmlspecialchars($data['english_test']) ?>
            </div>

            <div class="col-md-4 mt-3">
                <strong>Salary:</strong><br>
                <?= htmlspecialchars($data['salary']) ?>
            </div>

            <div class="col-md-4 mt-3">
                <strong>Passport Number:</strong><br>
                <?= htmlspecialchars($data['passport_number']) ?>
            </div>

        </div>
    </div>

    <!-- MARKETING SECTION -->
    

        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Marketing Jobs</h5>

                <a href="work-visa-marketing.php?visa_id=<?= $data['id'] ?>"
                   class="btn btn-primary btn-sm">
                    + Add Marketing Job
                </a>
            </div>

            <div class="card-body table-responsive">

                <?php if ($marketingJobs->num_rows > 0): ?>

                    <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th>Country</th>
                            <th>Job Role</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Docs</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>

                        <?php while ($row = $marketingJobs->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['country']) ?></td>
                                <td><?= htmlspecialchars($row['job_role']) ?></td>
                                <td><?= htmlspecialchars($row['contact_number']) ?></td>
                                <td><?= htmlspecialchars($row['application_status']) ?></td>
                                <td>
    <?= htmlspecialchars($row['documents_required']) ?>
<?php if (strtolower($row['documents_required'] ?? '') === 'yes' 
          && !empty($row['documents_details'])): ?>
    <br><small class="text-muted">
        <?= htmlspecialchars($row['documents_details']) ?>
    </small>
<?php endif; ?>
</td>

                                <td>
                                    <div class="d-flex gap-2">

<a href="work-marketing-edit.php?id=<?= $row['id'] ?>"
class="btn btn-sm btn-outline-success">
<i data-feather="edit-2"></i>
</a>

<form action="work-marketing-delete.php" method="POST"
onsubmit="return confirm('Delete this job entry?');"
style="display:inline;">

<input type="hidden" name="id" value="<?= $row['id'] ?>">
<input type="hidden" name="visa_id" value="<?= $data['id'] ?>">

<button class="btn btn-sm btn-outline-danger">
<i data-feather="trash-2"></i>
</button>

</form>

</div>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                        </tbody>
                    </table>

                <?php else: ?>
                    <p class="text-muted">No marketing jobs added yet.</p>
                <?php endif; ?>

            </div>
        </div>

    

    <!-- DIRECT SECTION -->


        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Direct Employer Jobs</h5>

                <a href="work-visa-direct.php?visa_id=<?= $data['id'] ?>"
                   class="btn btn-secondary btn-sm">
                    + Add Direct Job
                </a>
            </div>

            <div class="card-body table-responsive">

                <?php if ($directJobs->num_rows > 0): ?>

                    <table class="table table-bordered">
                        <thead>
                        <tr>
    <th>Employer</th>
    <th>Country</th>
    <th>Job Type</th>
    <th>Job Role</th>
    <th>Salary</th>
    <th>Applied Company</th>
    <th>Status</th>
    <th>Docs</th>
    <th>Comments</th>
    <th>Actions</th>
</tr>
                        </thead>
                        <tbody>

                        <?php while ($row = $directJobs->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['employer_name']) ?></td>
    <td><?= htmlspecialchars($row['country']) ?></td>
    <td><?= htmlspecialchars($row['job_type']) ?></td>
    <td><?= htmlspecialchars($row['job_role']) ?></td>
    <td><?= htmlspecialchars($row['salary']) ?></td>
    <td><?= htmlspecialchars($row['applied_company']) ?></td>
    <td><?= htmlspecialchars($row['job_status']) ?></td>

    <td>
        <?= htmlspecialchars($row['documents_required']) ?>
        <?php if ($row['documents_required'] === 'Yes' && !empty($row['documents_details'])): ?>
            <br><small class="text-muted">
                <?= htmlspecialchars($row['documents_details']) ?>
            </small>
        <?php endif; ?>
    </td>

    <td>
        <small><?= htmlspecialchars($row['comments']) ?></small>
    </td>

    <td>
        <div class="d-flex gap-2">

<a href="work-direct-edit.php?id=<?= $row['id'] ?>"
class="btn btn-sm btn-outline-success">
<i data-feather="edit-2"></i>
</a>

<form action="work-direct-delete.php" method="POST"
onsubmit="return confirm('Delete this job entry?');"
style="display:inline;">

<input type="hidden" name="id" value="<?= $row['id'] ?>">
<input type="hidden" name="visa_id" value="<?= $data['id'] ?>">

<button class="btn btn-sm btn-outline-danger">
<i data-feather="trash-2"></i>
</button>

</form>

</div>
    </td>
</tr>
<?php endwhile; ?>

                        </tbody>
                    </table>

                <?php else: ?>
                    <p class="text-muted">No direct employer jobs added yet.</p>
                <?php endif; ?>

            </div>
        </div>

    

</div>

<script src="assets/js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="assets/js/icons/feather-icon/feather.min.js"></script>
<script>
feather.replace();
</script>
</body>
</html>