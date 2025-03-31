<?php
class MeetingManagement {
    private $bbbBaseUrl;
    private $bbbSecret;

    public function __construct() {
        $this->bbbBaseUrl = BBB_API_URI;
        $this->bbbSecret = BBB_SECRET;
    }

    /**
     * Create a BigBlueButton meeting
     * @param string $meetingId Unique meeting identifier
     * @param string $name Meeting name
     * @param array $options Additional meeting options
     * @return array Meeting creation response
     */
    public function createMeeting($meetingId, $name, $options = []) {
        try {
            $params = [
                'name' => $name,
                'meetingID' => $meetingId,
                'attendeePW' => $options['attendeePW'] ?? 'techkid' . rand(1000, 9999),
                'moderatorPW' => $options['moderatorPW'] ?? 'techguru' . rand(1000, 9999),
                'welcome' => $options['welcome'] ?? 'Welcome to TechTutor Online Session!',
                'maxParticipants' => $options['maxParticipants'] ?? -1,
                'duration' => $options['duration'] ?? 0,
                'record' => $options['record'] ?? 'true',
                'autoStartRecording' => $options['autoStartRecording'] ?? 'true',
                'allowStartStopRecording' => $options['allowStartStopRecording'] ?? 'true',
                'disableRecording' => $options['disableRecording'] ?? 'false',
                'webcamsOnlyForModerator' => $options['webcamsOnlyForModerator'] ?? 'false',
                'muteOnStart' => $options['muteOnStart'] ?? 'true',
                'meta_classId' => $options['muteOnStart'] ?? null,
            ];

            $response = $this->makeRequest('create', $params);
            
            if ($response['returncode'] === 'SUCCESS') {
                log_error("Meeting created successfully: $meetingId", "meeting");
                return [
                    'success' => true,
                    'meetingID' => $meetingId,
                    'attendeePW' => $params['attendeePW'],
                    'moderatorPW' => $params['moderatorPW']
                ];
            } else {
                throw new Exception("Failed to create meeting: " . ($response['message'] ?? 'Unknown error'));
            }
        } catch (Exception $e) {
            log_error("Meeting creation error: " . $e->getMessage(), "meeting");
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Join a BigBlueButton meeting
     * @param string $meetingId Meeting identifier
     * @param string $name Participant name
     * @param string $password Meeting password (attendee or moderator)
     * @param string $userId Optional user ID
     * @param string $logoutUrl Optional custom logout URL
     * @return string Join URL
     */
    public function getJoinUrl($meetingId, $name, $password, $userId = null, $logoutUrl = null) {
        try {
            // Set default logout URL based on user role if not provided
            if (!$logoutUrl) {
                $class_id = isset($_GET['class_id']) ? $_GET['class_id'] : (isset($_POST['class_id']) ? $_POST['class_id'] : null);
                $schedule_id = isset($_GET['schedule_id']) ? $_GET['schedule_id'] : (isset($_POST['schedule_id']) ? $_POST['schedule_id'] : null);
                
                if ($class_id && $schedule_id) {
                    if (isset($_SESSION['role'])) {
                        switch ($_SESSION['role']) {
                            case 'TECHGURU':
                                $logoutUrl = 'http://' . $_SERVER['SERVER_NAME'] . '/dashboard/t/class/details?id=' . $class_id . '&ended=' . $schedule_id;
                                break;
                            case 'TECHKID':
                                $logoutUrl = 'http://' . $_SERVER['SERVER_NAME'] . '/dashboard/s/class/details?id=' . $class_id . '&ended=' . $schedule_id;
                                break;
                            case 'ADMIN':
                                $logoutUrl = 'http://' . $_SERVER['SERVER_NAME'] . '/dashboard/a/class/details?id=' . $class_id . '&ended=' . $schedule_id;
                                break;
                            default:
                                $logoutUrl = 'http://' . $_SERVER['SERVER_NAME'] . '/dashboard';
                        }
                    } else {
                        $logoutUrl = 'http://' . $_SERVER['SERVER_NAME'] . '/dashboard';
                    }
                } else {
                    $logoutUrl = 'http://' . $_SERVER['SERVER_NAME'] . '/dashboard';
                }
            }

            $params = [
                'meetingID' => $meetingId,
                'fullName' => $name,
                'password' => $password,
                'userID' => $userId ?? ($_SESSION['user'] ?? ''),
                'joinViaHtml5' => 'true',
                'logoutURL' => $logoutUrl
            ];

            return $this->buildUrl('join', $params);
        } catch (Exception $e) {
            log_error("Join URL generation error: " . $e->getMessage(), "meeting");
            throw new Exception("Failed to generate join URL");
        }
    }

    /**
     * End a BigBlueButton meeting
     * @param string $meetingId Meeting identifier
     * @param string $moderatorPW Moderator password
     * @return array End meeting response
     */
    public function endMeeting($meetingId, $moderatorPW) {
        try {
            $params = [
                'meetingID' => $meetingId,
                'password' => $moderatorPW
            ];

            $response = $this->makeRequest('end', $params);
            
            if ($response['returncode'] === 'SUCCESS') {
                log_error("Meeting ended successfully: $meetingId", "meeting");
                return ['success' => true];
            } else {
                throw new Exception("Failed to end meeting: " . ($response['message'] ?? 'Unknown error'));
            }
        } catch (Exception $e) {
            log_error("Meeting end error: " . $e->getMessage(), "meeting");
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check if a meeting is running
     * @param string $meetingId Meeting identifier
     * @return bool True if meeting is running
     */
    public function isMeetingRunning($meetingId) {
        try {
            $params = ['meetingID' => $meetingId];
            $response = $this->makeRequest('isMeetingRunning', $params);
            return $response['running'] === 'true';
        } catch (Exception $e) {
            log_error("Meeting status check error: " . $e->getMessage(), "meeting");
            return false;
        }
    }

    /**
     * Get meeting information
     * @param string $meetingId Meeting identifier
     * @param string $moderatorPW Moderator password
     * @return array Meeting information
     */
    public function getMeetingInfo($meetingId, $moderatorPW) {
        try {
            $params = [
                'meetingID' => $meetingId,
                'password' => $moderatorPW
            ];

            return $this->makeRequest('getMeetingInfo', $params);
        } catch (Exception $e) {
            log_error("Meeting info retrieval error: " . $e->getMessage(), "meeting");
            throw new Exception("Failed to get meeting information");
        }
    }

    /**
     * Get recordings for a meeting
     * @param string $meetingId Meeting identifier
     * @return array List of recordings
     */
    public function getRecordings($meetingId) {
        try {
            $params = ['meetingID' => $meetingId];
            $response = $this->makeRequest('getRecordings', $params);

            if ($response['returncode'] !== 'SUCCESS') {
                throw new Exception("Failed to get recordings: " . ($response['message'] ?? 'Unknown error'));
            }

            if (!isset($response['recordings']) || empty($response['recordings'])) {
                return [];
            }

            $recordings = [];
            foreach ($response['recordings'] as $recording) {
                $recordings[] = [
                    'recordID' => $recording['recordID'],
                    'meetingID' => $recording['meetingID'],
                    'name' => $recording['name'],
                    'published' => $recording['published'] === 'true',
                    'state' => $recording['state'],
                    'startTime' => $recording['startTime'],
                    'endTime' => $recording['endTime'],
                    'size' => $recording['size'] ?? 0,
                    'duration' => $recording['duration'] ?? 0,
                    'url' => $recording['playback']['format']['url'] ?? null,
                    'created_at' => strtotime($recording['startTime']),
                    'participants' => $recording['participants'] ?? 0,
                ];
            }

            return $recordings;
        } catch (Exception $e) {
            log_error("Recording retrieval error: " . $e->getMessage(), "meeting");
            throw new Exception("Failed to get recordings");
        }
    }

    /**
     * Delete a recording
     * @param string $recordId Recording identifier
     * @return array Delete operation response
     */
    public function deleteRecording($recordId) {
        try {
            $params = ['recordID' => $recordId];
            $response = $this->makeRequest('deleteRecordings', $params);

            if ($response['returncode'] === 'SUCCESS' && $response['deleted'] === 'true') {
                log_error("Recording deleted successfully: $recordId", "meeting");
                return ['success' => true];
            } else {
                throw new Exception("Failed to delete recording: " . ($response['message'] ?? 'Unknown error'));
            }
        } catch (Exception $e) {
            log_error("Recording deletion error: " . $e->getMessage(), "meeting");
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Update recording settings
     * @param string $recordId Recording identifier
     * @param array $meta Metadata to update
     * @return array Update operation response
     */
    public function updateRecordingSettings($recordId, $meta = []) {
        try {
            $params = ['recordID' => $recordId];
            
            // Add meta parameters
            foreach ($meta as $key => $value) {
                $params['meta_' . $key] = $value;
            }

            $response = $this->makeRequest('updateRecordings', $params);

            if ($response['returncode'] === 'SUCCESS' && $response['updated'] === 'true') {
                log_error("Recording settings updated successfully: $recordId", "meeting");
                return ['success' => true];
            } else {
                throw new Exception("Failed to update recording: " . ($response['message'] ?? 'Unknown error'));
            }
        } catch (Exception $e) {
            log_error("Recording update error: " . $e->getMessage(), "meeting");
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Publish or unpublish a recording
     * @param string $recordId Recording identifier
     * @param bool $publish Whether to publish or unpublish
     * @return array Operation response
     */
    public function publishRecording($recordId, $publish = true) {
        try {
            $params = [
                'recordID' => $recordId,
                'publish' => $publish ? 'true' : 'false'
            ];
            $response = $this->makeRequest('publishRecordings', $params);


            if ($response['returncode'] === 'SUCCESS' && $response['published'] === ($publish ? 'true' : 'false')) {
                $action = $publish ? 'published' : 'unpublished';
                log_error("Recording $action successfully: $recordId", "meeting");
                return ['success' => true];
            } else {
                throw new Exception("Failed to " . ($publish ? 'publish' : 'unpublish') . " recording: " . ($response['message'] ?? 'Unknown error'));
            }
        } catch (Exception $e) {
            log_error("Recording publish error: " . $e->getMessage(), "meeting");
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get statistics for a meeting
     * @param string $meetingId Meeting identifier
     * @return array Meeting statistics
     */
    public function getMeetingStats($meetingId, $scheduleId = null) {
        global $conn;

        $query = "SELECT moderator_pw FROM meetings WHERE schedule_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$scheduleId]);
        $mod_pas = $stmt->get_result()->fetch_assoc();
        try {
            $info = $this->getMeetingInfo($meetingId, $mod_pas);
            
            $stats = [
                'participant_count' => $info['participantCount'] ?? 0,
                'listener_count' => $info['listenerCount'] ?? 0,
                'voice_participant_count' => $info['voiceParticipantCount'] ?? 0,
                'video_count' => $info['videoCount'] ?? 0,
                'moderator_count' => $info['moderatorCount'] ?? 0,
                'attendee_count' => $info['attendeeCount'] ?? 0,
                'has_been_forcibly_ended' => ($info['hasBeenForciblyEnded'] ?? 'false') === 'true',
                'recording' => ($info['recording'] ?? 'false') === 'true'
            ];

            if (isset($info['startTime'])) {
                $startTime = (int)($info['startTime'] / 1000); // Convert from milliseconds
                $stats['duration_minutes'] = floor((time() - $startTime) / 60);
            }

            return $stats;
        } catch (Exception $e) {
            log_error("Meeting stats retrieval error: " . $e->getMessage(), "meeting");
            return [
                'participant_count' => 0,
                'listener_count' => 0,
                'voice_participant_count' => 0,
                'video_count' => 0,
                'moderator_count' => 0,
                'attendee_count' => 0,
                'has_been_forcibly_ended' => false,
                'recording' => false,
                'duration_minutes' => 0
            ];
        }
    }

    /**
     * Update the publish status of a recording (alias for publishRecording)
     * @param string $recordId Recording identifier
     * @param bool $publish Whether to publish or unpublish
     * @return array Operation response
     */
    public function updateRecordingPublishStatus($recordId, $publish) {
        return $this->publishRecording($recordId, $publish);
    }

    /**
     * Get download URL for a recording
     * @param array $recording Recording data
     * @return string|null Download URL
     */
    public function getRecordingDownloadUrl($recording) {
        try {
            if (!isset($recording['playback']['format']['url'])) {
                return null;
            }

            // Replace the playback URL with the download URL
            // BigBlueButton stores recordings in the format: https://server/playback/presentation/2.0/playback.html?meetingId=...
            // Download URL format: https://server/presentation/[meetingId]/video.mp4
            $playbackUrl = $recording['playback']['format']['url'];
            $pattern = '/playback\.html\?meetingId=([^&]+)/';
            if (preg_match($pattern, $playbackUrl, $matches)) {
                $baseUrl = substr($playbackUrl, 0, strpos($playbackUrl, '/playback/'));
                return $baseUrl . '/presentation/' . $matches[1] . '/video.mp4';
            }
            return null;
        } catch (Exception $e) {
            log_error("Error generating download URL: " . $e->getMessage(), "meeting");
            return null;
        }
    }

    /**
     * Archive or unarchive a recording
     * @param string $recordingId The ID of the recording to archive
     * @param bool $archive Whether to archive (true) or unarchive (false) the recording
     * @return array Result of the operation
     */
    public function archiveRecording($recordingId, $archive = true) {
        global $conn;
        
        try {
            // Prepare meta parameters for updating the recording
            if ($archive) {
                $params = [
                    'recordID' => $recordingId,
                    'meta_archived' => 'true',
                    'meta_archive_date' => date('Y-m-d H:i:s')
                ];
            } else {
                $params = [
                    'recordID' => $recordingId,
                    'meta_archived' => 'false',
                    'meta_archive_date' => ''
                ];
            }

            $response = $this->makeRequest('updateRecordings', $params);
            
            if ($response['returncode'] === 'SUCCESS') {
                // Update the recording_visibility table to set the is_archived flag
                // First check if this recording is already in the table
                $stmt = $conn->prepare("SELECT id, class_id FROM recording_visibility WHERE recording_id = ?");
                $stmt->bind_param("s", $recordingId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // Update existing record
                    $row = $result->fetch_assoc();
                    $stmt = $conn->prepare("UPDATE recording_visibility SET is_archived = ?, updated_at = NOW() WHERE id = ?");
                    $archivedValue = $archive ? 1 : 0;
                    $stmt->bind_param("ii", $archivedValue, $row['id']);
                    $stmt->execute();
                } else {
                    // Get the recordings data to find the meeting ID and class
                    $recordings = $this->getRecordings($recordingId);
                    if (!empty($recordings)) {
                        $recording = $recordings[0];
                        $meetingId = $recording['meetingID'] ?? null;
                        
                        if ($meetingId) {
                            // Find the class ID for this meeting
                            $stmt = $conn->prepare("
                                SELECT cs.class_id 
                                FROM meetings m 
                                JOIN class_schedule cs ON m.schedule_id = cs.schedule_id 
                                WHERE m.meeting_uid = ?
                            ");
                            $stmt->bind_param("s", $meetingId);
                            $stmt->execute();
                            $classResult = $stmt->get_result();
                            
                            if ($classResult->num_rows > 0) {
                                $classRow = $classResult->fetch_assoc();
                                $classId = $classRow['class_id'];
                                $userId = $_SESSION['user'] ?? 0;
                                
                                // Insert new record with archive value
                                $stmt = $conn->prepare("
                                    INSERT INTO recording_visibility 
                                    (recording_id, class_id, is_visible, is_archived, created_by) 
                                    VALUES (?, ?, 0, ?, ?)
                                ");
                                $archivedValue = $archive ? 1 : 0;
                                $stmt->bind_param("siis", $recordingId, $classId, $archivedValue, $userId);
                                $stmt->execute();
                            }
                        }
                    }
                }
                
                log_error("Recording archive status updated successfully: $recordingId, archived: " . ($archive ? 'true' : 'false'), "meeting");
                return [
                    'success' => true, 
                    'message' => 'Recording ' . ($archive ? 'archived' : 'unarchived') . ' successfully'
                ];
            } else {
                throw new Exception("Failed to update recording archive status: " . ($response['message'] ?? 'Unknown error'));
            }
        } catch (Exception $e) {
            log_error("Recording archive update error: " . $e->getMessage(), "meeting");
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get all recordings for a specific class
     * @param int $class_id Class identifier
     * @return array List of recordings with session details
     */
    public function getClassRecordings($class_id) {
        try {
            global $conn;
            
            // Get all meetings for this class
            $query = "SELECT m.*, cs.session_date, cs.start_time, cs.end_time 
                     FROM meetings m 
                     JOIN class_schedule cs ON m.schedule_id = cs.schedule_id 
                     WHERE cs.class_id = ? 
                     ORDER BY cs.session_date DESC, cs.start_time DESC";

            $stmt = $conn->prepare($query);
            $stmt->execute([$class_id]);
            $meetings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Get all visibility settings for this class
            $visibilitySettings = $this->getRecordingVisibilitySettings($class_id);

            // Get recordings for each meetingparticipants
            $recordings = [];
            foreach ($meetings as $meeting_data) {
                $meetingRecordings = $this->getRecordings($meeting_data['meeting_uid']);

                if (!empty($meetingRecordings)) {
                    foreach ($meetingRecordings as $recording) {
                        $recording['session_date'] = $meeting_data['session_date'];
                        $recording['start_time'] = $meeting_data['start_time'];
                        $recording['end_time'] = $meeting_data['end_time'];
                        $recording['download_url'] = $this->getRecordingDownloadUrl($recording);
                        
                        // Check if this recording is in our visibility table
                        $recordingId = $recording['recordID'];
                        if (isset($visibilitySettings[$recordingId])) {
                            $settings = $visibilitySettings[$recordingId];
                            $recording['is_visible'] = (bool)$settings['is_visible'];
                            $recording['is_archived'] = (bool)$settings['is_archived'];
                            $recording['archived'] = (bool)$settings['is_archived']; // For backward compatibility
                        } else {
                            // Fall back to meta data if not in our database
                            $recording['is_visible'] = true; // Default to visible
                            $recording['is_archived'] = isset($recording['meta']['archived']) && $recording['meta']['archived'] === 'true';
                            $recording['archived'] = $recording['is_archived']; // For backward compatibility
                        }
                        
                        $recordings[] = $recording;
                    }
                }
            }

            return [
                'success' => true,
                'recordings' => $recordings
            ];
        } catch (Exception $e) {
            log_error("Error retrieving class recordings: " . $e->getMessage(), "meeting");
            return [
                'success' => false,
                'error' => 'Failed to retrieve recordings',
                'recordings' => []
            ];
        }
    }

    /**
     * Fetch meeting analytics data from BigBlueButton and store in database
     * @param int $class_id Class ID to fetch data for
     * @return array Result with success status and counts
     */
    public function fetchMeetingAnalytics($class_id) {
        global $conn;
        
        try {
            // Check if class exists
            $stmt = $conn->prepare("SELECT tutor_id FROM class WHERE class_id = ?");
            $stmt->bind_param("i", $class_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("Class not found");
            }
            
            $classInfo = $result->fetch_assoc();
            $tutor_id = $classInfo['tutor_id'];
            
            // Get all schedule IDs for this class
            $stmt = $conn->prepare("SELECT schedule_id FROM class_schedule WHERE class_id = ?");
            $stmt->bind_param("i", $class_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return [
                    'success' => true,
                    'message' => 'No scheduled sessions found for this class',
                    'sessions_processed' => 0,
                    'analytics_updated' => 0
                ];
            }
            
            $scheduleIds = [];
            while ($row = $result->fetch_assoc()) {
                $scheduleIds[] = $row['schedule_id'];
            }
            
            $conn->begin_transaction();
            
            $sessionsProcessed = 0;
            $analyticsUpdated = 0;
            
            foreach ($scheduleIds as $schedule_id) {
                // Get meetings for this schedule if they exist
                $stmt = $conn->prepare("SELECT meeting_id, moderator_pw FROM meetings WHERE schedule_id = ?");
                $stmt->bind_param("i", $schedule_id);
                $stmt->execute();
                $meetings = $stmt->get_result();
                
                while ($meeting = $meetings->fetch_assoc()) {
                    $sessionsProcessed++;
                    
                    try {
                        // Get meeting info from BBB
                        $meetingInfo = $this->getMeetingInfo($meeting['meeting_id'], $meeting['moderator_pw']);
                        
                        if ($meetingInfo['returncode'] !== 'SUCCESS') {
                            continue; // Skip if we can't get meeting info
                        }
                        
                        // Check if meeting has ended (we only want analytics for completed meetings)
                        if ($this->isMeetingRunning($meeting['meeting_id'])) {
                            continue; // Skip active meetings
                        }
                        // Extract analytics data
                        $startTime = isset($meetingInfo['startTime']) ? 
                            date('Y-m-d H:i:s', round($meetingInfo['startTime'] / 1000)) : 
                            null;
                            
                        $endTime = isset($meetingInfo['endTime']) ? 
                            date('Y-m-d H:i:s', round($meetingInfo['endTime'] / 1000)) : 
                            date('Y-m-d H:i:s'); // Use current time if not available
                        
                        $participantCount = isset($meetingInfo['participantCount']) ? 
                            $meetingInfo['participantCount'] : 
                            (isset($meetingInfo['attendeeCount']) ? $meetingInfo['attendeeCount'] : 0);
                        
                        $duration = isset($meetingInfo['duration']) ? 
                            $meetingInfo['duration'] : 
                            (isset($startTime) && isset($endTime) ? 
                                round((strtotime($endTime) - strtotime($startTime)) / 60) : 0);
                        
                        // Check if this meeting has recordings
                        $recordings = $this->getRecordings($meeting['meeting_id']);
                        $recordingAvailable = !empty($recordings) ? 1 : 0;
                        
                        // Prepare analytics data
                        $analytics = [
                            'participant_count' => $participantCount,
                            'duration' => $duration,
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'recording_available' => $recordingAvailable
                        ];
                        
                        // Update analytics in database
                        $updated = $this->updateMeetingAnalytics($meeting['meeting_id'], $tutor_id, $analytics);
                        
                        if ($updated) {
                            $analyticsUpdated++;
                        }
                    } catch (Exception $e) {
                        log_error("Error processing meeting {$meeting['meeting_id']}: " . $e->getMessage(), "meeting");
                        continue; // Skip this meeting but try others
                    }
                }
            }
            
            $conn->commit();
            
            return [
                'success' => true,
                'message' => "Successfully processed $sessionsProcessed sessions and updated $analyticsUpdated analytics records",
                'sessions_processed' => $sessionsProcessed,
                'analytics_updated' => $analyticsUpdated
            ];
        } catch (Exception $e) {
            if (isset($conn) && $conn->ping()) {
                $conn->rollback();
            }
            log_error("Error fetching meeting analytics: " . $e->getMessage(), "meeting");
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get recording visibility settings for a class
     * @param int $class_id Class ID to get settings for
     * @return array Associative array of recording_id => settings
     */
    public function getRecordingVisibilitySettings($class_id) {
        global $conn;
        
        $settings = [];
        
        try {
            $stmt = $conn->prepare("
                SELECT recording_id, is_visible, is_archived, created_at, updated_at
                FROM recording_visibility
                WHERE class_id = ?
            ");
            $stmt->bind_param("i", $class_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $settings[$row['recording_id']] = $row;
            }
            
            return $settings;
        } catch (Exception $e) {
            log_error("Error retrieving recording visibility settings: " . $e->getMessage(), "meeting");
            return $settings;
        }
    }

    /**
     * Get meeting analytics data for a class or tutor
     * @param int|null $class_id Class ID to get analytics for (optional)
     * @param int|null $tutor_id Tutor ID to get analytics for (optional)
     * @return array Meeting analytics data
     */
    public function getMeetingAnalytics($class_id = null, $tutor_id = null) {
        global $conn;
        
        try {
            // Build the query based on provided filters
            $where_clauses = [];
            $params = [];
            $types = "";
            
            if ($class_id) {
                $where_clauses[] = "cs.class_id = ?";
                $params[] = $class_id;
                $types .= "i";
            }
            
            if ($tutor_id) {
                $where_clauses[] = "c.tutor_id = ?";
                $params[] = $tutor_id;
                $types .= "i";
            }
            
            $where_sql = "";
            if (!empty($where_clauses)) {
                $where_sql = "WHERE " . implode(" AND ", $where_clauses);
            }
            
            // Get overall statistics
            $query = "
                SELECT 
                    COUNT(DISTINCT m.meeting_id) as total_sessions,
                    SUM(ma.participant_count) as total_participants,
                    SUM(ma.duration) / 60 as total_hours,
                    SUM(ma.recording_available) as total_recordings
                FROM meetings m
                JOIN class_schedule cs ON m.schedule_id = cs.schedule_id
                JOIN class c ON cs.class_id = c.class_id
                LEFT JOIN meeting_analytics ma ON m.meeting_id = ma.meeting_id
                $where_sql
            ";
            
            $stmt = $conn->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $overall = $stmt->get_result()->fetch_assoc();
            
            // Get recent sessions
            $query = "
                SELECT 
                    m.meeting_id,
                    c.class_name,
                    cs.session_date,
                    ma.participant_count,
                    ma.duration,
                    ma.recording_available,
                    m.createtime as start_time,
                    m.end_time
                FROM meetings m
                JOIN class_schedule cs ON m.schedule_id = cs.schedule_id
                JOIN class c ON cs.class_id = c.class_id
                LEFT JOIN meeting_analytics ma ON m.meeting_id = ma.meeting_id
                $where_sql
                ORDER BY m.createtime DESC
                LIMIT 5
            ";
            
            $stmt = $conn->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $recent_sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Get activity data for chart (meetings per month)
            $query = "
                SELECT 
                    DATE_FORMAT(FROM_UNIXTIME(m.createtime), '%Y-%m') as month,
                    COUNT(DISTINCT m.meeting_id) as meeting_count
                FROM meetings m
                JOIN class_schedule cs ON m.schedule_id = cs.schedule_id
                JOIN class c ON cs.class_id = c.class_id
                $where_sql
                GROUP BY month
                ORDER BY month
                LIMIT 12
            ";
            
            $stmt = $conn->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $activity_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Get engagement data (average participants per meeting)
            $query = "
                SELECT 
                    DATE_FORMAT(FROM_UNIXTIME(m.createtime), '%Y-%m') as month,
                    ROUND(AVG(ma.participant_count), 1) as avg_participants
                FROM meetings m
                JOIN class_schedule cs ON m.schedule_id = cs.schedule_id
                JOIN class c ON cs.class_id = c.class_id
                LEFT JOIN meeting_analytics ma ON m.meeting_id = ma.meeting_id
                $where_sql
                GROUP BY month
                ORDER BY month
                LIMIT 12
            ";
            
            $stmt = $conn->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $engagement_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Get duration data (average meeting length)
            $query = "
                SELECT 
                    DATE_FORMAT(FROM_UNIXTIME(m.createtime), '%Y-%m') as month,
                    ROUND(AVG(ma.duration) / 60, 1) as avg_duration_hours
                FROM meetings m
                JOIN class_schedule cs ON m.schedule_id = cs.schedule_id
                JOIN class c ON cs.class_id = c.class_id
                LEFT JOIN meeting_analytics ma ON m.meeting_id = ma.meeting_id
                $where_sql
                GROUP BY month
                ORDER BY month
                LIMIT 12
            ";
            
            $stmt = $conn->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $duration_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            return [
                'total_sessions' => $overall['total_sessions'] ?? 0,
                'total_participants' => $overall['total_participants'] ?? 0,
                'total_hours' => round($overall['total_hours'] ?? 0, 1),
                'total_recordings' => $overall['total_recordings'] ?? 0,
                'recent_sessions' => $recent_sessions,
                'activity_data' => $activity_data,
                'engagement_data' => $engagement_data,
                'duration_data' => $duration_data
            ];
        } catch (Exception $e) {
            log_error("Error fetching meeting analytics: " . $e->getMessage(), "meeting");
            
            // Return empty data structure on error
            return [
                'total_sessions' => 0,
                'total_participants' => 0,
                'total_hours' => 0,
                'total_recordings' => 0,
                'recent_sessions' => [],
                'activity_data' => [],
                'engagement_data' => [],
                'duration_data' => []
            ];
        }
    }

    /**
     * Update or create analytics data for a meeting
     * @param string $meeting_id Meeting identifier
     * @param int $user_id User who updated the analytics
     * @param array $analytics Analytics data (participant_count, duration, etc.)
     * @return bool True if update was successful
     */
    public function updateMeetingAnalytics($meeting_id, $user_id, $analytics) {
        global $conn;
        
        try {
            // Check if analytics record already exists
            $stmt = $conn->prepare("SELECT id FROM meeting_analytics WHERE meeting_id = ?");
            $stmt->bind_param("s", $meeting_id);
            $stmt->execute();
            $exists = $stmt->get_result()->num_rows > 0;
            
            if ($exists) {
                // Update existing record
                $query = "UPDATE meeting_analytics SET 
                    participant_count = ?, 
                    duration = ?, 
                    recording_available = ?,
                    updated_by = ?,
                    updated_at = NOW()
                    WHERE meeting_id = ?";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param(
                    "iiiss", 
                    $analytics['participant_count'],
                    $analytics['duration'],
                    $analytics['recording_available'],
                    $user_id,
                    $meeting_id
                );
            } else {
                // Create new record
                $query = "INSERT INTO meeting_analytics (
                    meeting_id, participant_count, duration, 
                    start_time, end_time, recording_available,
                    created_by, updated_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param(
                    "siissiii", 
                    $meeting_id,
                    $analytics['participant_count'],
                    $analytics['duration'],
                    $analytics['start_time'],
                    $analytics['end_time'],
                    $analytics['recording_available'],
                    $user_id,
                    $user_id
                );
            }
            
            if ($stmt->execute()) {
                log_error("Meeting analytics updated for {$meeting_id}", "analytics");
                return true;
            } else {
                throw new Exception("Failed to update analytics: " . $stmt->error);
            }
        } catch (Exception $e) {
            log_error("Error updating meeting analytics: " . $e->getMessage(), "meeting");
            return false;
        }
    }

    /**
     * Make an API request to BigBlueButton server
     * @param string $action API action
     * @param array $params Request parameters
     * @return array API response
     */
    private function makeRequest($action, $params) {
        $url = $this->buildUrl($action, $params);
        $response = file_get_contents($url);
        
        if ($response === false) {
            throw new Exception("Failed to make API request");
        }
        $xml = simplexml_load_string($response);
        return json_decode(json_encode($xml), true);
    }

    /**
     * Build a signed URL for BigBlueButton API
     * @param string $action API action
     * @param array $params Request parameters
     * @return string Signed URL
     */
    private function buildUrl($action, $params) {
        $query = http_build_query($params);
        $checksum = sha1($action . $query . $this->bbbSecret);
        return $this->bbbBaseUrl . 'api/' . $action . '?' . $query . '&checksum=' . $checksum;
    }
}
