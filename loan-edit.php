<?php
session_start();
include "db.php";
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('loan_module')) {
    http_response_code(403);
    die("Access Denied");
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid Loan ID");
}

$loan_id = intval($_GET['id']);

/* FETCH LOAN RECORD */
$loan_query = mysqli_query($conn, "
    SELECT ln.*, l.lead_name, l.mobile, l.email
    FROM loans ln
    JOIN leads l ON l.id = ln.lead_id
    WHERE ln.id = $loan_id
");

$loan = mysqli_fetch_assoc($loan_query);

if (!$loan) {
    die("Loan not found");
}

/* UPDATE LOGIC */
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

    $update = "
    UPDATE loans SET
        loan_start_date = " . ($loan_start_date ? "'$loan_start_date'" : "NULL") . ",
        applied_banks = '$applied_banks',
        approved_bank_name = '$approved_bank_name',
        loan_status = '$loan_status',
        pending_documents = '$pending_documents',
        student_cibil_score = '$student_cibil_score',
        co1_name = '$co1_name',
        co1_relation = '$co1_relation',
        co1_cibil = '$co1_cibil',
        co2_name = '$co2_name',
        co2_relation = '$co2_relation',
        co2_cibil = '$co2_cibil',
        any_other_loans = '$any_other_loans',
        other_loan_details = '$other_loan_details',
        loan_sanctioned_date = " . ($loan_sanctioned_date ? "'$loan_sanctioned_date'" : "NULL") . ",
        loan_disbursement_date = " . ($loan_disbursement_date ? "'$loan_disbursement_date'" : "NULL") . ",
        loan_sanctioned_amount = " . ($loan_sanctioned_amount !== null ? "'$loan_sanctioned_amount'" : "NULL") . ",
        loan_disbursement_amount = " . ($loan_disbursement_amount !== null ? "'$loan_disbursement_amount'" : "NULL") . ",
        previous_rejections = '$previous_rejections',
        rejection_details = '$rejection_details',
        comments = '$comments'
    WHERE id = $loan_id
    ";

    if (mysqli_query($conn, $update)) {
        header("Location: loan-view.php?id=" . $loan_id);
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
    <meta name="description" content="Riho admin is super flexible, powerful, clean &amp; modern responsive bootstrap 5 admin template with unlimited possibilities.">
    <meta name="keywords" content="admin template, Riho admin template, dashboard template, flat admin template, responsive admin template, web app">
    <meta name="author" content="pixelstrap">
    <link rel="icon" href="assets/images/favicon.png" type="image/x-icon">
    <link rel="shortcut icon" href="assets/images/favicon.png" type="image/x-icon">
    <title> Loan data edit</title>
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
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/datatables.css">
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
      <div class="page-header">
        <div class="header-wrapper row m-0">
          <form class="form-inline search-full col" action="#" method="get">
            <div class="form-group w-100">
              <div class="Typeahead Typeahead--twitterUsers">
                <div class="u-posRelative"> 
                  <input class="demo-input Typeahead-input form-control-plaintext w-100" type="text" placeholder="Search Riho .." name="q" title="" autofocus="">
                  <div class="spinner-border Typeahead-spinner" role="status"><span class="sr-only">Loading... </span></div><i class="close-search" data-feather="x"></i>
                </div>
                <div class="Typeahead-menu"> </div>
              </div>
            </div>
          </form>
          <div class="header-logo-wrapper col-auto p-0">  
            <div class="logo-wrapper"> <a href="index.html"><img class="img-fluid for-light" src="assets/images/logo/logo_dark.png" alt="logo-light"><img class="img-fluid for-dark" src="assets/images/logo/logo.png" alt="logo-dark"></a></div>
            <div class="toggle-sidebar"> <i class="status_toggle middle sidebar-toggle" data-feather="align-center"></i></div>
          </div>
          <div class="left-header col-xxl-5 col-xl-6 col-lg-5 col-md-4 col-sm-3 p-0">
            <div> <a class="toggle-sidebar" href="#"> <i class="iconly-Category icli"> </i></a>
              <div class="d-flex align-items-center gap-2 ">
                <h4 class="f-w-600">Welcome User</h4><img class="mt-0" src="assets/images/hand.gif" alt="hand-gif">
              </div>
            </div>
            <div class="welcome-content d-xl-block d-none"><span class="text-truncate col-12">Here’s what’s happening with your Business today. </span></div>
          </div>
          <div class="nav-right col-xxl-7 col-xl-6 col-md-7 col-8 pull-right right-header p-0 ms-auto">
            <ul class="nav-menus"> 
              <li class="d-md-block d-none"> 
                <div class="form search-form mb-0">
                  <div class="input-group"><span class="input-icon">
                      <svg>
                        <use href="assets/svg/icon-sprite.svg#search-header"></use>
                      </svg>
                      <input class="w-100" type="search" placeholder="Search"></span></div>
                </div>
              </li>
              
              <li> 
                <div class="mode"><i class="moon" data-feather="moon"> </i></div>
              </li>
            </ul>
          </div>
          <script class="result-template" type="text/x-handlebars-template">
            <div class="ProfileCard u-cf">                        
            <div class="ProfileCard-avatar"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-airplay m-0"><path d="M5 17H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1"></path><polygon points="12 15 17 21 7 21 12 15"></polygon></svg></div>
            <div class="ProfileCard-details"> 
            <div class="ProfileCard-realName">{{name}}</div>
            </div> 
            </div>
          </script>
          <script class="empty-template" type="text/x-handlebars-template"><div class="EmptyMessage">Your search turned up 0 results. This most likely means the backend is down, yikes!</div></script>
        </div>
      </div>
      <!-- Page Header Ends                              -->
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
                  <h4>Default</h4>
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
                    <h4>Edit Student Loan Application</h4>
                </div>
                <div class="card-body">
                    <form method="POST">

                        <!-- Lead Info -->
                        <div class="mb-3">
                            <label>Student Name</label>
                            <input type="text" class="form-control"
                                value="<?= htmlspecialchars($loan['lead_name']) ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label>Contact Number</label>
                            <input type="text" class="form-control"
                                value="<?= htmlspecialchars($loan['mobile']) ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label>Email</label>
                            <input type="text" class="form-control"
                                value="<?= htmlspecialchars($loan['email']) ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label>Loan Start Date</label>
                            <input type="date" name="loan_start_date"
                                value="<?= $loan['loan_start_date'] ?>"
                                class="form-control">
                        </div>

                        <div class="mb-3">
                            <label>Applied Banks</label>
                            <textarea name="applied_banks" class="form-control"><?= htmlspecialchars($loan['applied_banks']) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label>Approved Bank Name</label>
                            <input type="text" name="approved_bank_name"
                                value="<?= htmlspecialchars($loan['approved_bank_name']) ?>"
                                class="form-control">
                        </div>

                        <div class="mb-3">
                            <label>Loan Status</label>
                            <input type="text" name="loan_status"
                                value="<?= htmlspecialchars($loan['loan_status']) ?>"
                                class="form-control">
                        </div>

                        <div class="mb-3">
                            <label>Pending Documents</label>
                            <textarea name="pending_documents" class="form-control"><?= htmlspecialchars($loan['pending_documents']) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label>Student CIBIL Score</label>
                            <input type="number" name="student_cibil_score"
                                value="<?= $loan['student_cibil_score'] ?>"
                                class="form-control">
                        </div>
                        <hr>
<h5>Co-Applicant 1</h5>

<input type="text" name="co1_name" 
    value="<?= htmlspecialchars($loan['co1_name']) ?>" 
    class="form-control mb-2" placeholder="Name">

<input type="text" name="co1_relation" 
    value="<?= htmlspecialchars($loan['co1_relation']) ?>" 
    class="form-control mb-2" placeholder="Relation">

<input type="number" name="co1_cibil" 
    value="<?= $loan['co1_cibil'] ?>" 
    class="form-control mb-3" placeholder="CIBIL Score">

<h5>Co-Applicant 2</h5>

<input type="text" name="co2_name" 
    value="<?= htmlspecialchars($loan['co2_name']) ?>" 
    class="form-control mb-2" placeholder="Name">

<input type="text" name="co2_relation" 
    value="<?= htmlspecialchars($loan['co2_relation']) ?>" 
    class="form-control mb-2" placeholder="Relation">

<input type="number" name="co2_cibil" 
    value="<?= $loan['co2_cibil'] ?>" 
    class="form-control mb-3" placeholder="CIBIL Score">
                        

                        <div class="row">
                            <div class="col-md-6">
                                <label>Loan Sanctioned Date</label>
                                <input type="date" name="loan_sanctioned_date"
                                    value="<?= $loan['loan_sanctioned_date'] ?>"
                                    class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label>Loan Disbursement Date</label>
                                <input type="date" name="loan_disbursement_date"
                                    value="<?= $loan['loan_disbursement_date'] ?>"
                                    class="form-control">
                            </div>

                            <div class="col-md-6 mt-3">
                                <label>Loan Sanctioned Amount</label>
                                <input type="number" step="0.01"
                                    name="loan_sanctioned_amount"
                                    value="<?= $loan['loan_sanctioned_amount'] ?>"
                                    class="form-control">
                            </div>

                            <div class="col-md-6 mt-3">
                                <label>Loan Disbursement Amount</label>
                                <input type="number" step="0.01"
                                    name="loan_disbursement_amount"
                                    value="<?= $loan['loan_disbursement_amount'] ?>"
                                    class="form-control">
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success">
                                Update Loan
                            </button>
                            <a href="loan-view.php?id=<?= $loan_id ?>" class="btn btn-secondary">
                                Cancel
                            </a>
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
        <footer class="footer">
          <div class="container-fluid">
            <div class="row">
              <div class="col-md-12 footer-copyright text-center">
                <p class="mb-0">Copyright 2025 © Indian Overseas Service  </p>
              </div>
            </div>
          </div>
        </footer>
      </div>
    </div>
    <!-- latest jquery-->
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
    <!-- calendar js-->
    <script src="assets/js/datatable/datatables/jquery.dataTables.min.js"></script>
    <script src="assets/js/datatable/datatables/datatable.custom.js"></script>
    <!-- Plugins JS Ends-->
    <!-- Theme js-->
    <script src="assets/js/script.js"></script>
    <script src="assets/js/theme-customizer/customizer.js"></script>
  </body>
</html>