<?php
require_once '../includes/header.php';

// Initialize variables
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$success = $error = '';

// Handle payroll generation
if (isset($_POST['generate_payroll'])) {
    $selected_month = sanitizeInput($_POST['month']);
    $selected_year = sanitizeInput($_POST['year']);
    
    // Calculate start and end dates for the pay period
    $start_date = $selected_year . '-' . $selected_month . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Get all active employees
        $emp_query = "SELECT emp_id, basic_salary FROM employees WHERE status = 'active'";
        $emp_result = $conn->query($emp_query);
        
        while ($emp = $emp_result->fetch_assoc()) {
            $emp_id = $emp['emp_id'];
            $basic_salary = $emp['basic_salary'];
            
            // Check if payroll already exists for this employee and period
            $check_query = "SELECT payroll_id FROM payroll 
                           WHERE emp_id = ? AND pay_period_start = ? AND pay_period_end = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("iss", $emp_id, $start_date, $end_date);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                // Skip if payroll already exists
                continue;
            }
            
            // Calculate attendance
            $attendance_query = "SELECT 
                                COUNT(CASE WHEN status = 'present' THEN 1 END) as present_days,
                                COUNT(CASE WHEN status = 'half_day' THEN 1 END) as half_days,
                                COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_days,
                                COUNT(CASE WHEN status = 'leave' THEN 1 END) as leave_days
                                FROM attendance 
                                WHERE emp_id = ? AND date BETWEEN ? AND ?";
            $attendance_stmt = $conn->prepare($attendance_query);
            $attendance_stmt->bind_param("iss", $emp_id, $start_date, $end_date);
            $attendance_stmt->execute();
            $attendance_result = $attendance_stmt->get_result();
            $attendance = $attendance_result->fetch_assoc();
            
            // Calculate working days in the month
            $working_days = getWorkingDays($start_date, $end_date);
            
            // Calculate salary based on attendance
            $present_days = $attendance['present_days'];
            $half_days = $attendance['half_days'];
            $leave_days = $attendance['leave_days'];
            
            $attendance_factor = ($present_days + ($half_days * 0.5) + $leave_days) / $working_days;
            $attendance_adjusted_salary = $basic_salary * $attendance_factor;
            
            // Get allowances
            $allowances_query = "SELECT SUM(amount) as total_allowances 
                                FROM employee_allowances 
                                WHERE emp_id = ? AND effective_date <= ?";
            $allowances_stmt = $conn->prepare($allowances_query);
            $allowances_stmt->bind_param("is", $emp_id, $end_date);
            $allowances_stmt->execute();
            $allowances_result = $allowances_stmt->get_result();
            $allowances_row = $allowances_result->fetch_assoc();
            $allowances = $allowances_row['total_allowances'] ? $allowances_row['total_allowances'] : 0;
            
            // Get deductions
            $deductions_query = "SELECT SUM(amount) as total_deductions 
                               FROM employee_deductions 
                               WHERE emp_id = ? AND effective_date <= ?";
            $deductions_stmt = $conn->prepare($deductions_query);
            $deductions_stmt->bind_param("is", $emp_id, $end_date);
            $deductions_stmt->execute();
            $deductions_result = $deductions_stmt->get_result();
            $deductions_row = $deductions_result->fetch_assoc();
            $deductions = $deductions_row['total_deductions'] ? $deductions_row['total_deductions'] : 0;
            
            // Calculate tax (simplified)
            $taxable_income = $attendance_adjusted_salary + $allowances;
            $tax_rate = 0.1; // 10% tax rate (simplified)
            $tax = $taxable_income * $tax_rate;
            
            // Calculate net salary
            $net_salary = $attendance_adjusted_salary + $allowances - $deductions - $tax;
            
            // Insert payroll record
            $insert_query = "INSERT INTO payroll 
                            (emp_id, pay_period_start, pay_period_end, basic_salary, 
                            allowances, deductions, tax, net_salary, payment_date, payment_status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
            $insert_stmt = $conn->prepare($insert_query);
            $payment_date = date('Y-m-d');
            $insert_stmt->bind_param("issddddds", $emp_id, $start_date, $end_date, $basic_salary, 
                                   $allowances, $deductions, $tax, $net_salary, $payment_date);
            $insert_stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        $success = "Payroll generated successfully for " . date('F Y', strtotime($start_date));
        
        // Update month and year to the selected values
        $month = $selected_month;
        $year = $selected_year;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error = "Error generating payroll: " . $e->getMessage();
    }
}

// Handle payroll payment status update
if (isset($_POST['update_status'])) {
    $payroll_id = sanitizeInput($_POST['payroll_id']);
    $status = sanitizeInput($_POST['status']);
    
    $update_query = "UPDATE payroll SET payment_status = ? WHERE payroll_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("si", $status, $payroll_id);
    
    if ($update_stmt->execute()) {
        $success = "Payment status updated successfully!";
    } else {
        $error = "Error updating payment status.";
    }
}

// Get payroll data for the selected month and year
$start_date = $year . '-' . $month . '-01';
$end_date = date('Y-m-t', strtotime($start_date));

$payroll_query = "SELECT p.*, e.first_name, e.last_name, e.email, d.dept_name 
                 FROM payroll p
                 JOIN employees e ON p.emp_id = e.emp_id
                 LEFT JOIN departments d ON e.dept_id = d.dept_id
                 WHERE p.pay_period_start = ? AND p.pay_period_end = ?
                 ORDER BY e.first_name, e.last_name";
$payroll_stmt = $conn->prepare($payroll_query);
$payroll_stmt->bind_param("ss", $start_date, $end_date);
$payroll_stmt->execute();
$payroll_result = $payroll_stmt->get_result();

// Function to calculate working days excluding weekends
function getWorkingDays($startDate, $endDate) {
    $begin = strtotime($startDate);
    $end = strtotime($endDate);
    
    if ($begin > $end) {
        return 0;
    }
    
    $workingDays = 0;
    $daySeconds = 86400; // 60*60*24
    
    for ($i = $begin; $i <= $end; $i = $i + $daySeconds) {
        $day = date("N", $i); // 1 (Monday) to 7 (Sunday)
        if ($day < 6) { // Monday to Friday
            $workingDays++;
        }
    }
    
    return $workingDays;
}
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Payroll Management</h1>
        <div>
            <a href="payslips.php" class="d-none d-sm-inline-block btn btn-sm btn-info shadow-sm mr-2">
                <i class="fas fa-file-invoice-dollar fa-sm text-white-50"></i> Generate Payslips
            </a>
            <a href="payroll_report.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-download fa-sm text-white-50"></i> Payroll Report
            </a>
        </div>
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

    <!-- Payroll Generation Form -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Generate Payroll</h6>
        </div>
        <div class="card-body">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="form-inline">
                <div class="form-group mb-2 mr-2">
                    <label for="month" class="sr-only">Month</label>
                    <select class="form-select" id="month" name="month">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo sprintf('%02d', $m); ?>" <?php echo ($month == sprintf('%02d', $m)) ? 'selected' : ''; ?>>
                                <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group mb-2 mr-2">
                    <label for="year" class="sr-only">Year</label>
                    <select class="form-select" id="year" name="year">
                        <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                            <option value="<?php echo $y; ?>" <?php echo ($year == $y) ? 'selected' : ''; ?>>
                                <?php echo $y; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <button type="submit" name="view_payroll" class="btn btn-secondary mb-2 mr-2">View</button>
                <button type="submit" name="generate_payroll" class="btn btn-primary mb-2" onclick="return confirm('Are you sure you want to generate payroll for this period? This will create new payroll records for all active employees.')">
                    Generate Payroll
                </button>
            </form>
        </div>
    </div>

    <!-- Payroll Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Payroll for <?php echo date('F Y', strtotime($start_date)); ?></h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Basic Salary</th>
                            <th>Allowances</th>
                            <th>Deductions</th>
                            <th>Tax</th>
                            <th>Net Salary</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($payroll_result->num_rows > 0): ?>
                            <?php while ($row = $payroll_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                                    <td><?php echo $row['dept_name']; ?></td>
                                    <td><?php echo formatCurrency($row['basic_salary']); ?></td>
                                    <td><?php echo formatCurrency($row['allowances']); ?></td>
                                    <td><?php echo formatCurrency($row['deductions']); ?></td>
                                    <td><?php echo formatCurrency($row['tax']); ?></td>
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
                                        <button class="btn btn-info btn-sm view-btn" data-bs-toggle="modal" data-bs-target="#viewPayrollModal" 
                                                data-id="<?php echo $row['payroll_id']; ?>"
                                                data-name="<?php echo $row['first_name'] . ' ' . $row['last_name']; ?>"
                                                data-email="<?php echo $row['email']; ?>"
                                                data-dept="<?php echo $row['dept_name']; ?>"
                                                data-basic="<?php echo $row['basic_salary']; ?>"
                                                data-allowances="<?php echo $row['allowances']; ?>"
                                                data-deductions="<?php echo $row['deductions']; ?>"
                                                data-tax="<?php echo $row['tax']; ?>"
                                                data-net="<?php echo $row['net_salary']; ?>"
                                                data-status="<?php echo $row['payment_status']; ?>"
                                                data-date="<?php echo formatDate($row['payment_date']); ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <?php if ($row['payment_status'] == 'pending'): ?>
                                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" style="display: inline;">
                                                <input type="hidden" name="payroll_id" value="<?php echo $row['payroll_id']; ?>">
                                                <input type="hidden" name="status" value="paid">
                                                <button type="submit" name="update_status" class="btn btn-success btn-sm" onclick="return confirm('Mark this payroll as paid?')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            
                                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" style="display: inline;">
                                                <input type="hidden" name="payroll_id" value="<?php echo $row['payroll_id']; ?>">
                                                <input type="hidden" name="status" value="cancelled">
                                                <button type="submit" name="update_status" class="btn btn-danger btn-sm" onclick="return confirm('Cancel this payroll?')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <a href="payslip.php?id=<?php echo $row['payroll_id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-file-invoice"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">No payroll records found for this period</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->

<!-- View Payroll Modal -->
<div class="modal fade" id="viewPayrollModal" tabindex="-1" role="dialog" aria-labelledby="viewPayrollModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewPayrollModalLabel">Payroll Details</h5>
                <button class="close" type="button" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Employee:</strong> <span id="modal-name"></span></p>
                        <p><strong>Email:</strong> <span id="modal-email"></span></p>
                        <p><strong>Department:</strong> <span id="modal-dept"></span></p>
                        <p><strong>Payment Date:</strong> <span id="modal-date"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Basic Salary:</strong> <span id="modal-basic"></span></p>
                        <p><strong>Allowances:</strong> <span id="modal-allowances"></span></p>
                        <p><strong>Deductions:</strong> <span id="modal-deductions"></span></p>
                        <p><strong>Tax:</strong> <span id="modal-tax"></span></p>
                        <p><strong>Net Salary:</strong> <span id="modal-net" class="text-primary font-weight-bold"></span></p>
                        <p><strong>Status:</strong> <span id="modal-status"></span></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
                <a id="payslip-link" href="#" class="btn btn-primary">Generate Payslip</a>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle view payroll modal data
        const viewButtons = document.querySelectorAll('.view-btn');
        
        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const email = this.getAttribute('data-email');
                const dept = this.getAttribute('data-dept');
                const basic = parseFloat(this.getAttribute('data-basic'));
                const allowances = parseFloat(this.getAttribute('data-allowances'));
                const deductions = parseFloat(this.getAttribute('data-deductions'));
                const tax = parseFloat(this.getAttribute('data-tax'));
                const net = parseFloat(this.getAttribute('data-net'));
                const status = this.getAttribute('data-status');
                const date = this.getAttribute('data-date');
                
                document.getElementById('modal-name').textContent = name;
                document.getElementById('modal-email').textContent = email;
                document.getElementById('modal-dept').textContent = dept;
                document.getElementById('modal-basic').textContent = '₹ ' + basic.toFixed(2);
                document.getElementById('modal-allowances').textContent = '₹ ' + allowances.toFixed(2);
                document.getElementById('modal-deductions').textContent = '₹ ' + deductions.toFixed(2);
                document.getElementById('modal-tax').textContent = '₹ ' + tax.toFixed(2);
                document.getElementById('modal-net').textContent = '₹ ' + net.toFixed(2);
                document.getElementById('modal-date').textContent = date;
                
                let statusText = '';
                if (status === 'paid') {
                    statusText = '<span class="badge bg-success">Paid</span>';
                } else if (status === 'pending') {
                    statusText = '<span class="badge bg-warning">Pending</span>';
                } else {
                    statusText = '<span class="badge bg-danger">Cancelled</span>';
                }
                document.getElementById('modal-status').innerHTML = statusText;
                
                document.getElementById('payslip-link').href = 'payslip.php?id=' + id;
            });
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>