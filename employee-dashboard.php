
<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

if ($_SESSION['role'] !== 'employee') {
    http_response_code(403);
    echo "Access denied";
    exit;
}

?>
<?php
include "db.php";

$employeeId = $_SESSION['user_id'];

/* FETCH UNSEEN TASK REVIEWS */
$notifyStmt = $conn->prepare("
    SELECT tr.id, tr.review_status, tr.rejection_reason, t.title
    FROM task_reports tr
    JOIN tasks t ON tr.task_id = t.id
    WHERE t.assigned_to = ?
    AND tr.review_status IN ('Approved','Rejected')
    AND tr.employee_seen = 0
    AND t.status IN ('Completed','Rejected')
    ORDER BY tr.reviewed_at DESC
");


$notifyStmt->bind_param("i", $employeeId);
$notifyStmt->execute();
$notifyResult = $notifyStmt->get_result();


/* TOTAL LEADS (ALL TIME – THIS EMPLOYEE) */
$totalLeads = 0;
$res = $conn->prepare("SELECT COUNT(*) FROM leads WHERE assigned_to = ?");
$res->bind_param("i", $employeeId);
$res->execute();
$res->bind_result($totalLeads);
$res->fetch();
$res->close();

/* NEW LEADS (THIS MONTH – THIS EMPLOYEE) */
$newLeads = 0;
$res = $conn->prepare("
    SELECT COUNT(*) 
    FROM leads 
    WHERE assigned_to = ?
    AND MONTH(created_at) = MONTH(CURRENT_DATE())
    AND YEAR(created_at) = YEAR(CURRENT_DATE())
");
$res->bind_param("i", $employeeId);
$res->execute();
$res->bind_result($newLeads);
$res->fetch();
$res->close();

/* TOTAL NEW LEADS THIS MONTH (ALL EMPLOYEES) */
$totalNewLeadsMonth = 0;

$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM leads 
    WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
    AND YEAR(created_at) = YEAR(CURRENT_DATE())
");

$stmt->execute();
$stmt->bind_result($totalNewLeadsMonth);
$stmt->fetch();
$stmt->close();


$approvedVisas = 0;

$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM visas v
    JOIN leads l ON v.lead_id = l.id
    WHERE l.assigned_to = ?
    AND v.status = 'Approved'
");

$stmt->bind_param("i", $employeeId);
$stmt->execute();
$stmt->bind_result($approvedVisas);
$stmt->fetch();
$stmt->close();




/* OFFERS RECEIVED (THIS EMPLOYEE) */
$offersReceived = 0;

$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM visas v
    JOIN leads l ON v.lead_id = l.id
    WHERE l.assigned_to = ?
    AND v.status = 'Offer Received'
");

$stmt->bind_param("i", $employeeId);
$stmt->execute();
$stmt->bind_result($offersReceived);
$stmt->fetch();
$stmt->close();

/* ================= TASK COUNTS ================= */

$totalTasks = 0;
$inProgress = 0;
$waitingApproval = 0;
$rejectedTasks = 0;
$completedTasks = 0;

/* TOTAL TASKS */
$stmt = $conn->prepare("
    SELECT COUNT(*) FROM tasks WHERE assigned_to = ?
");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$stmt->bind_result($totalTasks);
$stmt->fetch();
$stmt->close();

/* IN PROGRESS */
$stmt = $conn->prepare("
    SELECT COUNT(*) FROM tasks 
    WHERE assigned_to = ? AND status = 'In Progress'
");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$stmt->bind_result($inProgress);
$stmt->fetch();
$stmt->close();

/* WAITING APPROVAL */
$stmt = $conn->prepare("
    SELECT COUNT(*) FROM tasks 
    WHERE assigned_to = ? AND status = 'Waiting Approval'
");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$stmt->bind_result($waitingApproval);
$stmt->fetch();
$stmt->close();

/* REJECTED */
$stmt = $conn->prepare("
    SELECT COUNT(*) FROM tasks 
    WHERE assigned_to = ? AND status = 'Rejected'
");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$stmt->bind_result($rejectedTasks);
$stmt->fetch();
$stmt->close();

/* COMPLETED */
$stmt = $conn->prepare("
    SELECT COUNT(*) FROM tasks 
    WHERE assigned_to = ? AND status = 'Completed'
");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$stmt->bind_result($completedTasks);
$stmt->fetch();
$stmt->close();

/* ================= UPCOMING / PENDING TASKS ================= */

$todayTasks = [];
$overdueTasks = [];

$stmt = $conn->prepare("
    SELECT id, task_code, title, deadline, status
    FROM tasks
    WHERE assigned_to = ?
    AND DATE(deadline) = CURDATE()
    ORDER BY deadline ASC
");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$todayTasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* OVERDUE TASKS */
$stmt = $conn->prepare("
    SELECT id, task_code, title, deadline, status
    FROM tasks
    WHERE assigned_to = ?
    AND deadline < NOW()
    AND status NOT IN ('Completed')
    ORDER BY deadline ASC
");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$overdueTasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* ================= RECENT TASKS ================= */

$stmt = $conn->prepare("
    SELECT task_code, title, status, deadline
    FROM tasks
    WHERE assigned_to = ?
    ORDER BY created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$recentTasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$user_id = $_SESSION['user_id'];

/* ================= EMPLOYEE LOAN DASHBOARD ================= */

$totalLoans = $conn->query("
    SELECT COUNT(*) as total 
    FROM loans ln
    JOIN leads l ON l.id = ln.lead_id
    WHERE l.assigned_to = $user_id
")->fetch_assoc()['total'];

$approvedLoans = $conn->query("
    SELECT COUNT(*) as total 
    FROM loans ln
    JOIN leads l ON l.id = ln.lead_id
    WHERE l.assigned_to = $user_id
    AND LOWER(ln.loan_status) LIKE '%approved%'
")->fetch_assoc()['total'];

$rejectedLoans = $conn->query("
    SELECT COUNT(*) as total 
    FROM loans ln
    JOIN leads l ON l.id = ln.lead_id
    WHERE l.assigned_to = $user_id
    AND LOWER(ln.loan_status) LIKE '%rejected%'
")->fetch_assoc()['total'];

$pendingLoans = $conn->query("
    SELECT COUNT(*) as total 
    FROM loans ln
    JOIN leads l ON l.id = ln.lead_id
    WHERE l.assigned_to = $user_id
    AND LOWER(ln.loan_status) NOT LIKE '%approved%'
    AND LOWER(ln.loan_status) NOT LIKE '%rejected%'
")->fetch_assoc()['total'];

$monthlyLoans = $conn->query("
    SELECT COUNT(*) as total
    FROM loans ln
    JOIN leads l ON l.id = ln.lead_id
    WHERE l.assigned_to = $user_id
    AND MONTH(ln.created_at)=MONTH(CURDATE())
    AND YEAR(ln.created_at)=YEAR(CURDATE())
")->fetch_assoc()['total'];

$todayLoans = $conn->query("
    SELECT COUNT(*) as total
    FROM loans ln
    JOIN leads l ON l.id = ln.lead_id
    WHERE l.assigned_to = $user_id
    AND DATE(ln.created_at)=CURDATE()
")->fetch_assoc()['total'];

/* ================= STUDENT PIPELINE (EMPLOYEE) ================= */

$studentPipeline = [
    'applied'  => 0,
    'offer'    => 0,
    'approved' => 0,
    'rejected' => 0
];

$employeeId = $_SESSION['user_id'];

/* 1️⃣ TOTAL APPLIED */
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM visas v
    JOIN leads l ON v.lead_id = l.id
    WHERE v.visa_type = 'student'
    AND l.assigned_to = ?
");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$stmt->bind_result($studentPipeline['applied']);
$stmt->fetch();
$stmt->close();

/* 2️⃣ OFFER RECEIVED */
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM student_visa_details svd
    JOIN visas v ON svd.visa_id = v.id
    JOIN leads l ON v.lead_id = l.id
    WHERE v.visa_type = 'student'
    AND l.assigned_to = ?
    AND svd.offer_letter_status IN ('Conditional','Unconditional')
");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$stmt->bind_result($studentPipeline['offer']);
$stmt->fetch();
$stmt->close();

/* 3️⃣ VISA APPROVED */
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM visas v
    JOIN leads l ON v.lead_id = l.id
    WHERE v.visa_type = 'student'
    AND l.assigned_to = ?
    AND v.visa_status = 'Approved'
");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$stmt->bind_result($studentPipeline['approved']);
$stmt->fetch();
$stmt->close();

/* 4️⃣ VISA REJECTED */
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM visas v
    JOIN leads l ON v.lead_id = l.id
    WHERE v.visa_type = 'student'
    AND l.assigned_to = ?
    AND v.visa_status = 'Rejected'
");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$stmt->bind_result($studentPipeline['rejected']);
$stmt->fetch();
$stmt->close();

//CAMPAIGN LEADS....

$campaignData = [];

$stmt = $conn->prepare("
SELECT 
    c.id,
    c.campaign_name,
    COUNT(l.id) AS total_leads,

    SUM(CASE WHEN l.lead_type='Student' THEN 1 ELSE 0 END) AS student_count,
    SUM(CASE WHEN l.lead_type='Work Visa' THEN 1 ELSE 0 END) AS work_count,
    SUM(CASE WHEN l.lead_type='Loan' THEN 1 ELSE 0 END) AS loan_count,
    SUM(CASE WHEN l.lead_type='PR' THEN 1 ELSE 0 END) AS pr_count

FROM campaigns c
LEFT JOIN leads l 
    ON l.campaign_id = c.id
    AND l.assigned_to = ?
GROUP BY c.id
ORDER BY total_leads DESC
");

$stmt->bind_param("i", $employeeId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    $converted = $row['student_count'] 
               + $row['work_count'] 
               + $row['loan_count'] 
               + $row['pr_count'];

    $conversion = $row['total_leads'] > 0 
        ? round(($converted / $row['total_leads']) * 100, 1)
        : 0;

    $row['conversion'] = $conversion;

    $campaignData[] = $row;


    $totalCampaignLeads = 0;
$totalConverted = 0;

foreach ($campaignData as $c) {
    $totalCampaignLeads += $c['total_leads'];

    $totalConverted += 
        $c['student_count'] +
        $c['work_count'] +
        $c['loan_count'] +
        $c['pr_count'];
}

$conversionRate = $totalCampaignLeads > 0
    ? round(($totalConverted / $totalCampaignLeads) * 100, 1)
    : 0;
}

// Visitor Pipeline Counts (Employee Only)


$pipelineQuery = $conn->prepare("
    SELECT v.status, COUNT(*) as total
    FROM visas v
    JOIN leads l ON l.id = v.lead_id
    WHERE v.visa_type = 'visitor'
    AND l.assigned_to = ?
    GROUP BY v.status
");

$pipelineQuery->bind_param("i", $employeeId);
$pipelineQuery->execute();
$result = $pipelineQuery->get_result();

$pipeline = [
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0
];

while ($row = $result->fetch_assoc()) {
    $pipeline[$row['status']] = $row['total'];
}

$workPipeline = [
    'processing' => 0,
    'approved'   => 0,
    'rejected'   => 0
];

$stmt = $conn->prepare("
    SELECT v.status
    FROM visas v
    JOIN leads l ON l.id = v.lead_id
    WHERE v.visa_type = 'work'
    AND l.assigned_to = ?
");

$stmt->bind_param("i", $employeeId);
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

/* ================= PR CARDS & PIPELINE ================= */

$totalPRLeads = $conn->query("
    SELECT COUNT(*) as total
    FROM leads
    WHERE lead_type='PR'
    AND assigned_to = $employeeId
")->fetch_assoc()['total'];

$prEnquiries = $conn->query("
    SELECT COUNT(*) as total
    FROM pr_enquiries e
    JOIN leads l ON e.lead_id = l.id
    WHERE l.assigned_to = $employeeId
")->fetch_assoc()['total'];

$eligible = $conn->query("
    SELECT COUNT(*) as total
    FROM pr_enquiries e
    JOIN leads l ON e.lead_id = l.id
    WHERE e.eligibility_result='Eligible'
    AND l.assigned_to = $employeeId
")->fetch_assoc()['total'];

$notEligible = $conn->query("
    SELECT COUNT(*) as total
    FROM pr_enquiries e
    JOIN leads l ON e.lead_id = l.id
    WHERE e.eligibility_result='Not Eligible'
    AND l.assigned_to = $employeeId
")->fetch_assoc()['total'];

$documentation = $conn->query("
    SELECT COUNT(*) as total
    FROM pr_case_process p
    JOIN pr_enquiries e ON p.case_id = e.id
    JOIN leads l ON e.lead_id = l.id
    WHERE p.current_stage IN ('Documents Pending','Documents Verified')
    AND l.assigned_to = $employeeId
")->fetch_assoc()['total'];

$submitted = $conn->query("
    SELECT COUNT(*) as total
    FROM pr_case_process p
    JOIN pr_enquiries e ON p.case_id = e.id
    JOIN leads l ON e.lead_id = l.id
    WHERE p.current_stage IN ('Profile Submitted','PR Application Filed')
    AND l.assigned_to = $employeeId
")->fetch_assoc()['total'];

$invitation = $conn->query("
    SELECT COUNT(*) as total
    FROM pr_case_process p
    JOIN pr_enquiries e ON p.case_id = e.id
    JOIN leads l ON e.lead_id = l.id
    WHERE p.current_stage='Invitation Received'
    AND l.assigned_to = $employeeId
")->fetch_assoc()['total'];

$approved = $conn->query("
    SELECT COUNT(*) as total
    FROM pr_case_process p
    JOIN pr_enquiries e ON p.case_id = e.id
    JOIN leads l ON e.lead_id = l.id
    WHERE p.decision_status='Approved'
    AND l.assigned_to = $employeeId
")->fetch_assoc()['total'];

$totalReceived = $conn->query("
    SELECT IFNULL(SUM(pay.amount),0) as total
    FROM pr_payments pay
    JOIN leads l ON pay.lead_id = l.id
    WHERE l.assigned_to = $employeeId
")->fetch_assoc()['total'];

$totalAgreement = $conn->query("
    SELECT IFNULL(SUM(e.total_fee),0) as total
    FROM pr_enquiries e
    JOIN pr_case_details d ON d.lead_id = e.lead_id
    JOIN leads l ON e.lead_id = l.id
    WHERE d.agreement_signed = 'Yes'
    AND l.assigned_to = $employeeId
")->fetch_assoc()['total'];

$balanceAmount = $totalAgreement - $totalReceived;


/* ================= CALL & FOLLOW-UP ================= */

$todayFollowups = 0;
$overdueFollowups = 0;
$totalCalls = 0;

/* TODAY FOLLOW-UPS */
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM call_logs cl
    JOIN leads l ON cl.lead_id = l.id
    WHERE l.assigned_to = ?
    AND DATE(cl.next_followup_date) = CURDATE()
");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$stmt->bind_result($todayFollowups);
$stmt->fetch();
$stmt->close();

/* OVERDUE FOLLOW-UPS */
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM call_logs cl
    JOIN leads l ON cl.lead_id = l.id
    WHERE l.assigned_to = ?
    AND cl.next_followup_date < CURDATE()
    AND cl.next_followup_date IS NOT NULL
");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$stmt->bind_result($overdueFollowups);
$stmt->fetch();
$stmt->close();

/* TOTAL CALLS LOGGED */
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM call_logs cl
    JOIN leads l ON cl.lead_id = l.id
    WHERE l.assigned_to = ?
");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$stmt->bind_result($totalCalls);
$stmt->fetch();
$stmt->close();

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
    <title>Employee Dashboard</title>

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
    <style>

/* Base KPI Card */
.kpi-card {
    border-radius: 12px;
    background: #ffffff;
    transition: all 0.25s ease;
    border-left: 4px solid #1E3A8A; /* default blue */
}

.kpi-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 22px rgba(0,0,0,0.06);
}

/* Icons */
.kpi-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    background: #F3F4F6;
    display: flex;
    align-items: center;
    justify-content: center;
}

.kpi-icon i {
    width: 18px;
    height: 18px;
    color: #374151;
}

/* Status Variants */

.kpi-blue    { border-left-color: #1E3A8A; }
.kpi-green   { border-left-color: #15803D; }
.kpi-orange  { border-left-color: #D97706; }
.kpi-red     { border-left-color: #DC2626; }

.pr-stage {
    padding: 18px 10px;
    border-radius: 12px;
    transition: all 0.25s ease;
    box-shadow: 0 6px 18px rgba(0,0,0,0.05);
}

.pr-stage:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 22px rgba(0,0,0,0.08);
}

.pr-stage h3 {
    font-size: 24px;
}

.pr-warning { background: #FFF4E6; }
.pr-info { background: #E6F4FF; }
.pr-primary { background: #EDE9FE; }
.pr-success { background: #E9FBEF; }
.pr-danger { background: #FFECEC; }

.pipeline-card {
    padding: 20px 10px;
    border-radius: 14px;
    transition: all 0.25s ease;
    box-shadow: 0 6px 18px rgba(0,0,0,0.05);
}

.pipeline-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 22px rgba(0,0,0,0.08);
}

.pipeline-card h3 {
    font-size: 26px;
    font-weight: 700;
    margin-bottom: 4px;
}

/* Soft Colors */
.bg-warning-soft { background: #FFF4E6; }
.bg-info-soft { background: #E6F4FF; }
.bg-success-soft { background: #E9FBEF; }
.bg-danger-soft { background: #FFECEC; }
</style>
</head>

<body>

<!--<div class="loader-wrapper">
    <div class="loader"><div class="loader4"></div></div>
</div>-->

<div class="tap-top"><i data-feather="chevrons-up"></i></div>

<div class="page-wrapper compact-wrapper" id="pageWrapper">

    <?php include "header.php"; ?>

    <div class="page-body-wrapper">

        <?php include "sidebar.php"; ?>

        <div class="page-body">
            <div class="container-fluid">

            <div class="row g-3 mb-4">

    <!-- Today Follow-ups -->
    <div class="col-xl-4 col-md-6">
        <a href="call-list.php?filter=today" class="text-decoration-none">
            <div class="card shadow-sm border-0 h-100" style="background:#EAF4FF;">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">Today Follow-ups</small>
                        <h3 class="fw-bold text-primary mb-0">
                            <?= $todayFollowups ?>
                        </h3>
                    </div>
                    <div class="bg-white rounded-circle p-3 shadow-sm">
                        <i data-feather="phone-call" class="text-primary"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Overdue -->
    <div class="col-xl-4 col-md-6">
        <a href="call-list.php?filter=overdue" class="text-decoration-none">
            <div class="card shadow-sm border-0 h-100" style="background:#FFF3E8;">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">Overdue Follow-ups</small>
                        <h3 class="fw-bold text-warning mb-0">
                            <?= $overdueFollowups ?>
                        </h3>
                    </div>
                    <div class="bg-white rounded-circle p-3 shadow-sm">
                        <i data-feather="alert-triangle" class="text-warning"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Total Calls -->
    <div class="col-xl-4 col-md-6">
        <a href="call-list.php" class="text-decoration-none">
            <div class="card shadow-sm border-0 h-100" style="background:#E9FBEF;">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">Total Calls Logged</small>
                        <h3 class="fw-bold text-success mb-0">
                            <?= $totalCalls ?>
                        </h3>
                    </div>
                    <div class="bg-white rounded-circle p-3 shadow-sm">
                        <i data-feather="phone" class="text-success"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

</div>

<!-- ================= KPI CARDS ================= -->
<div class="row g-3 mb-4">

    <!-- Total Leads -->
    <div class="col-xl-2 col-md-4">
        <div class="card shadow-sm border-0 h-100 kpi-card kpi-blue">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold mb-0"><?= $totalLeads ?></h3>
                    <small class="text-muted">My Total Leads</small>
                </div>
                <div class="kpi-icon">
                    <i data-feather="users"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- New Leads -->
    <div class="col-xl-2 col-md-4">
        <div class="card shadow-sm border-0 h-100 kpi-card kpi-blue">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold mb-0"><?= $newLeads ?></h3>
                    <small class="text-muted">New This Month</small>
                </div>
                <div class="kpi-icon">
                    <i data-feather="user-plus"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Offers Received -->
    <div class="col-xl-2 col-md-4">
        <div class="card shadow-sm border-0 h-100 kpi-card kpi-orange">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold mb-0"><?= $offersReceived ?></h3>
                    <small class="text-muted">Offers Received</small>
                </div>
                <div class="kpi-icon">
                    <i data-feather="file-text"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Visas Approved -->
    <div class="col-xl-2 col-md-4">
        <div class="card shadow-sm border-0 h-100 kpi-card kpi-green">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold mb-0"><?= $approvedVisas ?></h3>
                    <small class="text-muted">Visas Approved</small>
                </div>
                <div class="kpi-icon">
                    <i data-feather="check-circle"></i>
                </div>
            </div>
        </div>
    </div>

</div>


<!-- ================= TASK KPI CARDS ================= -->
<div class="row g-3 mb-4">

    <!-- Total Tasks -->
    <div class="col-xl-2 col-md-4">
        <div class="card shadow-sm border-0 h-100 kpi-card kpi-blue">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold mb-0"><?= $totalTasks ?></h3>
                    <small class="text-muted">Total Tasks</small>
                </div>
                <div class="kpi-icon">
                    <i data-feather="clipboard"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- In Progress -->
    <div class="col-xl-2 col-md-4">
        <div class="card shadow-sm border-0 h-100 kpi-card kpi-orange">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold mb-0"><?= $inProgress ?></h3>
                    <small class="text-muted">In Progress</small>
                </div>
                <div class="kpi-icon">
                    <i data-feather="loader"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Waiting Approval -->
    <div class="col-xl-2 col-md-4">
        <div class="card shadow-sm border-0 h-100 kpi-card kpi-blue">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold mb-0"><?= $waitingApproval ?></h3>
                    <small class="text-muted">Waiting Approval</small>
                </div>
                <div class="kpi-icon">
                    <i data-feather="clock"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Rejected -->
    <div class="col-xl-2 col-md-4">
        <div class="card shadow-sm border-0 h-100 kpi-card kpi-red">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold mb-0"><?= $rejectedTasks ?></h3>
                    <small class="text-muted">Rejected</small>
                </div>
                <div class="kpi-icon">
                    <i data-feather="x-circle"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Completed -->
    <div class="col-xl-2 col-md-4">
        <div class="card shadow-sm border-0 h-100 kpi-card kpi-green">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold mb-0"><?= $completedTasks ?></h3>
                    <small class="text-muted">Completed</small>
                </div>
                <div class="kpi-icon">
                    <i data-feather="check-square"></i>
                </div>
            </div>
        </div>
    </div>

</div>             



<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
  <div class="card shadow-sm">
    <div class="card-body">
      <h6>My Campaign Leads</h6>
      <h3><?= $totalCampaignLeads ?></h3>
    </div>
  </div>
</div>

<div class="col-xl-3 col-md-6">
  <div class="card shadow-sm">
    <div class="card-body">
      <h6>My Converted Leads</h6>
      <h3><?= $totalConverted ?></h3>
    </div>
  </div>
</div>

<div class="col-xl-3 col-md-6">
  <div class="card shadow-sm">
    <div class="card-body">
      <h6>Conversion Rate</h6>
      <h3><?= $conversionRate ?>%</h3>
    </div>
  </div>
</div>
</div>


<div class="row g-3 mb-4">

<!-- My PR Leads -->
<div class="col-xl-3 col-md-6">
    <a href="pr-list.php" class="text-decoration-none">
        <div class="card shadow-sm border-0 h-100 kpi-card kpi-blue">
            <div class="card-body text-center">
                <i data-feather="users" class="mb-2 text-primary"></i>
                <h3 class="fw-bold"><?= $totalPRLeads ?></h3>
                <small class="text-muted">My PR Leads</small>
            </div>
        </div>
    </a>
</div>

<!-- Approved PR Cases -->
<div class="col-xl-3 col-md-6">
    <a href="pr-list.php?filter=approved" class="text-decoration-none">
        <div class="card shadow-sm border-0 h-100 kpi-card kpi-green">
            <div class="card-body text-center">
                <i data-feather="check-circle" class="mb-2 text-success"></i>
                <h3 class="fw-bold"><?= $approved ?></h3>
                <small class="text-muted">Approved Cases</small>
            </div>
        </div>
    </a>
</div>

<div class="col-xl-3 col-md-6">
<div class="card shadow-sm border-0 h-100">
<div class="card-body text-center">
<i data-feather="dollar-sign" class="mb-2 text-success"></i>
<h3 class="fw-bold">₹<?= number_format($totalReceived) ?></h3>
<small class="text-muted">Revenue Collected</small>
</div>
</div>
</div>

<div class="col-xl-3 col-md-6">
<div class="card shadow-sm border-0 h-100">
<div class="card-body text-center">
<i data-feather="alert-circle" class="mb-2 text-danger"></i>
<h3 class="fw-bold">₹<?= number_format($balanceAmount) ?></h3>
<small class="text-muted">Pending Amount</small>
</div>
</div>
</div>

</div>

<div class="card shadow-sm border-0 mt-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold text-primary">
            My PR Pipeline
        </h6>
    </div>

    <div class="card-body">
        <div class="row g-3 text-center">

            <!-- Documentation -->
            <div class="col">
                <div class="pr-stage pr-warning">
                    <h3 class="fw-bold mb-1"><?= $documentation ?></h3>
                    <small>Documentation</small>
                </div>
            </div>

            <!-- Filed -->
            <div class="col">
                <div class="pr-stage pr-info">
                    <h3 class="fw-bold mb-1"><?= $submitted ?></h3>
                    <small>Filed</small>
                </div>
            </div>

            <!-- Invitation -->
            <div class="col">
                <div class="pr-stage pr-primary">
                    <h3 class="fw-bold mb-1"><?= $invitation ?></h3>
                    <small>Invitation</small>
                </div>
            </div>

            <!-- Approved -->
            <div class="col">
                <div class="pr-stage pr-success">
                    <h3 class="fw-bold mb-1"><?= $approved ?></h3>
                    <small>Approved</small>
                </div>
            </div>

            <!-- Not Eligible -->
            <div class="col">
                <div class="pr-stage pr-danger">
                    <h3 class="fw-bold mb-1"><?= $notEligible ?></h3>
                    <small>Not Eligible</small>
                </div>
            </div>

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

<div class="card shadow-sm border-0 mt-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold text-primary">
            My Student Visa Pipeline
        </h6>
    </div>

    <div class="card-body">

    <?php if (array_sum($studentPipeline) == 0): ?>
        <div class="text-center text-muted py-4">
            No student cases found.
        </div>
    <?php else: ?>

        <div class="row g-3 text-center">

            <div class="col">
                <a href="student-visa-list.php?stage=applied" class="text-decoration-none">
                    <div class="pipeline-card bg-warning-soft">
                        <h3><?= $studentPipeline['applied'] ?></h3>
                        <small>Applied</small>
                    </div>
                </a>
            </div>

            <div class="col">
                <a href="student-visa-list.php?stage=offer" class="text-decoration-none">
                    <div class="pipeline-card bg-info-soft">
                        <h3><?= $studentPipeline['offer'] ?></h3>
                        <small>Offer Received</small>
                    </div>
                </a>
            </div>

            <div class="col">
                <a href="student-visa-list.php?stage=approved" class="text-decoration-none">
                    <div class="pipeline-card bg-success-soft">
                        <h3><?= $studentPipeline['approved'] ?></h3>
                        <small>Approved</small>
                    </div>
                </a>
            </div>

            <div class="col">
                <a href="student-visa-list.php?stage=rejected" class="text-decoration-none">
                    <div class="pipeline-card bg-danger-soft">
                        <h3><?= $studentPipeline['rejected'] ?></h3>
                        <small>Rejected</small>
                    </div>
                </a>
            </div>

        </div>

    <?php endif; ?>

    </div>
</div>

<div class="card shadow-sm border-0 mt-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold text-primary">
           My Visitor Visa Pipeline
        </h6>
    </div>

    <div class="card-body">

    <?php if (array_sum($pipeline) == 0): ?>
        <div class="text-center text-muted py-4">
            No visitor cases found.
        </div>
    <?php else: ?>

        <div class="row g-3 text-center">

            <div class="col">
                <a href="visitor-visa-list.php?status=pending" class="text-decoration-none">
                    <div class="pipeline-card bg-warning-soft">
                        <h3><?= $pipeline['pending'] ?></h3>
                        <small>Pending</small>
                    </div>
                </a>
            </div>

            <div class="col">
                <a href="visitor-visa-list.php?status=approved" class="text-decoration-none">
                    <div class="pipeline-card bg-success-soft">
                        <h3><?= $pipeline['approved'] ?></h3>
                        <small>Approved</small>
                    </div>
                </a>
            </div>

            <div class="col">
                <a href="visitor-visa-list.php?status=rejected" class="text-decoration-none">
                    <div class="pipeline-card bg-danger-soft">
                        <h3><?= $pipeline['rejected'] ?></h3>
                        <small>Rejected</small>
                    </div>
                </a>
            </div>

        </div>

    <?php endif; ?>

    </div>
</div>

                
<div class="card shadow-sm border-0 mt-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold text-primary">
            My Work Visa Pipeline
        </h6>
    </div>

    <div class="card-body">

        <div class="row g-3 text-center">

            <div class="col">
                <div class="pipeline-card bg-warning-soft">
                    <h3><?= $workPipeline['processing'] ?></h3>
                    <small>Processing</small>
                </div>
            </div>

            <div class="col">
                <div class="pipeline-card bg-success-soft">
                    <h3><?= $workPipeline['approved'] ?></h3>
                    <small>Approved</small>
                </div>
            </div>

            <div class="col">
                <div class="pipeline-card bg-danger-soft">
                    <h3><?= $workPipeline['rejected'] ?></h3>
                    <small>Rejected</small>
                </div>
            </div>

        </div>

    </div>
</div>
             


                <!-- ================= MAIN GRID ================= -->
                <div class="row">

                    <!-- LEFT -->
                    <div class="col-xl-8">

                        <div class="card mb-4">
    <div class="card-header">
        <h5>Follow-ups & Tasks</h5>
    </div>
    <div class="card-body">

        <?php if (!empty($todayTasks)): ?>
            <h6 class="text-primary">📅 Due Today</h6>
            <ul class="list-unstyled mb-3">
                <?php foreach ($todayTasks as $task): ?>
                    <li>
                        🔹 <?= htmlspecialchars($task['task_code']) ?> – 
                        <?= htmlspecialchars($task['title']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if (!empty($overdueTasks)): ?>
            <h6 class="text-danger">⚠ Overdue</h6>
            <ul class="list-unstyled">
                <?php foreach ($overdueTasks as $task): ?>
                    <li class="text-danger">
                        🔸 <?= htmlspecialchars($task['task_code']) ?> – 
                        <?= htmlspecialchars($task['title']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if (empty($todayTasks) && empty($overdueTasks)): ?>
            <p class="text-muted">No urgent tasks 🎉</p>
        <?php endif; ?>

    </div>
</div>


                       

                    


                        <div class="card mb-4">
    <div class="card-header">
        <h5>Recent Tasks</h5>
    </div>
    <div class="card-body">

        <?php if (!empty($recentTasks)): ?>
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Status</th>
                        <th>Deadline</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recentTasks as $task): ?>
                    <tr>
                        <td><?= htmlspecialchars($task['task_code']) ?></td>
                        <td><?= htmlspecialchars($task['status']) ?></td>
                        <td><?= date("d M Y", strtotime($task['deadline'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted">No recent tasks</p>
        <?php endif; ?>

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

            </div>
        </div>

        <?php include "footer.php"; ?>

    </div>
</div>

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

<script src="assets/js/script.js"></script>

<?php if($notifyResult->num_rows > 0): ?>

<div class="modal fade" id="taskDecisionModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Task Review Update</h5>
      </div>

      <div class="modal-body">

        <?php while($n = $notifyResult->fetch_assoc()): ?>

            <p>
                <strong><?= htmlspecialchars($n['title']) ?></strong><br>
                Status: 
                <span class="<?= $n['review_status']=='Approved' ? 'text-success' : 'text-danger' ?>">
                    <?= $n['review_status'] ?>
                </span>
            </p>

            <?php if($n['review_status'] == 'Rejected'): ?>
                <div class="alert alert-danger">
                    Reason: <?= htmlspecialchars($n['rejection_reason']) ?>
                </div>
            <?php endif; ?>

            <hr>

        <?php endwhile; ?>

      </div>

      <div class="modal-footer">
        <button class="btn btn-primary" data-bs-dismiss="modal">
            Okay
        </button>
      </div>

    </div>
  </div>
</div>

<script>
window.onload = function() {
    var myModal = new bootstrap.Modal(document.getElementById('taskDecisionModal'));
    myModal.show();
}
</script>

<?php endif; ?>

<?php
$shownReportIds = [];

if ($notifyResult->num_rows > 0) {

    $notifyResult->data_seek(0); // reset pointer

    while($row = $notifyResult->fetch_assoc()){
        $shownReportIds[] = $row['id'];
    }

    if (!empty($shownReportIds)) {

        $ids = implode(",", array_map("intval", $shownReportIds));

        $conn->query("
            UPDATE task_reports 
            SET employee_seen = 1
            WHERE id IN ($ids)
        ");
    }
}
?>




</body>
</html>
