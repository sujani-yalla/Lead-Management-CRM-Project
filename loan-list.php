<?php
session_start();
include "db.php";
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('loan_module')) {
    http_response_code(403);
    die("Access Denied");
}

/* HARD STOP */
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['filter'])) {

    if ($_GET['filter'] === 'approved') {
        $where .= " AND LOWER(ln.loan_status) LIKE '%approved%'";
    }

    if ($_GET['filter'] === 'rejected') {
        $where .= " AND LOWER(ln.loan_status) LIKE '%rejected%'";
    }

    if ($_GET['filter'] === 'pending') {
        $where .= " AND LOWER(ln.loan_status) NOT LIKE '%approved%'
                    AND LOWER(ln.loan_status) NOT LIKE '%rejected%'";
    }

    if ($_GET['filter'] === 'month') {
        $where .= " AND MONTH(ln.created_at)=MONTH(CURDATE())
                    AND YEAR(ln.created_at)=YEAR(CURDATE())";
    }

    if ($_GET['filter'] === 'today') {
        $where .= " AND DATE(ln.created_at)=CURDATE()";
    }
}

/* FETCH LOANS WITH LEAD DETAILS */

if ($_SESSION['role'] === 'admin') {

    $stmt = $conn->prepare("
    SELECT 
        ln.*,
        l.lead_name,
        l.mobile,
        u.name AS employee_name
    FROM loans ln
    JOIN leads l ON ln.lead_id = l.id
    LEFT JOIN users u ON u.id = ln.created_by
    ORDER BY ln.id DESC
");

} else {

    $stmt = $conn->prepare("
    SELECT 
        ln.*,
        l.lead_name,
        l.mobile,
        u.name AS employee_name
    FROM loans ln
    JOIN leads l ON ln.lead_id = l.id
    LEFT JOIN users u ON u.id = ln.created_by
    WHERE l.assigned_to = ?
    ORDER BY ln.id DESC
");

    $stmt->bind_param("i", $_SESSION['user_id']);
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
    <title>Loan Applications List</title>
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
                                    <h4>Loan Applications List</h4><span>Overview of all student loan applications and current processing status.</span>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive custom-scrollbar">
                                        <table class="display" id="basic-1">
                                            <thead>
                                                <tr>
                                                     <th>#</th>
                                                     <th>Employee</th>
                                                    <th>Student Name</th>
                                                    <th>Contact</th>
                                                    <th>Loan Start Date</th>
                                                    <th>Applied Banks</th>
                                                    <th>Approved Bank</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                          <tbody>
<?php 
$serial = 1;
while ($row = $result->fetch_assoc()) { 
?>
<tr>
    <td><?= $serial++; ?></td>
    <td><?= htmlspecialchars($row['employee_name']); ?></td>
    <td><?= htmlspecialchars($row['lead_name']); ?></td>
    <td><?= htmlspecialchars($row['mobile']); ?></td>
    <td><?= date("d M Y", strtotime($row['loan_start_date'])); ?></td>
    <td><?= htmlspecialchars($row['applied_banks']); ?></td>
    <td><?= htmlspecialchars($row['approved_bank_name']); ?></td>
    <td><?= htmlspecialchars($row['loan_status']); ?></td>

    <td>
        <div class="d-flex gap-1 justify-content-center">

            <a href="loan-view.php?id=<?= $row['id'] ?>" 
               class="btn btn-sm btn-outline-primary rounded-2 px-2">
               <i data-feather="eye" style="width:14px;height:14px;"></i>
            </a>

            <a href="loan-edit.php?id=<?= $row['id'] ?>" 
               class="btn btn-sm btn-outline-success rounded-2 px-2">
               <i data-feather="edit" style="width:14px;height:14px;"></i>
            </a>

            <form action="loan-delete.php" method="POST"
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