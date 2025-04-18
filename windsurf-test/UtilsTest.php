<?php
use PHPUnit\Framework\TestCase;

/**
 * Test case for utility functions in main.php
 */
class UtilsTest extends TestCase
{
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }
    
    /**
     * Test for normalizeStatus function
     */
    public function testNormalizeStatus()
    {
        $this->assertEquals('active', normalizeStatus(1), 'Status 1 should normalize to "active"');
        $this->assertEquals('inactive', normalizeStatus(0), 'Status 0 should normalize to "inactive"');
        $this->assertEquals('inactive', normalizeStatus(null), 'Null should normalize to "inactive"');
    }
    
    /**
     * Test for getStatusBadgeClass function
     */
    public function testGetStatusBadgeClass()
    {
        $this->assertEquals('status-badge status-active', getStatusBadgeClass(1), 'Active status should return correct CSS class');
        $this->assertEquals('status-badge status-inactive', getStatusBadgeClass(0), 'Inactive status should return correct CSS class');
    }
    
    /**
     * Test for getTimeAgo function
     */
    public function testGetTimeAgo()
    {
        // Test just now
        $now = date('Y-m-d H:i:s');
        $this->assertEquals('Just now', getTimeAgo($now));
        
        // Test minutes ago
        $minutesAgo = date('Y-m-d H:i:s', strtotime('-5 minutes'));
        $this->assertEquals('5 minutes ago', getTimeAgo($minutesAgo));
        
        // Test single minute
        $minuteAgo = date('Y-m-d H:i:s', strtotime('-1 minute'));
        $this->assertEquals('1 minute ago', getTimeAgo($minuteAgo));
        
        // Test hours ago
        $hoursAgo = date('Y-m-d H:i:s', strtotime('-3 hours'));
        $this->assertEquals('3 hours ago', getTimeAgo($hoursAgo));
        
        // Test days ago
        $daysAgo = date('Y-m-d H:i:s', strtotime('-2 days'));
        $this->assertEquals('2 days ago', getTimeAgo($daysAgo));
        
        // Test months ago
        $monthsAgo = date('Y-m-d H:i:s', strtotime('-3 months'));
        $this->assertEquals('3 months ago', getTimeAgo($monthsAgo));
        
        // Test years ago
        $yearsAgo = date('Y-m-d H:i:s', strtotime('-2 years'));
        $this->assertEquals('2 years ago', getTimeAgo($yearsAgo));
    }
    
    /**
     * Test for formatBytes function
     */
    public function testFormatBytes()
    {
        $this->assertEquals('0 B', formatBytes(0), 'Zero bytes should format correctly');
        $this->assertEquals('10 B', formatBytes(10), 'Bytes should format correctly');
        $this->assertEquals('1 KB', formatBytes(1024), 'KB should format correctly');
        $this->assertEquals('1 MB', formatBytes(1048576), 'MB should format correctly');
        $this->assertEquals('1 GB', formatBytes(1073741824), 'GB should format correctly');
        
        // Test with custom precision
        $this->assertEquals('1.5 KB', formatBytes(1536, 1), 'Custom precision should work correctly');
        $this->assertEquals('1.33 MB', formatBytes(1400000, 2), 'Custom precision should work correctly for MB');
    }
    
    /**
     * Test for human_time_diff function
     */
    public function testHumanTimeDiff()
    {
        $now = time();
        
        $this->assertEquals('just now', human_time_diff($now, $now), 'Same time should show as just now');
        $this->assertEquals('30 seconds', human_time_diff($now, $now + 30), '30 seconds difference');
        $this->assertEquals('5 minutes', human_time_diff($now, $now + 300), '5 minutes difference');
        $this->assertEquals('1 hour', human_time_diff($now, $now + 3600), '1 hour difference');
        $this->assertEquals('1 day', human_time_diff($now, $now + 86400), '1 day difference');
        $this->assertEquals('1 week', human_time_diff($now, $now + 604800), '1 week difference');
        $this->assertEquals('1 month', human_time_diff($now, $now + 2592000), '1 month difference');
        $this->assertEquals('1 year', human_time_diff($now, $now + 31536000), '1 year difference');
    }
    
    /**
     * Test for generateUuid function
     */
    public function testGenerateUuid()
    {
        $uuid = generateUuid();
        
        // UUID should match the standard format
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid, 'UUID should be valid v4 format');
        
        // Test uniqueness
        $uuid2 = generateUuid();
        $this->assertNotEquals($uuid, $uuid2, 'Generated UUIDs should be unique');
    }
}
