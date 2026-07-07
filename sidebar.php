<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "access-control.php";
$role = $_SESSION['role'] ?? 'employee';
$dashboardLink = ($role === 'admin') ? 'dashboard.php' : 'employee-dashboard.php';
?>
<?php if ($_SESSION['role'] === 'admin'): ?>
<li><a href="campaign-add.php">Add Campaign</a></li>
<?php endif; ?>

<div class="sidebar-wrapper" data-layout="stroke-svg">
    <div class="logo-wrapper">
        <!-- LOGO → ROLE BASED DASHBOARD -->
        <a href="<?= $dashboardLink ?>">
            <img class="img-fluid" src="assets/images/logo/logo.png" alt="Indian Overseas Services">
        </a>
        <div class="toggle-sidebar">
            <i class="status_toggle middle sidebar-toggle" data-feather="grid"></i>
        </div>
    </div>

    <nav class="sidebar-main">
        <div id="sidebar-menu">
            <ul class="sidebar-links" id="simple-bar">

                <!-- DASHBOARD -->
                <li class="sidebar-main-title">
                    <div>
                        <h6>Dashboard</h6>
                    </div>
                </li>

                <li class="sidebar-list">
                    <a class="sidebar-link link-nav" href="<?= $dashboardLink ?>">
                        <i data-feather="home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <!-- CRM MODULES -->
                <li class="sidebar-main-title">
                    <div>
                        <h6>CRM Modules</h6>
                    </div>
                </li>
                <?php if (hasAccess('lead_management')): ?>
                <li class="sidebar-list">
                    <a class="sidebar-link sidebar-title" href="#">
                        <i data-feather="users"></i>
                        <span>Lead Management</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="lead-add.php">Add Lead</a></li>
                        <li><a href="lead-list.php">Lead List</a></li>
                    </ul>
                </li>
                <?php endif; ?>
                <?php if (hasAccess('calling_followup')): ?>
                <li class="sidebar-list">
                    <a class="sidebar-link sidebar-title" href="#">
                        <i data-feather="phone-call"></i>
                        <span>Calling & Follow-up</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="call-add.php">Add Call Log</a></li>
                        <li><a href="call-list.php">Call History</a></li>
                    </ul>
                </li>
                <?php endif; ?>
                <?php if ($_SESSION['role'] === 'admin'): ?>

               <li class="sidebar-list">
                 <a class="sidebar-link sidebar-title" href="#">
                   <i data-feather="check-square"></i>
                    <span>Task Management</span>
                </a>
               <ul class="sidebar-submenu">
                  <li><a href="assign-task.php">Assign Task</a></li>
                  <li><a href="admin-review-tasks.php">Review Reports</a></li>
                  <li><a href="task-reports.php">All Reports</a></li>
              </ul>
                </li>

                <?php endif; ?>

                <?php if ($_SESSION['role'] === 'employee'): ?>

                <li class="sidebar-list">
                   <a class="sidebar-link sidebar-title" href="#">
                      <i data-feather="clipboard"></i>
                      <span>My Tasks</span>
                   </a>
                   <ul class="sidebar-submenu">
                       <li><a href="my-tasks.php">View Tasks</a></li>
                   </ul>
                </li>

                <?php endif; ?>


                <?php if (hasAccess('lead_source_tracking')): ?>
                <li class="sidebar-list">
                    <a class="sidebar-link sidebar-title" href="#">
                        <i data-feather="activity"></i>
                        <span>Lead Source Tracking</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="campaign-add.php">Add Campaign</a></li>
                        <li><a href="campaign-list.php">Campaign Leads</a></li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (hasAccess('staff_report')): ?>
                <li class="sidebar-list">
                    <a class="sidebar-link sidebar-title" href="#">
                        <i data-feather="user-check"></i>
                        <span>Staff Work Report</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="staff-work-add.php">Daily Entry</a></li>
                        <li><a href="staff-work-list.php">Staff Report</a></li>
                    </ul>
                </li>
                <?php endif; ?>
                 <?php if (hasAccess('student_visa')): ?>
                <li class="sidebar-list">
                    <a class="sidebar-link sidebar-title" href="#">
                        <i data-feather="book"></i>
                        <span>Student Visa</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="student-visa-add.php">Add Student</a></li>
                        <li><a href="student-visa-list.php">Student Records</a></li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (hasAccess('loan_module')): ?>
                <li class="sidebar-list">
                    <a class="sidebar-link sidebar-title" href="#">
                        <i data-feather="dollar-sign"></i>
                        <span>Loan Module</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="loan-add.php">Add Loan</a></li>
                        <li><a href="loan-list.php">Loan Status</a></li>
                    </ul>
                </li>
                <?php endif; ?>
                <?php if (hasAccess('work_visa')): ?>
                <li class="sidebar-list">
                    <a class="sidebar-link sidebar-title" href="#">
                        <i data-feather="briefcase"></i>
                        <span>Work Visa</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="work-visa-add.php">Add Work Case</a></li>
                        <li><a href="work-visa-list.php">Work Visa Cases</a></li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (hasAccess('visitor_visa')): ?>
                <li class="sidebar-list">
                    <a class="sidebar-link sidebar-title" href="#">
                        <i data-feather="map-pin"></i>
                        <span>Visitor Visa</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="visitor-visa-add.php">Add Visitor</a></li>
                        <li><a href="visitor-visa-list.php">Visitor Records</a></li>
                    </ul>
                </li>
                <?php endif; ?>
                <?php if (hasAccess('pr_application')): ?>
                <li class="sidebar-list">
                    <a class="sidebar-link sidebar-title" href="#">
                        <i data-feather="globe"></i>
                        <span>PR Application</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="pr-add.php">Add PR Case</a></li>
                        <li><a href="pr-list.php">PR Status</a></li>
                    </ul>
                </li>
                <?php endif; ?>
                <?php if (hasAccess('ca_legal')): ?>
                <li class="sidebar-list">
                    <a class="sidebar-link sidebar-title" href="#">
                        <i data-feather="file-text"></i>
                        <span>CA & Legal Work</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="legal-add.php">Add Case</a></li>
                        <li><a href="legal-list.php">Case Tracker</a></li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (hasAccess('social_media')): ?>
                <li class="sidebar-list">
                    <a class="sidebar-link sidebar-title" href="#">
                        <i data-feather="instagram"></i>
                        <span>Social Media</span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="social-add.php">Add Post</a></li>
                        <li><a href="social-list.php">Post Schedule</a></li>
                    </ul>
                </li>
                <?php endif; ?>
                <li class="sidebar-list">
    <a class="sidebar-link link-nav" href="reports/reports-dashboard.php">
        <i data-feather="bar-chart-2"></i>
        <span>Reports</span>
    </a>
</li>

                
                <?php if ($_SESSION['role'] === 'admin'): ?>
<li class="sidebar-main-title">
    <div>
        <h6>System Management</h6>
    </div>
</li>

<li class="sidebar-list">
    <a class="sidebar-link sidebar-title" href="#">
        <i data-feather="settings"></i>
        <span>Employee Management</span>
    </a>
    <ul class="sidebar-submenu">
        <li><a href="employee-list.php">Employee List</a></li>
        <li><a href="add-employee.php">Add Employee</a></li>
    </ul>
</li>
<?php endif; ?>

            </ul>
        </div>
    </nav>
</div>