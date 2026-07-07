<?php
$success_message = "";

/* ================= ADD EDUCATION ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_education'])) {

    $insert = $conn->prepare("
        INSERT INTO pr_case_education
        (case_id, education_level, institution_name, field_of_study, year_completed, eca_status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $insert->bind_param(
        "isssis",
        $case_id,
        $_POST['education_level'],
        $_POST['institution_name'],
        $_POST['field_of_study'],
        $_POST['year_completed'],
        $_POST['eca_status']
    );

    $insert->execute();

    header("Location: manage-case.php?lead_id=".$lead_id."&tab=education&success=added");
    exit;
}

/* ================= DELETE EDUCATION ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_education'])) {

    $delete_id = intval($_POST['delete_id']);

    $delete = $conn->prepare("
        DELETE FROM pr_case_education
        WHERE id = ? AND case_id = ?
    ");
    $delete->bind_param("ii", $delete_id, $case_id);
    $delete->execute();

    header("Location: manage-case.php?lead_id=".$lead_id."&tab=education&success=deleted");
    exit;
}

/* ================= FETCH EDUCATION ================= */
$listStmt = $conn->prepare("
    SELECT *
    FROM pr_case_education
    WHERE case_id = ?
    ORDER BY year_completed DESC
");
$listStmt->bind_param("i", $case_id);
$listStmt->execute();
$educationList = $listStmt->get_result();

/* ================= SUCCESS MESSAGE ================= */
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'added') {
        $success_message = "Education added successfully.";
    }
    if ($_GET['success'] === 'deleted') {
        $success_message = "Education deleted successfully.";
    }
}
?>

<div class="container-fluid py-4">

<div class="row g-4">

    <!-- ================= ADD EDUCATION ================= -->
    <div class="col-md-5">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="mb-3">Add Education</h5>

                <?php if (!empty($success_message)) : ?>
<div class="alert alert-success">
    <?= $success_message ?>
</div>
<?php endif; ?>

                <form method="POST" action="manage-case.php?lead_id=<?= $lead_id ?>&tab=education">

                    <div class="mb-3">
                        <label>Education Level</label>
                        <select name="education_level" class="form-control" required>
                            <option value="">Select</option>
                            <option>SSC</option>
                            <option>Intermediate</option>
                            <option>Degree</option>
                            <option>Post Graduation</option>
                            <option>PhD</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Institution Name</label>
                        <input type="text" name="institution_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Field of Study</label>
                        <input type="text" name="field_of_study" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Year Completed</label>
                        <input type="number" name="year_completed" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>ECA / Skill Assessment Status</label>
                        <input type="text" name="eca_status" class="form-control">
                    </div>

                    <div class="text-end">
                        <button type="submit" name="add_education" class="btn btn-primary">
                            Add Education
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    <!-- ================= EDUCATION LIST ================= -->
    <div class="col-md-7">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="mb-3">Education Records</h5>

                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Level</th>
                            <th>Institution</th>
                            <th>Year</th>
                            <th>ECA</th>
                            <th width="80" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($educationList->num_rows > 0): ?>
                            <?php while ($row = $educationList->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['education_level']) ?></td>
                                    <td><?= htmlspecialchars($row['institution_name']) ?></td>
                                    <td><?= htmlspecialchars($row['year_completed']) ?></td>
                                    <td><?= htmlspecialchars($row['eca_status']) ?></td>
                                    <td class="text-center">
    <form method="POST"
          action="manage-case.php?lead_id=<?= $lead_id ?>&tab=education"
          style="display:inline;">
        <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
        <button type="submit"
                name="delete_education"
                class="btn btn-sm btn-outline-danger"
                onclick="return confirm('Delete this education record?')"
                title="Delete">
            <i class="bi bi-trash"></i>
        </button>
    </form>
</td>
                                    
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No records found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

            </div>
        </div>
    </div>

</div>

</div> 