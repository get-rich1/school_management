<?php
// admin/dashboard.php
session_start();

require_once dirname(__DIR__) . '/config.php';


// Redirect if not logged in or not an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login/login.php');
    exit();
}

// Fetch admin's name and profile picture from the database
$admin_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) AS full_name, profile_picture FROM users WHERE user_id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$stmt->bind_result($admin_name, $profile_picture);
$stmt->fetch();
$stmt->close();

// Fetch statistics from the database
$total_students = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetch_row()[0];
$total_teachers = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'")->fetch_row()[0];
$total_classes = $conn->query("SELECT COUNT(*) FROM classes")->fetch_row()[0];
$total_fees_collected = $conn->query("SELECT SUM(amount) FROM payments")->fetch_row()[0] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- Local Bootstrap CSS -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <!-- Local FontAwesome CSS -->
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <!-- Local DataTables CSS -->
    <link rel="stylesheet" href="assets/datatables/css/dataTables.bootstrap.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Admin Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <!-- Notification Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell"></i>
                            <span class="badge bg-danger">3</span> <!-- Example notification badge -->
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                            <li><a class="dropdown-item" href="#">New student registered</a></li>
                            <li><a class="dropdown-item" href="#">Fee payment received</a></li>
                            <li><a class="dropdown-item" href="#">Exam schedule updated</a></li>
                        </ul>
                    </li>
                    <!-- Profile Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php if (!empty($profile_picture)) { ?>
                                <img src="<?php echo $profile_picture; ?>" alt="Profile Picture" class="rounded-circle me-2" style="width: 30px; height: 30px;">
                            <?php } else { ?>
                                <i class="fas fa-user-circle me-2"></i>
                            <?php } ?>
                            <?php echo htmlspecialchars($admin_name); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                            <li><a class="dropdown-item" href="edit_profile.php"><i class="fas fa-user-cog me-2"></i> Edit Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../includes/sidebar.php'; ?>

            <!-- Main Dashboard -->
            <div class="col-md-9 main-content">
                <h2>Welcome, <?php echo htmlspecialchars($admin_name); ?>!</h2>
                <div class="row mt-4">
                    <!-- Statistics Cards -->
                    <div class="col-md-3">
                        <div class="card text-white bg-primary mb-3 hover-shadow">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-user-graduate"></i> Students</h5>
                                <p class="card-text display-6"><?php echo $total_students; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success mb-3 hover-shadow">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-chalkboard-teacher"></i> Teachers</h5>
                                <p class="card-text display-6"><?php echo $total_teachers; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning mb-3 hover-shadow">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-school"></i> Classes</h5>
                                <p class="card-text display-6"><?php echo $total_classes; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-danger mb-3 hover-shadow">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-money-bill-wave"></i> Fees Collected</h5>
                                <p class="card-text display-6">$<?php echo number_format($total_fees_collected, 2); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Student Enrollment</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="studentEnrollmentChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Fee Collection</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="feeCollectionChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Recent Students</h5>
                            </div>
                            <div class="card-body">
                                <table id="studentsTable" class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Fetch recent students from the database
                                        $result = $conn->query("SELECT user_id, CONCAT(first_name, ' ', last_name) AS full_name, email FROM users WHERE role = 'student' ORDER BY user_id DESC LIMIT 10");
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>
                                                    <td>{$row['user_id']}</td>
                                                    <td>{$row['full_name']}</td>
                                                    <td>{$row['email']}</td>
                                                </tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Local jQuery -->
    <script src="assets/js/jquery.min.js"></script>
    <!-- Local Bootstrap JS -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <!-- Local DataTables JS -->
    <script src="assets/datatables/js/jquery.dataTables.min.js"></script>
    <script src="assets/datatables/js/dataTables.bootstrap5.min.js"></script>
    <!-- Local Chart.js -->
    <script src="assets/js/chart.min.js"></script>
    <!-- Custom JS -->
    <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('#studentsTable').DataTable();
        });

        // Chart.js - Student Enrollment
        const studentEnrollmentChart = new Chart(document.getElementById('studentEnrollmentChart'), {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Students Enrolled',
                    data: [30, 40, 50, 60, 70, 80],
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Student Enrollment Over Time'
                    }
                }
            }
        });

        // Chart.js - Fee Collection
        const feeCollectionChart = new Chart(document.getElementById('feeCollectionChart'), {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Fees Collected',
                    data: [5000, 7000, 9000, 12000, 15000, 18000],
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Fee Collection Over Time'
                    }
                }
            }
        });
    </script>
</body>
</html>