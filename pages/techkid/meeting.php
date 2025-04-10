<?php 
    require_once '../../backends/main.php';
    require_once BACKEND.'student_management.php';
    
    if (!isset($_SESSION)) {
        session_start();
    }

    // Check if user is logged in and is a TECHKID
    if (!isset($_SESSION['user']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'TECHKID') {
        header('Location: ' . BASE . 'login');
        exit;
    }

    // Check if schedule ID is provided
    if (!isset($_GET['schedule'])) {
        header('Location: ./');
        exit;
    }

    $schedule_id = $_GET['schedule'];
    $meeting_details = null;
    $error_message = null;

    try {
        // Get meeting details using the new function
        $meeting_details = getStudentMeetingDetails($schedule_id, $_SESSION['user']);

        if (!$meeting_details) {
            throw new Exception("Meeting not found or you don't have access to this session.");
        }

        // Check if the session is currently active
        $session_start = strtotime($meeting_details['session_date'] . ' ' . $meeting_details['start_time']);
        $session_end = strtotime($meeting_details['session_date'] . ' ' . $meeting_details['end_time']);
        $current_time = time();

        if ($current_time < $session_start) {
            throw new Exception("This session hasn't started yet. Please come back at the scheduled time.");
        }

        if ($current_time > $session_end) {
            throw new Exception("This session has already ended.");
        }

        // Initialize BigBlueButton meeting if not exists
        if (!$meeting_details['meeting_uid']) {
            $meeting = new MeetingManagement();
            $meetingId = 'class_' . $meeting_details['class_id'] . '_' . $schedule_id;
            $meetingName = $meeting_details['class_name'] . ' - ' . date('M d, Y', strtotime($meeting_details['session_date']));
            
            $result = $meeting->createMeeting($meetingId, $meetingName, [
                'duration' => 120,
                'record' => true,
                'autoStartRecording' => true,
                'muteOnStart' => true
            ]);

            if ($result['success']) {
                // Save meeting details to database
                $stmt = $conn->prepare("
                    INSERT INTO meetings (schedule_id, meeting_uid, moderator_pw, attendee_pw)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->bind_param("isss", 
                    $schedule_id, 
                    $meetingId,
                    $result['moderatorPW'],
                    $result['attendeePW']
                );
                $stmt->execute();
                
                $meeting_details['meeting_uid'] = $meetingId;
                $meeting_details['attendee_pw'] = $result['attendeePW'];
            } else {
                throw new Exception("Failed to create meeting room.");
            }
        }

        // Get join URL for student
        $meeting = new MeetingManagement();
        $student_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
        $join_url = $meeting->getJoinUrl(
            $meeting_details['meeting_uid'],
            $student_name,
            $meeting_details['attendee_pw']
        );

    } catch (Exception $e) {
        $error_message = $e->getMessage();
        log_error("Meeting error: " . $e->getMessage(), "meeting");
    }
?>
<!DOCTYPE html>
<html lang="en">
    <?php include ROOT_PATH . '/components/head.php'; ?>
    <body data-base="<?php echo BASE; ?>">
        <?php include ROOT_PATH . '/components/header.php'; ?>
        
        <!-- Main Dashboard Content -->
        <main class="dashboard-content bg">
            <div class="content-section">
                <div class="content-card bg-snow">
                    <div class="card-body">
                        <?php if ($error_message): ?>
                            <div class="meeting-error">
                                <i class="bi bi-exclamation-circle"></i>
                                <h3>Session Error</h3>
                                <p><?php echo htmlspecialchars($error_message); ?></p>
                                <a href="class" class="btn btn-primary">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Classes
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <div class="col-md-4 mb-4 mb-md-0">
                                    <div class="class-info">
                                        <img src="<?php echo !empty($meeting_details['thumbnail']) ? CLASS_IMG . $meeting_details['thumbnail'] : CLASS_IMG . 'default.jpg'; ?>" 
                                             class="img-fluid rounded mb-3" 
                                             alt="<?php echo htmlspecialchars($meeting_details['class_name']); ?>">
                                        
                                        <h4 class="mb-2"><?php echo htmlspecialchars($meeting_details['class_name']); ?></h4>
                                        <p class="text-muted mb-3"><?php echo htmlspecialchars($meeting_details['subject_name']); ?></p>
                                        
                                        <div class="tutor-info d-flex align-items-center mb-3">
                                            <img src="<?php echo !empty($meeting_details['tutor_avatar']) ? USER_IMG . $meeting_details['tutor_avatar'] : USER_IMG . 'default.jpg'; ?>" 
                                                 class="rounded-circle me-2" 
                                                 alt="Tutor">
                                            <div>
                                                <p class="mb-0 small">Tutor</p>
                                                <p class="mb-0"><?php echo htmlspecialchars($meeting_details['tutor_name']); ?></p>
                                            </div>
                                        </div>

                                        <div class="session-info">
                                            <div class="mb-2">
                                                <i class="bi bi-calendar-event me-2"></i>
                                                <?php echo date('F d, Y', strtotime($meeting_details['session_date'])); ?>
                                            </div>
                                            <div class="mb-2">
                                                <i class="bi bi-clock me-2"></i>
                                                <?php 
                                                    echo date('h:i A', strtotime($meeting_details['start_time'])) . ' - ' . 
                                                         date('h:i A', strtotime($meeting_details['end_time']));
                                                ?>
                                            </div>
                                            <div>
                                                <i class="bi bi-circle-fill me-2 text-success"></i>
                                                Session in Progress
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="meeting-container">
                                        <div class="text-center py-4">
                                            <h3>Ready to Join?</h3>
                                            <p class="text-muted mb-4">Click the button below to enter the virtual classroom.</p>
                                            
                                            <div class="meeting-guidelines mb-4">
                                                <h5>Before Joining:</h5>
                                                <ul class="list-unstyled">
                                                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>Test your audio and video</li>
                                                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>Ensure stable internet connection</li>
                                                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>Find a quiet environment</li>
                                                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>Have your learning materials ready</li>
                                                </ul>
                                            </div>
                                            
                                            <button onclick="joinMeeting('<?php echo $join_url; ?>')" class="btn btn-primary btn-lg btn-join">
                                                <i class="bi bi-camera-video-fill"></i>Join Session Now
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>

        <?php include ROOT_PATH . '/components/footer.php'; ?>

        <script>
            async function joinMeeting(url) {
                try {
                    showLoading(true);
                    // Simulate a quick check before joining
                    await new Promise(resolve => setTimeout(resolve, 1000));
                    
                    // Open meeting in new window
                    window.open(url, '_blank');
                    
                    showToast('success', 'Joining meeting room...');
                } catch (error) {
                    showToast('error', 'Failed to join meeting. Please try again.');
                    console.error('Meeting join error:', error);
                } finally {
                    showLoading(false);
                }
            }
        </script>
    </body>
</html>