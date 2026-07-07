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

$lead_id = $_GET['lead_id'] ?? '';

if (empty($lead_id)) {
    header("Location: lead-list.php");
    exit;
}

/* Fetch lead details */
$lead_sql = "SELECT * FROM leads WHERE id = ?";
$lead_stmt = $conn->prepare($lead_sql);
$lead_stmt->bind_param("i", $lead_id);
$lead_stmt->execute();
$lead = $lead_stmt->get_result()->fetch_assoc();

if (isset($_POST['submit'])) {

    $lead_id   = $_POST['lead_id'];
    $country   = $_POST['country'];

    // From form
    $application_status   = $_POST['application_status'] ?? '';
    $visa_status          = $_POST['visa_status'] ?? '';
    $processing_start_date= $_POST['processing_start_date'] ?? null;
    $passport_number      = $_POST['passport_number'] ?? '';
    $pending_documents    = $_POST['documents_required'] ?? '';
    $notes                = $_POST['notes'] ?? '';

    $visa_type     = 'student';
    $visa_category = 'student visa';

    $assigned_to = $_SESSION['user_id'];
    $created_by  = $_SESSION['user_id'];
    

    /* Insert into visas table */
    $sql = "INSERT INTO visas
        (lead_id, visa_type, country, visa_category, status, visa_status,
         processing_start_date, passport_number, pending_documents,
         assigned_to, created_by, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "issssssssiis",
        $lead_id,
        $visa_type,
        $country,
        $visa_category,
        $application_status,
        $visa_status,
        $processing_start_date,
        $passport_number,
        $pending_documents,
        $assigned_to,
        $created_by,
        $notes
    );

    if ($stmt->execute()) {

        $visa_id = $conn->insert_id;

        /* Student specific details */
        $course               = $_POST['course'] ?? '';
        $university           = $_POST['university'] ?? '';
        $offer_letter_status  = $_POST['offer_letter_status'] ?? '';
        $loan_required        = $_POST['loan_required'] ?? '';
        $intake               = $_POST['intake'] ?? '';
        $university_deadline  = $_POST['university_deadline'] ?? '';
        $student_address      = $_POST['student_address'] ?? '';
        $tuition_fees         = $_POST['tuition_fees'] ?? '';
        $course_duration      = $_POST['course_duration'] ?? '';
        

      
      $detail_sql = "INSERT INTO student_visa_details
(
    visa_id,
    course,
    university,
    application_status,
    offer_letter_status,
    loan_required,
    intake,
    university_deadline,
    student_address,
    tuition_fees,
    course_duration
    
)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$detail_stmt = $conn->prepare($detail_sql);

$detail_stmt->bind_param(
    "issssssssss",
    $visa_id,
    $course,
    $university,
    $application_status,
    $offer_letter_status,
    $loan_required,
    $intake,
    $university_deadline,
    $student_address,
    $tuition_fees,
    $course_duration
);

$detail_stmt->execute();

        header("Location: student-visa-list.php");
        exit;

    } else {
        echo "Error saving student visa";
    }
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
    <!--<div class="loader-wrapper">
        <div class="loader">
            <div class="loader4"></div>
        </div>
    </div>-->
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
    <input class="form-control" value="<?php echo $lead['lead_name']; ?>" readonly>
</div>

<div class="col-md-4">
    <label class="form-label">Contact Number</label>
    <input class="form-control" value="<?php echo $lead['mobile']; ?>" readonly>
</div>

<div class="col-md-4">
    <label class="form-label">Email</label>
    <input class="form-control" value="<?php echo $lead['email']; ?>" readonly>
</div>

<!-- Student Process -->
<div class="col-md-4">
    <label class="form-label">Student Process Start Date</label>
    <input type="date" name="processing_start_date" class="form-control">
</div>

<div class="col-md-4">
    <label class="form-label">Passport Number</label>
    <input type="text" name="passport_number" class="form-control">
</div>

<div class="col-md-4">
    <label class="form-label">Country</label>
    <input type="text" name="country" class="form-control">
</div>

<!-- Academic Details -->
<div class="col-md-4">
    <label class="form-label">Course</label>
    <input type="text" name="course" class="form-control">
</div>

<div class="col-md-4">
    <label class="form-label">University</label>
    <input type="text" name="university" class="form-control">
</div>

<div class="col-md-4">
    <label class="form-label">Tuition Fees</label>
    <input type="text" name="tuition_fees" class="form-control">
</div>

<div class="col-md-4">
    <label class="form-label">Course Duration</label>
    <input type="text" name="course_duration" class="form-control">
</div>

<div class="col-md-4">
    <label class="form-label">Intake</label>
    <input type="text" name="intake" class="form-control">
</div>

<div class="col-md-4">
    <label class="form-label">University Deadline</label>
    <input type="text" name="university_deadline" class="form-control">
</div>

<div class="col-md-6">
    <label class="form-label">Student Address</label>
    <textarea name="student_address" class="form-control"></textarea>
</div>

<!-- Status Section -->
<div class="col-md-4">
    <label class="form-label">Application Status</label>
    <input type="text" name="application_status" class="form-control">
</div>

<div class="col-md-4">
    <label class="form-label">Documents Required</label>
    <input type="text" name="documents_required" class="form-control">
</div>

<div class="col-md-4">
    <label class="form-label">Offer Letter Status</label>
   <select name="offer_letter_status" class="form-control">
    <option value="">Select</option>
    <option value="conditional">Conditional</option>
    <option value="unconditional">Unconditional</option>
    <option value="approved">Approved</option>
</select>
</div>

<div class="col-md-4">
    <label class="form-label">Visa Status</label>
    <select name="visa_status" class="form-control">
        <option value="">Select</option>
        <option value="pending">Pending</option>
        <option value="approved">Approved</option>
        <option value="rejected">Rejected</option>
    </select>
</div>

<div class="col-md-4">
    <label class="form-label">Loan Required</label>
    <select name="loan_required" class="form-select">
        <option>No</option>
        <option>Yes</option>
    </select>
</div>


<div class="col-md-12">
    <label class="form-label">Comment</label>
    <textarea name="notes" class="form-control"></textarea>
</div>

<div class="col-12">
    <button class="btn btn-primary" type="submit" name="submit">Submit Form</button>
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
