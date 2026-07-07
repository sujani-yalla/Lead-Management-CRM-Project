<?php
$success_message = "";

/* ================= HANDLE UPLOAD ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_document'])) {

    if (!empty($_FILES['document_file']['name'])) {

        $document_type = $_POST['document_type'];
        $file = $_FILES['document_file'];

        $upload_dir = "uploads/pr/";
        $unique_name = time() . "_" . basename($file['name']);
        $target_path = $upload_dir . $unique_name;

        if (move_uploaded_file($file['tmp_name'], $target_path)) {

            $insert = $conn->prepare("
                INSERT INTO pr_case_documents
                (case_id, document_type, file_name, file_path, uploaded_by)
                VALUES (?, ?, ?, ?, ?)
            ");

            $insert->bind_param(
                "isssi",
                $case_id,
                $document_type,
                $file['name'],
                $target_path,
                $_SESSION['user_id']
            );

            $insert->execute();
        }
    }

    header("Location: manage-case.php?lead_id=".$lead_id."&tab=documents&success=1");
    exit;
}

/* ================= HANDLE DELETE ================= */
if (isset($_GET['delete_doc'])) {

    $doc_id = intval($_GET['delete_doc']);

    $docStmt = $conn->prepare("SELECT file_path FROM pr_case_documents WHERE id = ? AND case_id = ?");
    $docStmt->bind_param("ii", $doc_id, $case_id);
    $docStmt->execute();
    $doc = $docStmt->get_result()->fetch_assoc();

    if ($doc) {
        if (file_exists($doc['file_path'])) {
            unlink($doc['file_path']);
        }

        $delete = $conn->prepare("DELETE FROM pr_case_documents WHERE id = ?");
        $delete->bind_param("i", $doc_id);
        $delete->execute();
    }

    header("Location: manage-case.php?lead_id=".$lead_id."&tab=documents");
    exit;
}

/* ================= FETCH DOCUMENTS ================= */
$listStmt = $conn->prepare("
    SELECT *
    FROM pr_case_documents
    WHERE case_id = ?
    ORDER BY uploaded_at DESC
");
$listStmt->bind_param("i", $case_id);
$listStmt->execute();
$documents = $listStmt->get_result();

if (isset($_GET['success'])) {
    $success_message = "Document uploaded successfully.";
}
?>

<div class="container-fluid py-4">

<?php if (!empty($success_message)) : ?>
<div class="alert alert-success">
    <?= $success_message ?>
</div>
<?php endif; ?>

<div class="row g-4">

<!-- ================= UPLOAD FORM ================= -->
<div class="col-md-4">
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h5 class="mb-3">Upload Document</h5>

            <form method="POST" enctype="multipart/form-data"
                  action="manage-case.php?lead_id=<?= $lead_id ?>&tab=documents">

                <div class="mb-3">
                    <label>Document Type</label>
                    <select name="document_type" class="form-control" required>
                        <option value="">Select</option>
                        <option>Passport</option>
                        <option>Education Certificate</option>
                        <option>Transcript</option>
                        <option>Experience Letter</option>
                        <option>Payslip</option>
                        <option>IELTS / Language</option>
                        <option>PCC</option>
                        <option>Medical</option>
                        <option>Other</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label>Select File</label>
                    <input type="file" name="document_file" class="form-control" required>
                </div>

                <div class="text-end">
                    <button type="submit" name="upload_document" class="btn btn-primary">
                        Upload
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<!-- ================= DOCUMENT LIST ================= -->
<div class="col-md-8">
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h5 class="mb-3">Uploaded Documents</h5>

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>File Name</th>
                        <th>Date</th>
                        <th width="150">Action</th>
                    </tr>
                </thead>
                <tbody>

                <?php if ($documents->num_rows > 0): ?>
                    <?php while ($doc = $documents->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($doc['document_type']) ?></td>
                        <td><?= htmlspecialchars($doc['file_name']) ?></td>
                        <td><?= $doc['uploaded_at'] ?></td>
                        <td class="text-center">

    <!-- Download -->
    <a href="download-document.php?id=<?= $doc['id'] ?>"
       class="btn btn-sm btn-outline-success me-1"
       title="Download">
        <i class="bi bi-download"></i>
    </a>

    <!-- Delete -->
    <a href="manage-case.php?lead_id=<?= $lead_id ?>&tab=documents&delete_doc=<?= $doc['id'] ?>"
       class="btn btn-sm btn-outline-danger"
       onclick="return confirm('Delete this document?')"
       title="Delete">
        <i class="bi bi-trash"></i>
    </a>

</td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted">No documents uploaded</td>
                    </tr>
                <?php endif; ?>

                </tbody>
            </table>

        </div>
    </div>
</div>

</div>

</div>