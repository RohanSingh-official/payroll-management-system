<?php
require_once '../includes/header.php';

// Handle department deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $dept_id = intval($_GET['delete']);
    
    // Check if department has employees
    $check_query = "SELECT COUNT(*) as emp_count FROM employees WHERE dept_id = ?";
    if ($stmt = $conn->prepare($check_query)) {
        $stmt->bind_param("i", $dept_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['emp_count'] > 0) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    Cannot delete department. There are employees assigned to this department.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
        } else {
            // Delete department
            $delete_query = "DELETE FROM departments WHERE dept_id = ?";
            if ($delete_stmt = $conn->prepare($delete_query)) {
                $delete_stmt->bind_param("i", $dept_id);
                
                if ($delete_stmt->execute()) {
                    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            Department deleted successfully!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                          </div>';
                } else {
                    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            Error deleting department. Please try again.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                          </div>';
                }
                
                $delete_stmt->close();
            }
        }
        
        $stmt->close();
    }
}

// Handle department addition/update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dept_name = sanitizeInput($_POST['dept_name']);
    $dept_head = sanitizeInput($_POST['dept_head']);
    $description = sanitizeInput($_POST['description']);
    
    if (isset($_POST['dept_id']) && !empty($_POST['dept_id'])) {
        // Update existing department
        $dept_id = intval($_POST['dept_id']);
        $update_query = "UPDATE departments SET dept_name = ?, dept_head = ?, description = ? WHERE dept_id = ?";
        
        if ($stmt = $conn->prepare($update_query)) {
            $stmt->bind_param("sssi", $dept_name, $dept_head, $description, $dept_id);
            
            if ($stmt->execute()) {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                        Department updated successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                      </div>';
            } else {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        Error updating department. Please try again.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                      </div>';
            }
            
            $stmt->close();
        }
    } else {
        // Add new department
        $insert_query = "INSERT INTO departments (dept_name, dept_head, description) VALUES (?, ?, ?)";
        
        if ($stmt = $conn->prepare($insert_query)) {
            $stmt->bind_param("sss", $dept_name, $dept_head, $description);
            
            if ($stmt->execute()) {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                        Department added successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                      </div>';
            } else {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        Error adding department. Please try again.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                      </div>';
            }
            
            $stmt->close();
        }
    }
}

// Get all departments with employee count
$query = "SELECT d.*, COUNT(e.emp_id) as employee_count 
          FROM departments d 
          LEFT JOIN employees e ON d.dept_id = e.dept_id 
          GROUP BY d.dept_id 
          ORDER BY d.dept_name";
$result = $conn->query($query);
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Departments Management</h1>
        <button class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
            <i class="fas fa-plus fa-sm text-white-50"></i> Add New Department
        </button>
    </div>

    <!-- Departments Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Departments List</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Department Name</th>
                            <th>Department Head</th>
                            <th>Description</th>
                            <th>Employees</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['dept_id']; ?></td>
                                    <td><?php echo $row['dept_name']; ?></td>
                                    <td><?php echo $row['dept_head']; ?></td>
                                    <td><?php echo $row['description']; ?></td>
                                    <td><?php echo $row['employee_count']; ?></td>
                                    <td>
                                        <button class="btn btn-primary btn-sm edit-btn" 
                                                data-id="<?php echo $row['dept_id']; ?>"
                                                data-name="<?php echo $row['dept_name']; ?>"
                                                data-head="<?php echo $row['dept_head']; ?>"
                                                data-desc="<?php echo $row['description']; ?>"
                                                data-bs-toggle="modal" data-bs-target="#editDepartmentModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="#" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteDeptModal<?php echo $row['dept_id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        
                                        <!-- Delete Modal -->
                                        <div class="modal fade" id="deleteDeptModal<?php echo $row['dept_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                                                        <button class="close" type="button" data-bs-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">×</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Are you sure you want to delete department: <strong><?php echo $row['dept_name']; ?></strong>?
                                                        <br>This action cannot be undone.
                                                        <?php if ($row['employee_count'] > 0): ?>
                                                            <br><br><span class="text-danger">Warning: This department has <?php echo $row['employee_count']; ?> employee(s) assigned to it.</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                                                        <a class="btn btn-danger" href="departments.php?delete=<?php echo $row['dept_id']; ?>">Delete</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No departments found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->

<!-- Add Department Modal -->
<div class="modal fade" id="addDepartmentModal" tabindex="-1" role="dialog" aria-labelledby="addDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDepartmentModalLabel">Add New Department</h5>
                <button class="close" type="button" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="dept_name" class="form-label">Department Name</label>
                        <input type="text" class="form-control" id="dept_name" name="dept_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="dept_head" class="form-label">Department Head</label>
                        <input type="text" class="form-control" id="dept_head" name="dept_head">
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Department Modal -->
<div class="modal fade" id="editDepartmentModal" tabindex="-1" role="dialog" aria-labelledby="editDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDepartmentModalLabel">Edit Department</h5>
                <button class="close" type="button" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <input type="hidden" id="edit_dept_id" name="dept_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_dept_name" class="form-label">Department Name</label>
                        <input type="text" class="form-control" id="edit_dept_name" name="dept_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_dept_head" class="form-label">Department Head</label>
                        <input type="text" class="form-control" id="edit_dept_head" name="dept_head">
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Handle edit department modal data
    document.addEventListener('DOMContentLoaded', function() {
        const editButtons = document.querySelectorAll('.edit-btn');
        
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const head = this.getAttribute('data-head');
                const desc = this.getAttribute('data-desc');
                
                document.getElementById('edit_dept_id').value = id;
                document.getElementById('edit_dept_name').value = name;
                document.getElementById('edit_dept_head').value = head;
                document.getElementById('edit_description').value = desc;
            });
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>