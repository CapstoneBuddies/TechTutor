<?php
include_once "../../backends/main.php";
include_once "../../components/head.php";
include_once "../../components/header.php";

// Check if user is logged in and is an admin
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'ADMIN') {
    $_SESSION['msg'] = "You must be logged in as an administrator to access this page";
    header("Location: " . BASE . "login");
    exit;
}

// Get webhook URL from env or fallback
$webhookUrl = BBB_WEBHOOK_URL ?? null;

?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title mb-0">BigBlueButton Webhook Management</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <p><strong>Note:</strong> Webhooks enable BigBlueButton to send event notifications to your application.</p>
                        <p>Current webhook URL: <code id="webhook-url"><?php echo $webhookUrl; ?></code></p>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Registered Webhooks</h5>
                        <div id="webhooks-list" class="mt-3">
                            <div class="d-flex justify-content-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Register New Webhook</h5>
                                </div>
                                <div class="card-body">
                                    <button id="register-webhook" class="btn btn-success mb-2">
                                        <i class="bi bi-plus-circle"></i> Register Webhook
                                    </button>
                                    
                                    <button id="send-test-webhook" class="btn btn-info mb-2 ms-2">
                                        <i class="bi bi-envelope"></i> Send Test Webhook
                                    </button>
                                    
                                    <div id="register-result" class="mt-3"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Webhook Details</h5>
                                </div>
                                <div class="card-body">
                                    <div id="webhook-details">
                                        <p class="text-muted">Select a webhook to view details</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for webhook management -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load webhooks on page load
    loadWebhooks();
    
    // Register webhook button
    document.getElementById('register-webhook').addEventListener('click', function() {
        registerWebhook();
    });
    
    // Send test webhook button
    document.getElementById('send-test-webhook').addEventListener('click', function() {
        sendTestWebhook();
    });
    
    // Function to load webhooks
    function loadWebhooks() {
        fetch('<?php echo BASE; ?>admin/webhooks')
            .then(response => response.json())
            .then(data => {
                const webhooksContainer = document.getElementById('webhooks-list');
                
                if (data.success && data.hooks && data.hooks.length > 0) {
                    let html = '<div class="table-responsive"><table class="table table-striped">';
                    html += '<thead><tr><th>Hook ID</th><th>Callback URL</th><th>Meeting ID</th><th>Actions</th></tr></thead>';
                    html += '<tbody>';
                    
                    data.hooks.forEach(hook => {
                        html += `<tr>
                            <td>${hook.hookID}</td>
                            <td>${hook.callbackURL}</td>
                            <td>${hook.meetingID || 'All meetings'}</td>
                            <td>
                                <button class="btn btn-sm btn-info view-details" data-hook-id="${hook.hookID}">
                                    <i class="bi bi-info-circle"></i>
                                </button>
                                <button class="btn btn-sm btn-danger delete-hook" data-hook-id="${hook.hookID}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>`;
                    });
                    
                    html += '</tbody></table></div>';
                    webhooksContainer.innerHTML = html;
                    
                    // Add event listeners for view details buttons
                    document.querySelectorAll('.view-details').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const hookId = this.getAttribute('data-hook-id');
                            viewWebhookDetails(hookId);
                        });
                    });
                    
                    // Add event listeners for delete buttons
                    document.querySelectorAll('.delete-hook').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const hookId = this.getAttribute('data-hook-id');
                            deleteWebhook(hookId);
                        });
                    });
                } else {
                    webhooksContainer.innerHTML = '<div class="alert alert-warning">No webhooks registered</div>';
                }
            })
            .catch(error => {
                console.error('Error loading webhooks:', error);
                document.getElementById('webhooks-list').innerHTML = 
                    '<div class="alert alert-danger">Error loading webhooks</div>';
            });
    }
    
    // Function to register a webhook
    function registerWebhook() {
        const registerBtn = document.getElementById('register-webhook');
        const resultDiv = document.getElementById('register-result');
        
        registerBtn.disabled = true;
        registerBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Registering...';
        
        fetch('<?php echo BASE; ?>admin/register-webhook')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = `<div class="alert alert-success">
                        <strong>Success!</strong> Webhook registered with ID: ${data.hookID}
                    </div>`;
                    loadWebhooks(); // Reload the webhooks list
                } else {
                    resultDiv.innerHTML = `<div class="alert alert-danger">
                        <strong>Error!</strong> ${data.message || 'Failed to register webhook'}
                    </div>`;
                }
            })
            .catch(error => {
                console.error('Error registering webhook:', error);
                resultDiv.innerHTML = '<div class="alert alert-danger">Error registering webhook</div>';
            })
            .finally(() => {
                registerBtn.disabled = false;
                registerBtn.innerHTML = '<i class="bi bi-plus-circle"></i> Register Webhook';
            });
    }
    
    // Function to view webhook details
    function viewWebhookDetails(hookId) {
        const detailsDiv = document.getElementById('webhook-details');
        
        detailsDiv.innerHTML = '<div class="d-flex justify-content-center">' +
            '<div class="spinner-border text-primary" role="status">' +
            '<span class="visually-hidden">Loading...</span></div></div>';
        
        fetch(`<?php echo BASE; ?>admin/webhooks?action=info&hookID=${hookId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.hook) {
                    const hook = data.hook;
                    detailsDiv.innerHTML = `
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Hook ID: ${hook.hookID}</h6>
                                <p><strong>Callback URL:</strong> ${hook.callbackURL}</p>
                                <p><strong>Meeting ID:</strong> ${hook.meetingID || 'All meetings'}</p>
                                <p><strong>Get Raw Data:</strong> ${hook.getRaw === 'true' ? 'Yes' : 'No'}</p>
                            </div>
                        </div>
                    `;
                } else {
                    detailsDiv.innerHTML = `<div class="alert alert-warning">
                        <strong>Warning!</strong> ${data.message || 'Could not retrieve webhook details'}
                    </div>`;
                }
            })
            .catch(error => {
                console.error('Error getting webhook details:', error);
                detailsDiv.innerHTML = '<div class="alert alert-danger">Error getting webhook details</div>';
            });
    }
    
    // Function to delete a webhook
    function deleteWebhook(hookId) {
        if (!confirm('Are you sure you want to delete this webhook?')) {
            return;
        }
        
        fetch(`<?php echo BASE; ?>admin/webhooks?action=delete&hookID=${hookId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Webhook deleted successfully');
                    loadWebhooks(); // Reload the webhooks list
                } else {
                    alert(`Error: ${data.message || 'Failed to delete webhook'}`);
                }
            })
            .catch(error => {
                console.error('Error deleting webhook:', error);
                alert('Error deleting webhook');
            });
    }
    
    // Function to send a test webhook
    function sendTestWebhook() {
        const resultDiv = document.getElementById('register-result');
        const testBtn = document.getElementById('send-test-webhook');
        
        testBtn.disabled = true;
        testBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';
        
        fetch('<?php echo BASE; ?>admin/send-test-webhook')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = `<div class="alert alert-success">
                        <strong>Success!</strong> Test webhook sent successfully.
                    </div>`;
                } else {
                    resultDiv.innerHTML = `<div class="alert alert-danger">
                        <strong>Error!</strong> ${data.message || 'Failed to send test webhook'}
                    </div>`;
                }
            })
            .catch(error => {
                console.error('Error sending test webhook:', error);
                resultDiv.innerHTML = '<div class="alert alert-danger">Error sending test webhook. Check the console for details.</div>';
            })
            .finally(() => {
                testBtn.disabled = false;
                testBtn.innerHTML = '<i class="bi bi-envelope"></i> Send Test Webhook';
            });
    }
});
</script>

<?php include_once "../../components/footer.php"; ?> 