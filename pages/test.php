<?php 
	echo "Test Web Page";



	// For techguru main dashbaord
	<!-- 
        <!-- Schedule and Feedback 
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
     -->
     
?>