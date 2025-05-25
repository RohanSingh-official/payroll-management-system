<?php
require_once '../includes/header.php';

// Initialize variables
$success = $error = '';

// Get current company settings
$settings_query = "SELECT * FROM company_settings WHERE id = 1";
$settings_result = $conn->query($settings_query);

// Check if settings table exists, if not create it
if (!$settings_result) {
    // Create company_settings table
    $create_table = "CREATE TABLE IF NOT EXISTS company_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        company_name VARCHAR(100) NOT NULL,
        company_address TEXT,
        company_phone VARCHAR(20),
        company_email VARCHAR(100),
        company_website VARCHAR(100),
        tax_rate DECIMAL(5,2) DEFAULT 0,
        currency VARCHAR(10) DEFAULT 'INR',
        logo_path VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_table)) {
        // Insert default settings
        $insert_default = "INSERT INTO company_settings (
            company_name, company_address, company_phone, company_email, company_website, tax_rate, currency
        ) VALUES (
            'Your Company Name', 
            '123 Business Street, City, Country', 
            '+1 234 567 890', 
            'info@yourcompany.com', 
            'www.yourcompany.com',
            10.00,
            'INR'
        )";
        $conn->query($insert_default);
        
        // Refresh settings query
        $settings_result = $conn->query($settings_query);
    } else {
        $error = "Error creating settings table: " . $conn->error;
    }
}

// Get settings data
$settings = $settings_result ? $settings_result->fetch_assoc() : null;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_settings'])) {
    $company_name = sanitizeInput($_POST['company_name']);
    $company_address = sanitizeInput($_POST['company_address']);
    $company_phone = sanitizeInput($_POST['company_phone']);
    $company_email = sanitizeInput($_POST['company_email']);
    $company_website = sanitizeInput($_POST['company_website']);
    $tax_rate = floatval($_POST['tax_rate']);
    $currency = sanitizeInput($_POST['currency']);
    
    // Handle logo upload
    $logo_path = $settings['logo_path'] ?? '';
    if (isset($_FILES['company_logo']) && $_FILES['company_logo']['size'] > 0) {
        $target_dir = "../assets/images/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES["company_logo"]["name"], PATHINFO_EXTENSION));
        $new_filename = "company_logo_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Check if image file is a actual image
        $check = getimagesize($_FILES["company_logo"]["tmp_name"]);
        if ($check !== false) {
            // Check file size (limit to 2MB)
            if ($_FILES["company_logo"]["size"] <= 2000000) {
                // Allow certain file formats
                if (in_array($file_extension, ["jpg", "jpeg", "png", "gif"])) {
                    if (move_uploaded_file($_FILES["company_logo"]["tmp_name"], $target_file)) {
                        $logo_path = $target_file;
                    } else {
                        $error = "Sorry, there was an error uploading your file.";
                    }
                } else {
                    $error = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
                }
            } else {
                $error = "Sorry, your file is too large. Maximum size is 2MB.";
            }
        } else {
            $error = "File is not an image.";
        }
    }
    
    if (empty($error)) {
        // Update settings
        $update_query = "UPDATE company_settings SET 
                        company_name = ?, 
                        company_address = ?, 
                        company_phone = ?, 
                        company_email = ?, 
                        company_website = ?,
                        tax_rate = ?,
                        currency = ?,
                        logo_path = ?
                        WHERE id = 1";
        
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("sssssdss", 
            $company_name, 
            $company_address, 
            $company_phone, 
            $company_email, 
            $company_website,
            $tax_rate,
            $currency,
            $logo_path
        );
        
        if ($update_stmt->execute()) {
            $success = "Company settings updated successfully!";
            // Refresh settings
            $settings_result = $conn->query($settings_query);
            $settings = $settings_result->fetch_assoc();
        } else {
            $error = "Error updating settings: " . $conn->error;
        }
    }
}

// Handle system backup
if (isset($_POST['create_backup'])) {
    // Get database tables
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    
    $backup_file = 'payroll_backup_' . date('Y-m-d_H-i-s') . '.sql';
    $backup_dir = "../backups/";
    
    // Create backup directory if it doesn't exist
    if (!file_exists($backup_dir)) {
        mkdir($backup_dir, 0777, true);
    }
    
    $backup_path = $backup_dir . $backup_file;
    
    // Open backup file
    $handle = fopen($backup_path, 'w');
    
    // Add header
    fwrite($handle, "-- Payroll Management System Database Backup\n");
    fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n\n");
    
    // Export each table
    foreach ($tables as $table) {
        // Get create table statement
        $result = $conn->query("SHOW CREATE TABLE $table");
        $row = $result->fetch_row();
        fwrite($handle, "-- Table structure for table `$table`\n\n");
        fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");
        fwrite($handle, $row[1] . ";\n\n");
        
        // Get table data
        $result = $conn->query("SELECT * FROM $table");
        if ($result->num_rows > 0) {
            fwrite($handle, "-- Dumping data for table `$table`\n");
            fwrite($handle, "INSERT INTO `$table` VALUES\n");
            
            $first_row = true;
            while ($row = $result->fetch_row()) {
                if (!$first_row) {
                    fwrite($handle, ",\n");
                } else {
                    $first_row = false;
                }
                
                fwrite($handle, "(");
                for ($i = 0; $i < count($row); $i++) {
                    if ($i > 0) {
                        fwrite($handle, ",");
                    }
                    
                    if ($row[$i] === null) {
                        fwrite($handle, "NULL");
                    } else {
                        fwrite($handle, "'" . $conn->real_escape_string($row[$i]) . "'");
                    }
                }
                fwrite($handle, ")");
            }
            fwrite($handle, ";\n\n");
        }
    }
    
    fclose($handle);
    
    // Provide download link
    $success = "Database backup created successfully! <a href='../backups/$backup_file' download>Download Backup</a>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Payroll Management System</title>
    <!-- Include your CSS files here if needed -->
</head>
<body>
    <!-- Begin Page Content -->
    <div class="container-fluid">

        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">System Settings</h1>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Company Settings -->
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Company Settings</h6>
                    </div>
                    <div class="card-body">
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="company_name" class="form-label">Company Name</label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo $settings['company_name'] ?? ''; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="company_email" class="form-label">Company Email</label>
                                    <input type="email" class="form-control" id="company_email" name="company_email" value="<?php echo $settings['company_email'] ?? ''; ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="company_phone" class="form-label">Company Phone</label>
                                    <input type="text" class="form-control" id="company_phone" name="company_phone" value="<?php echo $settings['company_phone'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="company_website" class="form-label">Company Website</label>
                                    <input type="text" class="form-control" id="company_website" name="company_website" value="<?php echo $settings['company_website'] ?? ''; ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="company_address" class="form-label">Company Address</label>
                                <textarea class="form-control" id="company_address" name="company_address" rows="3"><?php echo $settings['company_address'] ?? ''; ?></textarea>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="tax_rate" class="form-label">Default Tax Rate (%)</label>
                                    <input type="number" class="form-control" id="tax_rate" name="tax_rate" min="0" max="100" step="0.01" value="<?php echo $settings['tax_rate'] ?? '0.00'; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="currency" class="form-label">Currency</label>
                                    <select class="form-control" id="currency" name="currency">
                                        <option value="INR" <?php echo (isset($settings['currency']) && $settings['currency'] == 'INR') ? 'selected' : ''; ?>>INR - Indian Rupee</option>
                                        <option value="USD" <?php echo (isset($settings['currency']) && $settings['currency'] == 'USD') ? 'selected' : ''; ?>>USD - US Dollar</option>
                                        <option value="EUR" <?php echo (isset($settings['currency']) && $settings['currency'] == 'EUR') ? 'selected' : ''; ?>>EUR - Euro</option>
                                        <option value="GBP" <?php echo (isset($settings['currency']) && $settings['currency'] == 'GBP') ? 'selected' : ''; ?>>GBP - British Pound</option>
                                        <option value="JPY" <?php echo (isset($settings['currency']) && $settings['currency'] == 'JPY') ? 'selected' : ''; ?>>JPY - Japanese Yen</option>
                                        <option value="CAD" <?php echo (isset($settings['currency']) && $settings['currency'] == 'CAD') ? 'selected' : ''; ?>>CAD - Canadian Dollar</option>
                                        <option value="AUD" <?php echo (isset($settings['currency']) && $settings['currency'] == 'AUD') ? 'selected' : ''; ?>>AUD - Australian Dollar</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="company_logo" class="form-label">Company Logo</label>
                                <?php if (!empty($settings['logo_path'])): ?>
                                    <div class="mb-2">
                                        <img src="<?php echo $settings['logo_path']; ?>" alt="Company Logo" style="max-height: 100px;" class="img-thumbnail">
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" id="company_logo" name="company_logo">
                                <small class="text-muted">Recommended size: 200x200 pixels. Max file size: 2MB.</small>
                            </div>
                            
                            <button type="submit" name="update_settings" class="btn btn-primary">Save Settings</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- System Tools -->
            <div class="col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">System Tools</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h5>Database Backup</h5>
                            <p>Create a backup of your database. This will export all tables and data.</p>
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                <button type="submit" name="create_backup" class="btn btn-primary">Create Backup</button>
                            </form>
                        </div>
                        
                        <div class="mb-4">
                            <h5>System Information</h5>
                            <table class="table table-sm">
                                <tr>
                                    <td>PHP Version</td>
                                    <td><?php echo phpversion(); ?></td>
                                </tr>
                                <tr>
                                    <td>MySQL Version</td>
                                    <td><?php echo $conn->server_info; ?></td>
                                </tr>
                                <tr>
                                    <td>Server</td>
                                    <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">User Settings</h6>
                    </div>
                    <div class="card-body">
                        <p>Manage your account settings and change your password.</p>
                        <a href="profile.php" class="btn btn-primary">Go to Profile</a>
                        <!-- Logout button removed -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /.container-fluid -->
</body>
</html>

<?php require_once '../includes/footer.php'; ?>