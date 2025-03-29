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
                'record' => $options['record'] ?? 'false',
                'autoStartRecording' => $options['autoStartRecording'] ?? 'false',
                'allowStartStopRecording' => $options['allowStartStopRecording'] ?? 'true',
                'disableRecording' => $options['disableRecording'] ?? 'false',
                'webcamsOnlyForModerator' => $options['webcamsOnlyForModerator'] ?? 'false',
                'muteOnStart' => $options['muteOnStart'] ?? 'true',
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
                                $logoutUrl = $_SERVER['SERVER_NAME'] . '/dashboard/t/class/details?id=' . $class_id . '&ended=' . $schedule_id;
                                break;
                            case 'TECHKID':
                                $logoutUrl = $_SERVER['SERVER_NAME'] . '/dashboard/s/class/details?id=' . $class_id . '&ended=' . $schedule_id;
                                break;
                            case 'ADMIN':
                                $logoutUrl = $_SERVER['SERVER_NAME'] . '/dashboard/a/class/details?id=' . $class_id . '&ended=' . $schedule_id;
                                break;
                            default:
                                $logoutUrl = $_SERVER['SERVER_NAME'] . '/dashboard';
                        }
                    } else {
                        $logoutUrl = $_SERVER['SERVER_NAME'] . '/dashboard';
                    }
                } else {
                    $logoutUrl = $_SERVER['SERVER_NAME'] . '/dashboard';
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
                    'created_at' => strtotime($recording['startTime'])
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
            log_error(print_r($response,true));

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
    public function getMeetingStats($meetingId) {
        try {
            $info = $this->getMeetingInfo($meetingId, BBB_MODERATOR_PASSWORD);
            
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
     * Archive a recording
     * @param string $recordId Recording identifier
     * @param bool $archive Whether to archive or unarchive
     * @return array Operation response
     */
    public function archiveRecording($recordId, $archive = true) {
        try {
            // First, update the recording metadata to mark it as archived
            $meta = ['archived' => $archive ? 'true' : 'false'];
            $result = $this->updateRecordingSettings($recordId, $meta);

            if (!$result['success']) {
                throw new Exception("Failed to update recording archive status");
            }

            log_error("Recording " . ($archive ? "archived" : "unarchived") . " successfully: $recordId", "meeting");
            return ['success' => true];
        } catch (Exception $e) {
            log_error("Recording archive error: " . $e->getMessage(), "meeting");
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

            // Get recordings for each meeting
            $recordings = [];
            foreach ($meetings as $meeting_data) {
                $meetingRecordings = $this->getRecordings($meeting_data['meeting_uid']);
                if (!empty($meetingRecordings)) {
                    foreach ($meetingRecordings as $recording) {
                        $recording['session_date'] = $meeting_data['session_date'];
                        $recording['start_time'] = $meeting_data['start_time'];
                        $recording['end_time'] = $meeting_data['end_time'];
                        $recording['download_url'] = $this->getRecordingDownloadUrl($recording);
                        $recording['archived'] = isset($recording['meta']['archived']) && $recording['meta']['archived'] === 'true';
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
    /**
     * Get aggregated statistics for meetings
     * @param string $tutorId The tutor's ID
     * @param string $period daily|weekly|monthly
     * @param string $startDate Start date in Y-m-d format
     * @param string $endDate End date in Y-m-d format
     * @return array Aggregated statistics
     */
    public function getAggregatedStats($tutorId, $period = 'daily', $startDate = null, $endDate = null) {
        global $conn;
        try {
            $groupBy = '';
            switch ($period) {
                case 'daily':
                    $groupBy = 'DATE(start_time)';
                    break;
                case 'weekly':
                    $groupBy = 'YEARWEEK(start_time)';
                    break;
                case 'monthly':
                    $groupBy = 'DATE_FORMAT(start_time, "%Y-%m")';
                    break;
                default:
                    throw new Exception("Invalid period specified");
            }

            $query = "SELECT 
                        $groupBy as period,
                        COUNT(*) as total_sessions,
                        SUM(participant_count) as total_participants,
                        AVG(participant_count) as avg_participants,
                        AVG(duration) as avg_duration,
                        SUM(recording_available) as total_recordings
                     FROM meeting_analytics 
                     WHERE tutor_id = ?";

            $params = [$tutorId];

            if ($startDate && $endDate) {
                $query .= " AND start_time BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
            }

            $query .= " GROUP BY $groupBy ORDER BY period";

            $stmt = $conn->prepare($query);
            $stmt->execute($params); 
            $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Calculate additional metrics
            foreach ($results as &$row) {
                $row['engagement_rate'] = $row['avg_participants'] / ($row['total_sessions'] ?: 1);
                $row['recording_rate'] = $row['total_recordings'] / ($row['total_sessions'] ?: 1) * 100;
            }

            return $results;

        } catch (Exception $e) {
            log_error("Analytics aggregation error: " . $e->getMessage(), "error");
            return [];
        }
    }

    /**
     * Get participation trends by hour of day
     * @param string $tutorId The tutor's ID
     * @param string $startDate Start date in Y-m-d format
     * @param string $endDate End date in Y-m-d format
     * @return array Hourly participation trends
     */
    public function getParticipationTrends($tutorId, $startDate = null, $endDate = null) {
        global $conn;
        try {
            $query = "SELECT 
                        DATE_FORMAT(start_time, '%H:00') as hour_of_day,
                        AVG(participant_count) as avg_participants,
                        COUNT(*) as session_count
                     FROM meeting_analytics 
                     WHERE tutor_id = ?";
            
            $params = [$tutorId];

            if ($startDate && $endDate) {
                $query .= " AND start_time BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
            }

            $query .= " GROUP BY hour_of_day ORDER BY hour_of_day";

            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        } catch (Exception $e) {
            log_error("Participation trend analysis error: " . $e->getMessage(), "error");
            return [];
        }
    }

    /**
     * Get duration distribution statistics
     * @param string $tutorId The tutor's ID
     * @param string $startDate Start date in Y-m-d format
     * @param string $endDate End date in Y-m-d format
     * @return array Duration distribution data
     */
    public function getDurationDistribution($tutorId, $startDate = null, $endDate = null) {
        global $conn;
        try {
            $query = "SELECT 
                        CASE 
                            WHEN duration <= 1800 THEN '0-30'
                            WHEN duration <= 3600 THEN '31-60'
                            WHEN duration <= 5400 THEN '61-90'
                            ELSE '90+'
                        END as duration_range,
                        COUNT(*) as session_count
                     FROM meeting_analytics 
                     WHERE tutor_id = ?";
            
            $params = [$tutorId];

            if ($startDate && $endDate) {
                $query .= " AND start_time BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
            }

            $query .= " GROUP BY duration_range ORDER BY 
                        CASE duration_range 
                            WHEN '0-30' THEN 1 
                            WHEN '31-60' THEN 2 
                            WHEN '61-90' THEN 3 
                            ELSE 4 
                        END";

            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        } catch (Exception $e) {
            log_error("Duration distribution analysis error: " . $e->getMessage(), "error");
            return [];
        }
    }

    /**
     * Get recent sessions with details
     * @param string $tutorId The tutor's ID
     * @param int $limit Number of recent sessions to retrieve
     * @return array Recent session data
     */
    public function getRecentSessions($tutorId, $limit = 5) {
        global $conn;
        try {
            $query = "SELECT 
                        ma.*,
                        m.name as meeting_name
                     FROM meeting_analytics ma
                     JOIN meetings m ON ma.meeting_id = m.meeting_id
                     WHERE ma.tutor_id = ?
                     ORDER BY ma.start_time DESC
                     LIMIT ?";

            $stmt = $conn->prepare($query);
            $stmt->execute([$tutorId, $limit]);
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        } catch (Exception $e) {
            log_error("Recent sessions retrieval error: " . $e->getMessage(), "error");
            return [];
        }
    }

    /**
     * Get overall statistics summary
     * @param string $tutorId The tutor's ID
     * @return array Summary statistics
     */
    public function getStatsSummary($tutorId) {
        global $conn;
        try {
            $query = "SELECT 
                        COUNT(*) as total_sessions,
                        SUM(participant_count) as total_participants,
                        AVG(duration) as avg_duration,
                        SUM(recording_available) as total_recordings
                     FROM meeting_analytics 
                     WHERE tutor_id = ?";

            $stmt = $conn->prepare($query);
            $stmt->execute([$tutorId]);
            $result = $stmt->get_result()->fetch_assoc();

            // Add calculated metrics
            $result['total_hours'] = round(($result['avg_duration'] * $result['total_sessions']) / 3600, 1);
            $result['avg_participants'] = round($result['total_participants'] / ($result['total_sessions'] ?: 1), 1);

            return $result;

        } catch (Exception $e) {
            log_error("Stats summary error: " . $e->getMessage(), "error");
            return [];
        }
    }

    /**
     * Get detailed analytics for a specific class
     * @param int $classId Class identifier
     * @param string $tutorId Tutor's user ID
     * @return array Comprehensive analytics data for the class
     */
    public function getClassAnalytics($classId, $tutorId) {
        global $conn;
        try {
            // Summary statistics
            $summaryQuery = "SELECT 
                COUNT(*) as total_sessions,
                SUM(TIMESTAMPDIFF(MINUTE, cs.start_time, cs.end_time)) as total_minutes,
                COUNT(DISTINCT m.meeting_uid) as total_meetings,
                SUM(CASE WHEN m.recording_url IS NOT NULL THEN 1 ELSE 0 END) as total_recordings
            FROM class_schedule cs
            LEFT JOIN meetings m ON cs.schedule_id = m.schedule_id
            WHERE cs.class_id = ? AND cs.status IN ('completed', 'confirmed')";

            $stmt = $conn->prepare($summaryQuery);
            $stmt->bind_param("i", $classId);
            $stmt->execute();
            $summaryResult = $stmt->get_result()->fetch_assoc();

            // Calculate enrollments to get a sense of participants
            $enrollmentQuery = "SELECT COUNT(*) as total_enrollments 
                                FROM enrollments 
                                WHERE class_id = ? AND status = 'active'";
            $stmt = $conn->prepare($enrollmentQuery);
            $stmt->bind_param("i", $classId);
            $stmt->execute();
            $enrollmentResult = $stmt->get_result()->fetch_assoc();

            // Session activity over time
            $activityQuery = "SELECT 
                cs.session_date, 
                COUNT(*) as session_count,
                SUM(TIMESTAMPDIFF(MINUTE, cs.start_time, cs.end_time)) as total_duration
            FROM class_schedule cs
            WHERE cs.class_id = ? AND cs.status IN ('completed', 'confirmed')
            GROUP BY cs.session_date
            ORDER BY cs.session_date";

            $stmt = $conn->prepare($activityQuery);
            $stmt->bind_param("i", $classId);
            $stmt->execute();
            $activityResult = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Recent sessions
            $recentQuery = "SELECT 
                cs.schedule_id,
                cs.session_date,
                cs.start_time,
                cs.end_time,
                cs.status,
                m.meeting_uid,
                m.recording_url,
                TIMESTAMPDIFF(MINUTE, cs.start_time, cs.end_time) as duration_minutes
            FROM class_schedule cs
            LEFT JOIN meetings m ON cs.schedule_id = m.schedule_id
            WHERE cs.class_id = ? AND cs.status IN ('completed', 'confirmed')
            ORDER BY cs.session_date DESC, cs.start_time DESC
            LIMIT 5";

            $stmt = $conn->prepare($recentQuery);
            $stmt->bind_param("i", $classId);
            $stmt->execute();
            $recentSessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Session duration distribution
            $durationQuery = "SELECT 
                CASE 
                    WHEN TIMESTAMPDIFF(MINUTE, cs.start_time, cs.end_time) <= 30 THEN '0-30 min'
                    WHEN TIMESTAMPDIFF(MINUTE, cs.start_time, cs.end_time) <= 60 THEN '31-60 min'
                    WHEN TIMESTAMPDIFF(MINUTE, cs.start_time, cs.end_time) <= 90 THEN '61-90 min'
                    ELSE '90+ min'
                END as duration_range,
                COUNT(*) as session_count
            FROM class_schedule cs
            WHERE cs.class_id = ? AND cs.status IN ('completed', 'confirmed')
            GROUP BY duration_range";

            $stmt = $conn->prepare($durationQuery);
            $stmt->bind_param("i", $classId);
            $stmt->execute();
            $durationDistribution = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Format the data for the frontend
            $totalHours = round($summaryResult['total_minutes'] / 60, 1);
            
            return [
                'success' => true,
                'stats' => [
                    'total_sessions' => $summaryResult['total_sessions'],
                    'total_hours' => $totalHours,
                    'total_participants' => $enrollmentResult['total_enrollments'],
                    'total_recordings' => $summaryResult['total_recordings'],
                    'avg_duration' => $summaryResult['total_sessions'] > 0 ? 
                                    round($summaryResult['total_minutes'] / $summaryResult['total_sessions'], 1) : 0
                ],
                'session_activity' => $activityResult,
                'recent_sessions' => $recentSessions,
                'duration_distribution' => $durationDistribution
            ];
        } catch (Exception $e) {
            log_error("Error retrieving class analytics: " . $e->getMessage(), "meeting");
            return [
                'success' => false,
                'error' => 'Failed to retrieve analytics data',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get overall tutor analytics across all classes
     * @param string $tutorId Tutor's user ID
     * @param string $period Period to analyze (last_week, last_month, all_time)
     * @return array Analytics data across all classes
     */
    public function getTutorAnalytics($tutorId, $period = 'all_time') {
        global $conn;
        try {
            // Date condition based on period
            $dateCondition = "";
            if ($period === 'last_week') {
                $dateCondition = " AND cs.session_date >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
            } elseif ($period === 'last_month') {
                $dateCondition = " AND cs.session_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
            }

            // Overall summary
            $summaryQuery = "SELECT 
                COUNT(*) as total_sessions,
                SUM(TIMESTAMPDIFF(MINUTE, cs.start_time, cs.end_time)) as total_minutes,
                COUNT(DISTINCT cs.class_id) as total_classes,
                SUM(CASE WHEN m.recording_url IS NOT NULL THEN 1 ELSE 0 END) as total_recordings
            FROM class_schedule cs
            JOIN class c ON cs.class_id = c.class_id
            LEFT JOIN meetings m ON cs.schedule_id = m.schedule_id
            WHERE c.tutor_id = ? AND cs.status IN ('completed', 'confirmed')" . $dateCondition;

            $stmt = $conn->prepare($summaryQuery);
            $stmt->bind_param("s", $tutorId);
            $stmt->execute();
            $summaryResult = $stmt->get_result()->fetch_assoc();

            // Get total enrollments across all classes
            $enrollmentQuery = "SELECT COUNT(*) as total_enrollments 
                                FROM enrollments e
                                JOIN class c ON e.class_id = c.class_id
                                WHERE c.tutor_id = ? AND e.status = 'active'";
            $stmt = $conn->prepare($enrollmentQuery);
            $stmt->bind_param("s", $tutorId);
            $stmt->execute();
            $enrollmentResult = $stmt->get_result()->fetch_assoc();

            // Session activity by date
            $activityQuery = "SELECT 
                cs.session_date, 
                COUNT(*) as session_count,
                SUM(TIMESTAMPDIFF(MINUTE, cs.start_time, cs.end_time)) as total_duration
            FROM class_schedule cs
            JOIN class c ON cs.class_id = c.class_id
            WHERE c.tutor_id = ? AND cs.status IN ('completed', 'confirmed')" . $dateCondition . "
            GROUP BY cs.session_date
            ORDER BY cs.session_date";

            $stmt = $conn->prepare($activityQuery);
            $stmt->bind_param("s", $tutorId);
            $stmt->execute();
            $activityResult = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Session activity by class
            $classActivityQuery = "SELECT 
                c.class_id,
                c.class_name,
                COUNT(cs.schedule_id) as session_count,
                SUM(TIMESTAMPDIFF(MINUTE, cs.start_time, cs.end_time)) as total_duration
            FROM class c
            LEFT JOIN class_schedule cs ON c.class_id = cs.class_id AND cs.status IN ('completed', 'confirmed')
            WHERE c.tutor_id = ?" . $dateCondition . "
            GROUP BY c.class_id
            ORDER BY session_count DESC";

            $stmt = $conn->prepare($classActivityQuery);
            $stmt->bind_param("s", $tutorId);
            $stmt->execute();
            $classActivity = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Format the data for the frontend
            $totalHours = round($summaryResult['total_minutes'] / 60, 1);
            
            return [
                'success' => true,
                'stats' => [
                    'total_sessions' => $summaryResult['total_sessions'],
                    'total_hours' => $totalHours,
                    'total_classes' => $summaryResult['total_classes'],
                    'total_participants' => $enrollmentResult['total_enrollments'],
                    'total_recordings' => $summaryResult['total_recordings'],
                    'avg_duration' => $summaryResult['total_sessions'] > 0 ? 
                                    round($summaryResult['total_minutes'] / $summaryResult['total_sessions'], 1) : 0
                ],
                'session_activity' => $activityResult,
                'class_activity' => $classActivity
            ];
        } catch (Exception $e) {
            log_error("Error retrieving tutor analytics: " . $e->getMessage(), "meeting");
            return [
                'success' => false,
                'error' => 'Failed to retrieve analytics data',
                'message' => $e->getMessage()
            ];
        }
    }
}
