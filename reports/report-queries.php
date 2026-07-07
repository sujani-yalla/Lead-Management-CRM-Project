<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../db.php"; // adjust path if needed

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    die("Unauthorized access");
}

$role   = $_SESSION['role'];
$userId = $_SESSION['user_id'];
function getRoleCondition($alias = 'l') {
    global $role, $userId;

    if ($role === 'employee') {
        return " AND {$alias}.assigned_to = {$userId} ";
    }

    return "";
}

function getDashboardSummary() {
    global $conn, $role, $userId;

    $summary = [
        'total_leads' => 0,
        'total_visas' => 0,
        'pr_revenue' => 0,
        'loan_disbursed' => 0,
        'calls_this_month' => 0,
        'overdue_followups' => 0

    ];

    // 1️⃣ TOTAL LEADS
    $roleCondition = getRoleCondition('l');

    $sql = "
        SELECT COUNT(l.id) AS total
        FROM leads l
        WHERE 1=1
        $roleCondition
    ";

    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $summary['total_leads'] = $row['total'] ?? 0;
    }

    // 2️⃣ TOTAL VISAS
    $visaRoleCondition = "";

    if ($role === 'employee') {
        $visaRoleCondition = " AND v.assigned_to = {$userId} ";
    }

    $sql = "
        SELECT COUNT(v.id) AS total
        FROM visas v
        WHERE 1=1
        $visaRoleCondition
    ";

    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $summary['total_visas'] = $row['total'] ?? 0;
    }

    // 3️⃣ PR COLLECTED REVENUE
    $prRoleCondition = "";

    if ($role === 'employee') {
        $prRoleCondition = " AND l.assigned_to = {$userId} ";
    }

    $sql = "
        SELECT IFNULL(SUM(p.amount), 0) AS total
        FROM pr_payments p
        INNER JOIN leads l ON p.lead_id = l.id
        WHERE 1=1
        $prRoleCondition
    ";

    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $summary['pr_revenue'] = $row['total'] ?? 0;
    }

    // 4️⃣ LOAN DISBURSED AMOUNT

$loanRoleCondition = "";

if ($role === 'employee') {
    $loanRoleCondition = " AND l.assigned_to = {$userId} ";
}

$sql = "
    SELECT IFNULL(SUM(lo.loan_disbursement_amount), 0) AS total
    FROM loans lo
    INNER JOIN leads l ON lo.lead_id = l.id
    WHERE lo.loan_disbursement_amount IS NOT NULL
    $loanRoleCondition
";

$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $summary['loan_disbursed'] = $row['total'] ?? 0;
}

// 5️⃣ CALLS THIS MONTH

$callRoleCondition = "";

if ($role === 'employee') {
    $callRoleCondition = " AND c.user_id = {$userId} ";
}

$sql = "
    SELECT COUNT(c.id) AS total
    FROM call_logs c
    WHERE c.call_datetime >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
    $callRoleCondition
";

$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $summary['calls_this_month'] = $row['total'] ?? 0;
}



// 7️⃣ OVERDUE FOLLOWUPS

$overdueRoleCondition = "";

if ($role === 'employee') {
    $overdueRoleCondition = " AND c.user_id = {$userId} ";
}

$sql = "
    SELECT COUNT(c.id) AS total
    FROM call_logs c
    WHERE c.next_followup_date IS NOT NULL
    AND c.next_followup_date < CURDATE()
    $overdueRoleCondition
";

$result = $conn->query($sql);
if ($row = $result->fetch_assoc()) {
    $summary['overdue_followups'] = $row['total'] ?? 0;
}


    // 👇 ONLY ONE RETURN. ONLY HERE.
    return $summary;
}