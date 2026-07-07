<?php
// ===== Fetch Fresh Case Data =====
$coreStmt = $conn->prepare("
    SELECT *
    FROM pr_case_details
    WHERE id = ?
");
$coreStmt->bind_param("i", $case_id);
$coreStmt->execute();
$core = $coreStmt->get_result()->fetch_assoc();

// ===== Fetch Payment Summary =====
$payStmt = $conn->prepare("
    SELECT COALESCE(SUM(amount),0) as total_paid
    FROM pr_payments
    WHERE lead_id = (
        SELECT lead_id FROM pr_case_details WHERE id = ?
    )
");
$payStmt->bind_param("i", $case_id);
$payStmt->execute();
$payData = $payStmt->get_result()->fetch_assoc();

$total_paid = floatval($payData['total_paid']);
?>

<div class="row g-4">

    <!-- ================= CASE INFO ================= -->
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-body">

                <h6 class="text-muted mb-3">Case Information</h6>

                <div class="mb-2">
                    <strong>Case ID:</strong> PR-<?= $case_id ?>
                </div>

                <div class="mb-2">
                    <strong>Current Stage:</strong>
                    <?= htmlspecialchars($core['current_stage'] ?? '-') ?>
                </div>

                <div class="mb-2">
                    <strong>Eligibility:</strong>
                    <?= htmlspecialchars($core['eligibility_result'] ?? '-') ?>
                </div>

                <div class="mb-2">
                    <strong>Points:</strong>
                    <?= $core['points'] !== null ? htmlspecialchars($core['points']) : '-' ?>
                </div>

                <div class="mb-2">
                    <strong>Agreement Signed:</strong>
                    <?= htmlspecialchars($core['agreement_signed'] ?? '-') ?>
                </div>

                <div class="mb-2">
                    <strong>Last Updated:</strong>
                    <?= htmlspecialchars($core['updated_at'] ?? '-') ?>
                </div>

            </div>
        </div>
    </div>

    <!-- ================= PAYMENT SUMMARY ================= -->
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-body">

                <h6 class="text-muted mb-3">Payment Summary</h6>

                <div class="mb-2">
                    <strong>Total Paid:</strong>
                    ₹<?= number_format($total_paid, 2) ?>
                </div>

            </div>
        </div>
    </div>

</div>