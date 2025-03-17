<?php 
    require_once '../backends/main.php';
    $termsFilePath = ROOT_PATH . '/docs/Techtutor_Terms-and-Conditions.pdf';
    $lastModified = file_exists($termsFilePath) ? date('F d, Y', filemtime($termsFilePath)) : 'Unknown';
?>
<!DOCTYPE html>
<html lang="en">
<?php include ROOT_PATH . '/components/head.php'; ?>

<body data-user-role="<?php echo isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : ''; ?>">
    <div class="terms-container">
        <div class="terms-header">
            <h1>Terms and Conditions</h1>
            <p>Last updated: <?php echo $lastModified; ?></p>
        </div>

        <div class="terms-content">
            <h3>1. Introduction</h3>
            <p>Welcome to TechTutor, an online platform designed to bridge the IT education gap through digital connectivity. By accessing or using our platform, you agree to comply with these Terms and Conditions. If you do not agree, please refrain from using our services.</p>

            <h3>2. Definitions</h3>
            <ul>
                <li><strong>Platform:</strong> Refers to the TechTutor website and all associated services.</li>
                <li><strong>User:</strong> Refers to anyone accessing the platform, including students, tutors, and guests.</li>
                <li><strong>Content:</strong> Refers to text, images, videos, and materials shared on the platform.</li>
            </ul>

            <h3>3. User Responsibilities</h3>
            <p>Users must use the platform solely for educational purposes and provide accurate registration details. They are required to follow TechTutor's Fair Use Policy and engage in respectful interactions. Prohibited activities include:</p>
            <ul>
                <li>Harassment, discrimination, or offensive behavior</li>
                <li>Sharing illegal, inappropriate, or copyrighted content</li>
                <li>Spreading misinformation or misusing platform features</li>
            </ul>

            <h3>4. Account Registration and Security</h3>
            <p>Users are responsible for maintaining the security of their accounts. Any unauthorized access should be reported to <a href="mailto:support@techtutor.cfd">support@techtutor.cfd</a>. TechTutor reserves the right to suspend accounts that violate these terms.</p>

            <h3>5. Content Submission and Shared Use</h3>
            <p>By submitting content, users grant TechTutor a non-exclusive, royalty-free, worldwide license to use, distribute, and modify materials within the platform for educational purposes. TechTutor reserves the right to restrict or remove content that violates our policies.</p>

            <h3>6. Intellectual Property Rights</h3>
            <p>All platform materials belong to TechTutor or its content contributors. Unauthorized reproduction, sharing, or distribution is strictly prohibited. Violations may result in account suspension and legal consequences.</p>

            <h3 id="terms-of-use">7. Fair Use Policy</h3>
            <p>TechTutor promotes a respectful learning environment. Users must:</p>
            <ul>
                <li>Avoid inappropriate content or language</li>
                <li>Respect intellectual property and copyright laws</li>
                <li>Not record or distribute lessons without permission</li>
            </ul>
            <p>Misuse of materials, including false certification claims or plagiarism, may lead to disciplinary action.</p>

            <h3 id="data-protection">8. Data Protection and Privacy</h3>
            <p>TechTutor collects and processes user data as outlined in our <a href="<?php echo BASE; ?>privacy-policy">Data Protection Policy</a>. We prioritize user privacy and implement strict security measures to protect personal information. Concerns should be reported to <a href="mailto:datasecurity@techtutor.cfd">datasecurity@techtutor.cfd</a>.</p>

            <h3>9. Payments and Transactions</h3>
            <p>Certain services may require payment, which is generally non-refundable. Transactions are processed securely, and any unauthorized activity should be reported immediately to <a href="mailto:support@techtutor.cfd">support@techtutor.cfd</a>.</p>

            <h3>10. File Storage Policy</h3>
            <p>TechTutor utilizes Google Drive for file management. By using our platform, you agree to:</p>
            <ul>
                <li>Respect assigned storage quotas</li>
                <li>Only upload files relevant to learning activities</li>
                <li>Not share login credentials with others</li>
            </ul>

            <h3>11. Online Session Guidelines</h3>
            <p>Our virtual classroom sessions are powered by BigBlueButton. Users are expected to:</p>
            <ul>
                <li>Join sessions on time and adhere to session guidelines</li>
                <li>Ensure a stable internet connection for a smooth experience</li>
                <li>Not record or distribute session content without permission</li>
            </ul>

            <h3>12. Service Availability</h3>
            <p>While we strive for 100% uptime:</p>
            <ul>
                <li>Online sessions may be affected by technical issues</li>
                <li>File storage services may undergo maintenance</li>
                <li>Platform features may be updated or modified</li>
                <li>Service interruptions will be communicated when possible</li>
            </ul>

            <h3>13. Account Termination and Policy Enforcement</h3>
            <p>TechTutor reserves the right to suspend or terminate accounts that violate policies. Severe breaches, such as fraud or intellectual property theft, may result in legal consequences.</p>

            <h3>14. Disclaimer and Limitation of Liability</h3>
            <p>TechTutor provides services "as is" without warranties of any kind. We are not liable for any loss or damages arising from platform usage.</p>

            <h3>15. Indemnification</h3>
            <p>Users agree to indemnify TechTutor from any legal claims, damages, or disputes resulting from their use of the platform.</p>

            <h3>16. Changes to Terms and Conditions</h3>
            <p>TechTutor may update these Terms at any time. Continued use of the platform signifies acceptance of the updated terms.</p>

            <h3>17. Governing Law</h3>
            <p>These Terms shall be governed by the applicable laws of [Your Country/Region].</p>

            <h3>18. Contact Information</h3>
            <p>For inquiries or concerns, please contact:</p>
            <ul>
                <li><strong>Data Protection:</strong> <a href="mailto:datasecurity@techtutor.cfd">datasecurity@techtutor.cfd</a></li>
                <li><strong>Support:</strong> <a href="mailto:support@techtutor.cfd">support@techtutor.cfd</a></li>
                <li><strong>Administration:</strong> <a href="mailto:admin@techtutor.cfd">admin@techtutor.cfd</a></li>
            </ul>

            <p class="text-muted mt-5">By continuing to use TechTutor, you acknowledge that you have read and agree to these terms and conditions.</p>
        </div>
    </div>
    <!-- Back to Home Button -->
                <div class="text-center mt-4">
                    <a href="<?php echo BASE; ?>" class="btn btn-dark">
                        Back to Home
                    </a>
                </div>

    <!-- Footer & Scripts -->
    <?php include ROOT_PATH . '/components/footer.php'; ?>
</body>
</html>
