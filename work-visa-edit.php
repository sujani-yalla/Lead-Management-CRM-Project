<?php
session_start();
require 'db.php';
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('work_visa')) {
    http_response_code(403);
    die("Access Denied");
}

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: work-visa-list.php");
    exit;
}

$id     = intval($_GET['id']);
$userId = $_SESSION['user_id'];
$role   = $_SESSION['role'];

/* FETCH CASE */
if ($role === 'admin') {

    $stmt = $conn->prepare("SELECT * FROM work_visas WHERE id = ?");
    $stmt->bind_param("i", $id);

} else {

    $stmt = $conn->prepare("SELECT * FROM work_visas WHERE id = ? AND assigned_to = ?");
    $stmt->bind_param("ii", $id, $userId);
}

$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    header("Location: work-visa-list.php");
    exit;
}
$stmt->close();

/* HANDLE UPDATE */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $address              = $_POST['address'];
    $process_start_date   = $_POST['process_start_date'];
    $applying_date        = $_POST['applying_date'];
    $applying_country     = $_POST['applying_country'];
    $job_category         = $_POST['job_category'];
    $experience_years     = $_POST['experience_years'];
    $qualification        = $_POST['qualification'];
    $english_test         = $_POST['english_test'];
    $passport_number      = $_POST['passport_number'];
    $previous_visa_refusal= $_POST['previous_visa_refusal'];
    $salary               = $_POST['salary'];
    $pending_documents    = $_POST['pending_documents'];
    $status               = $_POST['status'];
    $notes                = $_POST['notes'];

    $update = $conn->prepare("
        UPDATE work_visas SET
            address = ?,
            process_start_date = ?,
            applying_date = ?,
            applying_country = ?,
            job_category = ?,
            experience_years = ?,
            qualification = ?,
            english_test = ?,
            passport_number = ?,
            previous_visa_refusal = ?,
            salary = ?,
            pending_documents = ?,
            status = ?,
            notes = ?
        WHERE id = ?
    ");

    $update->bind_param(
        "ssssssssssssssi",
        $address,
        $process_start_date,
        $applying_date,
        $applying_country,
        $job_category,
        $experience_years,
        $qualification,
        $english_test,
        $passport_number,
        $previous_visa_refusal,
        $salary,
        $pending_documents,
        $status,
        $notes,
        $id
    );

    if ($update->execute()) {
        header("Location: work-visa-view.php?id=" . $id);
        exit;
    }

    $update->close();
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
         <div class="sidebar-wrapper" data-layout="stroke-svg">
                <div class="logo-wrapper">
                    <a href="dashboard.php">
                        <img class="img-fluid" src="assets/images/logo/logo.png" alt="CRM Logo">
                    </a>
                    <div class="toggle-sidebar">
                        <i class="status_toggle middle sidebar-toggle" data-feather="grid"></i>
                    </div>
                </div>

                <nav class="sidebar-main">
                    <div id="sidebar-menu">
                        <ul class="sidebar-links" id="simple-bar">

                            <!-- DASHBOARD -->
                            <li class="sidebar-main-title">
                                <div>
                                    <h6>Dashboard</h6>
                                </div>
                            </li>
                            <li class="sidebar-list">
                                <a class="sidebar-link link-nav" href="dashboard.php">
                                    <i data-feather="home"></i>
                                    <span>Dashboard</span>
                                </a>
                            </li>

                            <!-- CRM MODULES -->
                            <li class="sidebar-main-title">
                                <div>
                                    <h6>CRM Modules</h6>
                                </div>
                            </li>

                            <!-- 1. LEAD MANAGEMENT -->
                            <li class="sidebar-list">
                                <a class="sidebar-link sidebar-title" href="#">
                                    <i data-feather="users"></i>
                                    <span>Lead Management</span>
                                </a>
                                <ul class="sidebar-submenu">
                                    <li><a href="lead-add.php">Add Lead</a></li>
                                    <li><a href="lead-list.php">Lead List</a></li>
                                </ul>
                            </li>

                            <!-- 2. CALLING & FOLLOW-UP -->
                            <li class="sidebar-list">
                                <a class="sidebar-link sidebar-title" href="#">
                                    <i data-feather="phone-call"></i>
                                    <span>Calling & Follow-up</span>
                                </a>
                                <ul class="sidebar-submenu">
                                    <li><a href="call-add.php">Add Call Log</a></li>
                                    <li><a href="call-list.php">Call History</a></li>
                                </ul>
                            </li>

                            <!-- 3. AUTO LEAD TRACKING -->
                            <li class="sidebar-list">
                                <a class="sidebar-link sidebar-title" href="#">
                                    <i data-feather="activity"></i>
                                    <span>Lead Source Tracking</span>
                                </a>
                                <ul class="sidebar-submenu">
                                    <li><a href="campaign-add.php">Add Campaign</a></li>
                                    <li><a href="campaign-list.php">Campaign Leads</a></li>
                                </ul>
                            </li>

                            <!-- 4. STAFF WORK RECORD -->
                            <li class="sidebar-list">
                                <a class="sidebar-link sidebar-title" href="#">
                                    <i data-feather="user-check"></i>
                                    <span>Staff Work Report</span>
                                </a>
                                <ul class="sidebar-submenu">
                                    <li><a href="staff-work-add.php">Daily Entry</a></li>
                                    <li><a href="staff-work-list.php">Staff Report</a></li>
                                </ul>
                            </li>

                            <!-- 5. STUDENT VISA -->
                            <li class="sidebar-list">
                                <a class="sidebar-link sidebar-title" href="#">
                                    <i data-feather="book"></i>
                                    <span>Student Visa</span>
                                </a>
                                <ul class="sidebar-submenu">
                                    <li><a href="student-visa-add.php">Add Student</a></li>
                                    <li><a href="student-visa-list.php">Student Records</a></li>
                                </ul>
                            </li>

                            <!-- 6. LOAN MODULE -->
                            <li class="sidebar-list">
                                <a class="sidebar-link sidebar-title" href="#">
                                    <i data-feather="dollar-sign"></i>
                                    <span>Loan Module</span>
                                </a>
                                <ul class="sidebar-submenu">
                                    <li><a href="loan-add.php">Add Loan</a></li>
                                    <li><a href="loan-list.php">Loan Status</a></li>
                                </ul>
                            </li>

                            <!-- 7. WORK VISA -->
                            <li class="sidebar-list">
                                <a class="sidebar-link sidebar-title" href="#">
                                    <i data-feather="briefcase"></i>
                                    <span>Work Visa</span>
                                </a>
                                <ul class="sidebar-submenu">
                                    <li><a href="work-visa-add.php">Add Work Case</a></li>
                                    <li><a href="work-visa-list.php">Work Visa Cases</a></li>
                                </ul>
                            </li>

                            <!-- 8. VISITOR VISA -->
                            <li class="sidebar-list">
                                <a class="sidebar-link sidebar-title" href="#">
                                    <i data-feather="map-pin"></i>
                                    <span>Visitor Visa</span>
                                </a>
                                <ul class="sidebar-submenu">
                                    <li><a href="visitor-visa-add.php">Add Visitor</a></li>
                                    <li><a href="visitor-visa-list.php">Visitor Records</a></li>
                                </ul>
                            </li>

                            <!-- 9. PR -->
                            <li class="sidebar-list">
                                <a class="sidebar-link sidebar-title" href="#">
                                    <i data-feather="globe"></i>
                                    <span>PR Application</span>
                                </a>
                                <ul class="sidebar-submenu">
                                    <li><a href="pr-add.php">Add PR Case</a></li>
                                    <li><a href="pr-list.php">PR Status</a></li>
                                </ul>
                            </li>

                            <!-- 10. CA & LEGAL -->
                            <li class="sidebar-list">
                                <a class="sidebar-link sidebar-title" href="#">
                                    <i data-feather="file-text"></i>
                                    <span>CA & Legal Work</span>
                                </a>
                                <ul class="sidebar-submenu">
                                    <li><a href="legal-add.php">Add Case</a></li>
                                    <li><a href="legal-list.php">Case Tracker</a></li>
                                </ul>
                            </li>

                            <!-- 11. SOCIAL MEDIA -->
                            <li class="sidebar-list">
                                <a class="sidebar-link sidebar-title" href="#">
                                    <i data-feather="instagram"></i>
                                    <span>Social Media</span>
                                </a>
                                <ul class="sidebar-submenu">
                                    <li><a href="social-add.php">Add Post</a></li>
                                    <li><a href="social-list.php">Post Schedule</a></li>
                                </ul>
                            </li>

                        </ul>
                    </div>
                </nav>
            </div>
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
           
           <div class="container mt-5">

<div class="card shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Edit Work Visa Case</h4>
        <a href="visitor-visa-list.php" class="btn btn-light btn-sm">
          ← Back to List
        </a>
      </div>

<div class="card-body">

<form method="POST">

<div class="row g-3">

<div class="col-md-6">
<label>Address</label>
<textarea name="address" class="form-control"><?= htmlspecialchars($data['address']) ?></textarea>
</div>

<div class="col-md-3">
<label>Process Start Date</label>
<input type="date" name="process_start_date" class="form-control"
value="<?= $data['process_start_date'] ?>">
</div>

<div class="col-md-3">
<label>Applying Date</label>
<input type="date" name="applying_date" class="form-control"
value="<?= $data['applying_date'] ?>">
</div>

<div class="col-md-4">
<label>Applying Country</label>
<input type="text" name="applying_country" class="form-control"
value="<?= htmlspecialchars($data['applying_country']) ?>">
</div>

<div class="col-md-4">
<label>Job Category</label>
<input type="text" name="job_category" class="form-control"
value="<?= htmlspecialchars($data['job_category']) ?>">
</div>

<div class="col-md-4">
<label>Experience (Years)</label>
<input type="text" name="experience_years" class="form-control"
value="<?= htmlspecialchars($data['experience_years']) ?>">
</div>

<div class="col-md-4">
<label>Qualification</label>
<input type="text" name="qualification" class="form-control"
value="<?= htmlspecialchars($data['qualification']) ?>">
</div>

<div class="col-md-4">
<label>English Test</label>
<select name="english_test" class="form-select">
<option <?= $data['english_test']=="IELTS"?"selected":"" ?>>IELTS</option>
<option <?= $data['english_test']=="PTE"?"selected":"" ?>>PTE</option>
<option <?= $data['english_test']=="None"?"selected":"" ?>>None</option>
</select>
</div>

<div class="col-md-4">
<label>Passport Number</label>
<input type="text" name="passport_number" class="form-control"
value="<?= htmlspecialchars($data['passport_number']) ?>">
</div>

<div class="col-md-4">
<label>Previous Visa Refusal</label>
<select name="previous_visa_refusal" class="form-select">
<option <?= $data['previous_visa_refusal']=="Yes"?"selected":"" ?>>Yes</option>
<option <?= $data['previous_visa_refusal']=="No"?"selected":"" ?>>No</option>
</select>
</div>

<div class="col-md-4">
<label>Salary</label>
<input type="text" name="salary" class="form-control"
value="<?= htmlspecialchars($data['salary']) ?>">
</div>

<div class="col-md-6">
<label>Pending Documents</label>
<textarea name="pending_documents" class="form-control"><?= htmlspecialchars($data['pending_documents']) ?></textarea>
</div>

<div class="col-md-6">
<label>Notes</label>
<textarea name="notes" class="form-control"><?= htmlspecialchars($data['notes']) ?></textarea>
</div>

<div class="col-md-6">
<label>Application Status</label>
<select name="status" class="form-select">
<option <?= $data['status']=="Screening Completed"?"selected":"" ?>>Screening Completed</option>
<option <?= $data['status']=="Profile Created"?"selected":"" ?>>Profile Created</option>
<option <?= $data['status']=="Interview Scheduled"?"selected":"" ?>>Interview Scheduled</option>
<option <?= $data['status']=="Offer Letter Received"?"selected":"" ?>>Offer Letter Received</option>
<option <?= $data['status']=="Visa Process Started"?"selected":"" ?>>Visa Process Started</option>
<option <?= $data['status']=="Approved"?"selected":"" ?>>Approved</option>
<option <?= $data['status']=="Travel Completed"?"selected":"" ?>>Travel Completed</option>
<option <?= $data['status']=="Closed"?"selected":"" ?>>Closed</option>
</select>
</div>

</div>

<div class="mt-4">
<button type="submit" class="btn btn-primary">Update Case</button>
<a href="work-visa-view.php?id=<?= $id ?>" class="btn btn-outline-secondary">Cancel</a>
</div>

</form>

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
                <p class="mb-0">Copyright 2025 © KBK Software Solutions  </p>
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