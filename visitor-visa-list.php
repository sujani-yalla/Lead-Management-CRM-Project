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
$statusFilter = '';
$params = [];
$types = '';

$statusMap = [
    'pending'  => 'pending',
    'approved' => 'approved',
    'rejected' => 'rejected'
];

if (isset($_GET['status']) && array_key_exists($_GET['status'], $statusMap)) {
    $statusFilter = " AND v.status = ?";
    $params[] = $statusMap[$_GET['status']];
    $types .= 's';
}

if ($_SESSION['role'] === 'admin') {

    $sql = "
        SELECT 
            v.*,
            l.lead_name,
            l.mobile,
            l.email,
            u.name AS employee_name
        FROM visas v
        JOIN leads l ON l.id = v.lead_id
        LEFT JOIN users u ON u.id = l.assigned_to
        WHERE v.visa_type = 'visitor'
        $statusFilter
        ORDER BY v.id DESC
    ";

    $stmt = $conn->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

} else {

    $sql = "
        SELECT 
            v.*,
            l.lead_name,
            l.mobile,
            l.email,
            u.name AS employee_name
        FROM visas v
        JOIN leads l ON l.id = v.lead_id
        LEFT JOIN users u ON u.id = l.assigned_to
        WHERE v.visa_type = 'visitor'
        AND l.assigned_to = ?
        $statusFilter
        ORDER BY v.id DESC
    ";

    $stmt = $conn->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param("i" . $types, $_SESSION['user_id'], ...$params);
    } else {
        $stmt->bind_param("i", $_SESSION['user_id']);
    }
}

$stmt->execute();
$result = $stmt->get_result();
if (!$result) {
    die($conn->error);
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
            <div class="row size-column">
                <div class="col-sm-12">
                <div class="card">
                  <div class="card-header pb-0 card-no-border">
                    <h4>Visitors Data</h4><span>Tracks visitor visa applicants, their application status, and travel dates for easy monitoring</span>
                  </div>
                  <div class="card-body">
                    <div class="table-responsive custom-scrollbar">
                      <table class="display" id="basic-1">
                        <thead>
                          <tr>
                            <th>ID</th>
                            <th>Lead Name</th>
                            <th>Employee</th>
                            <th>Mobile</th>
                            <th>Email</th>
                            <th>Country</th>
                            <th>Purpose</th>
                            <th>Travel Date</th>
                            <th>Duration</th>
                            <th>Passport</th>
                            <th>Refusal</th>
                            <th>Application Status</th>
                            <th>Visa Status</th>
                            <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                          <?php while ($row = $result->fetch_assoc()) { ?>
                            <tr>

                                <td>VV<?= str_pad($row['id'], 3, "0", STR_PAD_LEFT) ?></td>

                                <td><?= htmlspecialchars($row['lead_name']) ?></td>

                                <td><?= htmlspecialchars($row['employee_name']) ?></td>

                                <td><?= htmlspecialchars($row['mobile']) ?></td>

                                <td><?= htmlspecialchars($row['email']) ?></td>

                                <td><?= htmlspecialchars($row['country']) ?></td>

                               <td><?= htmlspecialchars($row['purpose_of_visit']) ?></td>

                                <td><?= $row['travel_date'] ? date("d-m-Y", strtotime($row['travel_date'])) : '-' ?></td>

                                <td><?= $row['travel_duration'] ?? '-' ?></td>

                               <td><?= htmlspecialchars($row['passport_number']) ?></td>

                               <td>
                                <?php if ($row['previous_visa_refusal'] === 'Yes') { ?>
                                 <span class="badge bg-danger">Yes</span>
                                <?php } else { ?>
                                 <span class="badge bg-success">No</span>
                                <?php } ?>
                               </td>

                                <td>
                                <?php
                                switch($row['status']) {
                                   case 'approved':
                                      echo '<span class="badge bg-success">Approved</span>';
                                      break;
                                   case 'submitted':
                                      echo '<span class="badge bg-info">Submitted</span>';
                                      break;
                                  case 'rejected':
                                      echo '<span class="badge bg-danger">Rejected</span>';
                                      break;
                                  default:
                                      echo '<span class="badge bg-warning">Pending</span>';
                                }
                                ?>
                                </td>

                               <td>
                                  <?php if ($row['visa_status'] === 'approved') { ?>
                                     <span class="badge bg-success">Approved</span>
                                  <?php } elseif ($row['visa_status'] === 'processing') { ?>
                                      <span class="badge bg-info">Processing</span>
                                  <?php } elseif ($row['visa_status'] === 'rejected') { ?>
                                      <span class="badge bg-danger">Rejected</span>
                                <?php } else { ?>
                                       <span class="badge bg-warning">Hold</span>
                                <?php } ?>
                               </td>

                               <td>
    <div class="d-flex gap-1 justify-content-center">

        <a href="visitor-visa-view.php?id=<?= $row['id'] ?>" 
           class="btn btn-sm btn-outline-primary rounded-2 px-2">
           <i data-feather="eye" style="width:14px;height:14px;"></i>
        </a>

        <a href="visitor-visa-edit.php?id=<?= $row['id'] ?>" 
           class="btn btn-sm btn-outline-success rounded-2 px-2">
           <i data-feather="edit" style="width:14px;height:14px;"></i>
        </a>

        <form action="visitor-visa-delete.php" method="POST" 
      onsubmit="return confirm('Are you sure you want to delete this record?');"
      style="display:inline;">
      
    <input type="hidden" name="id" value="<?= $row['id'] ?>">
    
    <button type="submit" 
            class="btn btn-sm btn-outline-danger rounded-2 px-2">
        <i data-feather="trash-2" style="width:14px;height:14px;"></i>
    </button>
</form>

    </div>
</td>

                              </tr>
                            <?php } ?>
                          </tbody>
                          <table class="display nowrap" id="basic-1" style="width:100%">

                        </table>
                    </div>
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