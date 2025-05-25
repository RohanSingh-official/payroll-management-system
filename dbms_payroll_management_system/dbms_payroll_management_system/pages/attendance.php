<?php
require_once '../includes/header.php';

// Set default date to today if not specified
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Handle attendance submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_attendance'])) {
    $date = sanitizeInput($_POST['date']);
    $employees = $_POST['employees'];
    $statuses = $_POST['status'];
    $time_ins = $_POST['time_in'];
    $time_outs = $_POST['time_out'];
    $notes = $_POST['notes'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // First delete existing attendance records for this date
        $delete_query = "DELETE FROM attendance WHERE date = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("s", $date);
        $delete_stmt->execute();
        
        // Insert new attendance records
        $insert_query = "INSERT INTO attendance (emp_id, date, time_in, time_out, status, notes) VALUES (?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        
        foreach ($employees as $index => $emp_id) {
            $status = $statuses[$index];
            $time_in = !empty($time_ins[$index]) ? $time_ins[$index] : NULL;
            $time_out = !empty($time_outs[$index]) ? $time_outs[$index] : NULL;
            $note = isset($notes[$index]) ? $notes[$index] : '';
            
            $insert_stmt->bind_param("isssss", $emp_id, $date, $time_in, $time_out, $status, $note);
            $insert_stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Store success message in session instead of echoing it directly
        $_SESSION['success_message'] = "Attendance records saved successfully!";
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        // Store error message in session instead of echoing it directly
        $_SESSION['error_message'] = "Error saving attendance records: " . $e->getMessage();
    }
    
    // Redirect to avoid form resubmission
    header("Location: attendance.php?date=" . $date);
    exit;
}

// Get all active employees
$emp_query = "SELECT e.emp_id, e.first_name, e.last_name, d.dept_name 
              FROM employees e 
              LEFT JOIN departments d ON e.dept_id = d.dept_id 
              WHERE e.status = 'active' 
              ORDER BY e.first_name, e.last_name";
$emp_result = $conn->query($emp_query);

// Get existing attendance records for the selected date
$attendance_query = "SELECT * FROM attendance WHERE date = ?";
$attendance_stmt = $conn->prepare($attendance_query);
$attendance_stmt->bind_param("s", $selected_date);
$attendance_stmt->execute();
$attendance_result = $attendance_stmt->get_result();

// Create an associative array of attendance records
$attendance_records = [];
while ($row = $attendance_result->fetch_assoc()) {
    $attendance_records[$row['emp_id']] = $row;
}
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Display session messages if they exist -->
    <?php if(isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if(isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Attendance Management</h1>
        <a href="reports.php?section=attendance" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-download fa-sm text-white-50"></i> Attendance Report
        </a>
    </div>

    <!-- Date Selection Form -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Select Date</h6>
        </div>
        <div class="card-body">
            <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="form-inline">
                <div class="form-group mb-2">
                    <label for="date" class="mr-2">Date:</label>
                    <input type="date" class="form-control" id="date" name="date" value="<?php echo $selected_date; ?>">
                </div>
                <button type="submit" class="btn btn-primary mb-2 ml-2">View Attendance</button>
            </form>
        </div>
    </div>

    <!-- Attendance Form -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Attendance for <?php echo formatDate($selected_date); ?></h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                    <div class="dropdown-header">Quick Actions:</div>
                    <a class="dropdown-item" href="#" id="markAllPresent">Mark All Present</a>
                    <a class="dropdown-item" href="#" id="markAllAbsent">Mark All Absent</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#" id="clearAll">Clear All</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <input type="hidden" name="date" value="<?php echo $selected_date; ?>">
                
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($emp_result->num_rows > 0) {
                                $emp_result->data_seek(0);
                                while ($emp = $emp_result->fetch_assoc()): 
                                    $attendance = isset($attendance_records[$emp['emp_id']]) ? $attendance_records[$emp['emp_id']] : null;
                            ?>
                                <tr>
                                    <td>
                                        <?php echo $emp['first_name'] . ' ' . $emp['last_name']; ?>
                                        <input type="hidden" name="employees[]" value="<?php echo $emp['emp_id']; ?>">
                                    </td>
                                    <td><?php echo $emp['dept_name']; ?></td>
                                    <td>
                                        <select class="form-select status-select" name="status[]">
                                            <option value="present" <?php echo ($attendance && $attendance['status'] == 'present') ? 'selected' : ''; ?>>Present</option>
                                            <option value="absent" <?php echo ($attendance && $attendance['status'] == 'absent') ? 'selected' : ''; ?>>Absent</option>
                                            <option value="half_day" <?php echo ($attendance && $attendance['status'] == 'half_day') ? 'selected' : ''; ?>>Half Day</option>
                                            <option value="leave" <?php echo ($attendance && $attendance['status'] == 'leave') ? 'selected' : ''; ?>>Leave</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="time" class="form-control time-in" name="time_in[]" value="<?php echo ($attendance && $attendance['time_in']) ? $attendance['time_in'] : ''; ?>">
                                    </td>
                                    <td>
                                        <input type="time" class="form-control time-out" name="time_out[]" value="<?php echo ($attendance && $attendance['time_out']) ? $attendance['time_out'] : ''; ?>">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" name="notes[]" value="<?php echo ($attendance && $attendance['notes']) ? $attendance['notes'] : ''; ?>">
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            <?php } else { ?>
                                <tr>
                                    <td colspan="6" class="text-center">No employees found</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    <button type="submit" name="submit_attendance" class="btn btn-primary">Save Attendance</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /.container-fluid -->

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mark all present
        document.getElementById('markAllPresent').addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.status-select').forEach(function(select) {
                select.value = 'present';
            });
            
            // Set default time in and out
            document.querySelectorAll('.time-in').forEach(function(input) {
                if (!input.value) input.value = '09:00';
            });
            
            document.querySelectorAll('.time-out').forEach(function(input) {
                if (!input.value) input.value = '17:00';
            });
        });
        
        // Mark all absent
        document.getElementById('markAllAbsent').addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.status-select').forEach(function(select) {
                select.value = 'absent';
            });
            
            // Clear time in and out
            document.querySelectorAll('.time-in').forEach(function(input) {
                input.value = '';
            });
            
            document.querySelectorAll('.time-out').forEach(function(input) {
                input.value = '';
            });
        });
        
        // Clear all
        document.getElementById('clearAll').addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.status-select').forEach(function(select) {
                select.selectedIndex = 0;
            });
            
            document.querySelectorAll('.time-in, .time-out').forEach(function(input) {
                input.value = '';
            });
            
            document.querySelectorAll('input[name="notes[]"]').forEach(function(input) {
                input.value = '';
            });
        });
        
        // Auto-fill time out based on status
        document.querySelectorAll('.status-select').forEach(function(select) {
            select.addEventListener('change', function() {
                const row = this.closest('tr');
                const timeIn = row.querySelector('.time-in');
                const timeOut = row.querySelector('.time-out');
                
                if (this.value === 'present' && !timeIn.value && !timeOut.value) {
                    timeIn.value = '09:00';
                    timeOut.value = '17:00';
                } else if (this.value === 'half_day' && !timeIn.value && !timeOut.value) {
                    timeIn.value = '09:00';
                    timeOut.value = '13:00';
                } else if (this.value === 'absent' || this.value === 'leave') {
                    timeIn.value = '';
                    timeOut.value = '';
                }
            });
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>