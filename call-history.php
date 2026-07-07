<?php
session_start();
include "db.php";
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('calling_followup')) {
    http_response_code(403);
    die("Access Denied");
}

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['lead_id']) || !is_numeric($_GET['lead_id'])) {
    die("Invalid Lead");
}

$lead_id = intval($_GET['lead_id']);
$user_id = $_SESSION['user_id'];

/* Fetch lead */
if ($_SESSION['role'] === 'admin') {
    $leadStmt = $conn->prepare("SELECT lead_name, mobile FROM leads WHERE id = ?");
    $leadStmt->bind_param("i", $lead_id);
} else {
    $leadStmt = $conn->prepare("SELECT lead_name, mobile FROM leads WHERE id = ? AND assigned_to = ?");
    $leadStmt->bind_param("ii", $lead_id, $user_id);
}

$leadStmt->execute();
$leadResult = $leadStmt->get_result();

if ($leadResult->num_rows === 0) {
    die("Unauthorized Access");
}

$lead = $leadResult->fetch_assoc();

/* Fetch call history */
if ($_SESSION['role'] === 'admin') {
    $stmt = $conn->prepare("
        SELECT cl.*, u.name AS staff_name
        FROM call_logs cl
        LEFT JOIN users u ON cl.user_id = u.id
        WHERE cl.lead_id = ?
        ORDER BY cl.call_datetime DESC
    ");
    $stmt->bind_param("i", $lead_id);
} else {
    $stmt = $conn->prepare("
        SELECT cl.*, u.name AS staff_name
        FROM call_logs cl
        LEFT JOIN users u ON cl.user_id = u.id
        WHERE cl.lead_id = ? AND cl.user_id = ?
        ORDER BY cl.call_datetime DESC
    ");
    $stmt->bind_param("ii", $lead_id, $user_id);
}

$stmt->execute();
$result = $stmt->get_result();
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
    <title>Calling data History</title>
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
                                    <h4>Calling History Table</h4><span>Shows all inbound and outbound call interactions with leads for better follow-up tracking and performance monitoring.</span>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive custom-scrollbar">
                                        <div class="mb-3 p-3 bg-light rounded">
    <strong>Lead:</strong> <?= htmlspecialchars($lead['lead_name']) ?> |
    <strong>Mobile:</strong> <?= htmlspecialchars($lead['mobile']) ?>
    
    <a href="call-add.php?lead_id=<?= $lead_id ?>" 
       class="btn btn-sm btn-primary float-end">
       + Add Call
    </a>
</div>
                                        <table class="display" id="basic-1">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Call Type</th>
                                                    <th>Call Date</th>
                                                    <th>Status</th>
                                                    <th>Outcome</th>
                                                    <th>Next Follow-up</th>
                                                    <th>Remarks</th>
                                                    <th class="text-center">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
$today = date('Y-m-d');
$count = 1;

while ($row = $result->fetch_assoc()) {

    $followupClass = '';

    if (!empty($row['next_followup_date'])) {
        if ($row['next_followup_date'] == $today) {
            $followupClass = 'table-danger'; // Today followup
        } elseif ($row['next_followup_date'] < $today) {
            $followupClass = 'table-warning'; // Overdue
        }
    }
?>
<tr class="<?= $followupClass ?>">
    <td><?= $count++ ?></td>
    <td><?= htmlspecialchars($row['call_type']) ?></td>
    <td><?= date("d M Y H:i", strtotime($row['call_datetime'])) ?></td>
    <td><?= ucfirst(str_replace('_',' ', $row['call_status'])) ?></td>
    <td><?= ucfirst(str_replace('_',' ', $row['call_outcome'])) ?></td>
    <td>
        <?= !empty($row['next_followup_date']) 
            ? date("d M Y", strtotime($row['next_followup_date'])) 
            : '-' ?>
    </td>
    <td><?= htmlspecialchars($row['remarks']) ?></td>
    <td class="text-center">
    <?php if ($_SESSION['role'] === 'admin' || $row['user_id'] == $_SESSION['user_id']): ?>
        <form action="call-delete.php" method="POST"
              onsubmit="return confirm('Are you sure you want to delete this call log?');"
              style="display:inline;">
              
            <input type="hidden" name="id" value="<?= $row['id'] ?>">
            <input type="hidden" name="lead_id" value="<?= $lead_id ?>">

            <button type="submit"
                    class="btn btn-sm btn-outline-danger rounded-2 px-2">
                <i data-feather="trash-2" style="width:14px;height:14px;"></i>
            </button>
        </form>
    <?php endif; ?>
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