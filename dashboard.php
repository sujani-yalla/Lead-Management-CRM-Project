
<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Access denied";
    exit;
}
ini_set('display_errors', 1);
error_reporting(E_ALL);
$role   = $_SESSION['role'];
$userId = $_SESSION['user_id'];

include "db.php";

$totalPRLeads = $conn->query("
    SELECT COUNT(*) as total
    FROM leads
    WHERE lead_type='PR'
    " . ($role === 'admin' ? "" : "AND assigned_to=$userId") . "
")->fetch_assoc()['total'];

// Eligible
$eligible = $conn->query("
    SELECT COUNT(*) as total
    FROM pr_case_details details
    WHERE details.eligibility_result = 'Eligible'
")->fetch_assoc()['total'];

// Not Eligible
$notEligible = $conn->query("
    SELECT COUNT(*) as total 
    FROM pr_enquiries e 
    " . ($role === 'admin' ? "WHERE eligibility_result='Not Eligible'" :
        "WHERE e.created_by=$userId AND eligibility_result='Not Eligible'")
)->fetch_assoc()['total'];

// Hot Leads
$hotLeads = $conn->query("
    SELECT COUNT(*) as total 
    FROM pr_enquiries e 
    " . ($role === 'admin' ? "WHERE priority_level='Hot'" :
        "WHERE e.created_by=$userId AND priority_level='Hot'")
)->fetch_assoc()['total'];

$pipelineCondition = ($role === 'admin') 
    ? "" 
    : "JOIN pr_enquiries e ON p.case_id = e.id 
       WHERE e.created_by = $userId";

// Documentation
$documentation = $conn->query("
    SELECT COUNT(*) as total
    FROM pr_case_process p
    " . ($role === 'admin'
        ? "WHERE p.current_stage IN ('Documents Pending','Documents Verified')"
        : "JOIN pr_enquiries e ON p.case_id = e.id 
           WHERE e.created_by=$userId 
           AND p.current_stage IN ('Documents Pending','Documents Verified')")
)->fetch_assoc()['total'];

// Profile Submitted
$submitted = $conn->query("
    SELECT COUNT(*) as total
    FROM pr_case_process p
    " . ($role === 'admin'
        ? "WHERE p.current_stage IN ('Profile Submitted','PR Application Filed')"
        : "JOIN pr_enquiries e ON p.case_id = e.id 
           WHERE e.created_by=$userId 
           AND p.current_stage IN ('Profile Submitted','PR Application Filed')")
)->fetch_assoc()['total'];

// Invitation
$invitation = $conn->query("
    SELECT COUNT(*) as total
    FROM pr_case_process p
    " . ($role === 'admin'
        ? "WHERE p.current_stage='Invitation Received'"
        : "JOIN pr_enquiries e ON p.case_id = e.id 
           WHERE e.created_by=$userId 
           AND p.current_stage='Invitation Received'")
)->fetch_assoc()['total'];

// Approved
$approved = $conn->query("
    SELECT COUNT(*) as total
    FROM pr_case_process p
    " . ($role === 'admin'
        ? "WHERE p.decision_status='Approved'"
        : "JOIN pr_enquiries e ON p.case_id = e.id 
           WHERE e.created_by=$userId 
           AND p.decision_status='Approved'")
)->fetch_assoc()['total'];

// Rejected
$rejected = $conn->query("
    SELECT COUNT(*) as total
    FROM pr_case_process p
    " . ($role === 'admin'
        ? "WHERE p.decision_status='Rejected'"
        : "JOIN pr_enquiries e ON p.case_id = e.id 
           WHERE e.created_by=$userId 
           AND p.decision_status='Rejected'")
)->fetch_assoc()['total'];

$totalReceived = $conn->query("
    SELECT IFNULL(SUM(pay.amount),0) as total
    FROM pr_payments pay
    " . ($role === 'admin'
        ? ""
        : "JOIN pr_enquiries e ON pay.lead_id = e.lead_id 
           WHERE e.created_by=$userId")
)->fetch_assoc()['total'];

$totalAgreement = $conn->query("
    SELECT IFNULL(SUM(e.total_fee),0) as total
    FROM pr_enquiries e
    JOIN pr_case_details d ON d.lead_id = e.lead_id
    " . ($role === 'admin'
        ? "WHERE d.agreement_signed='Yes'"
        : "WHERE e.created_by=$userId AND d.agreement_signed='Yes'")
)->fetch_assoc()['total'];

$balanceAmount = $totalAgreement - $totalReceived;

$revenueMonth = $conn->query("
    SELECT IFNULL(SUM(pay.amount),0) as total
    FROM pr_payments pay
    " . ($role === 'admin'
        ? "WHERE MONTH(pay.payment_date)=MONTH(CURDATE())
           AND YEAR(pay.payment_date)=YEAR(CURDATE())"
        : "JOIN pr_enquiries e ON pay.lead_id=e.lead_id
           WHERE e.created_by=$userId
           AND MONTH(pay.payment_date)=MONTH(CURDATE())
           AND YEAR(pay.payment_date)=YEAR(CURDATE())")
)->fetch_assoc()['total'];

$today = date('Y-m-d');

/* CALL DASHBOARD (ADMIN) */
$todayFollowups = $conn->query("
    SELECT COUNT(*) as total 
    FROM call_logs
    WHERE next_followup_date = '$today'
")->fetch_assoc()['total'];

$overdueFollowups = $conn->query("
    SELECT COUNT(*) as total 
    FROM call_logs
    WHERE next_followup_date < '$today'
")->fetch_assoc()['total'];

$totalCalls = $conn->query("
    SELECT COUNT(*) as total 
    FROM call_logs
")->fetch_assoc()['total'];

/* TOTAL LEADS */
$totalLeads = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM leads");
$stmt->execute();
$stmt->bind_result($totalLeads);
$stmt->fetch();
$stmt->close();

/* NEW LEADS TODAY */
$newLeadsToday = 0;
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM leads 
    WHERE DATE(created_at) = CURDATE()
");
$stmt->execute();
$stmt->bind_result($newLeadsToday);
$stmt->fetch();
$stmt->close();

/* VISA APPROVED COUNT */
$visaApproved = 0;
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM visas
    WHERE status = 'Approved'
    AND visa_type IN ('student','work','visitor')
");
$stmt->execute();
$stmt->bind_result($visaApproved);
$stmt->fetch();
$stmt->close();

/* TOTAL STUDENTS */
$totalStudents = 0;
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM visas
    WHERE visa_type = 'student'
");
$stmt->execute();
$stmt->bind_result($totalStudents);
$stmt->fetch();
$stmt->close();

/* TOTAL WORK VISAS */
$totalWorkVisas = 0;
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM visas
    WHERE visa_type = 'work'
");
$stmt->execute();
$stmt->bind_result($totalWorkVisas);
$stmt->fetch();
$stmt->close();

/* TOTAL TASKS */
$totalTasks = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM tasks");
$stmt->execute();
$stmt->bind_result($totalTasks);
$stmt->fetch();
$stmt->close();

/* RECENT ASSIGNED TASKS */
$taskList = $conn->query("
    SELECT t.task_code, t.title, t.priority, t.deadline, t.status, u.name as employee_name
    FROM tasks t
    JOIN users u ON t.assigned_to = u.id
    ORDER BY t.id DESC
    LIMIT 10
");


/* TASKS IN PROGRESS */
$tasksInProgress = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM tasks WHERE status='In Progress'");
$stmt->execute();
$stmt->bind_result($tasksInProgress);
$stmt->fetch();
$stmt->close();

/* WAITING APPROVAL */
$tasksWaiting = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM tasks WHERE status='Waiting Approval'");
$stmt->execute();
$stmt->bind_result($tasksWaiting);
$stmt->fetch();
$stmt->close();

/* COMPLETED TASKS */
$tasksCompleted = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM tasks WHERE status='Completed'");
$stmt->execute();
$stmt->bind_result($tasksCompleted);
$stmt->fetch();
$stmt->close();

/* ================= CAMPAIGN ANALYTICS ================= */

$campaignData = [];

$sql = "
SELECT 
    c.id,
    c.campaign_name,
    COUNT(l.id) AS total_leads,

    SUM(CASE WHEN l.lead_type='Student' THEN 1 ELSE 0 END) AS student_count,
    SUM(CASE WHEN l.lead_type='Work Visa' THEN 1 ELSE 0 END) AS work_count,
    SUM(CASE WHEN l.lead_type='Loan' THEN 1 ELSE 0 END) AS loan_count,
    SUM(CASE WHEN l.lead_type='PR' THEN 1 ELSE 0 END) AS pr_count

FROM campaigns c
LEFT JOIN leads l ON l.campaign_id = c.id
GROUP BY c.id
ORDER BY total_leads DESC
";

$resultCampaign = $conn->query($sql);

while ($row = $resultCampaign->fetch_assoc()) {

    $converted = $row['student_count'] 
               + $row['work_count'] 
               + $row['loan_count'] 
               + $row['pr_count'];

    $conversion = $row['total_leads'] > 0 
        ? round(($converted / $row['total_leads']) * 100, 1)
        : 0;

    $row['conversion'] = $conversion;

    $campaignData[] = $row;
}


/* ================= LOAN DASHBOARD ================= */

$totalLoans = $conn->query("
    SELECT COUNT(*) as total 
    FROM loans
")->fetch_assoc()['total'];

$approvedLoans = $conn->query("
    SELECT COUNT(*) as total 
    FROM loans 
    WHERE LOWER(loan_status) LIKE '%approved%'
")->fetch_assoc()['total'];

$rejectedLoans = $conn->query("
    SELECT COUNT(*) as total 
    FROM loans 
    WHERE LOWER(loan_status) LIKE '%rejected%'
")->fetch_assoc()['total'];

$pendingLoans = $conn->query("
    SELECT COUNT(*) as total 
    FROM loans 
    WHERE LOWER(loan_status) NOT LIKE '%approved%' 
    AND LOWER(loan_status) NOT LIKE '%rejected%'
")->fetch_assoc()['total'];

$monthlyLoans = $conn->query("
    SELECT COUNT(*) as total
    FROM loans
    WHERE MONTH(created_at)=MONTH(CURDATE())
    AND YEAR(created_at)=YEAR(CURDATE())
")->fetch_assoc()['total'];

$todayLoans = $conn->query("
    SELECT COUNT(*) as total
    FROM loans
    WHERE DATE(created_at)=CURDATE()
")->fetch_assoc()['total'];

/* ================= STUDENT PIPELINE (ADMIN – ALL EMPLOYEES) ================= */

$studentPipeline = [
    'applied'  => 0,
    'offer'    => 0,
    'approved' => 0,
    'rejected' => 0
];

/* TOTAL APPLIED (All student visa entries) */
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM visas
    WHERE visa_type = 'student'
");
$stmt->execute();
$stmt->bind_result($studentPipeline['applied']);
$stmt->fetch();
$stmt->close();

/* OFFER RECEIVED (Conditional + Unconditional) */
$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM student_visa_details svd
    JOIN visas v ON svd.visa_id = v.id
    WHERE v.visa_type = 'student'
    AND svd.offer_letter_status IN ('Conditional','Unconditional')
");
$stmt->execute();
$stmt->bind_result($studentPipeline['offer']);
$stmt->fetch();
$stmt->close();

/* VISA APPROVED */
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM visas
    WHERE visa_type = 'student'
    AND visa_status = 'Approved'
");
$stmt->execute();
$stmt->bind_result($studentPipeline['approved']);
$stmt->fetch();
$stmt->close();

/* VISA REJECTED */
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM visas
    WHERE visa_type = 'student'
    AND visa_status = 'Rejected'
");
$stmt->execute();
$stmt->bind_result($studentPipeline['rejected']);
$stmt->fetch();
$stmt->close();


// Visitor Pipeline Counts (Admin)
$pipelineQuery = $conn->query("
    SELECT status, COUNT(*) as total 
    FROM visas 
    WHERE visa_type='visitor'
    GROUP BY status
");

$pipeline = [
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0
];

while ($row = $pipelineQuery->fetch_assoc()) {
    $pipeline[$row['status']] = $row['total'];
}

$workPipeline = [
    'processing' => 0,
    'approved' => 0,
    'rejected' => 0
];

$stmt = $conn->prepare("
    SELECT status
    FROM visas
    WHERE visa_type = 'work'
");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    $status = strtolower(trim($row['status']));

if ($status === 'approved') {
    $workPipeline['approved']++;
} 
elseif ($status === 'rejected') {
    $workPipeline['rejected']++;
} 
else {
    $workPipeline['processing']++;
}

    
}
/* ================= LEGAL CASES COUNT ================= */

$totalLegal = $conn->query("
    SELECT COUNT(*) as total 
    FROM legal_cases
")->fetch_assoc()['total'];

$staffPerformance = [];

$stmt = $conn->prepare("
    SELECT u.id, u.name, COUNT(l.id) as total_leads
    FROM users u
    LEFT JOIN leads l ON l.assigned_to = u.id
    WHERE u.role = 'employee'
    GROUP BY u.id
    ORDER BY total_leads DESC
");

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $staffPerformance[] = $row;
}

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Riho admin is super flexible, powerful, clean & modern responsive bootstrap 5 admin template with unlimited possibilities.">
    <meta name="keywords"
        content="admin template, Riho admin template, dashboard template">
    <meta name="author" content="pixelstrap">

    <link rel="icon" href="assets/images/favicon.png" type="image/x-icon">
    <link rel="shortcut icon" href="assets/images/favicon.png" type="image/x-icon">
    <title>Admin Dashboard</title>

    <!-- Google font-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link href="../../css2-1?family=Montserrat:wght@200;300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" type="text/css" href="assets/css/font-awesome.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/icofont.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/themify.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/flag-icon.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/feather-icon.css">

    <link rel="stylesheet" type="text/css" href="assets/css/vendors/slick.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/slick-theme.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/scrollbar.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/animate.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/echart.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/date-picker.css">

    <link rel="stylesheet" type="text/css" href="assets/css/vendors/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
    <link id="color" rel="stylesheet" href="assets/css/color-1.css" media="screen">
    <link rel="stylesheet" type="text/css" href="assets/css/responsive.css">

    <!-- 🔹 MINIMAL REQUIRED DASHBOARD CSS -->
    <style>
        .funnel-step {
            position: relative;
            font-weight: 600;
        }
        .funnel-step:not(:last-child)::after {
            content: "→";
            position: absolute;
            right: -18px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 22px;
            font-weight: bold;
            color: #555;
        }
    </style>

<style>
.pipeline-wrapper {
    max-width: 900px;
    margin: auto;
    padding: 30px 20px;
}

.pipeline-stage {
    position: relative;
    z-index: 2;
}

.stage-circle {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    font-size: 20px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #F1F5F9;   /* neutral light */
    color: #0f172a;
    border: 2px solid #E2E8F0;
    box-shadow: none;
    transition: all 0.2s ease;
}

.stage-circle:hover {
    background: #E2E8F0;
}

.pipeline-line {
    flex: 1;
    height: 3px;
    background: #e9ecef;
    margin: 0 10px;
    position: relative;
    top: -10px;
}

.blue-card {
    border: none;
    border-radius: 20px;
    padding: 6px;
    background: linear-gradient(
        135deg,
        #EAF4FF 0%,
        #F4F9FF 40%,
        #FFFFFF 100%
    );
    box-shadow: 
        0 8px 20px rgba(0, 123, 255, 0.06),
        inset 0 1px 0 rgba(255,255,255,0.6);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.blue-card:hover {
    transform: translateY(-6px);
    box-shadow: 
        0 14px 35px rgba(0, 123, 255, 0.12),
        inset 0 1px 0 rgba(255,255,255,0.8);
}

/* subtle shine overlay */
.blue-card::before {
    content: "";
    position: absolute;
    top: -40%;
    left: -20%;
    width: 150%;
    height: 150%;
    background: radial-gradient(
        circle at top left,
        rgba(255,255,255,0.6),
        transparent 60%
    );
    pointer-events: none;
}

.mini-label {
    font-size: 12px;
    color: #6c757d;
    letter-spacing: 0.5px;
}

.mini-number {
    font-size: 28px;
    font-weight: 700;
    margin-top: 4px;
    color: #0d3b66;
}

.mini-icon {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    background: rgba(13, 110, 253, 0.08);
    display: flex;
    align-items: center;
    justify-content: center;
}
.table thead th {
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748B;
}
.card:hover {
   transform: translateY(-3px);
   transition: 0.2s ease;
}

.hover-shadow {
    transition: all 0.25s ease;
    border-radius: 18px;
}
.hover-shadow:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.06);
}

</style>

</head>

<body>

<!-- Loader -->
<!--<div class="loader-wrapper">
    <div class="loader">
        <div class="loader4"></div>
    </div>
</div>-->

<div class="tap-top"><i data-feather="chevrons-up"></i></div>

<div class="page-wrapper" id="pageWrapper">
    <?php include "header.php"; ?>

    <div class="page-body-wrapper">

        <?php include "sidebar.php"; ?>

        <div class="page-body">

            <!-- ================= ADMIN DASHBOARD ================= -->
            <div class="container-fluid">
<!-- ================= KPI CARDS (ALL) ================= -->
     <div class="row g-3 mb-4">

                    <?php
                    $cards = [

    ["Total Leads", number_format($totalLeads), "users", "primary"],
    ["New Leads (Today)", number_format($newLeadsToday), "user-plus", "success"],

    ["Total PR Leads", number_format($totalPRLeads), "briefcase", "info"],
    ["Visa Approved (All Types)", number_format($visaApproved), "check-circle", "success"],

    ["Total Students", number_format($totalStudents), "book-open", "info"],
["Total Work Visas", number_format($totalWorkVisas), "briefcase", "primary"],

    ["Total Loans", number_format($totalLoans), "dollar-sign", "warning"],
    ["Legal Cases", number_format($totalLegal), "file-text", "secondary"],

    ["Total Tasks", number_format($totalTasks), "clipboard", "primary"],
    ["Tasks In Progress", number_format($tasksInProgress), "activity", "warning"],
    ["Waiting Approval", number_format($tasksWaiting), "clock", "info"],
    ["Tasks Completed", number_format($tasksCompleted), "check-square", "success"],
];
                    foreach ($cards as $c) {
                        echo "
                       <div class='col-xl-3 col-md-4 col-sm-6'>
                            <div class='card widget-1'>
                            <div class='card-body'>
                                    <div class='widget-content'>
                                        <div class='widget-round {$c[3]}'>
                                            <div class='bg-round'>
                                                <i data-feather='{$c[2]}'></i>
                                            </div>
                                        </div>
                                        <div>
                                            <h4>{$c[1]}</h4>
                                            <span>{$c[0]}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>";
                    }
                    ?>

                </div>
<div class="row g-4 mb-4 justify-content-center">
                 <!-- Today -->
    <div class="col-xl-3 col-lg-4 col-md-6">
        <a href="call-list.php?filter=today" class="text-decoration-none">
            <div class="card blue-card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="mini-label">Today Follow-ups</div>
                        <div class="mini-number">
                            <?= $todayFollowups ?>
                        </div>
                    </div>
                    <div class="mini-icon">
                        <i data-feather="phone-call"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Overdue -->
    <div class="col-xl-3 col-lg-4 col-md-6">
        <a href="call-list.php?filter=overdue" class="text-decoration-none">
            <div class="card blue-card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="mini-label">Overdue Follow-ups</div>
                        <div class="mini-number">
                            <?= $overdueFollowups ?>
                        </div>
                    </div>
                    <div class="mini-icon">
                        <i data-feather="alert-triangle"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Total -->
    <div class="col-xl-3 col-lg-4 col-md-6">
        <a href="call-list.php" class="text-decoration-none">
            <div class="card blue-card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="mini-label">Total Calls Logged</div>
                        <div class="mini-number">
                            <?= $totalCalls ?>
                        </div>
                    </div>
                    <div class="mini-icon">
                        <i data-feather="phone"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

</div>

<h4 class="fw-bold mt-4 mb-3">Quick Access</h4>

<div class="row g-3 mb-4">

    <div class="col-xl-3 col-md-6">
        <a href="legal-list.php" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100" style="background:#F8FBFF;">
                <div class="card-body text-center">
                    <i data-feather="file-text" class="mb-2 text-primary"></i>
                    <h5 class="fw-bold"><?= $totalLegal ?></h5>
                    <small class="text-muted">Legal Cases</small>
                </div>
            </div>
        </a>
    </div>

    <div class="col-xl-3 col-md-6">
        <a href="pr-list.php" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100" style="background:#EEF4FF;">
                <div class="card-body text-center">
                    <i data-feather="briefcase" class="mb-2 text-info"></i>
                    <h5 class="fw-bold"><?= $totalPRLeads ?></h5>
                    <small class="text-muted">PR Leads</small>
                </div>
            </div>
        </a>
    </div>

    <div class="col-xl-3 col-md-6">
    <a href="lead-list.php" class="text-decoration-none">
        <div class="card border-0 shadow-sm h-100" style="background:#F0F9FF;">
            <div class="card-body text-center">
                <i data-feather="users" class="mb-2 text-primary"></i>
                <h5 class="fw-bold"><?= $totalLeads ?></h5>
                <small class="text-muted">Total Leads</small>
            </div>
        </div>
    </a>
</div>

    <div class="col-xl-3 col-md-6">
        <a href="loan-list.php" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100" style="background:#FFF4E6;">
                <div class="card-body text-center">
                    <i data-feather="dollar-sign" class="mb-2 text-warning"></i>
                    <h5 class="fw-bold"><?= $totalLoans ?></h5>
                    <small class="text-muted">Loans</small>
                </div>
            </div>
        </a>
    </div>

    <div class="col-xl-3 col-md-6">
    <a href="student-visa-list.php" class="text-decoration-none">
        <div class="card border-0 shadow-sm h-100" style="background:#EEF9FF;">
            <div class="card-body text-center">
                <i data-feather="book-open" class="mb-2 text-info"></i>
                <h5 class="fw-bold"><?= $totalStudents ?></h5>
                <small class="text-muted">Total Students</small>
            </div>
        </div>
    </a>
</div> 

    <div class="col-xl-3 col-md-6">
    <a href="work-visa-list.php" class="text-decoration-none">
        <div class="card border-0 shadow-sm h-100" style="background:#F0F4FF;">
            <div class="card-body text-center">
                <i data-feather="briefcase" class="mb-2 text-primary"></i>
                <h5 class="fw-bold"><?= $totalWorkVisas ?></h5>
                <small class="text-muted">Work Visa</small>
            </div>
        </div>
    </a>
</div>

</div>


    <h4 class="fw-bold mb-3 mt-5">Core Operations</h4>

                <div class="card border-0 shadow-lg mt-5">
    <div class="card-header bg-white border-bottom py-3">
        <h4 class="mb-0 fw-bold text-dark">
            PR Executive Dashboard
        </h4>
        <small class="text-muted">
            Permanent Residency Module – Real-time Overview
        </small>
    </div>

    <div class="card-body">

    <div class="row g-4 mb-5">

    <?php
   $prCards = [
    ["Total PR Leads", $totalPRLeads, "#EEF4FF", "pr-list.php"],
    ["Eligible Profiles", $eligible, "#E8FFF4", "pr-list.php?filter=eligible"],
    ["Approved Cases", $approved, "#E6FFF4", "pr-list.php?filter=approved"],
    ["Rejected Cases", $rejected, "#FFECEC", "pr-list.php?filter=rejected"],
];
    
    foreach ($prCards as $c): ?>
        <div class="col-xl-3 col-md-6">
    <a href="<?= $c[3] ?>" class="text-decoration-none">
        <div class="card border-0 h-100 shadow-sm"
             style="background:<?= $c[2] ?>; border-radius:18px; cursor:pointer;">
            <div class="card-body">
                <div class="text-muted small"><?= $c[0] ?></div>
                <h2 class="fw-bold mt-2"><?= number_format($c[1]) ?></h2>
            </div>
        </div>
    </a>
</div>
    <?php endforeach; ?>

</div>

<div class="mb-5">

    <h5 class="fw-bold mb-4">PR Application Lifecycle</h5>

    <div class="d-flex align-items-center justify-content-between flex-wrap">

        <?php
        $pipelineStages = [
            ["Documentation", $documentation, "warning"],
            ["Profile Submitted", $submitted, "info"],
            ["Invitation", $invitation, "primary"],
            ["Decision Awaited", 0, "secondary"], // optional if variable exists
            ["Approved", $approved, "success"],
            ["Rejected", $rejected, "danger"],
        ];

        foreach ($pipelineStages as $stage): ?>

            <div class="text-center mb-3" style="min-width:140px;">
                <div class="fw-bold text-<?= $stage[2] ?>" 
                     style="font-size:26px;">
                    <?= $stage[1] ?>
                </div>
                <div class="small text-muted">
                    <?= $stage[0] ?>
                </div>
            </div>

        <?php endforeach; ?>

    </div>

    <hr>

</div>

<div class="row g-4 mb-4">

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100"
             style="background:#F4F9FF; border-radius:18px;">
            <div class="card-body">
                <div class="text-muted small">Total Agreement Value</div>
                <h3 class="fw-bold mt-2">₹<?= number_format($totalAgreement) ?></h3>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100"
             style="background:#E8FFF4; border-radius:18px;">
            <div class="card-body">
                <div class="text-muted small">Total Received</div>
                <h3 class="fw-bold mt-2 text-success">
                    ₹<?= number_format($totalReceived) ?>
                </h3>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100"
             style="background:#FFF4E8; border-radius:18px;">
            <div class="card-body">
                <div class="text-muted small">Balance Pending</div>
                <h3 class="fw-bold mt-2 text-warning">
                    ₹<?= number_format($balanceAmount) ?>
                </h3>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100"
             style="background:#F1F5F9; border-radius:18px;">
            <div class="card-body">
                <div class="text-muted small">Revenue This Month</div>
                <h3 class="fw-bold mt-2 text-primary">
                    ₹<?= number_format($revenueMonth) ?>
                </h3>
            </div>
        </div>
    </div>

</div>

    </div>
</div>

<div class="card shadow-sm border-0 mt-5">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold text-primary">
            Student Visa Pipeline
        </h6>
    </div>

    <div class="card-body">
        <div class="row text-center">

          <?php
$studentStages = [
    ["Applied", $studentPipeline['applied'], "#FFF7ED", "#B45309"],
    ["Offer Received", $studentPipeline['offer'], "#EFF6FF", "#1D4ED8"],
    ["Approved", $studentPipeline['approved'], "#ECFDF5", "#047857"],
    ["Rejected", $studentPipeline['rejected'], "#FEF2F2", "#B91C1C"],
];

foreach ($studentStages as $stage):
?>
<div class="col-md-3">
    <a href="student-visa-list.php?stage=<?= strtolower(str_replace(' ', '-', $stage[0])) ?>"
       class="text-decoration-none">
        <div class="p-4 rounded-4 h-100 hover-shadow"
             style="background:<?= $stage[2] ?>;">
            <div class="small" style="color:<?= $stage[3] ?>;">
                <?= $stage[0] ?>
            </div>
            <div class="fs-1 fw-bold mt-2"
                 style="color:<?= $stage[3] ?>;">
                <?= $stage[1] ?>
            </div>
        </div>
    </a>
</div>
<?php endforeach; ?> 

        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mt-5">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold text-primary">
            Visitor Visa Pipeline
        </h6>
    </div>

    <div class="card-body">
        <div class="row text-center">

<?php
$visitorStages = [
    ["Pending",  $pipeline['pending'],  "#FFF7ED", "#B45309", "pending"],
    ["Approved", $pipeline['approved'], "#ECFDF5", "#047857", "approved"],
    ["Rejected", $pipeline['rejected'], "#FEF2F2", "#B91C1C", "rejected"],
];

foreach ($visitorStages as $stage):
?>
    <div class="col-md-4">
        <a href="visitor-visa-list.php?status=<?= $stage[4] ?>"
           class="text-decoration-none">
            <div class="p-4 rounded-4 h-100 hover-shadow"
                 style="background:<?= $stage[2] ?>;">
                 
                <div class="small fw-bold"
     style="color:<?= $stage[3] ?>; letter-spacing:0.3px;">
                    <?= $stage[0] ?>
                </div>

                <div class="fs-1 fw-bold mt-2"
                     style="color:<?= $stage[3] ?>;">
                    <?= $stage[1] ?>
                </div>

            </div>
        </a>
    </div>
<?php endforeach; ?>

</div>
        
    </div>
</div>

<div class="card shadow-sm border-0 mt-5">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold text-primary">
            Work Visa Pipeline
        </h6>
    </div>

    <div class="card-body">
       <div class="row text-center">

<?php
$workStages = [
    ["Processing", $workPipeline['processing'], "#EFF6FF", "#1D4ED8"],
    ["Approved",   $workPipeline['approved'],   "#ECFDF5", "#047857"],
    ["Rejected",   $workPipeline['rejected'],   "#FEF2F2", "#B91C1C"],
];

foreach ($workStages as $stage):
?>
    <div class="col-md-4">
        <div class="p-4 rounded-4 h-100 hover-shadow"
             style="background:<?= $stage[2] ?>;">

            <div class="small fw-bold"
     style="color:<?= $stage[3] ?>; letter-spacing:0.3px;">
                <?= $stage[0] ?>
            </div>

            <div class="fs-1 fw-bold mt-2"
                 style="color:<?= $stage[3] ?>;">
                <?= $stage[1] ?>
            </div>

        </div>
    </div>
<?php endforeach; ?>

</div>
    </div>
</div>

<div class="card shadow-sm border-0 mt-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold text-primary">
            Loan Performance Overview
        </h6>
    </div>

    <div class="card-body">
        <div class="row g-3">

            <!-- Total -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <a href="loan-list.php" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100"
                         style="background:#E6F4FF;">
                        <div class="card-body text-center">
                            <i data-feather="layers" class="mb-2"></i>
                            <h4 class="fw-bold"><?= $totalLoans ?></h4>
                            <small>Total Loans</small>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Approved -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <a href="loan-list.php?filter=approved" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100"
                         style="background:#E6FFF4;">
                        <div class="card-body text-center">
                            <i data-feather="check-circle" class="mb-2"></i>
                            <h4 class="fw-bold"><?= $approvedLoans ?></h4>
                            <small>Approved</small>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Pending -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <a href="loan-list.php?filter=pending" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100"
                         style="background:#FFF4E6;">
                        <div class="card-body text-center">
                            <i data-feather="clock" class="mb-2"></i>
                            <h4 class="fw-bold"><?= $pendingLoans ?></h4>
                            <small>Pending</small>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Rejected -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <a href="loan-list.php?filter=rejected" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100"
                         style="background:#FFECEC;">
                        <div class="card-body text-center">
                            <i data-feather="x-circle" class="mb-2"></i>
                            <h4 class="fw-bold"><?= $rejectedLoans ?></h4>
                            <small>Rejected</small>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Monthly -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <a href="loan-list.php?filter=month" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100"
                         style="background:#F4E6FF;">
                        <div class="card-body text-center">
                            <i data-feather="calendar" class="mb-2"></i>
                            <h4 class="fw-bold"><?= $monthlyLoans ?></h4>
                            <small>This Month</small>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Today -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <a href="loan-list.php?filter=today" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100"
                         style="background:#E6FBFF;">
                        <div class="card-body text-center">
                            <i data-feather="sun" class="mb-2"></i>
                            <h4 class="fw-bold"><?= $todayLoans ?></h4>
                            <small>Added Today</small>
                        </div>
                    </div>
                </a>
            </div>

        </div>
    </div>
</div>

            <h4 class="fw-bold mb-3 mt-5">Growth & Performance</h4>

                 <div class="card border-0 shadow-sm mt-5">
    <div class="card-header bg-white border-bottom py-3">
        <h5 class="mb-0 fw-bold text-dark">
            Campaign Performance Overview
        </h5>
    </div>

    <div class="card-body">
        <div class="row g-3 mb-4">

<?php
$totalCampaigns = count($campaignData);
$totalLeadsCampaign = array_sum(array_column($campaignData,'total_leads'));
?>

<div class="col-md-3">
    <div class="card border-0" style="background:#F8FAFC;">
        <div class="card-body">
            <div class="text-muted small">Total Campaigns</div>
            <h4 class="fw-bold"><?= $totalCampaigns ?></h4>
        </div>
    </div>
</div>

<div class="col-md-3">
    <div class="card border-0" style="background:#F1F5F9;">
        <div class="card-body">
            <div class="text-muted small">Total Leads Generated</div>
            <h4 class="fw-bold"><?= $totalLeadsCampaign ?></h4>
        </div>
    </div>
</div>

</div>
<div class="mt-5">
    <canvas id="campaignChart" height="120"></canvas>
</div>

<div class="table-responsive">
<table class="table align-middle">
    <thead class="table-light">
        <tr>
            <th>Campaign</th>
            <th class="text-center">Total</th>
            <th class="text-center">Student</th>
            <th class="text-center">Work</th>
            <th class="text-center">Loan</th>
            <th class="text-center">PR</th>
            <th class="text-center">Conversion %</th>
        </tr>
    </thead>
    <tbody>
    

<?php foreach ($campaignData as $row): ?>

<tr>
    <td class="fw-medium"><?= htmlspecialchars($row['campaign_name']) ?></td>

    <td class="text-center fw-bold text-dark">
        <?= $row['total_leads'] ?>
    </td>

    <td class="text-center"><?= $row['student_count'] ?></td>
    <td class="text-center"><?= $row['work_count'] ?></td>
    <td class="text-center"><?= $row['loan_count'] ?></td>
    <td class="text-center"><?= $row['pr_count'] ?></td>

    <td class="text-center">
        <span class="badge rounded-pill px-3"
            style="
            background: <?= $row['conversion'] > 30 ? '#DCFCE7' :
                           ($row['conversion'] > 10 ? '#FEF9C3' : '#FEE2E2') ?>;
            color:#111;
            ">
            <?= $row['conversion'] ?>%
        </span>
    </td>
</tr>

<?php endforeach; ?>

    </tbody>
</table>
</div>
</div> <!-- END card-body -->
</div> <!-- END campaign card -->
                
         <h4 class="fw-bold mb-3 mt-5">Internal Management</h4>



                <!-- ================= TASK CONTROL PANEL ================= -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5>Task Management Control</h5>
        <div>
            <a href="assign-task.php" class="btn btn-primary btn-sm me-2">
                <i data-feather="plus"></i> Assign New Task
            </a>
            <a href="admin-review-tasks.php" class="btn btn-warning btn-sm">
                <i data-feather="clipboard"></i> Review Pending Reports
            </a>
        </div>
    </div>

    <div class="card-body">

        <h6 class="mb-3">Recent Assigned Tasks</h6>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Task Code</th>
                        <th>Employee</th>
                        <th>Title</th>
                        <th>Priority</th>
                        <th>Deadline</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>

                <?php if ($taskList->num_rows > 0): ?>
                    <?php while($task = $taskList->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($task['task_code']) ?></td>
                            <td><?= htmlspecialchars($task['employee_name']) ?></td>
                            <td><?= htmlspecialchars($task['title']) ?></td>
                            <td>
                                <?php
                                    $priorityClass = '';
                                    if ($task['priority'] == 'High') $priorityClass = 'text-danger fw-bold';
                                    elseif ($task['priority'] == 'Medium') $priorityClass = 'text-warning fw-bold';
                                    elseif ($task['priority'] == 'Low') $priorityClass = 'text-success fw-bold';
                                ?>
                                <span class="<?= $priorityClass ?>">
                                    <?= htmlspecialchars($task['priority']) ?>
                                </span>
                            </td>
                            <td><?= date("d M Y h:i A", strtotime($task['deadline'])) ?></td>
                            <td><?= htmlspecialchars($task['status']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No tasks assigned yet</td>
                    </tr>
                <?php endif; ?>

                </tbody>
            </table>
        </div>

    </div>
</div>

<div class="card shadow-sm border-0 mt-4">
    <div class="card-header bg-white border-bottom">
        <h6 class="mb-0 fw-bold text-primary">
            Staff Lead Performance
        </h6>
    </div>

    <div class="card-body">

        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Staff</th>
                    <th class="text-end">Total Leads</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($staffPerformance as $staff): ?>
                    <tr>
                        <td><?= htmlspecialchars($staff['name']) ?></td>
                        <td class="text-end fw-bold">
                            <?= $staff['total_leads'] ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>
</div>

            </div>
        </div>

        <?php include "footer.php"; ?>

    </div>
</div>

<!-- SCRIPTS (UNCHANGED) -->
<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="assets/js/icons/feather-icon/feather.min.js"></script>
<script src="assets/js/icons/feather-icon/feather-icon.js"></script>
<script src="assets/js/scrollbar/simplebar.js"></script>
<script src="assets/js/scrollbar/custom.js"></script>
<script src="assets/js/config.js"></script>
<script src="assets/js/sidebar-menu.js"></script>
<script src="assets/js/sidebar-pin.js"></script>
<script src="assets/js/slick/slick.min.js"></script>
<script src="assets/js/slick/slick.js"></script>
<script src="assets/js/header-slick.js"></script>
<script src="assets/js/dashboard/dashboard_3.js"></script>
<script src="assets/js/script.js"></script>
<script src="assets/js/theme-customizer/customizer.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php if (!empty($campaignData)): ?>
<script>
const ctx = document.getElementById('campaignChart');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($campaignData,'campaign_name')) ?>,
        datasets: [{
            label: 'Total Leads',
            data: <?= json_encode(array_column($campaignData,'total_leads')) ?>,
            backgroundColor: '#CBD5E1'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color:'#E2E8F0' }
            },
            x: {
                grid: { display:false }
            }
        }
    }
});
</script>
<?php endif; ?>

</body>
</html>
