/**
 * Admin Class Management JavaScript Utilities
 * Provides functions for interacting with the class_handler.php API
 */

const ClassManager = {
    /**
     * Base configuration
     */
    config: {
        apiEndpoint: BASE + 'api/class',
        debug: false
    },
    
    /**
     * Makes an API request to the class handler
     * @param {Object} data - The data to send to the API
     * @param {Function} successCallback - Function to call on success
     * @param {Function} errorCallback - Function to call on error
     */
    apiRequest: function(data, successCallback, errorCallback) {
        // Log request in debug mode
        if (this.config.debug) {
            console.log('API Request:', data);
        }
        
        fetch(this.config.apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(data)
        })
        .then(response => response.json())
        .then(response => {
            // Log response in debug mode
            if (this.config.debug) {
                console.log('API Response:', response);
            }
            
            if (response.success) {
                if (typeof successCallback === 'function') {
                    successCallback(response);
                }
            } else {
                if (typeof errorCallback === 'function') {
                    errorCallback(response.error || 'An unknown error occurred');
                } else {
                    this.showAlert(response.error || 'An unknown error occurred', 'danger');
                }
            }
        })
        .catch(error => {
            console.error('API Error:', error);
            if (typeof errorCallback === 'function') {
                errorCallback('Network error. Please try again.');
            } else {
                this.showAlert('Network error. Please try again.', 'danger');
            }
        });
    },
    
    /**
     * Updates class details
     * @param {Object} classData - The class data to update
     * @param {Function} callback - Function to call after update
     */
    updateClass: function(classData, callback) {
        classData.action = 'update_class';
        this.apiRequest(classData, response => {
            this.showAlert(response.message, 'success');
            if (typeof callback === 'function') {
                callback(response);
            }
        });
    },
    
    /**
     * Enrolls a student in a class
     * @param {Number} studentId - The student ID
     * @param {Number} classId - The class ID
     * @param {Function} callback - Function to call after enrollment
     */
    enrollStudent: function(studentId, classId, callback) {
        this.apiRequest({
            action: 'enroll_student',
            student_id: studentId,
            class_id: classId
        }, response => {
            this.showAlert(response.message, 'success');
            if (typeof callback === 'function') {
                callback(response);
            }
        });
    },
    
    /**
     * Removes a student from a class
     * @param {Number} studentId - The student ID
     * @param {Number} classId - The class ID
     * @param {Function} callback - Function to call after removal
     */
    removeStudent: function(studentId, classId, callback) {
        this.apiRequest({
            action: 'remove_student',
            student_id: studentId,
            class_id: classId
        }, response => {
            this.showAlert(response.message, 'success');
            if (typeof callback === 'function') {
                callback(response);
            }
        });
    },
    
    /**
     * Archives or unarchives a recording
     * @param {String} recordingId - The recording ID
     * @param {Boolean} archive - Whether to archive (true) or unarchive (false)
     * @param {Number} classId - The class ID
     * @param {Function} callback - Function to call after archiving
     */
    archiveRecording: function(recordingId, archive, classId, callback) {
        this.apiRequest({
            action: 'archive_recording',
            recording_id: recordingId,
            archive: archive ? 'true' : 'false',
            class_id: classId
        }, response => {
            this.showAlert(response.message, 'success');
            if (typeof callback === 'function') {
                callback(response);
            }
        });
    },
    
    /**
     * Deletes a recording
     * @param {String} recordingId - The recording ID
     * @param {Number} classId - The class ID
     * @param {Function} callback - Function to call after deletion
     */
    deleteRecording: function(recordingId, classId, callback) {
        if (!confirm('Are you sure you want to delete this recording? This action cannot be undone.')) {
            return;
        }
        
        this.apiRequest({
            action: 'delete_recording',
            recording_id: recordingId,
            class_id: classId
        }, response => {
            this.showAlert(response.message, 'success');
            if (typeof callback === 'function') {
                callback(response);
            }
        });
    },
    
    /**
     * Archives a feedback
     * @param {Number} feedbackId - The feedback ID
     * @param {Number} classId - The class ID
     * @param {Function} callback - Function to call after archiving
     */
    archiveFeedback: function(feedbackId, classId, callback) {
        if (!confirm('Are you sure you want to archive this feedback?')) {
            return;
        }
        
        this.apiRequest({
            action: 'archive_feedback',
            feedback_id: feedbackId,
            class_id: classId
        }, response => {
            this.showAlert(response.message, 'success');
            if (typeof callback === 'function') {
                callback(response);
            }
        });
    },
    
    /**
     * Unarchives a feedback
     * @param {Number} feedbackId - The feedback ID
     * @param {Number} classId - The class ID
     * @param {Function} callback - Function to call after unarchiving
     */
    unarchiveFeedback: function(feedbackId, classId, callback) {
        if (!confirm('Are you sure you want to unarchive this feedback?')) {
            return;
        }
        
        this.apiRequest({
            action: 'unarchive_feedback',
            feedback_id: feedbackId,
            class_id: classId
        }, response => {
            this.showAlert(response.message, 'success');
            if (typeof callback === 'function') {
                callback(response);
            }
        });
    },
    
    /**
     * Deletes a feedback
     * @param {Number} feedbackId - The feedback ID
     * @param {Number} classId - The class ID
     * @param {Function} callback - Function to call after deletion
     */
    deleteFeedback: function(feedbackId, classId, callback) {
        if (!confirm('Are you sure you want to delete this feedback? This action cannot be undone.')) {
            return;
        }
        
        this.apiRequest({
            action: 'delete_feedback',
            feedback_id: feedbackId,
            class_id: classId
        }, response => {
            this.showAlert(response.message, 'success');
            if (typeof callback === 'function') {
                callback(response);
            }
        });
    },
    
    /**
     * Updates a session status
     * @param {Number} scheduleId - The schedule ID
     * @param {String} status - The new status
     * @param {Number} classId - The class ID
     * @param {Function} callback - Function to call after update
     */
    updateSessionStatus: function(scheduleId, status, classId, callback) {
        this.apiRequest({
            action: 'update_session_status',
            schedule_id: scheduleId,
            status: status,
            class_id: classId
        }, response => {
            this.showAlert(response.message, 'success');
            if (typeof callback === 'function') {
                callback(response);
            }
        });
    },
    
    /**
     * Reschedules a session
     * @param {Number} scheduleId - The schedule ID
     * @param {String} sessionDate - The new date (YYYY-MM-DD)
     * @param {String} startTime - The new start time (HH:MM)
     * @param {String} endTime - The new end time (HH:MM)
     * @param {Number} classId - The class ID
     * @param {Function} callback - Function to call after reschedule
     */
    rescheduleSession: function(scheduleId, sessionDate, startTime, endTime, classId, callback) {
        this.apiRequest({
            action: 'reschedule_session',
            schedule_id: scheduleId,
            session_date: sessionDate,
            start_time: startTime,
            end_time: endTime,
            class_id: classId
        }, response => {
            this.showAlert(response.message, 'success');
            if (typeof callback === 'function') {
                callback(response);
            }
        });
    },
    
    /**
     * Shows an alert message
     * @param {String} message - The message to display
     * @param {String} type - The alert type (success, danger, etc.)
     * @param {Number} duration - How long to show the alert (ms)
     */
    showAlert: function(message, type = 'success', duration = 5000) {
        // Create alert element if it doesn't exist
        let alertContainer = document.getElementById('alert-container');
        if (!alertContainer) {
            alertContainer = document.createElement('div');
            alertContainer.id = 'alert-container';
            alertContainer.style.position = 'fixed';
            alertContainer.style.top = '20px';
            alertContainer.style.right = '20px';
            alertContainer.style.zIndex = '9999';
            alertContainer.style.maxWidth = '400px';
            document.body.appendChild(alertContainer);
        }
        
        // Create the alert
        const alertEl = document.createElement('div');
        alertEl.className = `alert alert-${type} alert-dismissible fade show`;
        alertEl.role = 'alert';
        alertEl.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Add to container
        alertContainer.appendChild(alertEl);
        
        // Auto-dismiss after duration
        if (duration > 0) {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alertEl);
                bsAlert.close();
            }, duration);
        }
        
        // Remove after closing animation
        alertEl.addEventListener('closed.bs.alert', () => {
            alertEl.remove();
        });
    },
    
    /**
     * Common UI initialization for admin pages
     * Sets up common event listeners and UI behaviors
     */
    initUI: function() {
        // Auto-close alert messages after 5 seconds
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                const closeBtn = alert.querySelector('.btn-close');
                if (closeBtn) {
                    closeBtn.click();
                }
            }, 5000);
        });
        
        // Handle image loading errors
        document.querySelectorAll('img').forEach(img => {
            img.addEventListener('error', function() {
                if (!this.classList.contains('img-error')) {
                    this.classList.add('img-error');
                    this.src = BASE + 'assets/img/users/default.jpg';
                }
            });
        });
        
        // Initialize tooltips
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
                new bootstrap.Tooltip(el);
            });
        }
        
        // Initialize popovers
        if (typeof bootstrap !== 'undefined' && bootstrap.Popover) {
            document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => {
                new bootstrap.Popover(el);
            });
        }
    },
    
    /**
     * Helper method to format dates consistently
     * @param {String|Date} date - Date to format
     * @param {String} format - Output format (short, medium, long)
     * @returns {String} Formatted date string
     */
    formatDate: function(date, format = 'medium') {
        if (!date) return '-';
        
        const dateObj = typeof date === 'string' ? new Date(date) : date;
        
        if (isNaN(dateObj.getTime())) return 'Invalid date';
        
        switch (format) {
            case 'short':
                return dateObj.toLocaleDateString();
            case 'long':
                return dateObj.toLocaleDateString() + ' ' + dateObj.toLocaleTimeString();
            case 'time':
                return dateObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            case 'medium':
            default:
                return dateObj.toLocaleDateString('en-US', { 
                    month: 'short', 
                    day: 'numeric', 
                    year: 'numeric' 
                });
        }
    },
    
    /**
     * Helper method to format currency
     * @param {Number} amount - Amount to format
     * @param {String} currency - Currency code
     * @returns {String} Formatted currency string
     */
    formatCurrency: function(amount, currency = 'USD') {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency
        }).format(amount);
    }
}; 