<?php
require_once '../includes/header.php';

// Initialize variables
$first_name = $last_name = $email = $phone = $address = '';
$dept_id = $position = $hire_date = $basic_salary = $bank_account = '';
$error = '';
$success = '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $address = sanitizeInput($_POST['address']);
    $dept_id = sanitizeInput($_POST['dept_id']);
    $position = sanitizeInput($_POST['position']);
    $hire_date = sanitizeInput($_POST['hire_date']);
    $basic_salary = sanitizeInput($_POST['basic_salary']);
    $bank_account = sanitizeInput($_POST['bank_account']);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        // Check if email already exists
        $check_email = "SELECT emp_id FROM employees WHERE email = ?";
        if ($stmt = $conn->prepare($check_email)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $error = "Email already exists. Please use a different email.";
            }
            $stmt->close();
        }
    }
    
    // If no errors, insert employee
    if (empty($error)) {
        $insert_query = "INSERT INTO employees (first_name, last_name, email, phone, address, dept_id, position, hire_date, basic_salary, bank_account) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($insert_query)) {
            $stmt->bind_param("sssssissds", $first_name, $last_name, $email, $phone, $address, $dept_id, $position, $hire_date, $basic_salary, $bank_account);
            
            if ($stmt->execute()) {
                $success = "Employee added successfully!";
                // Clear form fields
                $first_name = $last_name = $email = $phone = $address = '';
                $dept_id = $position = $hire_date = $basic_salary = $bank_account = '';
            } else {
                $error = "Error: " . $stmt->error;
            }
            
            $stmt->close();
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

// Get departments for dropdown
$dept_query = "SELECT dept_id, dept_name FROM departments ORDER BY dept_name";
$dept_result = $conn->query($dept_query);
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Add New Employee</h1>
        <a href="employees.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Employees
        </a>
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

    <!-- Employee Form -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Employee Information</h6>
        </div>
        <div class="card-body">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo $first_name; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo $last_name; ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $phone; ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3"><?php echo $address; ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="dept_id" class="form-label">Department</label>
                        <select class="form-select" id="dept_id" name="dept_id" required>
                            <option value="">Select Department</option>
                            <?php while ($dept = $dept_result->fetch_assoc()): ?>
                                <option value="<?php echo $dept['dept_id']; ?>" <?php if ($dept_id == $dept['dept_id']) echo 'selected'; ?>>
                                    <?php echo $dept['dept_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="position" class="form-label">Position</label>
                        <input type="text" class="form-control" id="position" name="position" value="<?php echo $position; ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="hire_date" class="form-label">Hire Date</label>
                        <input type="date" class="form-control" id="hire_date" name="hire_date" value="<?php echo $hire_date; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="basic_salary" class="form-label">Basic Salary</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" step="0.01" class="form-control" id="basic_salary" name="basic_salary" value="<?php echo $basic_salary; ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="bank_account" class="form-label">Bank Account</label>
                    <input type="text" class="form-control" id="bank_account" name="bank_account" value="<?php echo $bank_account; ?>">
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Add Employee</button>
                    <a href="employees.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /.container-fluid -->

<?php require_once '../includes/footer.php'; ?>