<?php
require_once '../includes/header.php';

// Initialize variables
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$department = isset($_GET['department']) ? $_GET['department'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Get departments for filter
$dept_query = "SELECT dept_id, dept_name FROM departments ORDER BY dept_name";
$dept_result = $conn->query($dept_query);

// Build query for payroll report
$query = "SELECT p.*, e.first_name, e.last_name, e.position, d.dept_name 
          FROM payroll p
          JOIN employees e ON p.emp_id = e.emp_id
          LEFT JOIN departments d ON e.dept_id = d.dept_id
          WHERE p.payment_date BETWEEN ? AND ?";

$params = [$start_date, $end_date];
$types = "ss";

if (!empty($department)) {
    $query .= " AND e.dept_id = ?";
    $params[] = $department;
    $types .= "i";
}

if (!empty($status)) {
    $query .= " AND p.payment_status = ?";
    $params[] = $status;
    $types .= "s";
}

$query .= " ORDER BY p.payment_date DESC, e.first_name, e.last_name";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Calculate summary statistics
$total_basic = $total_allowances = $total_deductions = $total_tax = $total_net = 0;
$employee_count = 0;
$department_totals = [];

if ($result->num_rows > 0) {
    $temp_result = $result;
    while ($row = $temp_result->fetch_assoc()) {
        $total_basic += $row['basic_salary'];
        $total_allowances += $row['allowances'];
        $total_deductions += $row['deductions'];
        $total_tax += $row['tax'];
        $total_net += $row['net_salary'];
        $employee_count++;
        
        // Track department totals
        $dept = $row['dept_name'] ?? 'Unassigned';
        if (!isset($department_totals[$dept])) {
            $department_totals[$dept] = 0;
        }
        $department_totals[$dept] += $row['net_salary'];
    }
    
    // Reset result pointer
    $result->data_seek(0);
}
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Payroll Report</h1>
        <div>
            <button class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm" onclick="window.print()">
                <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
            </button>
            <button class="d-none d-sm-inline-block btn btn-sm btn-success shadow-sm ml-2" id="exportBtn">
                <i class="fas fa-file-excel fa-sm text-white-50"></i> Export to Excel
            </button>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card shadow mb-4 print-hide">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Options</h6>
        </div>
        <div class="card-body">
            <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="row g-3">
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-2">
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
                <div class="col-md-2">
                    <label for="status" class="form-label">Payment Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="paid" <?php echo ($status == 'paid') ? 'selected' : ''; ?>>Paid</option>
                        <option value="pending" <?php echo ($status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="cancelled" <?php echo ($status == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Summary -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Employees</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $employee_count; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Net Salary</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatCurrency($total_net); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Average Salary</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo ($employee_count > 0) ? formatCurrency($total_net / $employee_count) : formatCurrency(0); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Total Tax Deducted</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatCurrency($total_tax); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-percentage fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Department Distribution -->
    <div class="row">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Department Salary Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Department</th>
                                    <th>Total Salary</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($department_totals as $dept => $total): ?>
                                    <tr>
                                        <td><?php echo $dept; ?></td>
                                        <td><?php echo formatCurrency($total); ?></td>
                                        <td>
                                            <?php 
                                                $percentage = ($total_net > 0) ? ($total / $total_net) * 100 : 0;
                                                echo number_format($percentage, 2) . '%';
                                            ?>
                                            <div class="progress">
                                                <div class="progress-bar" role="progressbar" style="width: <?php echo $percentage; ?>%"
                                                    aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Salary Breakdown</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="salaryBreakdownChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <span class="mr-2">
                            <i class="fas fa-circle text-primary"></i> Basic Salary
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-success"></i> Allowances
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-danger"></i> Deductions
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-warning"></i> Tax
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payroll Data Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Payroll Report (<?php echo date('d M Y', strtotime($start_date)) . ' - ' . date('d M Y', strtotime($end_date)); ?>)
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="payrollTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Position</th>
                            <th>Department</th>
                            <th>Payment Date</th>
                            <th>Basic Salary</th>
                            <th>Allowances</th>
                            <th>Deductions</th>
                            <th>Tax</th>
                            <th>Net Salary</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                                    <td><?php echo $row['position']; ?></td>
                                    <td><?php echo $row['dept_name'] ?? 'Unassigned'; ?></td>
                                    <td><?php echo formatDate($row['payment_date']); ?></td>
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
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center">No payroll records found for the selected criteria</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th colspan="4">Total</th>
                            <th><?php echo formatCurrency($total_basic); ?></th>
                            <th><?php echo formatCurrency($total_allowances); ?></th>
                            <th><?php echo formatCurrency($total_deductions); ?></th>
                            <th><?php echo formatCurrency($total_tax); ?></th>
                            <th><?php echo formatCurrency($total_net); ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->

<style>
@media print {
    .print-hide, .sidebar, .navbar, .footer, .scroll-to-top {
        display: none !important;
    }
    .container-fluid {
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    body {
        padding: 20px !important;
    }
    table {
        font-size: 12px !important;
    }
}
</style>

<!-- Page level plugins -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Salary Breakdown Chart
    var ctx = document.getElementById("salaryBreakdownChart");
    var myPieChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ["Basic Salary", "Allowances", "Deductions", "Tax"],
            datasets: [{
                data: [
                    <?php echo $total_basic; ?>,
                    <?php echo $total_allowances; ?>,
                    <?php echo $total_deductions; ?>,
                    <?php echo $total_tax; ?>
                ],
                backgroundColor: ['#4e73df', '#1cc88a', '#e74a3b', '#f6c23e'],
                hoverBackgroundColor: ['#2e59d9', '#17a673', '#be2617', '#dda20a'],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }],
        },
        options: {
            maintainAspectRatio: false,
            tooltips: {
                callbacks: {
                    label: function(tooltipItem, data) {
                        var dataset = data.datasets[tooltipItem.datasetIndex];
                        var total = dataset.data.reduce(function(previousValue, currentValue) {
                            return previousValue + currentValue;
                        });
                        var currentValue = dataset.data[tooltipItem.index];
                        var percentage = Math.floor(((currentValue/total) * 100)+0.5);
                        return data.labels[tooltipItem.index] + ': ₹' + currentValue.toLocaleString() + ' (' + percentage + '%)';
                    }
                }
            }
        }
    });

    // Export to Excel functionality
    document.getElementById('exportBtn').addEventListener('click', function() {
        var wb = XLSX.utils.book_new();
        var table = document.getElementById('payrollTable');
        var ws = XLSX.utils.table_to_sheet(table);
        
        // Add title
        var title = 'Payroll Report (<?php echo date('d M Y', strtotime($start_date)) . ' - ' . date('d M Y', strtotime($end_date)); ?>)';
        XLSX.utils.sheet_add_aoa(ws, [[title]], { origin: "A1" });
        
        XLSX.utils.book_append_sheet(wb, ws, 'Payroll Report');
        XLSX.writeFile(wb, 'Payroll_Report_<?php echo date('Y-m-d'); ?>.xlsx');
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>