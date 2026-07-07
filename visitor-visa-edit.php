<?php
session_start();
include "db.php";
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('visitor_visa')) {
    http_response_code(403);
    die("Access Denied");
}

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("Invalid Access");
}

$id = intval($_GET['id']);
$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];

/* ================= FETCH WITH ROLE SECURITY ================= */

if ($role === 'admin') {

    $fetch = $conn->prepare("
        SELECT v.*, l.lead_name, l.mobile, l.email
        FROM visas v
        JOIN leads l ON l.id = v.lead_id
        WHERE v.id = ? AND v.visa_type = 'visitor'
    ");

    $fetch->bind_param("i", $id);

} else {

    $fetch = $conn->prepare("
        SELECT v.*, l.lead_name, l.mobile, l.email
        FROM visas v
        JOIN leads l ON l.id = v.lead_id
        WHERE v.id = ?
        AND v.visa_type = 'visitor'
        AND l.assigned_to = ?
    ");

    $fetch->bind_param("ii", $id, $userId);
}

$fetch->execute();
$result = $fetch->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("Unauthorized or Record not found");
}
$fetch->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $country = $_POST['country'];
    $processingStartDate = $_POST['processing_start_date'];
    $purpose = $_POST['purpose_of_visit'];
    $travelDate = $_POST['travel_date'];
    $duration = $_POST['travel_duration'];
    $passport = $_POST['passport_number'];
    $refusal = $_POST['previous_visa_refusal'];
    $previousCountries = $_POST['previous_visit_countries'];
    $applicationStatus = $_POST['application_status'];
    $visaStatus = $_POST['visa_status'];
    $pendingDocs = $_POST['pending_documents'];
    $notes = $_POST['notes'];

    $update = $conn->prepare("
        UPDATE visas SET
            country = ?,
            processing_start_date = ?,
            purpose_of_visit = ?,
            travel_date = ?,
            travel_duration = ?,
            passport_number = ?,
            previous_visa_refusal = ?,
            previous_visit_countries = ?,
            status = ?,
            visa_status = ?,
            pending_documents = ?,
            notes = ?
        WHERE id = ? AND visa_type = 'visitor'
    ");

    $update->bind_param(
        "ssssisssssssi",
        $country,
        $processingStartDate,
        $purpose,
        $travelDate,
        $duration,
        $passport,
        $refusal,
        $previousCountries,
        $applicationStatus,
        $visaStatus,
        $pendingDocs,
        $notes,
        $id
    );

    $update->execute();
    $update->close();

    header("Location: visitor-visa-list.php?updated=1");
    exit;
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
    <title> Visitors data</title>
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
  <div class="col-12">

    <div class="card shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Edit Visitor Visa Case</h4>
        <a href="visitor-visa-list.php" class="btn btn-light btn-sm">
          ← Back to List
        </a>
      </div>

      <div class="card-body">

        <form method="POST" class="row g-4">

          <!-- Applicant Info -->
          <div class="col-12">
            <div class="border-bottom pb-2 mb-3">
              <h6 class="fw-bold text-primary mb-0">Applicant Information</h6>
            </div>
          </div>

          <div class="col-md-4">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-control"
                   value="<?= htmlspecialchars($data['lead_name']) ?>" disabled>
          </div>

          <div class="col-md-4">
            <label class="form-label">Mobile</label>
            <input type="text" class="form-control"
                   value="<?= htmlspecialchars($data['mobile']) ?>" disabled>
          </div>

          <div class="col-md-4">
            <label class="form-label">Email</label>
            <input type="text" class="form-control"
                   value="<?= htmlspecialchars($data['email']) ?>" disabled>
          </div>

          <!-- Visa Details -->
          <div class="col-12 mt-4">
            <div class="border-bottom pb-2 mb-3">
              <h6 class="fw-bold text-primary mb-0">Visa & Travel Details</h6>
            </div>
          </div>

          <div class="col-md-4">
            <label class="form-label">Applying Country</label>
            <input type="text" name="country" class="form-control"
                   value="<?= htmlspecialchars($data['country']) ?>">
          </div>

          <div class="col-md-4">
            <label class="form-label">Processing Start Date</label>
            <input type="date" name="processing_start_date" class="form-control"
                   value="<?= $data['processing_start_date'] ?>">
          </div>

          <div class="col-md-4">
            <label class="form-label">Purpose of Visit</label>
            <input type="text" name="purpose_of_visit" class="form-control"
                   value="<?= htmlspecialchars($data['purpose_of_visit']) ?>">
          </div>

          <div class="col-md-4">
            <label class="form-label">Travel Date</label>
            <input type="date" name="travel_date" class="form-control"
                   value="<?= $data['travel_date'] ?>">
          </div>

          <div class="col-md-4">
            <label class="form-label">Travel Duration (Days)</label>
            <input type="number" name="travel_duration" class="form-control"
                   value="<?= $data['travel_duration'] ?>">
          </div>

          <div class="col-md-4">
            <label class="form-label">Passport Number</label>
            <input type="text" name="passport_number" class="form-control"
                   value="<?= htmlspecialchars($data['passport_number']) ?>">
          </div>

          <div class="col-md-4">
            <label class="form-label">Previous Visa Refusal</label>
            <select name="previous_visa_refusal" class="form-select">
              <option value="No" <?= $data['previous_visa_refusal']=='No'?'selected':'' ?>>No</option>
              <option value="Yes" <?= $data['previous_visa_refusal']=='Yes'?'selected':'' ?>>Yes</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Previous Visit Countries</label>
            <input type="text" name="previous_visit_countries" class="form-control"
                   value="<?= htmlspecialchars($data['previous_visit_countries']) ?>">
          </div>

          <!-- Status Section -->
          <div class="col-12 mt-4">
            <div class="border-bottom pb-2 mb-3">
              <h6 class="fw-bold text-primary mb-0">Application Status</h6>
            </div>
          </div>

          <div class="col-md-4">
            <label class="form-label">Application Status</label>
            <select name="application_status" class="form-select">
              <option value="pending" <?= $data['status']=='pending'?'selected':'' ?>>Pending</option>
              <option value="submitted" <?= $data['status']=='submitted'?'selected':'' ?>>Submitted</option>
              <option value="approved" <?= $data['status']=='approved'?'selected':'' ?>>Approved</option>
              <option value="rejected" <?= $data['status']=='rejected'?'selected':'' ?>>Rejected</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Visa Status</label>
            <select name="visa_status" class="form-select">
              <option value="hold" <?= $data['visa_status']=='hold'?'selected':'' ?>>Hold</option>
              <option value="processing" <?= $data['visa_status']=='processing'?'selected':'' ?>>Processing</option>
              <option value="approved" <?= $data['visa_status']=='approved'?'selected':'' ?>>Approved</option>
              <option value="rejected" <?= $data['visa_status']=='rejected'?'selected':'' ?>>Rejected</option>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Pending Documents</label>
            <textarea name="pending_documents" rows="2" class="form-control"><?= htmlspecialchars($data['pending_documents']) ?></textarea>
          </div>

          <div class="col-12">
            <label class="form-label">Notes</label>
            <textarea name="notes" rows="3" class="form-control"><?= htmlspecialchars($data['notes']) ?></textarea>
          </div>

          <div class="col-12 text-end mt-4">
            <button type="submit" class="btn btn-primary px-4">
              Update Case
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