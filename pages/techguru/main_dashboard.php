<?php 
    require_once '../../backends/config.php';
    require_once ROOT_PATH . '/backends/main.php';
    
    // Get student data with pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $items_per_page = 8;
    $offset = ($page - 1) * $items_per_page;
    
    // Get total counts
    $studentCount = getItemCountByTable('students');
    $courseCount = getItemCountByTable('course');
    $classCount = getItemCountByTable('class');
    $total_pages = ceil($studentCount / $items_per_page);
    
    // Get paginated students
    $sql = "SELECT * FROM students ORDER BY id DESC LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $items_per_page, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $students = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechTutor | TechGuru Dashboard</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo ROOT_URL; ?>/assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <?php include ROOT_PATH . '/components/header.php'; ?>

    <!-- Dashboard Content -->
    <div class="dashboard-content">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1>Hello <?php echo explode(' ', $_SESSION['name'])[0]; ?></h1>
            <p class="role">TechGuru</p>
            <p class="subject">Computer Programming</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-section">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Students</h3>
                        <p class="stat-number"><?php echo $studentCount; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-book"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Courses</h3>
                        <p class="stat-number"><?php echo $courseCount; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Classes</h3>
                        <p class="stat-number"><?php echo $classCount; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-star"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Rating</h3>
                        <p class="stat-number">4.8</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Students Section -->
        <div class="users-section">
            <div class="section-header">
                <h2>My Students</h2>
                <div class="search-bar">
                    <i class="bi bi-search"></i>
                    <input type="text" placeholder="Search students">
                </div>
            </div>
            <div class="users-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Class</th>
                            <th>Schedule</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo $student['student_id']; ?></td>
                            <td><?php echo $student['name']; ?></td>
                            <td><?php echo $student['email']; ?></td>
                            <td><?php echo $student['class']; ?></td>
                            <td><?php echo $student['schedule']; ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-primary" onclick="viewStudent(<?php echo $student['id']; ?>)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-success" onclick="startClass(<?php echo $student['id']; ?>)">
                                        <i class="bi bi-play-circle"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="<?php echo $page == $i ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Schedule and Feedback -->
        <div class="row mt-4">
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Upcoming Classes</h5>
                        <div class="schedule-list">
                            <?php
                            // Get upcoming classes
                            $sql = "SELECT * FROM class WHERE tutor_id = ? AND date >= CURDATE() ORDER BY date, time LIMIT 5";
                            $stmt = mysqli_prepare($conn, $sql);
                            mysqli_stmt_bind_param($stmt, "i", $_SESSION['user']);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            
                            if (mysqli_num_rows($result) > 0) {
                                while ($class = mysqli_fetch_assoc($result)) {
                                    echo '<div class="schedule-item">';
                                    echo '<div class="schedule-time">' . date('M d, Y', strtotime($class['date'])) . ' at ' . date('h:i A', strtotime($class['time'])) . '</div>';
                                    echo '<div class="schedule-details">';
                                    echo '<div class="student-name">' . $class['student_name'] . '</div>';
                                    echo '<div class="course-name">' . $class['course_name'] . '</div>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<p class="text-muted">No upcoming classes</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Recent Feedback</h5>
                        <div class="feedback-list">
                            <?php
                            // Get recent feedback
                            $sql = "SELECT * FROM feedback WHERE tutor_id = ? ORDER BY date DESC LIMIT 5";
                            $stmt = mysqli_prepare($conn, $sql);
                            mysqli_stmt_bind_param($stmt, "i", $_SESSION['user']);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            
                            if (mysqli_num_rows($result) > 0) {
                                while ($feedback = mysqli_fetch_assoc($result)) {
                                    echo '<div class="feedback-item">';
                                    echo '<div class="feedback-header">';
                                    echo '<div class="student-name">' . $feedback['student_name'] . '</div>';
                                    echo '<div class="rating">';
                                    for ($i = 0; $i < $feedback['rating']; $i++) {
                                        echo '<i class="bi bi-star-fill text-warning"></i>';
                                    }
                                    echo '</div>';
                                    echo '</div>';
                                    echo '<div class="feedback-text">' . $feedback['comment'] . '</div>';
                                    echo '<div class="feedback-date">' . date('M d, Y', strtotime($feedback['date'])) . '</div>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<p class="text-muted">No feedback yet</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewStudent(studentId) {
            window.location.href = `view-student.php?id=${studentId}`;
        }

        function startClass(studentId) {
            window.location.href = `start-class.php?id=${studentId}`;
        }
    </script>
</body>
</html>