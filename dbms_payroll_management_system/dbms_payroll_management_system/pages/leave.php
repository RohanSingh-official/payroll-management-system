<?php
require_once '../includes/header.php';

// Check if leave_types table exists, if not create it
$check_table_query = "SHOW TABLES LIKE 'leave_types'";
$table_exists = $conn->query($check_table_query);

if ($table_exists->num_rows == 0) {
    // Create leave_types table
    $create_leave_types = "CREATE TABLE leave_types (
        leave_type_id INT AUTO_INCREMENT PRIMARY KEY,
        leave_type VARCHAR(50) NOT NULL,
        allowed_days INT NOT NULL DEFAULT 0,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_leave_types)) {
        // Insert default leave types
        $insert_defaults = "INSERT INTO leave_types (leave_type, allowed_days, description) VALUES 
            ('Annual Leave', 20, 'Regular vacation leave'),
            ('Sick Leave', 10, 'Leave due to illness'),
            ('Personal Leave', 5, 'Leave for personal matters'),
            ('Maternity Leave', 90, 'Leave for childbirth and recovery'),
            ('Paternity Leave', 7, 'Leave for new fathers')";
        $conn->query($insert_defaults);
    }
}

// Check if leaves table exists, if not create it
$check_leaves_table = "SHOW TABLES LIKE 'leaves'";
$leaves_exists = $conn->query($check_leaves_table);

if ($leaves_exists->num_rows == 0) {
    // Create leaves table
    $create_leaves = "CREATE TABLE leaves (
        leave_id INT AUTO_INCREMENT PRIMARY KEY,
        emp_id INT NOT NULL,
        leave_type_id INT NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        days INT NOT NULL,
        reason TEXT,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        comments TEXT,
        applied_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_on TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (emp_id) REFERENCES employees(emp_id) ON DELETE CASCADE,
        FOREIGN KEY (leave_type_id) REFERENCES leave_types(leave_type_id) ON DELETE CASCADE
    )";
    $conn->query($create_leaves);
}

// Initialize variables
$employee = $leave_type = $start_date = $end_date = $reason = '';
$error = $success = '';

// Get all active employees for dropdown
$emp_query = "SELECT emp_id, first_name, last_name FROM employees WHERE status = 'active' ORDER BY first_name, last_name";
$emp_result = $conn->query($emp_query);

// Get leave types for dropdown
$leave_types_query = "SELECT leave_type_id, leave_type, allowed_days FROM leave_types ORDER BY leave_type";
$leave_types_result = $conn->query($leave_types_query);

// Process leave application form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['apply_leave'])) {
    $emp_id = sanitizeInput($_POST['emp_id']);
    $leave_type_id = sanitizeInput($_POST['leave_type_id']);
    $start_date = sanitizeInput($_POST['start_date']);
    $end_date = sanitizeInput($_POST['end_date']);
    $reason = sanitizeInput($_POST['reason']);
    
    // Calculate number of days
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = $start->diff($end);
    $days = $interval->days + 1; // Include both start and end dates
    
    // Validate dates
    if ($start > $end) {
        $error = "End date cannot be before start date";
    } else {
        // Check if employee has enough leave balance
        $balance_query = "SELECT lt.allowed_days, 
                         COALESCE(SUM(l.days), 0) as used_days
                         FROM leave_types lt
                         LEFT JOIN leaves l ON lt.leave_type_id = l.leave_type_id 
                                          AND l.emp_id = ? 
                                          AND YEAR(l.start_date) = YEAR(CURRENT_DATE())
                                          AND l.status != 'rejected'
                         WHERE lt.leave_type_id = ?
                         GROUP BY lt.leave_type_id";
        $balance_stmt = $conn->prepare($balance_query);
        $balance_stmt->bind_param("ii", $emp_id, $leave_type_id);
        $balance_stmt->execute();
        $balance_result = $balance_stmt->get_result();
        $balance = $balance_result->fetch_assoc();
        
        $allowed_days = $balance['allowed_days'];
        $used_days = $balance['used_days'] ?? 0;
        $remaining_days = $allowed_days - $used_days;
        
        if ($days > $remaining_days) {
            $error = "Not enough leave balance. You have $remaining_days days remaining.";
        } else {
            // Insert leave application
            $insert_query = "INSERT INTO leaves (emp_id, leave_type_id, start_date, end_date, days, reason, status, applied_on) 
                           VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("iissds", $emp_id, $leave_type_id, $start_date, $end_date, $days, $reason);
            
            if ($insert_stmt->execute()) {
                $success = "Leave application submitted successfully!";
                // Clear form fields
                $employee = $leave_type = $start_date = $end_date = $reason = '';
            } else {
                $error = "Error submitting leave application: " . $conn->error;
            }
        }
    }
}

// Handle leave approval/rejection
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_leave'])) {
    $leave_id = sanitizeInput($_POST['leave_id']);
    $status = sanitizeInput($_POST['status']);
    $comments = isset($_POST['comments']) ? sanitizeInput($_POST['comments']) : '';
    
    $update_query = "UPDATE leaves SET status = ?, comments = ?, updated_on = NOW() WHERE leave_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ssi", $status, $comments, $leave_id);
    
    if ($update_stmt->execute()) {
        $success = "Leave application " . ucfirst($status) . " successfully!";
    } else {
        $error = "Error updating leave application: " . $conn->error;
    }
}

// Get all leave applications with employee and leave type details
$leaves_query = "SELECT l.*, e.first_name, e.last_name, lt.leave_type 
                FROM leaves l
                JOIN employees e ON l.emp_id = e.emp_id
                JOIN leave_types lt ON l.leave_type_id = lt.leave_type_id
                ORDER BY l.applied_on DESC";
$leaves_result = $conn->query($leaves_query);
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Display messages -->
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

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Leave Management</h1>
        <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addLeaveTypeModal">
            <i class="fas fa-plus fa-sm text-white-50"></i> Add Leave Type
        </a>
    </div>

    <div class="row">
        <!-- Apply Leave Form -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Apply for Leave</h6>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="mb-3">
                            <label for="emp_id" class="form-label">Employee</label>
                            <select class="form-control" id="emp_id" name="emp_id" required>
                                <option value="">Select Employee</option>
                                <?php if ($emp_result && $emp_result->num_rows > 0): ?>
                                    <?php while ($emp = $emp_result->fetch_assoc()): ?>
                                        <option value="<?php echo $emp['emp_id']; ?>"><?php echo $emp['first_name'] . ' ' . $emp['last_name']; ?></option>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <option disabled>No active employees found</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="leave_type_id" class="form-label">Leave Type</label>
                            <select class="form-control" id="leave_type_id" name="leave_type_id" required>
                                <option value="">Select Leave Type</option>
                                <?php if ($leave_types_result && $leave_types_result->num_rows > 0): ?>
                                    <?php while ($type = $leave_types_result->fetch_assoc()): ?>
                                        <option value="<?php echo $type['leave_type_id']; ?>"><?php echo $type['leave_type'] . ' (' . $type['allowed_days'] . ' days/year)'; ?></option>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <option disabled>No leave types found</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                        </div>
                        
                        <button type="submit" name="apply_leave" class="btn btn-primary">Submit Application</button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Leave Applications Table -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Leave Applications</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Leave Type</th>
                                    <th>Period</th>
                                    <th>Days</th>
                                    <th>Status</th>
                                    <th>Applied On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($leaves_result && $leaves_result->num_rows > 0): ?>
                                    <?php while ($leave = $leaves_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $leave['first_name'] . ' ' . $leave['last_name']; ?></td>
                                            <td><?php echo $leave['leave_type']; ?></td>
                                            <td><?php echo formatDate($leave['start_date']) . ' to ' . formatDate($leave['end_date']); ?></td>
                                            <td><?php echo $leave['days']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo ($leave['status'] == 'approved') ? 'success' : 
                                                        (($leave['status'] == 'rejected') ? 'danger' : 'warning'); 
                                                ?>">
                                                    <?php echo ucfirst($leave['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatDate($leave['applied_on']); ?></td>
                                            <td>
                                                <?php if ($leave['status'] == 'pending'): ?>
                                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#leaveModal<?php echo $leave['leave_id']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    
                                                    <!-- Leave Action Modal -->
                                                    <div class="modal fade" id="leaveModal<?php echo $leave['leave_id']; ?>" tabindex="-1" aria-labelledby="leaveModalLabel" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="leaveModalLabel">Update Leave Application</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="leave_id" value="<?php echo $leave['leave_id']; ?>">
                                                                        
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Employee</label>
                                                                            <p class="form-control-static"><?php echo $leave['first_name'] . ' ' . $leave['last_name']; ?></p>
                                                                        </div>
                                                                        
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Leave Type</label>
                                                                            <p class="form-control-static"><?php echo $leave['leave_type']; ?></p>
                                                                        </div>
                                                                        
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Period</label>
                                                                            <p class="form-control-static"><?php echo formatDate($leave['start_date']) . ' to ' . formatDate($leave['end_date']); ?> (<?php echo $leave['days']; ?> days)</p>
                                                                        </div>
                                                                        
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Reason</label>
                                                                            <p class="form-control-static"><?php echo $leave['reason']; ?></p>
                                                                        </div>
                                                                        
                                                                        <div class="mb-3">
                                                                            <label for="status" class="form-label">Status</label>
                                                                            <select class="form-control" id="status" name="status" required>
                                                                                <option value="approved">Approve</option>
                                                                                <option value="rejected">Reject</option>
                                                                            </select>
                                                                        </div>
                                                                        
                                                                        <div class="mb-3">
                                                                            <label for="comments" class="form-label">Comments</label>
                                                                            <textarea class="form-control" id="comments" name="comments" rows="3"></textarea>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                        <button type="submit" name="update_leave" class="btn btn-primary">Update</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewLeaveModal<?php echo $leave['leave_id']; ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    
                                                    <!-- View Leave Modal -->
                                                    <div class="modal fade" id="viewLeaveModal<?php echo $leave['leave_id']; ?>" tabindex="-1" aria-labelledby="viewLeaveModalLabel" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="viewLeaveModalLabel">Leave Application Details</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Employee</label>
                                                                        <p class="form-control-static"><?php echo $leave['first_name'] . ' ' . $leave['last_name']; ?></p>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Leave Type</label>
                                                                        <p class="form-control-static"><?php echo $leave['leave_type']; ?></p>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Period</label>
                                                                        <p class="form-control-static"><?php echo formatDate($leave['start_date']) . ' to ' . formatDate($leave['end_date']); ?> (<?php echo $leave['days']; ?> days)</p>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Reason</label>
                                                                        <p class="form-control-static"><?php echo $leave['reason']; ?></p>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Status</label>
                                                                        <p class="form-control-static">
                                                                            <span class="badge bg-<?php 
                                                                                echo ($leave['status'] == 'approved') ? 'success' : 'danger'; 
                                                                            ?>">
                                                                                <?php echo ucfirst($leave['status']); ?>
                                                                            </span>
                                                                        </p>
                                                                    </div>
                                                                    
                                                                    <?php if (!empty($leave['comments'])): ?>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Comments</label>
                                                                        <p class="form-control-static"><?php echo $leave['comments']; ?></p>
                                                                    </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No leave applications found</td>
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

<!-- Add Leave Type Modal -->
<div class="modal fade" id="addLeaveTypeModal" tabindex="-1" aria-labelledby="addLeaveTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addLeaveTypeModalLabel">Add New Leave Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="leave_type" class="form-label">Leave Type Name</label>
                        <input type="text" class="form-control" id="leave_type" name="leave_type" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="allowed_days" class="form-label">Allowed Days Per Year</label>
                        <input type="number" class="form-control" id="allowed_days" name="allowed_days" min="1" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_leave_type" class="btn btn-primary">Add Leave Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- /.container-fluid -->
<?php require_once '../includes/footer.php'; ?>