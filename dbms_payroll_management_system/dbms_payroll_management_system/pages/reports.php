<?php
require_once '../includes/header.php';

// Get year for filtering
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Get monthly salary data
$monthly_salary_query = "SELECT 
                        MONTH(payment_date) as month,
                        SUM(net_salary) as total
                        FROM payroll
                        WHERE YEAR(payment_date) = ?
                        AND payment_status = 'paid'
                        GROUP BY MONTH(payment_date)
                        ORDER BY MONTH(payment_date)";
$monthly_salary_stmt = $conn->prepare($monthly_salary_query);
$monthly_salary_stmt->bind_param("i", $year);
$monthly_salary_stmt->execute();
$monthly_salary_result = $monthly_salary_stmt->get_result();

$months = [];
$salary_data = [];
while ($row = $monthly_salary_result->fetch_assoc()) {
    $months[] = date('M', mktime(0, 0, 0, $row['month'], 10));
    $salary_data[] = $row['total'];
}

// Get department salary distribution
$dept_salary_query = "SELECT 
                     d.dept_name,
                     SUM(p.net_salary) as total_salary
                     FROM payroll p
                     JOIN employees e ON p.emp_id = e.emp_id
                     JOIN departments d ON e.dept_id = d.dept_id
                     WHERE YEAR(p.payment_date) = ?
                     AND p.payment_status = 'paid'
                     GROUP BY d.dept_id
                     ORDER BY total_salary DESC";
$dept_salary_stmt = $conn->prepare($dept_salary_query);
$dept_salary_stmt->bind_param("i", $year);
$dept_salary_stmt->execute();
$dept_salary_result = $dept_salary_stmt->get_result();

$dept_labels = [];
$dept_data = [];
while ($row = $dept_salary_result->fetch_assoc()) {
    $dept_labels[] = $row['dept_name'];
    $dept_data[] = $row['total_salary'];
}

// Get attendance statistics
$attendance_query = "SELECT 
                    MONTH(date) as month,
                    COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
                    COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent,
                    COUNT(CASE WHEN status = 'half_day' THEN 1 END) as half_day,
                    COUNT(CASE WHEN status = 'leave' THEN 1 END) as leave_count
                    FROM attendance
                    WHERE YEAR(date) = ?
                    GROUP BY MONTH(date)
                    ORDER BY MONTH(date)";
$attendance_stmt = $conn->prepare($attendance_query);
$attendance_stmt->bind_param("i", $year);
$attendance_stmt->execute();
$attendance_result = $attendance_stmt->get_result();

$attendance_months = [];
$present_data = [];
$absent_data = [];
$half_day_data = [];
$leave_data = [];

while ($row = $attendance_result->fetch_assoc()) {
    $attendance_months[] = date('M', mktime(0, 0, 0, $row['month'], 10));
    $present_data[] = $row['present'];
    $absent_data[] = $row['absent'];
    $half_day_data[] = $row['half_day'];
    $leave_data[] = $row['leave_count'];
}

// Get top 5 highest paid employees
$top_employees_query = "SELECT 
                       e.first_name, 
                       e.last_name, 
                       d.dept_name,
                       p.net_salary
                       FROM payroll p
                       JOIN employees e ON p.emp_id = e.emp_id
                       LEFT JOIN departments d ON e.dept_id = d.dept_id
                       WHERE YEAR(p.payment_date) = ?
                       AND MONTH(p.payment_date) = ?
                       AND p.payment_status = 'paid'
                       ORDER BY p.net_salary DESC
                       LIMIT 5";
$top_employees_stmt = $conn->prepare($top_employees_query);
$current_month = date('m');
$top_employees_stmt->bind_param("ii", $year, $current_month);
$top_employees_stmt->execute();
$top_employees_result = $top_employees_stmt->get_result();

// Get total payroll statistics
$payroll_stats_query = "SELECT 
                       COUNT(DISTINCT p.emp_id) as total_employees,
                       SUM(CASE WHEN p.payment_status = 'paid' THEN p.net_salary ELSE 0 END) as total_paid,
                       SUM(CASE WHEN p.payment_status = 'pending' THEN p.net_salary ELSE 0 END) as total_pending,
                       AVG(p.net_salary) as average_salary
                       FROM payroll p
                       WHERE YEAR(p.payment_date) = ?";
$payroll_stats_stmt = $conn->prepare($payroll_stats_query);
$payroll_stats_stmt->bind_param("i", $year);
$payroll_stats_stmt->execute();
$payroll_stats = $payroll_stats_stmt->get_result()->fetch_assoc();
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Payroll Reports & Analytics</h1>
        <div>
            <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="form-inline">
                <div class="form-group mb-2 mr-2">
                    <label for="year" class="sr-only">Year</label>
                    <select class="form-select" id="year" name="year" onchange="this.form.submit()">
                        <?php for ($y = date('Y') - 5; $y <= date('Y'); $y++): ?>
                            <option value="<?php echo $y; ?>" <?php echo ($year == $y) ? 'selected' : ''; ?>>
                                <?php echo $y; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </form>
            <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm" onclick="window.print()">
                <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
            </a>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- Total Paid -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Paid (<?php echo $year; ?>)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo formatCurrency($payroll_stats['total_paid'] ?? 0); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Pending -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Pending Payments</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo formatCurrency($payroll_stats['total_pending'] ?? 0); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Average Salary -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Average Salary</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo formatCurrency($payroll_stats['average_salary'] ?? 0); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Employees -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Employees</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $payroll_stats['total_employees'] ?? 0; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- Monthly Salary Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Monthly Salary Disbursement (<?php echo $year; ?>)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="monthlySalaryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Department Salary Distribution -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Department Salary Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="departmentSalaryChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <?php foreach ($dept_labels as $index => $dept): ?>
                            <span class="mr-2">
                                <i class="fas fa-circle" style="color: <?php echo getChartColor($index); ?>"></i> <?php echo $dept; ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- Attendance Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Monthly Attendance Statistics (<?php echo $year; ?>)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-bar">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Employees -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Top Paid Employees (Current Month)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Salary</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($top_employees_result->num_rows > 0): ?>
                                    <?php while ($emp = $top_employees_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $emp['first_name'] . ' ' . $emp['last_name']; ?></td>
                                            <td><?php echo $emp['dept_name']; ?></td>
                                            <td><?php echo formatCurrency($emp['net_salary']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center">No data available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->

<?php
// Function to get chart colors
function getChartColor($index) {
    $colors = [
        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
        '#5a5c69', '#6610f2', '#6f42c1', '#fd7e14', '#20c9a6'
    ];
    return $colors[$index % count($colors)];
}
?>

<!-- Page level custom scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Monthly Salary Chart
    var monthlySalaryCtx = document.getElementById("monthlySalaryChart");
    var monthlySalaryChart = new Chart(monthlySalaryCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: "Salary Disbursement",
                lineTension: 0.3,
                backgroundColor: "rgba(78, 115, 223, 0.05)",
                borderColor: "rgba(78, 115, 223, 1)",
                pointRadius: 3,
                pointBackgroundColor: "rgba(78, 115, 223, 1)",
                pointBorderColor: "rgba(78, 115, 223, 1)",
                pointHoverRadius: 3,
                pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                pointHitRadius: 10,
                pointBorderWidth: 2,
                data: <?php echo json_encode($salary_data); ?>,
            }],
        },
        options: {
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 10,
                    right: 25,
                    top: 25,
                    bottom: 0
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    }
                },
                y: {
                    ticks: {
                        callback: function(value) {
                            return '₹' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Total: ₹' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Department Salary Chart
    var deptSalaryCtx = document.getElementById("departmentSalaryChart");
    var deptSalaryChart = new Chart(deptSalaryCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($dept_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($dept_data); ?>,
                backgroundColor: [
                    '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                    '#5a5c69', '#6610f2', '#6f42c1', '#fd7e14', '#20c9a6'
                ],
                hoverBackgroundColor: [
                    '#2e59d9', '#17a673', '#2c9faf', '#dda20a', '#be3326',
                    '#4a4b50', '#5000d1', '#5e37a1', '#da6302', '#169b80'
                ],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }],
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ₹' + context.parsed.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Attendance Chart
    var attendanceCtx = document.getElementById("attendanceChart");
    var attendanceChart = new Chart(attendanceCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($attendance_months); ?>,
            datasets: [
                {
                    label: "Present",
                    backgroundColor: "#1cc88a",
                    data: <?php echo json_encode($present_data); ?>
                },
                {
                    label: "Absent",
                    backgroundColor: "#e74a3b",
                    data: <?php echo json_encode($absent_data); ?>
                },
                {
                    label: "Half Day",
                    backgroundColor: "#f6c23e",
                    data: <?php echo json_encode($half_day_data); ?>
                },
                {
                    label: "Leave",
                    backgroundColor: "#36b9cc",
                    data: <?php echo json_encode($leave_data); ?>
                }
            ],
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                x: {
                    stacked: true,
                },
                y: {
                    stacked: true,
                    beginAtZero: true
                }
            }
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>