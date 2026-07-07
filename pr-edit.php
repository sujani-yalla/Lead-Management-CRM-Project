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

if (!isset($_GET['id'])) {
    die("PR Enquiry ID missing");
}

$pr_id = intval($_GET['id']);

/* =========================
   FETCH PR ENQUIRY
========================= */
$stmt = $conn->prepare("
    SELECT e.*, l.lead_name, l.mobile, l.email
    FROM pr_enquiries e
    JOIN leads l ON e.lead_id = l.id
    WHERE e.id = ?
");
$stmt->bind_param("i", $pr_id);
$stmt->execute();
$result = $stmt->get_result();



if ($result->num_rows === 0) {
    die("Invalid PR Enquiry");
}

$data = $result->fetch_assoc();
$stmt->close();

/* =========================
   HANDLE UPDATE
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $current_country = $_POST['current_country'];
    $city = $_POST['city'];
    $age = intval($_POST['age']);
    $gender = $_POST['gender'];
    $qualification = $_POST['qualification'];
    $field_of_study = $_POST['field_of_study'];
    $total_experience = floatval($_POST['total_experience']);
    $relevant_experience = floatval($_POST['relevant_experience']);
    $current_job_title = $_POST['current_job_title'];
    $occupation_mapping = $_POST['occupation_mapping'];
    $target_country = $_POST['target_country'];
    $language = $_POST['language'] ?? null;
    $english_test_taken = $_POST['english_test_taken'];
    $test_type = $_POST['test_type'];
    $overall_score = $_POST['overall_score'];
    $individual_scores = $_POST['individual_scores'];
    $marital_status = $_POST['marital_status'];
    $spouse_included = $_POST['spouse_included'];
    $spouse_qualification = $_POST['spouse_qualification'];
    $spouse_experience = floatval($_POST['spouse_experience']);
    $spouse_english = $_POST['spouse_english'];
    $passport_available = $_POST['passport_available'];
    $funds_available = $_POST['funds_available'];
    $previous_visa_refusal = $_POST['previous_visa_refusal'];
    $source = $_POST['source'];
    $lead_status = $_POST['lead_status'];
    $followup_date = $_POST['followup_date'];
    $notes = $_POST['notes'];
    $total_fee = floatval($_POST['total_fee']);

    $update = $conn->prepare("
    UPDATE pr_enquiries SET
        current_country = ?, 
        city = ?, 
        age = ?, 
        gender = ?,
        qualification = ?, 
        field_of_study = ?,
        total_experience = ?, 
        relevant_experience = ?,
        current_job_title = ?, 
        occupation_mapping = ?,
        target_country = ?, 
        language = ?,
        english_test_taken = ?, 
        test_type = ?,
        overall_score = ?, 
        individual_scores = ?,
        marital_status = ?, 
        spouse_included = ?,
        spouse_qualification = ?, 
        spouse_experience = ?,
        spouse_english = ?,
        passport_available = ?, 
        funds_available = ?,
        previous_visa_refusal = ?,
        source = ?, 
        lead_status = ?, 
        followup_date = ?, 
        notes = ?,
        total_fee = ?
    WHERE id = ?
");

if (!$update) {
    die("Prepare failed: " . $conn->error);
}

    $update->bind_param(
    "ssisssddsssssssssssdssssssssdi",
    $current_country,
    $city,
    $age,
    $gender,
    $qualification,
    $field_of_study,
    $total_experience,
    $relevant_experience,
    $current_job_title,
    $occupation_mapping,
    $target_country,
    $language,
    $english_test_taken,
    $test_type,
    $overall_score,
    $individual_scores,
    $marital_status,
    $spouse_included,
    $spouse_qualification,
    $spouse_experience,
    $spouse_english,
    $passport_available,
    $funds_available,
    $previous_visa_refusal,
    $source,
    $lead_status,
    $followup_date,
    $notes,
    $total_fee,
    $pr_id
);
  
   
    if (!$update->execute()) {
        die($update->error);
    }

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
                    <h4>Edit PR Details</h4>
                </div>
                <div class="card-body">

<form method="POST" class="row g-3 custom-input">

<!-- BASIC INFO (READ ONLY) -->

<div class="col-md-4 position-relative">
    <label class="form-label">Full Name</label>
    <input type="text" class="form-control"
        value="<?= htmlspecialchars($data['lead_name']) ?>" readonly>
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Mobile</label>
    <input type="text" class="form-control"
        value="<?= htmlspecialchars($data['mobile']) ?>" readonly>
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Email</label>
    <input type="email" class="form-control"
        value="<?= htmlspecialchars($data['email']) ?>" readonly>
</div>

<!-- LOCATION -->

<div class="col-md-4 position-relative">
    <label class="form-label">Current Country</label>
    <input type="text" name="current_country"
        value="<?= htmlspecialchars($data['current_country']) ?>"
        class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">City</label>
    <input type="text" name="city"
        value="<?= htmlspecialchars($data['city']) ?>"
        class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Age</label>
    <input type="number" name="age"
        value="<?= htmlspecialchars($data['age']) ?>"
        class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Gender</label>
    <select name="gender" class="form-select">
        <option value="">Select</option>
        <option value="Male" <?= $data['gender']=='Male'?'selected':'' ?>>Male</option>
        <option value="Female" <?= $data['gender']=='Female'?'selected':'' ?>>Female</option>
        <option value="Other" <?= $data['gender']=='Other'?'selected':'' ?>>Other</option>
    </select>
</div>

<!-- EDUCATION & EXPERIENCE -->

<div class="col-md-4 position-relative">
    <label class="form-label">Qualification</label>
    <input type="text" name="qualification"
        value="<?= htmlspecialchars($data['qualification']) ?>"
        class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Field of Study</label>
    <input type="text" name="field_of_study"
        value="<?= htmlspecialchars($data['field_of_study']) ?>"
        class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Total Experience</label>
    <input type="number" step="0.1" name="total_experience"
        value="<?= htmlspecialchars($data['total_experience']) ?>"
        class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Relevant Experience</label>
    <input type="number" step="0.1" name="relevant_experience"
        value="<?= htmlspecialchars($data['relevant_experience']) ?>"
        class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Current Job Title</label>
    <input type="text" name="current_job_title"
        value="<?= htmlspecialchars($data['current_job_title']) ?>"
        class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Occupation Mapping</label>
    <input type="text" name="occupation_mapping"
        value="<?= htmlspecialchars($data['occupation_mapping']) ?>"
        class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Target Country</label>
    <select name="target_country" class="form-select">
        <?php
        $countries = ['Canada','Australia','UK','Germany','New Zealand'];
        foreach($countries as $c){
            $selected = $data['target_country']==$c?'selected':'';
            echo "<option value='$c' $selected>$c</option>";
        }
        ?>
    </select>
</div>

<!-- LANGUAGE -->

<div class="col-md-4 position-relative">
    <label class="form-label">English Test Taken</label>
    <select name="english_test_taken" class="form-select">
        <option value="Yes" <?= $data['english_test_taken']=='Yes'?'selected':'' ?>>Yes</option>
        <option value="No" <?= $data['english_test_taken']=='No'?'selected':'' ?>>No</option>
    </select>
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Test Type</label>
    <select name="test_type" class="form-select">
        <?php
        $tests = ['IELTS','PTE','TOEFL'];
        foreach($tests as $t){
            $selected = $data['test_type']==$t?'selected':'';
            echo "<option value='$t' $selected>$t</option>";
        }
        ?>
    </select>
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Overall Score</label>
    <input type="text" name="overall_score"
        value="<?= htmlspecialchars($data['overall_score']) ?>"
        class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Individual Scores</label>
    <input type="text" name="individual_scores"
        value="<?= htmlspecialchars($data['individual_scores']) ?>"
        class="form-control">
</div>

<!-- FAMILY -->

<div class="col-md-4 position-relative">
    <label class="form-label">Marital Status</label>
    <select name="marital_status" class="form-select">
        <option value="Single" <?= $data['marital_status']=='Single'?'selected':'' ?>>Single</option>
        <option value="Married" <?= $data['marital_status']=='Married'?'selected':'' ?>>Married</option>
    </select>
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Spouse Included</label>
    <select name="spouse_included" class="form-select">
        <option value="Yes" <?= $data['spouse_included']=='Yes'?'selected':'' ?>>Yes</option>
        <option value="No" <?= $data['spouse_included']=='No'?'selected':'' ?>>No</option>
    </select>
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Spouse Qualification</label>
    <input type="text" name="spouse_qualification"
        value="<?= htmlspecialchars($data['spouse_qualification']) ?>"
        class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Spouse Experience</label>
    <input type="number" step="0.1" name="spouse_experience"
        value="<?= htmlspecialchars($data['spouse_experience']) ?>"
        class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Spouse English</label>
    <input type="text" name="spouse_english"
        value="<?= htmlspecialchars($data['spouse_english']) ?>"
        class="form-control">
</div>

<!-- OTHER -->

<div class="col-md-4 position-relative">
    <label class="form-label">Passport Available</label>
    <select name="passport_available" class="form-select">
        <option value="Yes" <?= $data['passport_available']=='Yes'?'selected':'' ?>>Yes</option>
        <option value="No" <?= $data['passport_available']=='No'?'selected':'' ?>>No</option>
    </select>
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Funds Available</label>
    <input type="text" name="funds_available"
        value="<?= htmlspecialchars($data['funds_available']) ?>"
        class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Previous Visa Refusal</label>
    <input type="text" name="previous_visa_refusal"
        value="<?= htmlspecialchars($data['previous_visa_refusal']) ?>"
        class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Source</label>
    <input type="text" name="source"
        value="<?= htmlspecialchars($data['source']) ?>"
        class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Lead Status</label>
    <select name="lead_status" class="form-select">
        <?php
        $statuses = ['New','Contacted','Interested','Not Eligible'];
        foreach($statuses as $s){
            $selected = $data['lead_status']==$s?'selected':'';
            echo "<option value='$s' $selected>$s</option>";
        }
        ?>
    </select>
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Follow-up Date</label>
    <input type="date" name="followup_date"
        value="<?= htmlspecialchars($data['followup_date']) ?>"
        class="form-control">
</div>

<div class="col-md-4 position-relative">
    <label class="form-label">Total Fee (₹)</label>
    <input type="number" step="0.01" name="total_fee"
        value="<?= htmlspecialchars($data['total_fee']) ?>"
        class="form-control">
</div>

<div class="col-12 position-relative">
    <label class="form-label">Notes</label>
    <textarea name="notes" rows="3"
        class="form-control"><?= htmlspecialchars($data['notes']) ?></textarea>
</div>

<div class="col-12">
    <button type="submit" class="btn btn-primary">
        Update PR Details
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