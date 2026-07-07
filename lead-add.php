<?php
session_start();
require 'db.php';              // ✅ FIRST
require_once 'access-control.php';

if (!hasAccess('lead_management')) {
    http_response_code(403);
    die("Access Denied");
}


/* HARD STOP if not logged in */
if (!isset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['name'])) {
    die("Access denied. Please login.");
}


/* INSERT LOGIC */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $lead_name   = $_POST['lead_name'] ?? '';
    $mobile      = $_POST['mobile'] ?? '';
    $email       = $_POST['email'] ?? '';
    $lead_source = $_POST['lead_source'] ?? '';
    $lead_type   = $_POST['lead_type'] ?? '';
    $address     = $_POST['address'] ?? '';

    // NEW FIELDS
    $reference_name    = $_POST['reference_name'] ?? NULL;
    $reference_contact = $_POST['reference_contact'] ?? NULL;
    $comment           = $_POST['comment'] ?? NULL;
    $re_comment        = $_POST['re_comment'] ?? NULL;
    $campaign_id       = !empty($_POST['campaign_id']) ? $_POST['campaign_id'] : NULL;

    $assigned_to = $_SESSION['user_id'];

    $sql = "INSERT INTO leads
        (lead_name, mobile, email, lead_source, lead_type,
         reference_name, reference_contact,
         comment, re_comment,
         campaign_id,
         assigned_to, address)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die($conn->error);
    }

    $stmt->bind_param(
        "sssssssssiis",
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
        $assigned_to,
        $address
    );

    if ($stmt->execute()) {
        header("Location: lead-add.php?success=1");
        exit();
    } else {
        die($stmt->error);
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
        <!-- Page Header Ends -->
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
                                <h4>Leads</h4>
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
                                    <h4>Lead Details Form</h4>
                                </div>
                                <div class="card-body">
                                    <!-- Success Modal -->
                                    <div class="modal fade" id="successModal" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                        <div class="modal-header bg-success text-white">
                                            <h5 class="modal-title">Success</h5>
                                        </div>
                                        <div class="modal-body text-center">
                                            <i class="fa fa-check-circle fa-3x text-success mb-3"></i>
                                            <p class="fs-5">Lead added successfully</p>
                                        </div>
                                        </div>
                                    </div>
                                    </div>

                                    <form class="row g-3 needs-validation custom-input" id="leadForm" method="POST" action="">
                                        <div class="col-md-4 position-relative">
                                            <label class="form-label" for="validationTooltip01">Lead Name</label>
                                            <input class="form-control" id="validationTooltip01" type="text" name="lead_name"
                                                placeholder="Enter Lead Name" required="">
                                            <div class="valid-tooltip">Looks good!</div>
                                        </div>
                                        <div class="col-md-4 position-relative">
                                            <label class="form-label" for="validationTooltip02">Mobile Number</label>
                                            <input class="form-control" id="validationTooltip02" type="text" name="mobile"
                                                placeholder="Enter Mobile Number" required="">
                                            <div class="valid-tooltip">Looks good!</div>
                                        </div>
                                        <div class="col-md-4 position-relative">
                                            <label class="form-label" for="validationTooltipUsername">Email</label>
                                            <div class="input-group has-validation">
                                                <input class="form-control" id="validationTooltip02" type="text" name="email"
                                                placeholder="Enter Email" required="">
                                            </div>
                                        </div>
                                        <div class="col-md-4 position-relative">
                                            <label class="form-label" for="validationTooltip04">Lead Source</label>
                                            <select class="form-select" id="validationTooltip04" name="lead_source" required="">
                                                <option>Select Source</option>
                                                <option>Website</option>
                                                <option>Walk-in</option>
                                                <option>Instagram</option>
                                                <option>Facebook</option>
                                                <option>Google Ads</option>
                                                <option>Reference</option>
                                                <option>Telecalling</option>
                                                <option>IVR</option>
                                                <option>Other Platforms</option>
                                                <option>Just Dial</option>
                                                <option>Old Student Reference</option>
                                            </select>
                                            <div class="invalid-tooltip">Please select a valid state.</div>
                                        </div>
                                        <div class="col-md-4 position-relative">
                                            <label class="form-label">Campaign</label>
                                            <select class="form-select" name="campaign_id">
                                                 <option value="">Select Campaign (Optional)</option>
                                                   <?php
                                                    $campaigns = $conn->query("SELECT id, campaign_name FROM campaigns ORDER BY id DESC");
                                                     while ($c = $campaigns->fetch_assoc()) {
                                                  echo "<option value='{$c['id']}'>" . htmlspecialchars($c['campaign_name']) . "</option>";
                                                 }
                                                 ?>
                                                </select>
                                            </div>

                                        <div class="col-md-4 position-relative" id="referenceFields" style="display:none;">
                                           <label class="form-label">Reference Name</label>
    <input class="form-control" type="text" name="reference_name">
</div>

<div class="col-md-4 position-relative" id="referenceContactField" style="display:none;">
    <label class="form-label">Reference Contact Number</label>
    <input class="form-control" type="text" name="reference_contact">
</div>

                                        
                                        <div class="col-md-4 position-relative">
                                            <label class="form-label" for="validationTooltip04">Lead Type</label>
                                            <select class="form-select" id="validationTooltip04" name="lead_type" required="">
                                                   <option>Select Type</option>
                                                    <option>Student</option>
                                                    <option>Work Visa</option>
                                                    <option>PR</option>
                                                    <option>Visitor</option>
                                                    <option>Loan</option>
                                                    <option>University Transfer</option>
                                                    <option>Forex</option>
                                                    <option>Flight Tickets</option>
                                                    <option>MBBS</option>
                                                    <option>Accomdating Private</option>
                                                    <option>Accomdating University</option>
                                                    <option>Pick Up</option>
                                                    <option>Coaching</option>
                                                    <option>Admission India</option>
                                                    <option>Admission Other Countries</option>
                                                    <option>Scholarships</option>
                                                    <option>Financial Assistant</option>
                                                    <option>Immigration</option>
                                                    <option>Others</option>
                                            </select>
                                            <div class="invalid-tooltip">Please select a valid state.</div>
                                        </div>
                                        <!-- <div class="col-md-4 position-relative"> 
                                            <label class="form-label" for="validationTooltip04">Assigned Counsellor</label>
                                            <select class="form-select" id="validationTooltip04" name="assigned_counsellor" required="">
                                                <option value="">Select Staff</option>
                                                <option value="Ramesh">Ramesh</option>
                                                <option value="Suresh">Suresh</option>
                                                <option value="Priya">Priya</option>
                                            </select>
                                            <div class="invalid-tooltip">Please select a valid state.</div>
                                         </div> -->
                                        <div class="col-md-12">
                                            <label for="form-label">Address</label>
                                            <textarea name="address" id="address" class="form-control"></textarea>
                                        </div>
                                        <div class="col-md-6">
    <label class="form-label">Comment</label>
    <textarea name="comment" class="form-control"></textarea>
</div>

<div class="col-md-6">
    <label class="form-label">Re-Comment</label>
    <textarea name="re_comment" class="form-control"></textarea>
</div>
                                        <div class="col-12">
                                            <button class="btn btn-primary" type="submit" name="submit" id="leadSubmit">Submit form</button>
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
    <!-- calendar js-->
    
    <!-- Plugins JS Ends-->
    <!-- Theme js-->
    <script src="assets/js/script.js"></script>
    <script src="assets/js/theme-customizer/customizer.js"></script>
    <script>
document.addEventListener("DOMContentLoaded", function () {
    if (typeof feather !== "undefined") {
        feather.replace();
    }

    if (typeof $.fn.sidebarMenu === "function") {
        $('#sidebar-menu').sidebarMenu();
    }
});
</script>
</body>

</html>