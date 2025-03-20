<?php
require_once 'config.php'; // Include BBB configuration

class MeetingManagement {
    private $bbbServer;
    private $bbbSecret;
    
    public function __construct() {
        $this->bbbServer = BBB_API_URI;
        $this->bbbSecret = BBB_SECRET;
    }
    
    private function generateChecksum($apiCall, $queryString) {
        return sha1($apiCall . $queryString . $this->bbbSecret);
    }
    
    public function createMeeting($meetingID, $meetingName, $moderatorPW, $attendeePW) {
        $queryString = "meetingID=$meetingID&name=$meetingName&moderatorPW=$moderatorPW&attendeePW=$attendeePW";
        $checksum = $this->generateChecksum('create', $queryString);
        $url = "$this->bbbServer/api/create?$queryString&checksum=$checksum";
        return file_get_contents($url);
    }
    
    public function getJoinURL($fullName, $meetingID, $password) {
        $queryString = "fullName=$fullName&meetingID=$meetingID&password=$password";
        $checksum = $this->generateChecksum('join', $queryString);
        return "$this->bbbServer/api/join?$queryString&checksum=$checksum";
    }
    
    public function isMeetingRunning($meetingID) {
        $queryString = "meetingID=$meetingID";
        $checksum = $this->generateChecksum('isMeetingRunning', $queryString);
        $url = "$this->bbbServer/api/isMeetingRunning?$queryString&checksum=$checksum";
        $response = file_get_contents($url);
        return strpos($response, '<running>true</running>') !== false;
    }
    
    public function endMeeting($meetingID, $moderatorPW) {
        $queryString = "meetingID=$meetingID&password=$moderatorPW";
        $checksum = $this->generateChecksum('end', $queryString);
        $url = "$this->bbbServer/api/end?$queryString&checksum=$checksum";
        return file_get_contents($url);
    }
}
// Initialize the class
$bbb = new MeetingManagement($bbbServer, $bbbSecret);