<input type="checkbox" id="student_visa" name="modules[]" value="student_visa">

<input type="checkbox" id="lead_management" name="modules[]" value="lead_management">
<input type="checkbox" id="lead_source_tracking" name="modules[]" value="lead_source_tracking">
<input type="checkbox" id="calling_followup" name="modules[]" value="calling_followup">
<input type="checkbox" id="staff_work_report" name="modules[]" value="staff_work_report">
<script>
document.addEventListener("DOMContentLoaded", function () {

    const triggerModules = [
        "student_visa",
        "work_visa",
        "visitor_visa",
        "pr",
        "loan"
    ];

    const dependentModules = [
        "lead_management",
        "lead_source_tracking",
        "calling_followup",
        "staff_work_report"
    ];

    function updateDependencies() {

    let anySelected = triggerModules.some(id =>
        document.getElementById(id)?.checked
    );

    dependentModules.forEach(id => {
        const checkbox = document.getElementById(id);
        if (!checkbox) return;

        if (anySelected) {
            checkbox.checked = true;
            checkbox.disabled = true;
        } else {
            checkbox.checked = false;   // 👈 uncheck when no trigger
            checkbox.disabled = false;
        }
    });
}

    triggerModules.forEach(id => {
        document.getElementById(id)?.addEventListener("change", updateDependencies);
    });

    updateDependencies(); // run on page load

});
</script>