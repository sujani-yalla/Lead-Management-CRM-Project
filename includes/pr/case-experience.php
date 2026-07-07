<?php
$success_message = "";

/* ================= ADD EXPERIENCE ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_experience'])) {

    $insert = $conn->prepare("
        INSERT INTO pr_case_experience
        (case_id, employer_name, job_title, duration_from, duration_to)
        VALUES (?, ?, ?, ?, ?)
    ");

    $insert->bind_param(
        "issss",
        $case_id,
        $_POST['employer_name'],
        $_POST['job_title'],
        $_POST['duration_from'],
        $_POST['duration_to']
    );

    $insert->execute();

    header("Location: manage-case.php?lead_id=".$lead_id."&tab=experience&success=added");
    exit;
}

/* ================= DELETE EXPERIENCE ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_experience'])) {

    $delete = $conn->prepare("
        DELETE FROM pr_case_experience
        WHERE id = ? AND case_id = ?
    ");

    $delete->bind_param("ii", $_POST['delete_id'], $case_id);
    $delete->execute();

    header("Location: manage-case.php?lead_id=".$lead_id."&tab=experience&success=deleted");
    exit;
}

/* ================= FETCH EXPERIENCE LIST ================= */
$listStmt = $conn->prepare("
    SELECT *
    FROM pr_case_experience
    WHERE case_id = ?
    ORDER BY duration_from DESC
");
$listStmt->bind_param("i", $case_id);
$listStmt->execute();
$experienceList = $listStmt->get_result();

/* ================= SUCCESS MESSAGE ================= */
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'added') {
        $success_message = "Experience added successfully.";
    }
    if ($_GET['success'] === 'deleted') {
        $success_message = "Experience deleted successfully.";
    }
}
?>

<div class="container-fluid py-4">

<?php if (!empty($success_message)) : ?>
<div class="alert alert-success">
    <?= $success_message ?>
</div>
<?php endif; ?>

<div class="row g-4">

    <!-- ================= ADD EXPERIENCE ================= -->
    <div class="col-md-5">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="mb-3">Add Experience</h5>

                <form method="POST" action="manage-case.php?lead_id=<?= $lead_id ?>&tab=experience">

                    <div class="mb-3">
                        <label>Employer Name</label>
                        <input type="text" name="employer_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Job Title</label>
                        <input type="text" name="job_title" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>From</label>
                        <input type="date" name="duration_from" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>To</label>
                        <input type="date" name="duration_to" class="form-control">
                    </div>

                    <div class="text-end">
                        <button type="submit" name="add_experience" class="btn btn-primary">
                            Add Experience
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    <!-- ================= EXPERIENCE LIST ================= -->
    <div class="col-md-7">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="mb-3">Experience Records</h5>

                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Employer</th>
                            <th>Job Title</th>
                            <th>From</th>
                            <th>To</th>
                            <th width="80"class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>

                    <?php if ($experienceList->num_rows > 0): ?>
                        <?php while ($row = $experienceList->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['employer_name']) ?></td>
                            <td><?= htmlspecialchars($row['job_title']) ?></td>
                            <td><?= htmlspecialchars($row['duration_from']) ?></td>
                            <td><?= htmlspecialchars($row['duration_to']) ?></td>
                            <td class="text-center">
    <form method="POST" style="display:inline;"
          action="manage-case.php?lead_id=<?= $lead_id ?>&tab=experience">
        <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
        <button type="submit"
                name="delete_experience"
                class="btn btn-sm btn-outline-danger"
                onclick="return confirm('Delete this experience record?')"
                title="Delete">
            <i class="bi bi-trash"></i>
        </button>
    </form>
</td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">
                                No records found
                            </td>
                        </tr>
                    <?php endif; ?>

                    </tbody>
                </table>

            </div>
        </div>
    </div>

</div>

</div>