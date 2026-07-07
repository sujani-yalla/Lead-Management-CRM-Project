<?php
session_start();
include "db.php";
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('pr_application')) {
    http_response_code(403);
    die("Access Denied");
}


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['lead_id'])) {
    die("Invalid Lead");
}

$lead_id = intval($_GET['lead_id']);

/* =========================
   FETCH PR + PAYMENT DATA
========================= */

$stmt = $conn->prepare("
    SELECT pr.id, pr.total_fee, l.lead_name
    FROM pr_enquiries pr
    JOIN leads l ON l.id = pr.lead_id
    WHERE pr.lead_id = ?
");
$stmt->bind_param("i", $lead_id);
$stmt->execute();
$pr = $stmt->get_result()->fetch_assoc();

if (!$pr) {
    die("PR case not found");
}

/* TOTAL PAID */
$payStmt = $conn->prepare("
    SELECT SUM(amount) as total_paid
    FROM pr_payments
    WHERE lead_id = ?
");
$payStmt->bind_param("i", $lead_id);
$payStmt->execute();
$paymentData = $payStmt->get_result()->fetch_assoc();

$total_fee  = floatval($pr['total_fee']);
$total_paid = floatval($paymentData['total_paid'] ?? 0);
$balance    = $total_fee - $total_paid;

$checkCase = $conn->prepare("SELECT id FROM pr_case_details WHERE lead_id = ?");
$checkCase->bind_param("i", $lead_id);
$checkCase->execute();
$checkCase->store_result();

if ($checkCase->num_rows == 0) {

    $createCase = $conn->prepare("
        INSERT INTO pr_case_details 
        (lead_id, created_by, current_stage) 
        VALUES (?, ?, 'Eligibility Check')
    ");
    $createCase->bind_param("ii", $lead_id, $_SESSION['user_id']);
    $createCase->execute();
}

/* =========================
   HANDLE FORM SUBMIT
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $amount       = floatval($_POST['amount']);
    $payment_date = $_POST['payment_date'];
    $payment_mode = $_POST['payment_mode'];
    $receipt_file = null;

    /* Prevent overpayment */
    if ($amount > $balance) {
        die("Amount exceeds remaining balance.");
    }

    /* Receipt Upload */
    if (!empty($_FILES['receipt']['name'])) {

        $allowed = ['pdf','jpg','jpeg','png'];
        $ext = strtolower(pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            die("Invalid file type.");
        }

        if ($_FILES['receipt']['size'] > 5 * 1024 * 1024) {
            die("File too large. Max 5MB.");
        }

        $receipt_file = time().'_'.$_FILES['receipt']['name'];
        move_uploaded_file(
            $_FILES['receipt']['tmp_name'],
            "uploads/pr_receipts/".$receipt_file
        );
    }

    $insert = $conn->prepare("
        INSERT INTO pr_payments
        (lead_id, amount, payment_date, payment_mode, receipt_file, created_by, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");

    $insert->bind_param(
        "idsssi",
        $lead_id,
        $amount,
        $payment_date,
        $payment_mode,
        $receipt_file,
        $_SESSION['user_id']
    );

    $insert->execute();

    header("Location: pr-view.php?id=".$pr['id']);
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Riho admin is super flexible, powerful, clean &amp; modern responsive bootstrap 5 admin template with unlimited possibilities.">
    <meta name="keywords"
        content="admin template, Riho admin template, dashboard template, flat admin template, responsive admin template, web app">
    <meta name="author" content="pixelstrap">
    <link rel="icon" href="assets/images/favicon.png" type="image/x-icon">
    <link rel="shortcut icon" href="assets/images/favicon.png" type="image/x-icon">
    <title>PR details Adding</title>
    <!-- Google font-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link href="../../css2-1?family=Montserrat:wght@200;300;400;500;600;700;800&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="assets/css/font-awesome.css">
    <!-- ico-font-->
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/icofont.css">
    <!-- Themify icon-->
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/themify.css">
    <!-- Flag icon-->
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/flag-icon.css">
    <!-- Feather icon-->
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/feather-icon.css">
    <!-- Plugins css start-->
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/slick.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/slick-theme.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/scrollbar.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/animate.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/echart.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/date-picker.css">
    <!-- Plugins css Ends-->
    <!-- Bootstrap css-->
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/bootstrap.css">
    <!-- App css-->
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
    <link id="color" rel="stylesheet" href="assets/css/color-1.css" media="screen">
    <!-- Responsive css-->
    <link rel="stylesheet" type="text/css" href="assets/css/responsive.css">
</head>

<body>
    <!-- loader starts-->
    <div class="loader-wrapper">
        <div class="loader">
            <div class="loader4"></div>
        </div>
    </div>
    <!-- loader ends-->
    <!-- tap on top starts-->
    <div class="tap-top"><i data-feather="chevrons-up"></i></div>
    <!-- tap on tap ends-->
    <!-- page-wrapper Start-->
    <div class="page-wrapper compact-wrapper" id="pageWrapper">
        <!-- Page Header Start-->
        <?php include "header.php";?>
        <!-- Page Header Ends                              -->
        <!-- Page Body Start-->
        <div class="page-body-wrapper">
            <!-- Page Sidebar Start-->
           <?php include "sidebar.php";?>
            <!-- Page Sidebar Ends-->
            <div class="page-body">
                <div class="container-fluid">
                    <div class="page-title">
                        <div class="row">
                            <div class="col-6">
                                <h4>PR(Permanent Residency)</h4>
                            </div>
                            <div class="col-6">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.html">
                                            <svg class="stroke-icon">
                                                <use href="assets/svg/icon-sprite.svg#stroke-home"></use>
                                            </svg></a></li>
                                    <li class="breadcrumb-item">Dashboard</li>
                                    <li class="breadcrumb-item active">Default</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Container-fluid starts-->
                <div class="container-fluid">
<div class="row">
<div class="col-md-8 offset-md-2">

<div class="card">
<div class="card-header bg-light">
<h5 class="mb-0">Add Payment – <?= htmlspecialchars($pr['lead_name']) ?></h5>
</div>

<div class="card-body">

<!-- Payment Summary -->
<div class="row mb-4">
<div class="col-md-4">
<div class="border p-3 rounded bg-light">
<div class="small text-muted">Total Fee</div>
<div class="fw-bold">₹<?= number_format($total_fee,2) ?></div>
</div>
</div>

<div class="col-md-4">
<div class="border p-3 rounded bg-light">
<div class="small text-muted">Total Paid</div>
<div class="fw-bold">₹<?= number_format($total_paid,2) ?></div>
</div>
</div>

<div class="col-md-4">
<div class="border p-3 rounded bg-light">
<div class="small text-muted">Balance</div>
<div class="fw-bold">
<?= $balance <= 0 ? 'Fully Paid' : '₹'.number_format($balance,2) ?>
</div>
</div>
</div>
</div>

<form method="POST" enctype="multipart/form-data">

<div class="row g-3">

<div class="col-md-6">
<label class="form-label">Payment Date</label>
<input type="date" name="payment_date" class="form-control" required>
</div>

<div class="col-md-6">
<label class="form-label">Payment Mode</label>
<select name="payment_mode" class="form-select" required>
<option value="">Select Mode</option>
<option>Cash</option>
<option>UPI</option>
<option>Bank Transfer</option>
<option>Cheque</option>
</select>
</div>

<div class="col-md-6">
<label class="form-label">Amount</label>
<input type="number" step="0.01" name="amount" class="form-control" required>
</div>

<div class="col-md-6">
<label class="form-label">Upload Receipt (Optional)</label>
<input type="file" name="receipt" class="form-control">
</div>

<div class="col-12 mt-3">
<button type="submit" class="btn btn-outline-primary">
Save Payment
</button>

<a href="pr-view.php?id=<?= $pr['id'] ?>" 
class="btn btn-light border">
Cancel
</a>
</div>

</div>
</form>

</div>
</div>

</div>
</div>
</div>
                                    
                <!-- Container-fluid Ends-->
            </div>
            <!-- footer start-->
            <?php include "footer.php";?>
        </div>
    </div>
    <!-- latest jquery-->
    <script src="assets/js/jquery.min.js"></script>
    <!-- Bootstrap js-->
    <script src="assets/js/bootstrap/bootstrap.bundle.min.js"></script>
    <!-- feather icon js-->
    <script src="assets/js/icons/feather-icon/feather.min.js"></script>
    <script src="assets/js/icons/feather-icon/feather-icon.js"></script>
    <!-- scrollbar js-->
    <script src="assets/js/scrollbar/simplebar.js"></script>
    <script src="assets/js/scrollbar/custom.js"></script>
    <!-- Sidebar jquery-->
    <script src="assets/js/config.js"></script>
    <!-- Plugins JS start-->
    <script src="assets/js/sidebar-menu.js"></script>
    <script src="assets/js/sidebar-pin.js"></script>
    <script src="assets/js/slick/slick.min.js"></script>
    <script src="assets/js/slick/slick.js"></script>
    <script src="assets/js/header-slick.js"></script>
    <script src="assets/js/chart/apex-chart/apex-chart.js"></script>
    <script src="assets/js/chart/apex-chart/stock-prices.js"></script>
    <script src="assets/js/chart/apex-chart/moment.min.js"></script>
    <script src="assets/js/chart/echart/esl.js"></script>
    <script src="assets/js/chart/echart/config.js"></script>
    <script src="assets/js/chart/echart/pie-chart/facePrint.js"></script>
    <script src="assets/js/chart/echart/pie-chart/testHelper.js"></script>
    <script src="assets/js/chart/echart/pie-chart/custom-transition-texture.js"></script>
    <script src="assets/js/chart/echart/data/symbols.js"></script>
    <!-- calendar js-->
    <script src="assets/js/datepicker/date-picker/datepicker.js"></script>
    <script src="assets/js/datepicker/date-picker/datepicker.en.js"></script>
    <script src="assets/js/datepicker/date-picker/datepicker.custom.js"></script>
    <script src="assets/js/dashboard/dashboard_3.js"></script>
    <!-- Plugins JS Ends-->
    <!-- Theme js-->
    <script src="assets/js/script.js"></script>
    <script src="assets/js/theme-customizer/customizer.js"></script>

<!-- <script>
document.getElementById("prSubmit").addEventListener("click", function (e) {

    // Prevent normal submission temporarily
    e.preventDefault();

    let form = document.getElementById("prForm");

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    // Show success modal
    let modal = new bootstrap.Modal(document.getElementById('successModal'));
    modal.show();

    // Submit form after 1.5 sec
    setTimeout(function () {
        form.submit();
    }, 1500);
});

</script> -->


</body>

</html> 