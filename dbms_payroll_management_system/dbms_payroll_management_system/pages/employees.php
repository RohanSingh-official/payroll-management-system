<?php
require_once '../includes/header.php';

// Handle employee deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $emp_id = intval($_GET['delete']);
    $delete_query = "DELETE FROM employees WHERE emp_id = ?";
    
    if ($stmt = $conn->prepare($delete_query)) {
        $stmt->bind_param("i", $emp_id);
        
        if ($stmt->execute()) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    Employee deleted successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
        } else {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    Error deleting employee. Please try again.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
        }
        
        $stmt->close();
    }
}

// Get all employees with department names
$query = "SELECT e.*, d.dept_name 
          FROM employees e 
          LEFT JOIN departments d ON e.dept_id = d.dept_id 
          ORDER BY e.emp_id DESC";
$result = $conn->query($query);
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Employees Management</h1>
        <a href="add_employee.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Add New Employee
        </a>
    </div>

    <!-- Employees Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Employees List</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Hire Date</th>
                            <th>Salary</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['emp_id']; ?></td>
                                    <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                                    <td><?php echo $row['email']; ?></td>
                                    <td><?php echo $row['dept_name']; ?></td>
                                    <td><?php echo $row['position']; ?></td>
                                    <td><?php echo formatDate($row['hire_date']); ?></td>
                                    <td><?php echo formatCurrency($row['basic_salary']); ?></td>
                                    <td>
                                        <?php if ($row['status'] == 'active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php elseif ($row['status'] == 'inactive'): ?>
                                            <span class="badge bg-danger">Inactive</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">On Leave</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="view_employee.php?id=<?php echo $row['emp_id']; ?>" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_employee.php?id=<?php echo $row['emp_id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="#" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $row['emp_id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        
                                        <!-- Delete Modal -->
                                        <div class="modal fade" id="deleteModal<?php echo $row['emp_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                                                        <button class="close" type="button" data-bs-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">×</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Are you sure you want to delete employee: <strong><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></strong>?
                                                        <br>This action cannot be undone.
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                                                        <a class="btn btn-danger" href="employees.php?delete=<?php echo $row['emp_id']; ?>">Delete</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">No employees found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->

<?php require_once '../includes/footer.php'; ?>