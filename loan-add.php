<?php
session_start();
include "db.php"; // your DB connection file
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('loan_module')) {
    http_response_code(403);
    die("Access Denied");
}

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Validate lead_id
if (!isset($_GET['lead_id']) || empty($_GET['lead_id'])) {
    die("Invalid Lead ID");
}

$lead_id = intval($_GET['lead_id']);

// Fetch lead details (for display only)
$lead_query = mysqli_query($conn, "SELECT * FROM leads WHERE id = $lead_id");
$lead = mysqli_fetch_assoc($lead_query);

if (!$lead) {
    die("Lead not found");
}

// Save form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $loan_start_date = $_POST['loan_start_date'] ?? null;
    $applied_banks = mysqli_real_escape_string($conn, $_POST['applied_banks']);
    $approved_bank_name = mysqli_real_escape_string($conn, $_POST['approved_bank_name']);
    $loan_status = mysqli_real_escape_string($conn, $_POST['loan_status']);
    $pending_documents = mysqli_real_escape_string($conn, $_POST['pending_documents']);
    $student_cibil_score = intval($_POST['student_cibil_score']);

    $loan_sanctioned_date = $_POST['loan_sanctioned_date'] ?? null;
    $loan_disbursement_date = $_POST['loan_disbursement_date'] ?? null;

    $loan_sanctioned_amount = !empty($_POST['loan_sanctioned_amount']) 
    ? floatval($_POST['loan_sanctioned_amount']) 
    : null;

    $loan_disbursement_amount = !empty($_POST['loan_disbursement_amount']) 
    ? floatval($_POST['loan_disbursement_amount']) 
    : null;

    $co1_name = mysqli_real_escape_string($conn, $_POST['co1_name']);
    $co1_relation = mysqli_real_escape_string($conn, $_POST['co1_relation']);
    $co1_cibil = intval($_POST['co1_cibil']);

    $co2_name = mysqli_real_escape_string($conn, $_POST['co2_name']);
    $co2_relation = mysqli_real_escape_string($conn, $_POST['co2_relation']);
    $co2_cibil = intval($_POST['co2_cibil']);
    

    $any_other_loans = $_POST['any_other_loans'];
    $other_loan_details = mysqli_real_escape_string($conn, $_POST['other_loan_details']);

    $previous_rejections = $_POST['previous_rejections'];
    $rejection_details = mysqli_real_escape_string($conn, $_POST['rejection_details']);

    $comments = mysqli_real_escape_string($conn, $_POST['comments']);

    $created_by = $_SESSION['user_id'];

    $insert = "INSERT INTO loans (
    lead_id, loan_start_date, applied_banks, approved_bank_name,
    loan_status, pending_documents, student_cibil_score,
    co1_name, co1_relation, co1_cibil,
    co2_name, co2_relation, co2_cibil,
    any_other_loans, other_loan_details,
    loan_sanctioned_date, loan_disbursement_date,
    loan_sanctioned_amount, loan_disbursement_amount,
    previous_rejections, rejection_details,
    comments, created_by
) VALUES (
    '$lead_id', '$loan_start_date', '$applied_banks', '$approved_bank_name',
    '$loan_status', '$pending_documents', '$student_cibil_score',
    '$co1_name', '$co1_relation', '$co1_cibil',
    '$co2_name', '$co2_relation', '$co2_cibil',
    '$any_other_loans', '$other_loan_details',
    " . ($loan_sanctioned_date ? "'$loan_sanctioned_date'" : "NULL") . ",
    " . ($loan_disbursement_date ? "'$loan_disbursement_date'" : "NULL") . ",
    " . ($loan_sanctioned_amount !== null ? "'$loan_sanctioned_amount'" : "NULL") . ",
    " . ($loan_disbursement_amount !== null ? "'$loan_disbursement_amount'" : "NULL") . ",
    '$previous_rejections', '$rejection_details',
    '$comments', '$created_by'
)";

    if (mysqli_query($conn, $insert)) {
        header("Location: loan-list.php");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
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
    <title>Loan Add</title>
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
        <?php include "header.php"; ?>
        <!-- Page Header Ends -->
        <!-- Page Body Start-->
        <div class="page-body-wrapper">
            <!-- Page Sidebar Start-->
            <?php include "sidebar.php"; ?>

            <!-- Page Sidebar Ends-->
            <div class="page-body">
                <div class="container-fluid">
                    <div class="page-title">
                        <div class="row">
                            <div class="col-6">
                                <!-- <h4>Default</h4> -->
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
        <div class="col-xl-8 offset-xl-2">
            <div class="card">
                <div class="card-header text-center">
                    <h4>Student Loan Application</h4>
                </div>
                <div class="card-body">
                    <form method="POST">

                        <!-- Lead Info (Readonly) -->
                        <div class="mb-3">
                            <label>Student Name</label>
                            <input type="text" class="form-control" 
                                value="<?= $lead['lead_name']; ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label>Contact Number</label>
                            <input type="text" class="form-control" 
                                value="<?= $lead['mobile']; ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label>Email</label>
                            <input type="text" class="form-control" 
                                value="<?= $lead['email']; ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label>Loan Start Date</label>
                            <input type="date" name="loan_start_date" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label>Applied Banks</label>
                            <textarea name="applied_banks" class="form-control"></textarea>
                        </div>

                        <div class="mb-3">
                            <label>Approved Bank Name</label>
                            <input type="text" name="approved_bank_name" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label>Loan Status</label>
                            <input type="text" name="loan_status" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label>Pending Documents</label>
                            <textarea name="pending_documents" class="form-control"></textarea>
                        </div>

                        <div class="mb-3">
                            <label>Student CIBIL Score</label>
                            <input type="number" name="student_cibil_score" class="form-control">
                        </div>

                        <hr>
                        <h5>Co-Applicant 1</h5>

                        <input type="text" name="co1_name" class="form-control mb-2" placeholder="Name">
                        <input type="text" name="co1_relation" class="form-control mb-2" placeholder="Relation">
                        <input type="number" name="co1_cibil" class="form-control mb-3" placeholder="CIBIL Score">

                        <h5>Co-Applicant 2</h5>

                        <input type="text" name="co2_name" class="form-control mb-2" placeholder="Name">
                        <input type="text" name="co2_relation" class="form-control mb-2" placeholder="Relation">
                        <input type="number" name="co2_cibil" class="form-control mb-3" placeholder="CIBIL Score">

                        <div class="mb-3">
                            <label>Any Other Loans?</label>
                            <select name="any_other_loans" class="form-control">
                                <option>No</option>
                                <option>Yes</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label>Other Loan Details</label>
                            <textarea name="other_loan_details" class="form-control"></textarea>
                        </div>
                        
                        
                        <div class="row">
                        <div class="col-md-6">
                            <label>Loan Sanctioned Date</label>
                            <input type="date" name="loan_sanctioned_date" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label>Loan Disbursement Date</label>
                            <input type="date" name="loan_disbursement_date" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label>Loan Sanctioned Amount</label>
                            <input type="number" step="0.01" name="loan_sanctioned_amount" class="form-control">
                        </div>

                       <div class="col-md-6">
                            <label>Loan Disbursement Amount</label>
                            <input type="number" step="0.01" name="loan_disbursement_amount" class="form-control">
                        </div>
                        </div>

                        <div class="mb-4">
                            <label>Any Previous Rejections?</label>
                            <select name="previous_rejections" class="form-control">
                                <option>No</option>
                                <option>Yes</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label>Rejection Details</label>
                            <textarea name="rejection_details" class="form-control"></textarea>
                        </div>

                        <div class="mb-4">
                            <label>Comments</label>
                            <textarea name="comments" class="form-control"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Save Loan</button>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
                <!-- Container-fluid Ends-->
            </div>
            <!-- footer start-->
            <?php include "footer.php"; ?>
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
    <script>
     document.getElementById("has_coapplicant").addEventListener("change", function () {
        let section = document.getElementById("coapplicant_section");

           if (this.value === "Yes") {
            section.style.display = "block";
            } else {
            section.style.display = "none";

             // Clear inputs if No
             section.querySelectorAll("input").forEach(input => input.value = "");
            }
            });
    </script>
    <script>
    document.querySelector("form").addEventListener("submit", function(e){

       let hasCo = document.getElementById("has_coapplicant").value;

       if(hasCo === "Yes"){
         let names = document.querySelectorAll("input[name='co_name[]']");
         let phones = document.querySelectorAll("input[name='co_phone[]']");

         let filled = false;

         for(let i=0; i<names.length; i++){
            if(names[i].value.trim() !== "" || phones[i].value.trim() !== ""){
                filled = true;
                break;
            }
        }

        if(!filled){
            alert("Please enter at least one co-applicant details.");
            e.preventDefault();
        }
       }

     });
    </script>


</body>

</html>