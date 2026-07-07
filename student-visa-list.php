<?php
session_start();
require 'db.php';
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('student_visa')) {
    http_response_code(403);
    die("Access Denied");
}


if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

/* ================= STAGE FILTER ================= */

$stage = $_GET['stage'] ?? '';

$where = "WHERE v.visa_type = 'student'";


if ($stage === 'offer') {
    $where .= " AND LOWER(svd.offer_letter_status) IN ('conditional','unconditional')";
}
elseif ($stage === 'approved') {
    $where .= " AND LOWER(v.visa_status) = 'approved'";
}
elseif ($stage === 'rejected') {
    $where .= " AND LOWER(v.visa_status) = 'rejected'";
}
elseif ($stage === 'pending') {
    $where .= " AND LOWER(v.visa_status) = 'pending'";
}


/* Role restriction (Employee sees only their leads) */
if ($_SESSION['role'] !== 'admin') {
    $user_id = (int) $_SESSION['user_id'];
    $where .= " AND l.assigned_to = $user_id";
}

/* ================= MAIN QUERY ================= */

$query = "
SELECT
    v.id,
    l.lead_name,
    l.mobile,
    v.country,
    v.status AS application_status,
    v.visa_status,
    svd.course,
    svd.university,
    svd.offer_letter_status,
    svd.loan_required,
    u.name AS employee_name
FROM visas v
JOIN leads l ON v.lead_id = l.id
LEFT JOIN student_visa_details svd ON svd.visa_id = v.id
LEFT JOIN users u ON l.assigned_to = u.id
$where
ORDER BY v.id DESC
";

$result = $conn->query($query);

if (!$result) {
    die("Query Failed: " . $conn->error);
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
    <title>Student Visa Applications</title>
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
                    <div class="row size-column">
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-header pb-0 card-no-border">
                                    <h4>Student Visa Applications</h4><span> List of all student visa applications and current progress.</span>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive custom-scrollbar">
                                        <table class="display" id="basic-1">
                                           <thead>
                                           <tr>
                                               <th>#</th>
                                               <th>Employee</th>
                                               <th>Student</th>
                                               <th>Mobile</th>
                                                <th>Country</th>
                                                <th>Course</th>
                                                <th>University</th>
                                                <th>Application</th>
                                                <th>Offer Letter</th>
                                                 <th>Visa</th>
                                                <th>Action</th>
                                           </tr>
                                            </thead>
                                            <tbody>
<?php
$i = 1;
while ($row = $result->fetch_assoc()) {
?>
<tr>
<td><?= $i++ ?></td>

<td><?= htmlspecialchars($row['employee_name']) ?></td>
<td><?= htmlspecialchars($row['lead_name']) ?></td>
<td><?= htmlspecialchars($row['mobile']) ?></td>
<td><?= htmlspecialchars($row['country']) ?></td>
<td><?= htmlspecialchars($row['course'] ?? '-') ?></td>
<td><?= htmlspecialchars($row['university'] ?? '-') ?></td>
<td><?= htmlspecialchars($row['application_status'] ?? '-') ?></td>
<td><?= htmlspecialchars($row['offer_letter_status'] ?? '-') ?></td>
<td><?= ucfirst($row['visa_status'] ?? '-') ?></td>

<td>
<div class="d-flex gap-1 justify-content-center">

<a href="student-visa-view.php?id=<?= $row['id'] ?>" 
   class="btn btn-sm btn-outline-primary rounded-2 px-2">
   <i data-feather="eye" style="width:14px;height:14px;"></i>
</a>

<a href="student-visa-edit.php?id=<?= $row['id'] ?>" 
   class="btn btn-sm btn-outline-success rounded-2 px-2">
   <i data-feather="edit" style="width:14px;height:14px;"></i>
</a>

<form action="student-visa-delete.php" method="POST"
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
            <?php include "footer.php";?>
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