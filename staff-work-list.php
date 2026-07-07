<?php
session_start();
include "db.php";
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('staff_report')) {
    http_response_code(403);
    die("Access Denied");
}

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$role   = $_SESSION['role'];

if ($role === 'admin') {

    $sql = "
        SELECT sw.*, u.name 
        FROM staff_work_reports sw
        JOIN users u ON u.id = sw.user_id
        ORDER BY sw.work_date DESC
    ";

} else {

    $sql = "
        SELECT sw.*, u.name 
        FROM staff_work_reports sw
        JOIN users u ON u.id = sw.user_id
        WHERE sw.user_id = $userId
        ORDER BY sw.work_date DESC
    ";
}

$result = $conn->query($sql);
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
    <title>Campaign data History</title>
    <!-- Google font-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link href="../../css2-1?family=Montserrat:wght@200;300;400;500;600;700;800&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="assets/css/font-awesome.css">
    <!-- ico-font-->
    <link rel="+
    stylesheet" type="text/css" href="assets/css/vendors/icofont.css">
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
                                    <h4>Staff Working Report</h4><span> Daily summary of staff calls, follow-ups, and productivity.</span>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive custom-scrollbar">
                                        <table class="display" id="basic-1">
                                            <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Date</th>
                                                <th>Staff Name</th>
                                                <th>Calls Made</th>
                                                <th>Connected</th>
                                                <th>Busy / Invalid</th>
                                                <th>Follow-ups</th>
                                                <th>Working Hours</th>
                                                <th>Status</th>
                                                <th>Other Work</th>
                                                <?php if ($role === 'admin' || $role === 'employee'): ?>
                                                <th>Actions</th>
                                                <?php endif; ?>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php 
                                            $serial = 1;
                                            while ($row = $result->fetch_assoc()) { 
                                            ?>
                                            <tr>
                                                <td><?= $serial++ ?></td>
                                                <td><?= !empty($row['work_date']) && $row['work_date'] !== '0000-00-00'
                                                        ? date("d M Y", strtotime($row['work_date']))
                                                        : '<span class="text-muted">—</span>' ?></td>
                                                <td><?= htmlspecialchars($row['name']) ?></td>
                                                <td><?= $row['total_calls'] ?></td>
                                                <td><?= $row['calls_connected'] ?></td>
                                                <td><?= $row['busy_invalid_calls'] ?></td>
                                                <td><?= $row['followups_done'] ?></td>
                                                <td><?= htmlspecialchars($row['working_hours']) ?></td>
                                                <td><?= htmlspecialchars($row['daily_status']) ?></td>
                                                <td style="max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                                   <a href="#" 
       class="text-decoration-none text-dark"
       data-bs-toggle="modal"
       data-bs-target="#otherWorkModal"
       data-description="<?= htmlspecialchars($row['other_work'], ENT_QUOTES) ?>">
       
       <?= htmlspecialchars(substr($row['other_work'], 0, 30)) ?>
       <?= strlen($row['other_work']) > 30 ? '...' : '' ?>
    </a>
</td>
                                                <?php if ($role === 'admin' || $row['user_id'] == $userId): ?>
                                                <td class="text-center">

                                                    <a href="staff-work-edit.php?id=<?= $row['id'] ?>" 
                                                       class="btn btn-sm btn-outline-success rounded-2 px-2">
                                                       <i data-feather="edit" style="width:14px;height:14px;"></i>
                                                    </a>

                                                    <?php if ($role === 'admin'): ?>
                                                    <form action="staff-work-delete.php" method="POST"
                                                        onsubmit="return confirm('Are you sure you want to delete this record?');"
                                                        style="display:inline;">
                                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                        <button type="submit" 
                                                            class="btn btn-sm btn-outline-danger rounded-2 px-2">
                                                            <i data-feather="trash-2" style="width:14px;height:14px;"></i>
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>

                                                </td>
                                                <?php endif; ?>
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
    <div class="modal fade" id="otherWorkModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      
      <div class="modal-header">
        <h5 class="modal-title">Full Work Description</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      
      <div class="modal-body">
        <p id="fullDescription"></p>
      </div>
      
    </div>
  </div>
</div>
<script>
var otherWorkModal = document.getElementById('otherWorkModal');
otherWorkModal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var description = button.getAttribute('data-description');
    document.getElementById('fullDescription').textContent = description;
});
</script>
</body>

</html>