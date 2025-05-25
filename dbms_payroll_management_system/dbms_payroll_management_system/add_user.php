<?php
require_once 'includes/config.php';

// New user details
$username = 'rohan';
$password = 'rohan503';
$email = 'rohan@example.com';
$full_name = 'Rohan User';
$role = 'admin';

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Check if user already exists
$check_sql = "SELECT user_id FROM users WHERE username = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $username);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows > 0) {
    echo "User '$username' already exists. Updating password...<br>";
    
    // Update existing user's password
    $update_sql = "UPDATE users SET password = ? WHERE username = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ss", $hashed_password, $username);
    
    if ($update_stmt->execute()) {
        echo "Password updated successfully for user '$username'!<br>";
    } else {
        echo "Error updating password: " . $conn->error . "<br>";
    }
    
    $update_stmt->close();
} else {
    // Insert new user
    $insert_sql = "INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("sssss", $username, $hashed_password, $email, $full_name, $role);
    
    if ($insert_stmt->execute()) {
        echo "New user '$username' created successfully!<br>";
    } else {
        echo "Error creating user: " . $conn->error . "<br>";
    }
    
    $insert_stmt->close();
}

$check_stmt->close();
$conn->close();

echo "<br>You can now login with:<br>";
echo "Username: $username<br>";
echo "Password: $password<br>";
echo "<br><a href='index.php'>Go to Login Page</a>";
?>