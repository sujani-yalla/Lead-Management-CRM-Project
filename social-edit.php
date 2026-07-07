<?php
session_start();
require 'db.php';
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('social_media')) {
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

$id       = intval($_GET['id']);
$user_id  = $_SESSION['user_id'];
$role     = $_SESSION['role'];

/* ================= FETCH WITH ROLE SECURITY ================= */

if ($role === 'admin') {

    $stmt = $conn->prepare("
        SELECT * FROM social_posts
        WHERE id = ?
    ");
    $stmt->bind_param("i", $id);

} else {

    $stmt = $conn->prepare("
        SELECT * FROM social_posts
        WHERE id = ? AND created_by = ?
    ");
    $stmt->bind_param("ii", $id, $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Unauthorized Access");
}

$post = $result->fetch_assoc();

/* ================= UPDATE LOGIC ================= */

if (isset($_POST['update'])) {

    $poster_name    = $_POST['poster_name'] ?? '';
    $platform       = $_POST['platform'] ?? '';
    $posting_type   = $_POST['posting_type'] ?? '';
    $content_status = $_POST['content_status'] ?? '';
    $posting_date   = $_POST['posting_date'] ?? '';
    $posting_time   = $_POST['posting_time'] ?? '';

    if ($role === 'admin') {

        $update = $conn->prepare("
            UPDATE social_posts
            SET poster_name=?, platform=?, posting_type=?, content_status=?, posting_date=?, posting_time=?
            WHERE id=?
        ");
        $update->bind_param(
            "ssssssi",
            $poster_name,
            $platform,
            $posting_type,
            $content_status,
            $posting_date,
            $posting_time,
            $id
        );

    } else {

        $update = $conn->prepare("
            UPDATE social_posts
            SET poster_name=?, platform=?, posting_type=?, content_status=?, posting_date=?, posting_time=?
            WHERE id=? AND created_by=?
        ");
        $update->bind_param(
            "ssssssii",
            $poster_name,
            $platform,
            $posting_type,
            $content_status,
            $posting_date,
            $posting_time,
            $id,
            $user_id
        );
    }

    if ($update->execute()) {
        header("Location: social-list.php?updated=1");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Riho admin is super flexible, powerful, clean &amp; modern responsive bootstrap 5 admin template with unlimited possibilities.">
    <meta name="keywords"
        content="admin template, Riho admin template, dashboard template, flat admin template, responsive admin template, web app">
    <meta name="author" content="pixelstrap">
    <link rel="icon" href="assets/images/favicon.png" type="image/x-icon">
    <link rel="shortcut icon" href="assets/images/favicon.png" type="image/x-icon">
    <title>Posts Adding</title>
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
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/echart.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/date-picker.css">
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
                                <h4>Social Media</h4>
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
                                    <h4>Post Details Form</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (isset($_GET['success'])): ?>
                                    <div class="alert alert-success">
                                        Post added successfully!
                                    </div>
                                <?php endif; ?>

                                        <form class="row g-3 needs-validation custom-input" method="POST">
                                        
                                        <div class="col-md-4 position-relative">
                                            <label class="form-label">Poster Name</label>
                                            <input type="text" name="poster_name" 
                                                class="form-control"
                                                value="<?= htmlspecialchars($post['poster_name']); ?>" required>
                                        </div>

                                        <!-- Platform -->
                                        <div class="col-md-4 position-relative">
                                            <label class="form-label">Platform</label>
                                             <input type="text" name="platform"
                                                class="form-control"
                                                value="<?= htmlspecialchars($post['platform']); ?>" required>
                                        </div>
                                    

                                        <!-- Posting Type -->
                                        <div class="col-md-4 position-relative">
                                            <label class="form-label">Posting Type</label>
                                            <select name="posting_type" class="form-select" required>
                                                <option value="company" <?= $post['posting_type']=='company'?'selected':''; ?>>Company</option>
                                                <option value="personal" <?= $post['posting_type']=='personal'?'selected':''; ?>>Personal</option>
                                            </select>
                                        </div>

                                        <!-- Content Status -->
                                        <div class="col-md-4 position-relative">
                                            <label class="form-label">Content Status</label>
                                            <select name="content_status" class="form-select" required>
                                                <option value="draft" <?= $post['content_status']=='draft'?'selected':''; ?>>Draft</option>
                                                <option value="scheduled" <?= $post['content_status']=='scheduled'?'selected':''; ?>>Scheduled</option>
                                                <option value="posted" <?= $post['content_status']=='posted'?'selected':''; ?>>Posted</option>
                                            </select>
                                        </div>

                                        <!-- Posting Date -->
                                        <div class="col-md-4 position-relative">
                                            <label class="form-label">Posting Date</label>
                                            <input type="date" name="posting_date"
                                                   class="form-control"
                                                   value="<?= $post['posting_date']; ?>" required>
                                        </div>

                                        <div class="col-md-4 position-relative">
                                            <label class="form-label">Posting Time</label>
                                            <input type="time" name="posting_time"
                                                   class="form-control"
                                                   value="<?= $post['posting_time']; ?>" required>
                                        </div>

                                        <!-- Submit -->
                                        <div class="col-12">
                                            <button class="btn btn-primary" type="submit" name="update">
                                                Update Post
                                            </button>
                                            <a href="social-list.php" 
                                                class="btn btn-secondary">
                                                Cancel
                                            </a>
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
    <script src="assets/js/chart/apex-chart/apex-chart.js"></script>
    <script src="assets/js/chart/apex-chart/stock-prices.js"></script>
    <script src="assets/js/chart/apex-chart/moment.min.js"></script>
    <script src="assets/js/chart/echart/esl.js"></script>
    <script src="assets/js/chart/echart/config.js"></script>
    <script src="assets/js/chart/echart/pie-chart/facePrint.js"></script>
    <script src="assets/js/chart/echart/pie-chart/testHelper.js"></script>
    <script src="assets/js/chart/echart/pie-chart/custom-transition-texture.js"></script>
    <script src="assets/js/chart/echart/data/symbols.js"></script>
    <!-- calendar js-->
    <script src="assets/js/datepicker/date-picker/datepicker.js"></script>
    <script src="assets/js/datepicker/date-picker/datepicker.en.js"></script>
    <script src="assets/js/datepicker/date-picker/datepicker.custom.js"></script>
    <script src="assets/js/dashboard/dashboard_3.js"></script>
    <!-- Plugins JS Ends-->
    <!-- Theme js-->
    <script src="assets/js/script.js"></script>
    <script src="assets/js/theme-customizer/customizer.js"></script>



</body>

</html>