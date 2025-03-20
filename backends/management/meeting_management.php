<?php
require_once '../main.php';

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
                'webcamsOnlyForModerator' => $options['webcamsOnlyForModerator'] ?? 'false',
                'muteOnStart' => $options['muteOnStart'] ?? 'true'
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
     * @return string Join URL
     */
    public function getJoinUrl($meetingId, $name, $password) {
        try {
            $params = [
                'meetingID' => $meetingId,
                'fullName' => $name,
                'password' => $password,
                'userID' => $_SESSION['user'] ?? '',
                'joinViaHtml5' => 'true'
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
            foreach ($response['recordings']['recording'] as $recording) {
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
