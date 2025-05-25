<?php
require_once '../includes/header.php';

// Check if payroll ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<div class="alert alert-danger">Payroll ID is required.</div>';
    exit;
}

$payroll_id = intval($_GET['id']);

// Get payroll details
$payroll_query = "SELECT p.*, 
                 e.first_name, e.last_name, e.email, e.phone, e.position, e.bank_account,
                 d.dept_name
                 FROM payroll p
                 JOIN employees e ON p.emp_id = e.emp_id
                 LEFT JOIN departments d ON e.dept_id = d.dept_id
                 WHERE p.payroll_id = ?";
$payroll_stmt = $conn->prepare($payroll_query);
$payroll_stmt->bind_param("i", $payroll_id);
$payroll_stmt->execute();
$payroll_result = $payroll_stmt->get_result();

if ($payroll_result->num_rows === 0) {
    echo '<div class="alert alert-danger">Payroll record not found.</div>';
    exit;
}

$payroll = $payroll_result->fetch_assoc();

// Get company details
$company_name = "Your Company Name";
$company_address = "123 Business Street, City, Country";
$company_phone = "+1 234 567 890";
$company_email = "info@yourcompany.com";
$company_website = "www.yourcompany.com";

// Calculate pay period
$start_date = new DateTime($payroll['pay_period_start']);
$end_date = new DateTime($payroll['pay_period_end']);
$pay_period = $start_date->format('d M Y') . ' - ' . $end_date->format('d M Y');

// Get attendance details for the pay period
$attendance_query = "SELECT 
                    COUNT(CASE WHEN status = 'present' THEN 1 END) as present_days,
                    COUNT(CASE WHEN status = 'half_day' THEN 1 END) as half_days,
                    COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_days,
                    COUNT(CASE WHEN status = 'leave' THEN 1 END) as leave_days
                    FROM attendance 
                    WHERE emp_id = ? AND date BETWEEN ? AND ?";
$attendance_stmt = $conn->prepare($attendance_query);
$attendance_stmt->bind_param("iss", $payroll['emp_id'], $payroll['pay_period_start'], $payroll['pay_period_end']);
$attendance_stmt->execute();
$attendance_result = $attendance_stmt->get_result();
$attendance = $attendance_result->fetch_assoc();

// Get working days in the month
$working_days = getWorkingDays($payroll['pay_period_start'], $payroll['pay_period_end']);

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
        <h1 class="h3 mb-0 text-gray-800">Payslip</h1>
        <div>
            <button class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm" onclick="window.print()">
                <i class="fas fa-print fa-sm text-white-50"></i> Print Payslip
            </button>
            <a href="payroll.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm ml-2">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Payroll
            </a>
        </div>
    </div>

    <!-- Payslip -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div id="payslip" class="p-4">
                <!-- Payslip Header -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h2 class="text-primary"><?php echo $company_name; ?></h2>
                        <p><?php echo $company_address; ?><br>
                        Phone: <?php echo $company_phone; ?><br>
                        Email: <?php echo $company_email; ?><br>
                        Website: <?php echo $company_website; ?></p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h2 class="text-primary">PAYSLIP</h2>
                        <p>Payslip #: <?php echo $payroll_id; ?><br>
                        Pay Period: <?php echo $pay_period; ?><br>
                        Payment Date: <?php echo formatDate($payroll['payment_date']); ?><br>
                        Payment Status: 
                        <?php if ($payroll['payment_status'] == 'paid'): ?>
                            <span class="badge bg-success">Paid</span>
                        <?php elseif ($payroll['payment_status'] == 'pending'): ?>
                            <span class="badge bg-warning">Pending</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Cancelled</span>
                        <?php endif; ?>
                        </p>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Employee Information -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5 class="text-primary">Employee Information</h5>
                        <p>Name: <?php echo $payroll['first_name'] . ' ' . $payroll['last_name']; ?><br>
                        Position: <?php echo $payroll['position']; ?><br>
                        Department: <?php echo $payroll['dept_name']; ?><br>
                        Email: <?php echo $payroll['email']; ?><br>
                        Phone: <?php echo $payroll['phone']; ?></p>
                    </div>
                    <div class="col-md-6">
                        <h5 class="text-primary">Payment Information</h5>
                        <p>Bank Account: <?php echo $payroll['bank_account']; ?><br>
                        Payment Method: Bank Transfer</p>
                        
                        <h5 class="text-primary mt-3">Attendance Summary</h5>
                        <p>Working Days: <?php echo $working_days; ?> days<br>
                        Present: <?php echo $attendance['present_days']; ?> days<br>
                        Half Day: <?php echo $attendance['half_days']; ?> days<br>
                        Leave: <?php echo $attendance['leave_days']; ?> days<br>
                        Absent: <?php echo $attendance['absent_days']; ?> days</p>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Salary Details -->
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="text-primary">Salary Details</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Description</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Basic Salary</td>
                                        <td class="text-end"><?php echo formatCurrency($payroll['basic_salary']); ?></td>
                                    </tr>
                                    <?php if ($payroll['allowances'] > 0): ?>
                                    <tr>
                                        <td>Allowances</td>
                                        <td class="text-end"><?php echo formatCurrency($payroll['allowances']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td>Gross Salary</td>
                                        <td class="text-end"><?php echo formatCurrency($payroll['basic_salary'] + $payroll['allowances']); ?></td>
                                    </tr>
                                    <?php if ($payroll['deductions'] > 0): ?>
                                    <tr>
                                        <td>Deductions</td>
                                        <td class="text-end">-<?php echo formatCurrency($payroll['deductions']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td>Tax</td>
                                        <td class="text-end">-<?php echo formatCurrency($payroll['tax']); ?></td>
                                    </tr>
                                    <tr class="table-primary">
                                        <td><strong>Net Salary</strong></td>
                                        <td class="text-end"><strong><?php echo formatCurrency($payroll['net_salary']); ?></strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Footer -->
                <div class="row">
                    <div class="col-md-8">
                        <p><small>This is a computer-generated document. No signature is required.</small></p>
                    </div>
                    <div class="col-md-4 text-end">
                        <p>Generated on: <?php echo date('d M Y H:i'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #payslip, #payslip * {
        visibility: visible;
    }
    #payslip {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .btn {
        display: none !important;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>

// Get working days in the month
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