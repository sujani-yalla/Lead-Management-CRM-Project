<?php
session_start();
include "db.php";
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('pr_application')) {
    http_response_code(403);
    die("Access Denied");
}


if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['lead_id'])) {
    die("Lead ID missing");
}

$lead_id = intval($_GET['lead_id']);

/* =========================
   VALIDATE LEAD
========================= */
$check = $conn->prepare("SELECT id FROM leads WHERE id = ?");
$check->bind_param("i", $lead_id);
$check->execute();
$check->store_result();

if ($check->num_rows === 0) {
    die("Invalid Lead");
}
$check->close();

/* =========================
   FETCH LEAD DATA
========================= */
$leadData = $conn->prepare("
    SELECT lead_name, mobile, email 
    FROM leads 
    WHERE id = ?
");
$leadData->bind_param("i", $lead_id);
$leadData->execute();
$result = $leadData->get_result();
$lead = $result->fetch_assoc();
$leadData->close();

/* =========================
   HANDLE FORM SUBMIT
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $lead_id = intval($_POST['lead_id']);
    $total_fee = floatval($_POST['total_fee']);

    /* Prevent duplicate PR enquiry */
    $check = $conn->prepare("SELECT id FROM pr_enquiries WHERE lead_id = ?");
    $check->bind_param("i", $lead_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        die("PR enquiry already exists for this lead.");
    }
    $check->close();

    $stmt = $conn->prepare("
        INSERT INTO pr_enquiries (
            lead_id, lead_name, mobile, email,
            current_country, city, age, gender,
            qualification, field_of_study,
            total_experience, relevant_experience,
            current_job_title, occupation_mapping,
            target_country, language,
            english_test_taken, test_type,
            overall_score, individual_scores,
            marital_status, spouse_included,
            spouse_qualification, spouse_experience,
            spouse_english,
            passport_available, funds_available,
            previous_visa_refusal,
            source, lead_status, followup_date, notes,
            total_fee, created_by, created_at
        )
        VALUES (
            ?,?,?,?,?,?,?,?,?,?,
            ?,?,?,?,?,?,?,?,?,?,
            ?,?,?,?,
            ?,?,?,?,
            ?,?,?,?,
            ?,?,NOW()
        )
    ");

    $stmt->bind_param(
        "isssssisissddssssssssssssssssissdi",
        $lead_id,
        $_POST['lead_name'],
        $_POST['mobile'],
        $_POST['email'],
        $_POST['current_country'],
        $_POST['city'],
        $_POST['age'],
        $_POST['gender'],
        $_POST['qualification'],
        $_POST['field_of_study'],
        $_POST['total_experience'],
        $_POST['relevant_experience'],
        $_POST['current_job_title'],
        $_POST['occupation_mapping'],
        $_POST['target_country'],
        $_POST['language'],
        $_POST['english_test_taken'],
        $_POST['test_type'],
        $_POST['overall_score'],
        $_POST['individual_scores'],
        $_POST['marital_status'],
        $_POST['spouse_included'],
        $_POST['spouse_qualification'],
        $_POST['spouse_experience'],
        $_POST['spouse_english'],
        $_POST['passport_available'],
        $_POST['funds_available'],
        $_POST['previous_visa_refusal'],
        $_POST['source'],
        $_POST['lead_status'],
        $_POST['followup_date'],
        $_POST['notes'],
        $total_fee,
        $_SESSION['user_id']
    );

    $stmt->execute();

    header("Location: pr-list.php");
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
                    <div class="row size-column">
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4>PR Details Form</h4>
                                </div>
                                <div class="card-body">
                                    <!-- Success Modal -->
                                    <div class="modal fade" id="successModal" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                        <div class="modal-header bg-success text-white">
                                            <h5 class="modal-title">Success</h5>
                                        </div>
                                        <div class="modal-body text-center">
                                            <i class="fa fa-check-circle fa-3x text-success mb-3"></i>
                                            <p class="fs-5">PR details added successfully</p>
                                        </div>
                                        </div>
                                    </div>
                                    </div>

                                    <form  method="POST" class="row g-3 needs-validation custom-input" id="prForm">
                                        <input type="hidden" name="lead_id" value="<?= $lead_id ?>">
                                           <!-- Basic Details -->
<div class="col-md-4 position-relative">
    <label class="form-label">Full Name</label>
    <input type="text" name="lead_name" class="form-control"
        value="<?= htmlspecialchars($lead['lead_name']) ?>" readonly>
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Mobile</label>
    <input type="text" name="mobile" class="form-control"
        value="<?= htmlspecialchars($lead['mobile']) ?>" readonly>
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control"
        value="<?= htmlspecialchars($lead['email']) ?>" readonly>
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Current Country</label>
    <input type="text" name="current_country" class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">City</label>
    <input type="text" name="city" class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Age</label>
    <input type="number" name="age" class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Gender</label>
    <select name="gender" class="form-select">
        <option value="">Select</option>
        <option>Male</option>
        <option>Female</option>
        <option>Other</option>
    </select>
</div>

<!-- Profile Basics -->

<div class="col-md-4 position-relative">
    <label class="form-label">Qualification</label>
    <input type="text" name="qualification" class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Field of Study</label>
    <input type="text" name="field_of_study" class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Total Experience (Years)</label>
    <input type="number" step="0.1" name="total_experience" class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Relevant Experience (Years)</label>
    <input type="number" step="0.1" name="relevant_experience" class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Current Job Title</label>
    <input type="text" name="current_job_title" class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Occupation (PR Mapping)</label>
    <input type="text" name="occupation_mapping" class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Target Country</label>
    <select name="target_country" class="form-select">
        <option value="">Select</option>
        <option>Canada</option>
        <option>Australia</option>
        <option>UK</option>
        <option>Germany</option>
        <option>New Zealand</option>
    </select>
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">English Test Taken?</label>
    <select name="english_test_taken" class="form-select">
        <option value="">Select</option>
        <option>Yes</option>
        <option>No</option>
    </select>
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Test Type</label>
    <select name="test_type" class="form-select">
        <option value="">Select</option>
        <option>IELTS</option>
        <option>PTE</option>
        <option>TOEFL</option>
    </select>
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Overall Score</label>
    <input type="text" name="overall_score" class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Individual Scores (L/R/W/S)</label>
    <input type="text" name="individual_scores" class="form-control">
</div>

<!-- Family / Points -->

<div class="col-md-4 position-relative">
    <label class="form-label">Marital Status</label>
    <select name="marital_status" class="form-select">
        <option value="">Select</option>
        <option>Single</option>
        <option>Married</option>
    </select>
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Spouse Included?</label>
    <select name="spouse_included" class="form-select">
        <option value="">Select</option>
        <option>Yes</option>
        <option>No</option>
    </select>
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Spouse Qualification</label>
    <input type="text" name="spouse_qualification" class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Spouse Experience (Years)</label>
    <input type="number" step="0.1" name="spouse_experience" class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Spouse English (Score)</label>
    <input type="text" name="spouse_english" class="form-control">
</div>

<!-- Other -->

<div class="col-md-4 position-relative">
    <label class="form-label">Passport Available</label>
    <select name="passport_available" class="form-select">
        <option value="">Select</option>
        <option>Yes</option>
        <option>No</option>
    </select>
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Funds Available</label>
    <input type="text" name="funds_available" class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Previous Visa Refusal</label>
    <input type="text" name="previous_visa_refusal" class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Source</label>
    <input type="text" name="source" class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Lead Status</label>
    <select name="lead_status" class="form-select">
        <option value="">Select</option>
        <option>New</option>
        <option>Contacted</option>
        <option>Interested</option>
        <option>Not Eligible</option>
    </select>
</div>

<div class="col-md-6">
    <label class="form-label">Total Fee (₹)</label>
    <input type="number" step="0.01" name="total_fee" 
           class="form-control" required>
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Follow-up Date</label>
    <input type="date" name="followup_date" class="form-control">
</div>

<div class="col-12 position-relative">
    <label class="form-label">Notes</label>
    <textarea name="notes" class="form-control" rows="3"></textarea>
</div>
<div class="col-12">
    <button class="btn btn-primary" type="submit">
        Submit Form
    </button>
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