# Meeting Analytics Implementation Notes

## Overview
This document outlines the implementation of BigBlueButton meeting analytics in the TechTutor platform.

## Database Structure
```sql
CREATE TABLE IF NOT EXISTS meeting_analytics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    meeting_id VARCHAR(255) NOT NULL,
    tutor_id VARCHAR(255) NOT NULL,
    participant_count INT DEFAULT 0,
    duration INT DEFAULT 0,
    start_time DATETIME,
    end_time DATETIME,
    recording_available BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meeting_id) REFERENCES meetings(meeting_id),
    FOREIGN KEY (tutor_id) REFERENCES users(user_id)
);
```

## Components

### 1. Webhook Integration
- Location: `backends/handler/meeting_webhook.php`
- Purpose: Captures meeting events from BigBlueButton
- Events handled:
  - Meeting started
  - Meeting ended
  - Recording ready
  - Participant joined/left

### 2. Data Aggregation
- Location: `backends/management/meeting_analytics.php`
- Features:
  - Daily/Weekly/Monthly statistics
  - Participation trends
  - Engagement metrics
  - Recording statistics

## Implementation Steps

1. Add webhook endpoint in BigBlueButton configuration:
```xml
<bigbluebutton>
    <webhook>
        <callbackURL>https://your-domain.com/backends/handler/meeting_webhook.php</callbackURL>
        <meetingEvents>true</meetingEvents>
        <recordingEvents>true</recordingEvents>
    </webhook>
</bigbluebutton>
```

2. Configure analytics collection:
```php
// In meeting creation
$options['meta_analytics-callback-url'] = BASE . 'backends/handler/meeting_webhook.php';
```

3. Access analytics:
```php
// Example: Get monthly statistics
$analytics = new MeetingAnalytics();
$stats = $analytics->getAggregatedStats($tutorId, 'monthly');
```

## Usage Examples

### Fetch Aggregated Stats
```php
$analytics = new MeetingAnalytics();

// Monthly stats
$monthlyStats = $analytics->getAggregatedStats(
    $_SESSION['user'],
    'monthly',
    date('Y-m-d', strtotime('-6 months')),
    date('Y-m-d')
);

// Participation trends
$trends = $analytics->getParticipationTrends(
    $_SESSION['user'],
    date('Y-m-d', strtotime('-30 days')),
    date('Y-m-d')
);
```

### Process Webhook Data
```php
// In meeting_webhook.php
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

switch ($data['event']) {
    case 'meeting_ended':
        // Record meeting statistics
        break;
    case 'participant_joined':
        // Update participant count
        break;
}
```

## Charts and Visualizations

The analytics page (`pages/techguru/analytics.php`) includes:
1. Session activity timeline
2. Participant engagement metrics
3. Duration distribution
4. Recording statistics

## Future Enhancements

1. Real-time Analytics
   - WebSocket integration for live updates
   - Live participant tracking

2. Advanced Metrics
   - Engagement scoring
   - Session quality indicators
   - Participation patterns

3. Export Features
   - CSV/Excel reports
   - Automated reporting
   - Custom date ranges

## Maintenance

Regular tasks:
1. Monitor webhook reliability
2. Clean up old analytics data
3. Optimize queries for large datasets
4. Backup analytics data

## Troubleshooting

Common issues and solutions:
1. Missing webhook events
   - Check BigBlueButton configuration
   - Verify server accessibility
   - Check error logs

2. Data inconsistencies
   - Run validation queries
   - Check foreign key constraints
   - Verify webhook processing

3. Performance issues
   - Index optimization
   - Query optimization
   - Data archiving 