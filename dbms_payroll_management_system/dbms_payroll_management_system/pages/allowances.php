<?php
require_once '../includes/header.php';

// Initialize variables
$success = $error = '';
$emp_id = isset($_GET['emp_id']) ? intval($_GET['emp_id']) : 0;

// Get employee details if emp_id is provided
$employee = null;
if ($emp_id > 0) {
    $emp_query = "SELECT e.*, d.dept_name 
                 FROM employees e 
                 LEFT JOIN departments d ON e.dept_id = d.dept_id 
                 WHERE e.emp_id = ?";
    $emp_stmt = $conn->prepare($emp_query);
    $emp_stmt->bind_param("i", $emp_id);
    $emp_stmt->execute();
    $employee = $emp_stmt->get_result()->fetch_assoc();
    
    if (!$employee) {
        $error = "Employee not found.";
    }
}

// Handle add allowance
if (isset($_POST['add_allowance'])) {
    $emp_id = sanitizeInput($_POST['emp_id']);
    $allowance_type = sanitizeInput($_POST['allowance_type']);
    $amount = floatval($_POST['amount']);
    $effective_date = sanitizeInput($_POST['effective_date']);
    $description = sanitizeInput($_POST['description']);
    
    $insert_query = "INSERT INTO employee_allowances (emp_id, allowance_type, amount, effective_date, description) 
                    VALUES (?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("isdss", $emp_id, $allowance_type, $amount, $effective_date, $description);
    
    if ($insert_stmt->execute()) {
        $success = "Allowance added successfully!";
    } else {
        $error = "Error adding allowance: " . $conn->error;
    }
}

// Handle add deduction
if (isset($_POST['add_deduction'])) {
    $emp_id = sanitizeInput($_POST['emp_id']);
    $deduction_type = sanitizeInput($_POST['deduction_type']);
    $amount = floatval($_POST['amount']);
    $effective_date = sanitizeInput($_POST['effective_date']);
    $description = sanitizeInput($_POST['description']);
    
    $insert_query = "INSERT INTO employee_deductions (emp_id, deduction_type, amount, effective_date, description) 
                    VALUES (?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("isdss", $emp_id, $deduction_type, $amount, $effective_date, $description);
    
    if ($insert_stmt->execute()) {
        $success = "Deduction added successfully!";
    } else {
        $error = "Error adding deduction: " . $conn->error;
    }
}

// Handle delete allowance
if (isset($_POST['delete_allowance'])) {
    $allowance_id = intval($_POST['allowance_id']);
    
    $delete_query = "DELETE FROM employee_allowances WHERE allowance_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $allowance_id);
    
    if ($delete_stmt->execute()) {
        $success = "Allowance deleted successfully!";
    } else {
        $error = "Error deleting allowance: " . $conn->error;
    }
}

// Handle delete deduction
if (isset($_POST['delete_deduction'])) {
    $deduction_id = intval($_POST['deduction_id']);
    
    $delete_query = "DELETE FROM employee_deductions WHERE deduction_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $deduction_id);
    
    if ($delete_stmt->execute()) {
        $success = "Deduction deleted successfully!";
    } else {
        $error = "Error deleting deduction: " . $conn->error;
    }
}

// Get all employees for dropdown
$employees_query = "SELECT emp_id, first_name, last_name FROM employees WHERE status = 'active' ORDER BY first_name, last_name";
$employees_result = $conn->query($employees_query);

// Get allowances for the selected employee
$allowances = [];
if ($emp_id > 0) {
    $allowances_query = "SELECT * FROM employee_allowances WHERE emp_id = ? ORDER BY effective_date DESC";
    $allowances_stmt = $conn->prepare($allowances_query);
    $allowances_stmt->bind_param("i", $emp_id);
    $allowances_stmt->execute();
    $allowances_result = $allowances_stmt->get_result();
    
    while ($row = $allowances_result->fetch_assoc()) {
        $allowances[] = $row;
    }
}

// Get deductions for the selected employee
$deductions = [];
if ($emp_id > 0) {
    $deductions_query = "SELECT * FROM employee_deductions WHERE emp_id = ? ORDER BY effective_date DESC";
    $deductions_stmt = $conn->prepare($deductions_query);
    $deductions_stmt->bind_param("i", $emp_id);
    $deductions_stmt->execute();
    $deductions_result = $deductions_stmt->get_result();
    
    while ($row = $deductions_result->fetch_assoc()) {
        $deductions[] = $row;
    }
}

// Calculate totals
$total_allowances = 0;
foreach ($allowances as $allowance) {
    $total_allowances += $allowance['amount'];
}

$total_deductions = 0;
foreach ($deductions as $deduction) {
    $total_deductions += $deduction['amount'];
}
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <?php echo $employee ? 'Allowances & Deductions: ' . $employee['first_name'] . ' ' . $employee['last_name'] : 'Allowances & Deductions'; ?>
        </h1>
        <?php if ($employee): ?>
            <a href="employee.php?id=<?php echo $emp_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Employee
            </a>
        <?php endif; ?>
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

    <!-- Employee Selection Form -->
    <?php if (!$employee): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Select Employee</h6>
        </div>
        <div class="card-body">
            <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="form-inline">
                <div class="form-group mb-2 mr-2">
                    <label for="emp_id" class="sr-only">Employee</label>
                    <select class="form-select" id="emp_id" name="emp_id" required>
                        <option value="">Select Employee</option>
                        <?php while ($emp = $employees_result->fetch_assoc()): ?>
                            <option value="<?php echo $emp['emp_id']; ?>">
                                <?php echo $emp['first_name'] . ' ' . $emp['last_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary mb-2">View</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($employee): ?>
    <!-- Employee Details -->
    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Employee</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?>
                            </div>
                            <div class="text-xs text-gray-600 mt-1">
                                <?php echo $employee['position']; ?> | <?php echo $employee['dept_name']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Allowances</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo formatCurrency($total_allowances); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-plus-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Total Deductions</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo formatCurrency($total_deductions); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-minus-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Allowances Section -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Allowances</h6>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAllowanceModal">
                        <i class="fas fa-plus fa-sm"></i> Add Allowance
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Effective Date</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($allowances) > 0): ?>
                                    <?php foreach ($allowances as $allowance): ?>
                                        <tr>
                                            <td><?php echo ucfirst($allowance['allowance_type']); ?></td>
                                            <td><?php echo formatCurrency($allowance['amount']); ?></td>
                                            <td><?php echo formatDate($allowance['effective_date']); ?></td>
                                            <td><?php echo $allowance['description']; ?></td>
                                            <td>
                                                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?emp_id=' . $emp_id; ?>" style="display: inline;">
                                                    <input type="hidden" name="allowance_id" value="<?php echo $allowance['allowance_id']; ?>">
                                                    <button type="submit" name="delete_allowance" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this allowance?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No allowances found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Deductions Section -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Deductions</h6>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addDeductionModal">
                        <i class="fas fa-plus fa-sm"></i> Add Deduction
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Effective Date</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($deductions) > 0): ?>
                                    <?php foreach ($deductions as $deduction): ?>
                                        <tr>
                                            <td><?php echo ucfirst($deduction['deduction_type']); ?></td>
                                            <td><?php echo formatCurrency($deduction['amount']); ?></td>
                                            <td><?php echo formatDate($deduction['effective_date']); ?></td>
                                            <td><?php echo $deduction['description']; ?></td>
                                            <td>
                                                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?emp_id=' . $emp_id; ?>" style="display: inline;">
                                                    <input type="hidden" name="deduction_id" value="<?php echo $deduction['deduction_id']; ?>">
                                                    <button type="submit" name="delete_deduction" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this deduction?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No deductions found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<!-- /.container-fluid -->

<!-- Add Allowance Modal -->
<div class="modal fade" id="addAllowanceModal" tabindex="-1" role="dialog" aria-labelledby="addAllowanceModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAllowanceModalLabel">Add Allowance</h5>
                <button class="close" type="button" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?emp_id=' . $emp_id; ?>">
                <div class="modal-body">
                    <input type="hidden" name="emp_id" value="<?php echo $emp_id; ?>">
                    
                    <div class="mb-3">
                        <label for="allowance_type" class="form-label">Allowance Type</label>
                        <select class="form-select" id="allowance_type" name="allowance_type" required>
                            <option value="">Select Type</option>
                            <option value="housing">Housing Allowance</option>
                            <option value="transport">Transport Allowance</option>
                            <option value="medical">Medical Allowance</option>
                            <option value="meal">Meal Allowance</option>
                            <option value="bonus">Bonus</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount</label>
                        <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="effective_date" class="form-label">Effective Date</label>
                        <input type="date" class="form-control" id="effective_date" name="effective_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_allowance" class="btn btn-primary">Add Allowance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Deduction Modal -->
<div class="modal fade" id="addDeductionModal" tabindex="-1" role="dialog" aria-labelledby="addDeductionModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDeductionModalLabel">Add Deduction</h5>
                <button class="close" type="button" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?emp_id=' . $emp_id; ?>">
                <div class="modal-body">
                    <input type="hidden" name="emp_id" value="<?php echo $emp_id; ?>">
                    
                    <div class="mb-3">
                        <label for="deduction_type" class="form-label">Deduction Type</label>
                        <select class="form-select" id="deduction_type" name="deduction_type" required>
                            <option value="">Select Type</option>
                            <option value="tax">Tax</option>
                            <option value="insurance">Insurance</option>
                            <option value="loan">Loan Repayment</option>
                            <option value="advance">Salary Advance</option>
                            <option value="pension">Pension</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount</label>
                        <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="effective_date" class="form-label">Effective Date</label>
                        <input type="date" class="form-control" id="effective_date" name="effective_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_deduction" class="btn btn-primary">Add Deduction</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>