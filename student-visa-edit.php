<?php
session_start();
require 'db.php';
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('student_visa')) {
    http_response_code(403);
    die("Access Denied");
}


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'] ?? '';

$visa_id = $_GET['id'] ?? '';

if (empty($visa_id)) {
    header("Location: student-visa-list.php");
    exit;
}

/* ================= FETCH EXISTING DATA WITH SECURITY ================= */

if ($role === 'admin') {

    $sql = "
        SELECT v.*, s.*, l.lead_name, l.mobile, l.email
        FROM visas v
        LEFT JOIN student_visa_details s ON v.id = s.visa_id
        JOIN leads l ON l.id = v.lead_id
        WHERE v.id = ?
        AND v.visa_type = 'student'
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $visa_id);

} else {

    $sql = "
        SELECT v.*, s.*, l.lead_name, l.mobile, l.email
        FROM visas v
        LEFT JOIN student_visa_details s ON v.id = s.visa_id
        JOIN leads l ON l.id = v.lead_id
        WHERE v.id = ?
        AND v.visa_type = 'student'
        AND l.assigned_to = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $visa_id, $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "Access denied";
    exit;
}

$data = $result->fetch_assoc();


/* ================= UPDATE LOGIC ================= */

if (isset($_POST['submit'])) {

    $processing_start_date = $_POST['processing_start_date'] ?? null;
    $passport_number       = $_POST['passport_number'] ?? '';
    $country               = $_POST['country'] ?? '';
    $application_status    = $_POST['application_status'] ?? '';
    $visa_status           = $_POST['visa_status'] ?? '';
    $documents_required    = $_POST['documents_required'] ?? '';
    $notes                 = $_POST['notes'] ?? '';
    
    /* Update visas table */
    $updateVisa = $conn->prepare("
        UPDATE visas SET
            country = ?,
            status = ?,
            visa_status = ?,
            processing_start_date = ?,
            passport_number = ?,
            pending_documents = ?,
            notes = ?
        WHERE id = ?
    ");

    $updateVisa->bind_param(
        "sssssssi",
        $country,
        $application_status,
        $visa_status,
        $processing_start_date,
        $passport_number,
        $documents_required,
        $notes,
        $visa_id
    );

    $updateVisa->execute();


    /* Student specific update */
    $course              = $_POST['course'] ?? '';
    $university          = $_POST['university'] ?? '';
    $offer_letter_status = $_POST['offer_letter_status'] ?? '';
    $loan_required       = $_POST['loan_required'] ?? '';
    $intake              = $_POST['intake'] ?? '';
    $university_deadline = $_POST['university_deadline'] ?? '';
    $student_address     = $_POST['student_address'] ?? '';
    $tuition_fees        = $_POST['tuition_fees'] ?? '';
    $course_duration     = $_POST['course_duration'] ?? '';
    

    $updateDetails = $conn->prepare("
    UPDATE student_visa_details SET
        course = ?,
        university = ?,
        application_status = ?,
        offer_letter_status = ?,
        loan_required = ?,
        intake = ?,
        university_deadline = ?,
        student_address = ?,
        tuition_fees = ?,
        course_duration = ?
    WHERE visa_id = ?
");

$updateDetails->bind_param(
    "ssssssssssi",
    $course,
    $university,
    $application_status,
    $offer_letter_status,
    $loan_required,
    $intake,
    $university_deadline,
    $student_address,
    $tuition_fees,
    $course_duration,
    $visa_id
);

$updateDetails->execute();

    header("Location: student-visa-list.php?updated=1");
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
    <title>Student Visa Add</title>
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
                                <!-- <h4>Student Visa Application</h4> -->
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
                    <h4>Student Visa Application</h4>
                </div>
                <div class="card-body">

<form class="row g-3" method="POST">
<input type="hidden" name="lead_id" value="<?php echo $lead_id; ?>">

<!-- Lead Info (Readonly) -->
<div class="col-md-4">
    <label class="form-label">Student Name</label>
    <input class="form-control" value="<?php echo $data['lead_name']; ?>" readonly>
</div>

<div class="col-md-4">
    <label class="form-label">Contact Number</label>
    <input class="form-control" value="<?php echo $data['mobile']; ?>" readonly>
</div>

<div class="col-md-4">
    <label class="form-label">Email</label>
    <input class="form-control" value="<?php echo $data['email']; ?>" readonly>
</div>

<!-- Student Process -->
<div class="col-md-4">
    <label class="form-label">Student Process Start Date</label>
    <input type="date" 
       name="processing_start_date" 
       class="form-control"
       value="<?= htmlspecialchars($data['processing_start_date'] ?? '') ?>">
</div>

<div class="col-md-4">
    <label class="form-label">Passport Number</label>
    <input type="text" 
       name="passport_number" 
       class="form-control"
       value="<?= htmlspecialchars($data['passport_number'] ?? '') ?>">
</div>

<div class="col-md-4">
    <label class="form-label">Country</label>
    <input type="text" 
       name="country" 
       class="form-control"
       value="<?= htmlspecialchars($data['country'] ?? '') ?>">
</div>

<!-- Academic Details -->
<div class="col-md-4">
    <label class="form-label">Course</label>
    <input type="text" 
       name="course" 
       class="form-control"
       value="<?= htmlspecialchars($data['course'] ?? '') ?>">
</div>

<div class="col-md-4">
    <label class="form-label">University</label>
    <input type="text" 
       name="university" 
       class="form-control"
       value="<?= htmlspecialchars($data['university'] ?? '') ?>">
</div>

<div class="col-md-4">
    <label class="form-label">Tuition Fees</label>
    <input type="number" 
       name="tuition_fees" 
       class="form-control"
       value="<?= htmlspecialchars($data['tuition_fees'] ?? '') ?>">
</div>

<div class="col-md-4">
    <label class="form-label">Course Duration</label>
    <input type="text" 
       name="course_duration" 
       class="form-control"
       value="<?= htmlspecialchars($data['course_duration'] ?? '') ?>">
</div>

<div class="col-md-4">
    <label class="form-label">Intake</label>
   <input type="text" 
       name="intake" 
       class="form-control"
       value="<?= htmlspecialchars($data['intake'] ?? '') ?>">
</div>

<div class="col-md-4">
    <label class="form-label">University Deadline</label>
    <input type="text" 
       name="university_deadline" 
       class="form-control"
       value="<?= htmlspecialchars($data['university_deadline'] ?? '') ?>">
</div>

<div class="col-md-6">
    <label class="form-label">Student Address</label>
    <textarea name="student_address" class="form-control"><?= 
    htmlspecialchars($data['student_address'] ?? '') 
?></textarea>
</div>

<!-- Status Section -->
<div class="col-md-4">
    <label class="form-label">Application Status</label>
    <input type="text" 
       name="application_status" 
       class="form-control"
       value="<?= htmlspecialchars($data['status'] ?? '') ?>">
</div>

<div class="col-md-4">
    <label class="form-label">Documents Required</label>
    <input type="text" 
       name="documents_required" 
       class="form-control"
       value="<?= htmlspecialchars($data['pending_documents'] ?? '') ?>">
</div>

<div class="col-md-4">
    <label class="form-label">Offer Letter Status</label>
   <select name="offer_letter_status" class="form-select">
    <option value="">Select</option>

    <option value="conditional"
        <?= ($data['offer_letter_status'] ?? '') == 'conditional' ? 'selected' : '' ?>>
        Conditional
    </option>

    <option value="unconditional"
        <?= ($data['offer_letter_status'] ?? '') == 'unconditional' ? 'selected' : '' ?>>
        Unconditional
    </option>

    <option value="approved"
        <?= ($data['offer_letter_status'] ?? '') == 'approved' ? 'selected' : '' ?>>
        Approved
    </option>
</select>
</div>

<div class="col-md-4">
    <label class="form-label">Visa Status</label>
    <select name="visa_status" class="form-select">
    <option value="">Select</option>
    <option value="pending" 
        <?= ($data['visa_status'] ?? '') == 'pending' ? 'selected' : '' ?>>
        Pending
    </option>

    <option value="approved" 
        <?= ($data['visa_status'] ?? '') == 'approved' ? 'selected' : '' ?>>
        Approved
    </option>

    <option value="rejected" 
        <?= ($data['visa_status'] ?? '') == 'rejected' ? 'selected' : '' ?>>
        Rejected
    </option>
</select>
</div>

<div class="col-md-4">
    <label class="form-label">Loan Required</label>
    <select name="loan_required" class="form-select">

    <option value="No"
        <?= ($data['loan_required'] ?? '') == 'No' ? 'selected' : '' ?>>
        No
    </option>

    <option value="Yes"
        <?= ($data['loan_required'] ?? '') == 'Yes' ? 'selected' : '' ?>>
        Yes
    </option>

</select>
</div>

<div class="col-md-12">
    <label class="form-label">Comment</label>
     <textarea name="notes" class="form-control"><?= 
    htmlspecialchars($data['notes'] ?? '') 
?></textarea>
</div>

<div class="col-12">
    <button class="btn btn-primary" type="submit" name="submit">Update Case</button>
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


</body>

</html>
