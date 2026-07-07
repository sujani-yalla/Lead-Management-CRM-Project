<?php
session_start();
include "db.php";
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('lead_management')) {
    http_response_code(403);
    die("Access Denied");
}

/* ===== SECURITY CHECK ===== */
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Access");
}

$id = (int) $_GET['id'];
$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];

/* ===== FETCH DATA ===== */
if ($role === 'admin') {

    $stmt = $conn->prepare("
        SELECT l.*, c.campaign_name
        FROM leads l
        LEFT JOIN campaigns c ON c.id = l.campaign_id
        WHERE l.id = ?
    ");
    $stmt->bind_param("i", $id);

} else {

    $stmt = $conn->prepare("
        SELECT l.*, c.campaign_name
        FROM leads l
        LEFT JOIN campaigns c ON c.id = l.campaign_id
        WHERE l.id = ?
        AND l.assigned_to = ?
    ");
    $stmt->bind_param("ii", $id, $userId);
}

$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("Unauthorized or record not found");
}
$stmt->close();


/* ===== UPDATE PART ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $lead_name   = $_POST['lead_name'];
    $mobile      = $_POST['mobile'];
    $email       = $_POST['email'];
    $lead_source = $_POST['lead_source'];
    $lead_type   = $_POST['lead_type'];
    $address     = $_POST['address'];

    $reference_name    = $_POST['reference_name'] ?? NULL;
    $reference_contact = $_POST['reference_contact'] ?? NULL;
    $comment           = $_POST['comment'] ?? NULL;
    $re_comment        = $_POST['re_comment'] ?? NULL;
    $campaign_id       = !empty($_POST['campaign_id']) ? $_POST['campaign_id'] : NULL;

    if ($role === 'admin') {

        $update = $conn->prepare("
            UPDATE leads
            SET lead_name=?, mobile=?, email=?, lead_source=?, lead_type=?,
                reference_name=?, reference_contact=?,
                comment=?, re_comment=?, campaign_id=?, address=?
            WHERE id=?
        ");

        $update->bind_param(
            "ssssssssissi",
            $lead_name,
            $mobile,
            $email,
            $lead_source,
            $lead_type,
            $reference_name,
            $reference_contact,
            $comment,
            $re_comment,
            $campaign_id,
            $address,
            $id
        );

    } else {

        $update = $conn->prepare("
            UPDATE leads
            SET lead_name=?, mobile=?, email=?, lead_source=?, lead_type=?,
                reference_name=?, reference_contact=?,
                comment=?, re_comment=?, campaign_id=?, address=?
            WHERE id=? AND assigned_to=?
        ");

        $update->bind_param(
            "ssssssssissii",
            $lead_name,
            $mobile,
            $email,
            $lead_source,
            $lead_type,
            $reference_name,
            $reference_contact,
            $comment,
            $re_comment,
            $campaign_id,
            $address,
            $id,
            $userId
        );
    }

    $update->execute();
    $update->close();

    header("Location: lead-list.php?updated=1");
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
    <title> Lead data edit</title>
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
          <div class="container-fluid mt-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Edit Lead</h4>
        <a href="lead-list.php" class="btn btn-outline-secondary btn-sm rounded-2">
            ← Back to List
        </a>
    </div>

    <form method="POST">

        <div class="row">

            <!-- Basic Info -->
            <div class="col-md-4 mb-3">
                <label>Name</label>
                <input type="text" name="lead_name"
                       value="<?= htmlspecialchars($data['lead_name']) ?>"
                       class="form-control" required>
            </div>

            <div class="col-md-4 mb-3">
                <label>Mobile</label>
                <input type="text" name="mobile"
                       value="<?= htmlspecialchars($data['mobile']) ?>"
                       class="form-control" required>
            </div>

            <div class="col-md-4 mb-3">
                <label>Email</label>
                <input type="email" name="email"
                       value="<?= htmlspecialchars($data['email']) ?>"
                       class="form-control">
            </div>

            <!-- Source -->
            <div class="col-md-4 mb-3">
    <label>Lead Source</label>
    <select name="lead_source" class="form-control" required>
        <?php
        $sources = [
            "Website",
            "Walk-in",
            "Instagram",
            "Facebook",
            "Google Ads",
            "Reference",
            "Telecalling",
            "IVR",
            "Other Platforms",
            "Just Dial",
            "Old Student Reference"
        ];

        foreach ($sources as $source) {
            $selected = ($data['lead_source'] === $source) ? "selected" : "";
            echo "<option value='$source' $selected>$source</option>";
        }
        ?>
    </select>
</div>

            <!-- Campaign -->
            <div class="col-md-4 mb-3">
    <label>Campaign</label>
    <select name="campaign_id" class="form-control">
        <option value="">Select Campaign</option>
        <?php
        $campaigns = $conn->query("SELECT id, campaign_name FROM campaigns ORDER BY id DESC");
        while ($c = $campaigns->fetch_assoc()) {
            $selected = ($data['campaign_id'] == $c['id']) ? "selected" : "";
            echo "<option value='{$c['id']}' $selected>{$c['campaign_name']}</option>";
        }
        ?>
    </select>
</div>

            <!-- Type -->
            <div class="col-md-4 mb-3">
    <label>Lead Type</label>
    <select name="lead_type" class="form-control" required>
        <?php
        $types = [
            "Student",
            "Work Visa",
            "PR",
            "Visitor",
            "Loan",
            "University Transfer",
            "Forex",
            "Flight Tickets",
            "MBBS",
            "Accomdating Private",
            "Accomdating University",
            "Pick Up",
            "Coaching",
            "Admission India",
            "Admission Other Countries",
            "Scholarships",
            "Financial Assistant",
            "Immigration",
            "Others"
        ];

        foreach ($types as $type) {
            $selected = ($data['lead_type'] === $type) ? "selected" : "";
            echo "<option value='$type' $selected>$type</option>";
        }
        ?>
    </select>
</div>

            <!-- Reference -->
            <div class="col-md-6 mb-3">
                <label>Reference Name</label>
                <input type="text" name="reference_name"
                       value="<?= htmlspecialchars($data['reference_name']) ?>"
                       class="form-control">
            </div>

            <div class="col-md-6 mb-3">
                <label>Reference Contact</label>
                <input type="text" name="reference_contact"
                       value="<?= htmlspecialchars($data['reference_contact']) ?>"
                       class="form-control">
            </div>

            <!-- Address -->
            <div class="col-md-12 mb-3">
                <label>Address</label>
                <textarea name="address" class="form-control"><?= htmlspecialchars($data['address']) ?></textarea>
            </div>

            <!-- Comments -->
            <div class="col-md-6 mb-3">
                <label>Comment</label>
                <textarea name="comment" class="form-control"><?= htmlspecialchars($data['comment']) ?></textarea>
            </div>

            <div class="col-md-6 mb-3">
                <label>Re-Comment</label>
                <textarea name="re_comment" class="form-control"><?= htmlspecialchars($data['re_comment']) ?></textarea>
            </div>

        </div>

        <button class="btn btn-success rounded-2 px-4">Update Lead</button>

    </form>

</div>
            
          <!-- Container-fluid Ends-->
        </div>
        <!-- footer start-->
        <footer class="footer">
          <div class="container-fluid">
            <div class="row">
              <div class="col-md-12 footer-copyright text-center">
                <p class="mb-0">Copyright 2025 © Indian Overseas service  </p>
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
<script>
document.addEventListener("DOMContentLoaded", function () {

    const sourceSelect = document.querySelector("select[name='lead_source']");
    const refName = document.querySelector("input[name='reference_name']").closest('.col-md-6');
    const refContact = document.querySelector("input[name='reference_contact']").closest('.col-md-6');

    function toggleReferenceFields() {
        const value = sourceSelect.value;

        if (value === "Reference" || value === "Old Student Reference") {
            refName.style.display = "block";
            refContact.style.display = "block";
        } else {
            refName.style.display = "none";
            refContact.style.display = "none";
        }
    }

    // Initial load check
    toggleReferenceFields();

    // Change event
    sourceSelect.addEventListener("change", toggleReferenceFields);
});
</script>
    
</body>
</html>