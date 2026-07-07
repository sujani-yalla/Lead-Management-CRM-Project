<?php
session_start();

// If logged in, redirect
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: dashboard.php");
        exit;
    }
    if ($_SESSION['role'] === 'employee') {
        header("Location: employee-dashboard.php");
        exit;
    }
}

// If NOT logged in → show login page below
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CRM Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="assets/css/vendors/bootstrap.css" rel="stylesheet">
<style>
html, body {
    height: 100%;
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
}

.container-fluid,
.row {
    height: 100%;
}
/* LEFT SECTION */
.left-section {
    background: linear-gradient(rgba(0,50,150,0.7), rgba(0,80,200,0.7)),
                url('assets/images/login-bg.jpg') center/cover no-repeat;
    color: white;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 60px;
    min-height: 100vh;
}

.left-section h1 {
    font-weight: 700;
    font-size: 42px;
}

.left-section span {
    color: #ffd54f;
}

/* LOGIN CARD */
.login-card {
    background: white;
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    width: 100%;
    max-width: 420px;
}

.login-btn {
    background: linear-gradient(90deg, #0d47a1, #1565c0);
    border: none;
    padding: 12px;
    font-weight: 600;
}

.login-btn:hover {
    background: linear-gradient(90deg, #1565c0, #0d47a1);
}

.footer-note {
    font-size: 13px;
    color: #777;
    margin-top: 15px;
}

/* ===== MOBILE RESPONSIVE ===== */

@media (max-width: 991px) {

    body {
        overflow-y: auto;
    }

    .left-section {
        display: none;
    }

    .login-card {
        padding: 30px 25px;
        margin: 20px;
        border-radius: 10px;
    }

    .login-card h4 {
        font-size: 20px;
    }

    .footer-note {
        font-size: 12px;
    }
}

@media (max-width: 480px) {

    .login-card {
        padding: 25px 20px;
    }

    .login-card h4 {
        font-size: 18px;
    }

    .login-btn {
        padding: 10px;
        font-size: 14px;
    }
}
</style>
</head>
<body>

<div class="container-fluid vh-100">
    <div class="row vh-100">

        <!-- LEFT -->
         <div class="col-lg-7 d-flex flex-column justify-content-center align-items-start left-section">

            <img src="assets/images/logo.png" 
                 style="width:140px; margin-bottom:30px;">
            <h1>Manage Global <br><span>Student Success</span></h1>
            <p class="mt-3">
                Leads • Applications • Visa Process • Reports
            </p>
            <p class="mt-4">Secure. Smart. Professional.</p>
        </div>

        <!-- RIGHT -->
        <div class="col-lg-5 d-flex justify-content-center align-items-center">

            <div class="login-card">

                <h4 class="text-center mb-4">CRM Login Portal</h4>

                <form action="login-process.php" method="POST">

                    <div class="mb-3">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control"
                               placeholder="Enter your official email" required>
                    </div>

                    <div class="mb-3">
                        <label>Password</label>
                        <div class="position-relative">
                          <input type="password" name="password" id="password"
                                 class="form-control pe-5"
                                 placeholder="Enter your password" required>

                          <span onclick="togglePassword()"
                                style="position:absolute; right:15px; top:50%; transform:translateY(-50%);
                                       cursor:pointer; color:#666;">
                             <i id="eyeIcon" data-feather="eye"></i>
                           </span>
                        </div>

                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <div>
                            <input type="checkbox"> Remember Me
                        </div>
                        <a href="#" onclick="showResetMessage(); return false;">
    Forgot Password?
</a>

<script>
function showResetMessage() {
    alert("Please contact the System Administrator to reset your password.");
}
</script>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 login-btn">
                        Login to Dashboard
                    </button>

                    <div class="footer-note text-center">
                        <p>Your data is 100% secure</p>
                        <p>Access restricted to authorized staff only</p>
                    </div>
                    <?php if (isset($_GET['error'])): ?>
                      <div class="alert alert-danger mt-3">
                           <?= htmlspecialchars($_GET['error']) ?>
                     </div>
                   <?php endif; ?>



                </form>
            </div>

        </div>
    </div>
</div>
<script>
function togglePassword() {
    const password = document.getElementById("password");
    const icon = document.getElementById("eyeIcon");

    if (password.type === "password") {
        password.type = "text";
        icon.setAttribute("data-feather", "eye-off");
    } else {
        password.type = "password";
        icon.setAttribute("data-feather", "eye");
    }

    feather.replace();
}
</script>

<script src="assets/js/icons/feather-icon/feather.min.js"></script>
<script>
    feather.replace();
</script>

</body>
</html>

