<?php
require_once '../includes/header.php';

// Get total employees count
$emp_query = "SELECT COUNT(*) as total_employees FROM employees";
$emp_result = $conn->query($emp_query);
$emp_row = $emp_result->fetch_assoc();
$total_employees = $emp_row['total_employees'];

// Get departments count
$dept_query = "SELECT COUNT(*) as total_departments FROM departments";
$dept_result = $conn->query($dept_query);
$dept_row = $dept_result->fetch_assoc();
$total_departments = $dept_row['total_departments'];

// Get total salary paid this month
$salary_query = "SELECT SUM(net_salary) as total_salary FROM payroll 
                WHERE MONTH(payment_date) = MONTH(CURRENT_DATE()) 
                AND YEAR(payment_date) = YEAR(CURRENT_DATE())";
$salary_result = $conn->query($salary_query);
$salary_row = $salary_result->fetch_assoc();
$total_salary = $salary_row['total_salary'] ? $salary_row['total_salary'] : 0;

// Get attendance percentage for today
$today = date('Y-m-d');
$attendance_query = "SELECT 
                    (SELECT COUNT(*) FROM attendance WHERE date = '$today' AND status = 'present') as present,
                    COUNT(*) as total 
                    FROM employees WHERE status = 'active'";
$attendance_result = $conn->query($attendance_query);
$attendance_row = $attendance_result->fetch_assoc();
$attendance_percentage = $attendance_row['total'] > 0 ? 
                        round(($attendance_row['present'] / $attendance_row['total']) * 100) : 0;

// Get department distribution for pie chart
$dept_dist_query = "SELECT d.dept_name, COUNT(e.emp_id) as emp_count 
                    FROM departments d
                    LEFT JOIN employees e ON d.dept_id = e.dept_id
                    GROUP BY d.dept_id";
$dept_dist_result = $conn->query($dept_dist_query);
$dept_labels = [];
$dept_data = [];
while ($row = $dept_dist_result->fetch_assoc()) {
    $dept_labels[] = $row['dept_name'];
    $dept_data[] = $row['emp_count'];
}

// Get monthly salary data for bar chart
$monthly_salary_query = "SELECT 
                        MONTH(payment_date) as month,
                        SUM(net_salary) as total
                        FROM payroll
                        WHERE YEAR(payment_date) = YEAR(CURRENT_DATE())
                        GROUP BY MONTH(payment_date)
                        ORDER BY MONTH(payment_date)";
$monthly_salary_result = $conn->query($monthly_salary_query);
$months = [];
$salary_data = [];
while ($row = $monthly_salary_result->fetch_assoc()) {
    $months[] = date('M', mktime(0, 0, 0, $row['month'], 10));
    $salary_data[] = $row['total'];
}

// Get recent employees
$recent_emp_query = "SELECT e.emp_id, e.first_name, e.last_name, e.email, d.dept_name, e.hire_date 
                    FROM employees e
                    LEFT JOIN departments d ON e.dept_id = d.dept_id
                    ORDER BY e.hire_date DESC
                    LIMIT 5";
$recent_emp_result = $conn->query($recent_emp_query);

// Get pending leave applications
$leave_query = "SELECT l.leave_id, e.first_name, e.last_name, lt.leave_type, 
                l.start_date, l.end_date, l.status
                FROM leave_applications l
                JOIN employees e ON l.emp_id = e.emp_id
                JOIN leave_types lt ON l.leave_type_id = lt.leave_type_id
                WHERE l.status = 'pending'
                ORDER BY l.applied_on DESC
                LIMIT 5";
$leave_result = $conn->query($leave_query);
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
        <a href="reports.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
        </a>
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- Total Employees Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Employees</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_employees; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Departments Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Departments</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_departments; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-building fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Salary Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Monthly Salary</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatCurrency($total_salary); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Today's Attendance</div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800"><?php echo $attendance_percentage; ?>%</div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $attendance_percentage; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- Department Distribution Chart -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Department Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="departmentPieChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Salary Chart -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Monthly Salary Overview</h6>
                </div>
                <div class="card-body">
                    <div class="chart-bar">
                        <canvas id="monthlySalaryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- Recent Employees -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Employees</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Hire Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($emp = $recent_emp_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $emp['first_name'] . ' ' . $emp['last_name']; ?></td>
                                    <td><?php echo $emp['dept_name']; ?></td>
                                    <td><?php echo formatDate($emp['hire_date']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if ($recent_emp_result->num_rows == 0): ?>
                                <tr>
                                    <td colspan="3" class="text-center">No employees found</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Leave Requests -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Pending Leave Requests</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Leave Type</th>
                                    <th>Duration</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($leave = $leave_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $leave['first_name'] . ' ' . $leave['last_name']; ?></td>
                                    <td><?php echo $leave['leave_type']; ?></td>
                                    <td><?php echo formatDate($leave['start_date']) . ' to ' . formatDate($leave['end_date']); ?></td>
                                    <td>
                                        <a href="leave_details.php?id=<?php echo $leave['leave_id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if ($leave_result->num_rows == 0): ?>
                                <tr>
                                    <td colspan="4" class="text-center">No pending leave requests</td>
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

<!-- Chart Scripts -->
<script>
// Department Distribution Pie Chart
var ctx = document.getElementById("departmentPieChart");
var departmentPieChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($dept_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($dept_data); ?>,
            backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'],
            hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#dda20a', '#be2617', '#60616f'],
            hoverBorderColor: "rgba(234, 236, 244, 1)",
        }],
    },
    options: {
        maintainAspectRatio: false,
        tooltips: {
            backgroundColor: "rgb(255,255,255)",
            bodyFontColor: "#858796",
            borderColor: '#dddfeb',
            borderWidth: 1,
            xPadding: 15,
            yPadding: 15,
            displayColors: false,
            caretPadding: 10,
        },
        legend: {
            display: true,
            position: 'bottom'
        },
        cutoutPercentage: 70,
    },
});

// Monthly Salary Bar Chart
var ctx2 = document.getElementById("monthlySalaryChart");
var monthlySalaryChart = new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($months); ?>,
        datasets: [{
            label: "Salary",
            backgroundColor: "#4e73df",
            hoverBackgroundColor: "#2e59d9",
            borderColor: "#4e73df",
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
            xAxes: [{
                gridLines: {
                    display: false,
                    drawBorder: false
                },
                ticks: {
                    maxTicksLimit: 12
                }
            }],
            yAxes: [{
                ticks: {
                    maxTicksLimit: 5,
                    padding: 10,
                    callback: function(value, index, values) {
                        return '₹' + number_format(value);
                    }
                },
                gridLines: {
                    color: "rgb(234, 236, 244)",
                    zeroLineColor: "rgb(234, 236, 244)",
                    drawBorder: false,
                    borderDash: [2],
                    zeroLineBorderDash: [2]
                }
            }],
        },
        legend: {
            display: false
        },
        tooltips: {
            titleMarginBottom: 10,
            titleFontColor: '#6e707e',
            titleFontSize: 14,
            backgroundColor: "rgb(255,255,255)",
            bodyFontColor: "#858796",
            borderColor: '#dddfeb',
            borderWidth: 1,
            xPadding: 15,
            yPadding: 15,
            displayColors: false,
            caretPadding: 10,
            callbacks: {
                label: function(tooltipItem, chart) {
                    var datasetLabel = chart.datasets[tooltipItem.datasetIndex].label || '';
                    return datasetLabel + ': ₹' + number_format(tooltipItem.yLabel);
                }
            }
        },
    }
});

// Format number with commas
function number_format(number, decimals, dec_point, thousands_sep) {
    number = (number + '').replace(',', '').replace(' ', '');
    var n = !isFinite(+number) ? 0 : +number,
        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
        sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
        dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
        s = '',
        toFixedFix = function(n, prec) {
            var k = Math.pow(10, prec);
            return '' + Math.round(n * k) / k;
        };
    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0');
    }
    return s.join(dec);
}
</script>

<?php require_once '../includes/footer.php'; ?>