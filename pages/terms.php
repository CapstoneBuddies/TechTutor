<?php 
    require_once '../backends/main.php';
?>
<!DOCTYPE html>
<html lang="en">
<?php include ROOT_PATH . '/components/head.php'; ?>

<body data-user-role="<?php echo isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : ''; ?>">
    <div class="terms-container">
        <div class="terms-header">
            <h1>Terms and Conditions</h1>
            <p>Last updated: <?php echo date('F d, Y'); ?></p>
        </div>

        <div class="terms-content">
            <h3>1. Introduction</h3>
            <p>Welcome to TechTutor! These terms and conditions outline the rules and regulations for using our platform.</p>

            <h3>2. Platform Overview</h3>
            <p>TechTutor is a platform connecting TechKids (students) with TechGurus (tutors) for personalized IT learning experiences.</p>

            <h3>3. User Roles</h3>
            <p><strong>TechKids:</strong> Learners seeking IT knowledge and skills through our platform.<br>
            <strong>TechGurus:</strong> Qualified tutors providing IT education and guidance.</p>

            <h3>4. Privacy & Data</h3>
            <p>We respect your privacy and protect your personal information. Your contact information may be used for platform-related communications and connecting with tutors/students.</p>

            <h3>5. File Storage</h3>
            <p>TechTutor uses Google Drive for secure file storage. By using our platform, you agree to:
                <ul>
                    <li>Respect storage quotas assigned to your account</li>
                    <li>Only upload files related to learning activities</li>
                    <li>Not share access credentials with others</li>
                    <li>Understand that files may be monitored for compliance</li>
                </ul>
            </p>

            <h3>6. Online Sessions</h3>
            <p>Our virtual classroom sessions are powered by BigBlueButton. When participating in online sessions:
                <ul>
                    <li>Maintain professional conduct during all sessions</li>
                    <li>Do not record sessions without explicit permission</li>
                    <li>Ensure stable internet connection for quality experience</li>
                    <li>Join sessions on time and follow session guidelines</li>
                    <li>Report technical issues promptly to support</li>
                </ul>
            </p>

            <h3>7. Terms of Use</h3>
            <p>By using our platform, you agree to:
                <ul>
                    <li>Provide accurate information during registration</li>
                    <li>Maintain professional conduct during sessions</li>
                    <li>Respect intellectual property rights</li>
                    <li>Follow our community guidelines</li>
                    <li>Not misuse platform resources or services</li>
                </ul>
            </p>

            <h3>8. Service Availability</h3>
            <p>While we strive for 100% uptime:
                <ul>
                    <li>Online sessions may be affected by technical issues</li>
                    <li>File storage service may undergo maintenance</li>
                    <li>Platform features may be updated or modified</li>
                    <li>Service interruptions will be communicated when possible</li>
                </ul>
            </p>

            <p class="text-muted mt-5">By continuing to use TechTutor, you acknowledge that you have read and agree to these terms and conditions. We reserve the right to update these terms as needed, with notifications of significant changes sent to all users.</p>
        </div>
    </div>

    <!-- Vendor JS Files -->
    <?php include ROOT_PATH . '/components/footer.php'; ?>
</body>
</html>