<?php
session_start();
include "db.php";

/* ===== SECURITY CHECK ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    die("Access denied");
}

$task_id = $_GET['id'] ?? 0;

/* ===== FETCH ONLY EMPLOYEE'S TASK ===== */
$stmt = $conn->prepare("
    SELECT * FROM tasks 
    WHERE id=? AND assigned_to=?
");
$stmt->bind_param("ii", $task_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$task = $result->fetch_assoc();
$stmt->close();

if (!$task) {
    die("Task not found or access denied.");
}

/* ===== FORM SUBMISSION ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $task_id = $_POST['task_id'];
    $work_description = trim($_POST['work_description']);
    $time_taken = $_POST['time_taken'];
    $client_name = $_POST['client_name'];
    $remarks = $_POST['remarks'];

   

    /* ===== INSERT INTO task_reports (WITHOUT proof_file) ===== */
    $stmt = $conn->prepare("
        INSERT INTO task_reports 
        (task_id, work_description, time_taken, client_name, remarks, submitted_at, review_status)
        VALUES (?, ?, ?, ?, ?, NOW(), 'Waiting')
    ");

    $stmt->bind_param(
        "issss",
        $task_id,
        $work_description,
        $time_taken,
        $client_name,
        $remarks
    );

    $stmt->execute();
    $report_id = $stmt->insert_id;
    $stmt->close();

    /* ===== MULTIPLE FILE UPLOAD ===== */
    if (!empty($_FILES['proof_files']['name'][0])) {

        $upload_path = "uploads/task_proofs/";

        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0777, true);
        }

        $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];

        foreach ($_FILES['proof_files']['name'] as $key => $name) {

            $tmp_name = $_FILES['proof_files']['tmp_name'][$key];
            $error = $_FILES['proof_files']['error'][$key];
            $size = $_FILES['proof_files']['size'][$key];

            if ($error === 0) {

                $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                if (!in_array($extension, $allowed_types)) {
                    die("Only JPG, PNG and PDF files allowed.");
                }

                if ($size > 5 * 1024 * 1024) {
                    die("Each file must be less than 5MB.");
                }

                $new_file_name = time() . "_" . uniqid() . "." . $extension;
                $final_path = $upload_path . $new_file_name;

                move_uploaded_file($tmp_name, $final_path);

                $fileStmt = $conn->prepare("
                    INSERT INTO task_report_files (report_id, file_path)
                    VALUES (?, ?)
                ");

                $fileStmt->bind_param("is", $report_id, $final_path);
                $fileStmt->execute();
                $fileStmt->close();
            }
        }

    } else {
        die("At least one proof file is required.");
    }

    /* ===== UPDATE MAIN TASK STATUS ===== */
    $update = $conn->prepare("
        UPDATE tasks 
        SET status='Waiting Approval' 
        WHERE id=?
    ");
    $update->bind_param("i", $task_id);
    $update->execute();
    $update->close();

    header("Location: my-tasks.php?submitted=1");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Complete Task</title>
    <link rel="stylesheet" href="assets/css/vendors/bootstrap.css">

    <style>
        body {
            background: #f5f7fa;
            font-family: "Segoe UI", Arial, sans-serif;
        }

        .page-header {
            background: #1f2937;
            color: white;
            padding: 18px 25px;
            border-radius: 10px 10px 0 0;
            font-size: 18px;
            font-weight: 500;
        }

        .card {
            border-radius: 10px;
            border: 1px solid #e5e7eb;
        }

        .card-body {
            padding: 30px;
        }

        .form-label {
            font-weight: 500;
            font-size: 14px;
            color: #374151;
        }

        .form-control {
            border-radius: 6px;
            border: 1px solid #d1d5db;
            font-size: 14px;
        }

        .form-control:focus {
            border-color: #2563eb;
            box-shadow: none;
        }

        .upload-note {
            font-size: 13px;
            color: #6b7280;
            margin-top: 6px;
        }

        .btn-primary-custom {
            background: #2563eb;
            border: none;
            padding: 8px 20px;
            font-weight: 500;
            border-radius: 6px;
        }

        .btn-primary-custom:hover {
            background: #1e40af;
        }

        .top-actions {
            margin-bottom: 20px;
        }

        .top-actions a {
            font-size: 14px;
            margin-right: 10px;
        }
    </style>
</head>

<body>

<div class="container mt-4">

    <!-- Top Navigation -->
    <div class="top-actions">
        <a href="my-tasks.php" class="btn btn-light border">
            ⬅ Back to My Tasks
        </a>

        <a href="dashboard.php" class="btn btn-light border">
            ⬅ Dashboard
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="page-header">
            Complete Task: <?= htmlspecialchars($task['title']) ?>
        </div>

        <div class="card-body">

            <form method="POST" enctype="multipart/form-data">

                <input type="hidden" name="task_id" value="<?= $task['id'] ?>">

                <div class="mb-4">
                    <label class="form-label">
                        Work Description 
                    </label>
                    <textarea name="work_description"
                              class="form-control"
                              rows="5"
                              required></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label">
                        Upload Proof Files
                    </label>

                    <input type="file"
                       name="proof_files[]"
                       class="form-control"
                       multiple
                       accept=".jpg,.jpeg,.png,.pdf"
                       required>
                    <div class="upload-note">
                        You may upload multiple files (JPG, PNG, PDF).  
                        Maximum size: 5MB per file.
                    </div>
                </div>

                <div class="row">

                    <div class="col-md-4 mb-4">
                        <label class="form-label">Time Taken (Hours)</label>
                        <input type="number"
                               name="time_taken"
                               class="form-control"
                               required>
                    </div>

                    <div class="col-md-4 mb-4">
                        <label class="form-label">Client Name (Optional)</label>
                        <input type="text"
                               name="client_name"
                               class="form-control">
                    </div>

                </div>

                <div class="mb-4">
                    <label class="form-label">Remarks (Optional)</label>
                    <textarea name="remarks"
                              class="form-control"
                              rows="3"></textarea>
                </div>

                <button type="submit" class="btn btn-primary-custom">
                    Submit Task Completion
                </button>

            </form>

        </div>
    </div>
</div>

</body>
</html>