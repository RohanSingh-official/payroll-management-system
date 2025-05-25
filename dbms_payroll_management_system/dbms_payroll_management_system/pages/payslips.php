<?php
require_once '../includes/header.php';

// Initialize variables
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$department = isset($_GET['department']) ? $_GET['department'] : '';
$success = $error = '';

// Get departments for filter
$dept_query = "SELECT dept_id, dept_name FROM departments ORDER BY dept_name";
$dept_result = $conn->query($dept_query);

// Calculate start and end dates for the pay period
$start_date = $year . '-' . $month . '-01';
$end_date = date('Y-m-t', strtotime($start_date));

// Build query for payroll data
$query = "SELECT p.*, e.first_name, e.last_name, e.email, d.dept_name 
         FROM payroll p
         JOIN employees e ON p.emp_id = e.emp_id
         LEFT JOIN departments d ON e.dept_id = d.dept_id
         WHERE p.pay_period_start = ? AND p.pay_period_end = ?";

$params = [$start_date, $end_date];
$types = "ss";

if (!empty($department)) {
    $query .= " AND e.dept_id = ?";
    $params[] = $department;
    $types .= "i";
}

$query .= " ORDER BY e.first_name, e.last_name";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Handle email payslips
if (isset($_POST['email_payslips'])) {
    $selected_employees = isset($_POST['selected_employees']) ? $_POST['selected_employees'] : [];
    
    if (count($selected_employees) > 0) {
        // In a real application, you would implement email functionality here
        // For this demo, we'll just simulate success
        $success = count($selected_employees) . " payslips have been emailed successfully.";
    } else {
        $error = "No employees selected for emailing payslips.";
    }
}

// Handle download payslips
if (isset($_POST['download_payslips'])) {
    $selected_employees = isset($_POST['selected_employees']) ? $_POST['selected_employees'] : [];
    
    if (count($selected_employees) > 0) {
        // In a real application, you would implement PDF generation and download here
        // For this demo, we'll just simulate success
        $success = count($selected_employees) . " payslips have been prepared for download.";
    } else {
        $error = "No employees selected for downloading payslips.";
    }
}
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Generate Payslips</h1>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Filter Form -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Options</h6>
        </div>
        <div class="card-body">
            <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="row g-3">
                <div class="col-md-4">
                    <label for="month" class="form-label">Month</label>
                    <select class="form-select" id="month" name="month">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo sprintf('%02d', $m); ?>" <?php echo ($month == sprintf('%02d', $m)) ? 'selected' : ''; ?>>
                                <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="year" class="form-label">Year</label>
                    <select class="form-select" id="year" name="year">
                        <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                            <option value="<?php echo $y; ?>" <?php echo ($year == $y) ? 'selected' : ''; ?>>
                                <?php echo $y; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="department" class="form-label">Department</label>
                    <select class="form-select" id="department" name="department">
                        <option value="">All Departments</option>
                        <?php while ($dept = $dept_result->fetch_assoc()): ?>
                            <option value="<?php echo $dept['dept_id']; ?>" <?php echo ($department == $dept['dept_id']) ? 'selected' : ''; ?>>
                                <?php echo $dept['dept_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-12 mt-3">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Payslips Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">
                Payslips for <?php echo date('F Y', strtotime($start_date)); ?>
            </h6>
            <div>
                <button type="button" id="selectAllBtn" class="btn btn-sm btn-secondary">Select All</button>
                <button type="button" id="deselectAllBtn" class="btn btn-sm btn-secondary">Deselect All</button>
            </div>
        </div>
        <div class="card-body">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?month=' . $month . '&year=' . $year . '&department=' . $department; ?>">
                <div class="table-responsive">
                    <table class="table table-bordered" id="payslipsTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th width="5%">
                                    <input type="checkbox" id="checkAll">
                                </th>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Basic Salary</th>
                                <th>Net Salary</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="selected_employees[]" value="<?php echo $row['payroll_id']; ?>" class="employee-checkbox">
                                        </td>
                                        <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                                        <td><?php echo $row['dept_name'] ?? 'Unassigned'; ?></td>
                                        <td><?php echo formatCurrency($row['basic_salary']); ?></td>
                                        <td><?php echo formatCurrency($row['net_salary']); ?></td>
                                        <td>
                                            <?php if ($row['payment_status'] == 'paid'): ?>
                                                <span class="badge bg-success">Paid</span>
                                            <?php elseif ($row['payment_status'] == 'pending'): ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Cancelled</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="payslip.php?id=<?php echo $row['payroll_id']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="payslip.php?id=<?php echo $row['payroll_id']; ?>&print=1" class="btn btn-info btn-sm" target="_blank">
                                                <i class="fas fa-print"></i> Print
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No payroll records found for this period</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($result->num_rows > 0): ?>
                <div class="mt-3">
                    <button type="submit" name="email_payslips" class="btn btn-success">
                        <i class="fas fa-envelope"></i> Email Selected Payslips
                    </button>
                    <button type="submit" name="download_payslips" class="btn btn-primary ml-2">
                        <i class="fas fa-download"></i> Download Selected Payslips
                    </button>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>
<!-- /.container-fluid -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check/Uncheck all checkboxes
    document.getElementById('checkAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.employee-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });
    
    // Select All button
    document.getElementById('selectAllBtn').addEventListener('click', function() {
        document.getElementById('checkAll').checked = true;
        const checkboxes = document.querySelectorAll('.employee-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = true;
        });
    });
    
    // Deselect All button
    document.getElementById('deselectAllBtn').addEventListener('click', function() {
        document.getElementById('checkAll').checked = false;
        const checkboxes = document.querySelectorAll('.employee-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
    });
    
    // Update "Check All" checkbox state when individual checkboxes change
    const employeeCheckboxes = document.querySelectorAll('.employee-checkbox');
    employeeCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const allChecked = document.querySelectorAll('.employee-checkbox:checked').length === employeeCheckboxes.length;
            document.getElementById('checkAll').checked = allChecked;
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>