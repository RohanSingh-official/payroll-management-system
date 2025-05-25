<?php
require_once __DIR__ . '/config.php';
requireLogin();

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
        }
        
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fc;
        }
        
        /* Sidebar */
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #4e73df 10%, #224abe 100%);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            z-index: 1;
            position: fixed;
            width: 250px;
            transition: all 0.3s;
        }
        
        .sidebar-brand {
            height: 4.375rem;
            text-decoration: none;
            font-size: 1.2rem;
            font-weight: 800;
            padding: 1.5rem 1rem;
            text-align: center;
            letter-spacing: 0.05rem;
            z-index: 1;
        }
        
        .sidebar-brand-icon {
            font-size: 2rem;
        }
        
        .sidebar-brand-text {
            display: inline;
            color: white;
        }
        
        .sidebar hr {
            margin: 0 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        .sidebar .nav-item {
            position: relative;
        }
        
        .sidebar .nav-item .nav-link {
            display: block;
            width: 100%;
            text-align: left;
            padding: 0.75rem 1rem;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .sidebar .nav-item .nav-link i {
            margin-right: 0.25rem;
            font-size: 0.85rem;
        }
        
        .sidebar .nav-item .nav-link span {
            font-size: 0.85rem;
            display: inline;
        }
        
        .sidebar .nav-item .nav-link:hover,
        .sidebar .nav-item .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        /* Content */
        .content-wrapper {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        /* Topbar */
        .topbar {
            height: 4.375rem;
            background-color: white;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .topbar .navbar-search {
            width: 25rem;
        }
        
        .topbar .navbar-search input {
            font-size: 0.85rem;
            height: auto;
        }
        
        .topbar .topbar-divider {
            width: 0;
            border-right: 1px solid #e3e6f0;
            height: calc(4.375rem - 2rem);
            margin: auto 1rem;
        }
        
        .topbar .nav-item .nav-link {
            height: 4.375rem;
            display: flex;
            align-items: center;
            padding: 0 0.75rem;
        }
        
        .topbar .nav-item .nav-link .badge-counter {
            position: absolute;
            transform: scale(0.7);
            transform-origin: top right;
            right: 0.25rem;
            margin-top: -0.25rem;
        }
        
        .topbar .dropdown-list {
            padding: 0;
            border: none;
            overflow: hidden;
        }
        
        .topbar .dropdown-list .dropdown-header {
            background-color: #4e73df;
            border: 1px solid #4e73df;
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
            color: white;
        }
        
        /* Cards */
        .card {
            margin-bottom: 24px;
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }
        
        .card-body {
            flex: 1 1 auto;
            min-height: 1px;
            padding: 1.25rem;
        }
        
        /* Dashboard Cards */
        .card-stats .card-body {
            padding: 1rem;
        }
        
        .card-stats .card-body .icon {
            font-size: 2rem;
            position: absolute;
            right: 20px;
            top: 15px;
            opacity: 0.3;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 100px;
            }
            
            .sidebar .nav-item .nav-link span,
            .sidebar-brand-text {
                display: none;
            }
            
            .sidebar .nav-item .nav-link i {
                font-size: 1.2rem;
                margin-right: 0;
            }
            
            .content-wrapper {
                margin-left: 100px;
            }
        }
        
        /* Toggle Sidebar */
        .sidebar-toggled .sidebar {
            width: 100px;
        }
        
        .sidebar-toggled .sidebar .nav-item .nav-link span,
        .sidebar-toggled .sidebar-brand-text {
            display: none;
        }
        
        .sidebar-toggled .sidebar .nav-item .nav-link i {
            font-size: 1.2rem;
            margin-right: 0;
        }
        
        .sidebar-toggled .content-wrapper {
            margin-left: 100px;
        }
        
        /* DataTables */
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.25rem 0.5rem;
            margin-left: 0.25rem;
            border-radius: 0.25rem;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
            <div class="sidebar-brand-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="sidebar-brand-text mx-3">Payroll MS</div>
        </a>
        
        <hr class="sidebar-divider my-0">
        
        <!-- Nav Item - Dashboard -->
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <hr class="sidebar-divider">
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'employees.php') ? 'active' : ''; ?>" href="employees.php">
                    <i class="fas fa-fw fa-users"></i>
                    <span>Employees</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'departments.php') ? 'active' : ''; ?>" href="departments.php">
                    <i class="fas fa-fw fa-building"></i>
                    <span>Departments</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'attendance.php') ? 'active' : ''; ?>" href="attendance.php">
                    <i class="fas fa-fw fa-calendar-check"></i>
                    <span>Attendance</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'leave.php') ? 'active' : ''; ?>" href="leave.php">
                    <i class="fas fa-fw fa-calendar-minus"></i>
                    <span>Leave Management</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'payroll.php') ? 'active' : ''; ?>" href="payroll.php">
                    <i class="fas fa-fw fa-money-check-alt"></i>
                    <span>Payroll</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>" href="reports.php">
                    <i class="fas fa-fw fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </li>
            
            <hr class="sidebar-divider">
            
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>" href="settings.php">
                    <i class="fas fa-fw fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <!-- Topbar -->
        <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
            <!-- Sidebar Toggle (Topbar) -->
            <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                <i class="fa fa-bars"></i>
            </button>
            
            <!-- Topbar Search -->
            <form class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search">
                <div class="input-group">
                    <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..." aria-label="Search" aria-describedby="basic-addon2">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="button">
                            <i class="fas fa-search fa-sm"></i>
                        </button>
                    </div>
                </div>
            </form>
            
            <!-- Topbar Navbar -->
            <ul class="navbar-nav ml-auto">
                <div class="topbar-divider d-none d-sm-block"></div>
                
                <img class="img-profile rounded-circle" src="../assets/images/user.png">
                                    </a>
                                    <!-- Dropdown - User Information -->
                                    <div class="dropdown-menu dropdown-menu-end shadow animated--grow-in" aria-labelledby="userDropdown">
                                        <a class="dropdown-item" href="profile.php">
                                            <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                            Profile
                                        </a>
                                        <a class="dropdown-item" href="settings.php">
                                            <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                                            Settings
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
                                            <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                            Logout
                                        </a>
                                    </div>
                                </li>
                            </ul>
                        </nav>
                        <!-- End of Topbar -->
        <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo $_SESSION['full_name']; ?></span>
        <img class="img-profile rounded-