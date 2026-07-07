<?php
session_start();
include "db.php";
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('lead_management')) {
    http_response_code(403);
    die("Access Denied");
}

/* HARD STOP */
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['role'];
$userId = $_SESSION['user_id'];

/* ===========================
   CLEAN PROFESSIONAL QUERY
   =========================== */

$sql = "
SELECT 
    l.id,
    l.lead_name,
    l.mobile,
    l.lead_source,
    l.lead_type,
    l.created_at,
    l.comment,
    u.name AS staff_name,
    c.campaign_name,

    /* Case existence checks (NO JOIN duplication risk) */
    (SELECT id FROM visas 
        WHERE lead_id = l.id 
        AND visa_type = 'student' 
        LIMIT 1) AS student_visa_id,

    (SELECT id FROM work_visas 
        WHERE lead_id = l.id 
        LIMIT 1) AS work_visa_id,

    (SELECT id FROM visas 
        WHERE lead_id = l.id 
        AND visa_type = 'visitor' 
        LIMIT 1) AS visitor_visa_id,

    (SELECT id FROM pr_enquiries WHERE lead_id = l.id LIMIT 1) AS pr_id,
    (SELECT id FROM loans 
        WHERE lead_id = l.id 
        LIMIT 1) AS loan_id,

    (SELECT COUNT(*) 
        FROM call_logs cl 
        WHERE cl.lead_id = l.id) AS call_count

FROM leads l
LEFT JOIN users u ON u.id = l.assigned_to
LEFT JOIN campaigns c ON c.id = l.campaign_id
";

/* Employee restriction */
if ($role !== 'admin') {
    $sql .= " WHERE l.assigned_to = ? ";
}

$sql .= " ORDER BY l.id DESC";

/* Prepare statement */
$stmt = $conn->prepare($sql);

if ($role !== 'admin') {
    $stmt->bind_param("i", $userId);
}

$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Query failed: " . $conn->error);
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
    <title>Leads-data</title>
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
                <h4 class="f-w-600">Welcome Dashboard</h4><img class="mt-0" src="assets/images/hand.gif" alt="hand-gif">
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
                    <h4>Leads Data</h4><span>Displays all collected leads with assigned counsellors for easy tracking and follow-up.</span>
                  </div>
                  <div class="card-body">
                    <div class="table-responsive custom-scrollbar">
                      <table class="display" id="basic-1">
                        <thead>
                          <tr>
                             <th>Staff</th>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>Source</th>
                            <th>Campaign</th>
                            <th>Type</th>
                            <th>comment</th>
                            <th>case</th>
                            <th class="text-center">Actions</th>
                          </tr>
                        </thead>
                       <tbody>
<?php while ($row = $result->fetch_assoc()) { ?>
<tr>

    <!-- Staff -->
    <td class="text-center align-middle">
        <?= htmlspecialchars($row['staff_name']) ?>
    </td>

    <!-- Name -->
    <td class="text-center align-middle">
        <?= htmlspecialchars($row['lead_name']) ?>
    </td>

    <!-- Mobile -->
    <td class="text-center align-middle">
        <?= htmlspecialchars($row['mobile']) ?>
    </td>

    <!-- Source -->
    <td class="text-center align-middle">
        <?= htmlspecialchars($row['lead_source']) ?>
    </td>

    <!-- Campaign -->
    <td class="text-center align-middle">
        <?= !empty($row['campaign_name']) 
            ? htmlspecialchars($row['campaign_name']) 
            : '<span class="text-muted">—</span>' ?>
    </td>

    <!-- Type -->
    <td class="text-center align-middle">
        <span class="badge bg-light text-dark border rounded-pill">
            <?= htmlspecialchars($row['lead_type']) ?>
        </span>
    </td>

    <!-- Comment -->
    <td class="text-center align-middle">
        <?= !empty($row['comment']) 
            ? substr(htmlspecialchars($row['comment']),0,40) . '...' 
            : '<span class="text-muted">—</span>' ?>
    </td>

    <!-- Case -->
    <td class="text-center align-middle">

    <?php if ($row['lead_type'] === 'Student'): ?>

        <?php if (empty($row['student_visa_id'])): ?>
            <a href="student-visa-add.php?lead_id=<?= $row['id'] ?>"
               class="btn btn-sm btn-outline-primary rounded-pill px-3">
                + Student
            </a>
        <?php else: ?>
            <span class="badge bg-success-subtle text-success border rounded-pill">
                ✓ Student Added
            </span>
        <?php endif; ?>

    <?php elseif ($row['lead_type'] === 'Work Visa'): ?>

        <?php if (empty($row['work_visa_id'])): ?>
            <a href="work-visa-add.php?lead_id=<?= $row['id'] ?>"
               class="btn btn-sm btn-outline-warning rounded-pill px-3">
                + Work
            </a>
        <?php else: ?>
            <span class="badge bg-success-subtle text-success border rounded-pill">
                ✓ Work Created
            </span>
        <?php endif; ?>

    <?php elseif ($row['lead_type'] === 'Visitor'): ?>

        <?php if (empty($row['visitor_visa_id'])): ?>
            <a href="visitor-visa-add.php?lead_id=<?= $row['id'] ?>"
               class="btn btn-sm btn-outline-info rounded-pill px-3">
                + Visitor
            </a>
        <?php else: ?>
            <span class="badge bg-success-subtle text-success border rounded-pill">
                ✓ Visitor Added
            </span>
        <?php endif; ?>

    <?php elseif ($row['lead_type'] === 'Loan'): ?>

        <?php if (empty($row['loan_id'])): ?>
            <a href="loan-add.php?lead_id=<?= $row['id'] ?>"
               class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                + Loan
            </a>
        <?php else: ?>
            <span class="badge bg-success-subtle text-success border rounded-pill">
                ✓ Loan Added
            </span>
        <?php endif; ?>

    <?php elseif ($row['lead_type'] === 'PR'): ?>

        <?php if (empty($row['pr_id'])): ?>
            <a href="pr-add.php?lead_id=<?= $row['id'] ?>"
               class="btn btn-sm btn-outline-dark rounded-pill px-3">
                + PR
            </a>
        <?php else: ?>
            <span class="badge bg-success-subtle text-success border rounded-pill">
                ✓ PR Added
            </span>
        <?php endif; ?>

    <?php endif; ?>

    </td>

    <!-- Actions -->
    <td class="align-middle">
        <div class="d-flex justify-content-center align-items-center gap-2">

            <!-- Call -->
            <?php
            $phoneClass = $row['call_count'] > 0
                ? 'btn btn-sm btn-outline-success rounded-2 px-2'
                : 'btn btn-sm btn-outline-secondary rounded-2 px-2';
            ?>
            <a href="call-history.php?lead_id=<?= $row['id'] ?>" 
               class="<?= $phoneClass ?>">
                <i data-feather="phone" style="width:14px;height:14px;"></i>
            </a>

            <?php if ($row['call_count'] > 0): ?>
                <span class="badge bg-light text-muted small">
                    <?= $row['call_count'] ?>
                </span>
            <?php endif; ?>

            <!-- View -->
            <a href="lead-view.php?id=<?= $row['id'] ?>" 
               class="btn btn-sm btn-outline-primary rounded-2 px-2">
                <i data-feather="eye" style="width:14px;height:14px;"></i>
            </a>

            <!-- Edit -->
            <a href="lead-edit.php?id=<?= $row['id'] ?>" 
               class="btn btn-sm btn-outline-success rounded-2 px-2">
                <i data-feather="edit" style="width:14px;height:14px;"></i>
            </a>

            <!-- Delete -->
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <form action="lead-delete.php" method="POST"
                  onsubmit="return confirm('Are you sure you want to delete this record?');"
                  style="display:inline;">
                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                <button type="submit"
                        class="btn btn-sm btn-outline-danger rounded-2 px-2">
                    <i data-feather="trash-2" style="width:14px;height:14px;"></i>
                </button>
            </form>
            <?php endif; ?>

        </div>
    </td>

</tr>
<?php } ?>
</tbody>
             
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