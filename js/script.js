// Global utility functions for the application

class ResearchDB {
    static async apiRequest(url, options = {}) {
        try {
            const response = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    ...options.headers
                },
                ...options
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    }

    static showAlert(message, type = 'info', duration = 5000) {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.alert');
        existingAlerts.forEach(alert => alert.remove());

        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            <i class="fas ${this.getAlertIcon(type)} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const container = document.querySelector('.container') || document.body;
        container.insertBefore(alertDiv, container.firstChild);
        
        if (duration > 0) {
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, duration);
        }

        return alertDiv;
    }

    static getAlertIcon(type) {
        const icons = {
            success: 'fa-check-circle',
            danger: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        return icons[type] || 'fa-info-circle';
    }

    static formatDate(dateString) {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString();
    }

    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    static validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    static formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        }
        if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return num.toString();
    }

    static async checkSession() {
        try {
            const response = await fetch('api/auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    username: '<?php echo ADMIN_USERNAME; ?>',
                    password: '<?php echo ADMIN_PASSWORD; ?>'
                })
            });
            return response.ok;
        } catch (error) {
            return false;
        }
    }

    static handleApiError(error) {
        console.error('API Error:', error);
        this.showAlert(
            error.message || 'An error occurred while processing your request',
            'danger'
        );
    }

    // Form validation utilities
    static validateRequiredFields(formData, requiredFields) {
        const errors = [];
        requiredFields.forEach(field => {
            if (!formData[field] || formData[field].toString().trim() === '') {
                errors.push(`${field} is required`);
            }
        });
        return errors;
    }

    static async validateUniqueField(endpoint, field, value, excludeId = null) {
        try {
            const data = await this.apiRequest(endpoint);
            return !data.some(item => {
                if (excludeId && item.id === excludeId) return false;
                return item[field] === value;
            });
        } catch (error) {
            return false;
        }
    }
}

// DOM Ready utility
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.parentNode) {
                bootstrap.Alert.getOrCreateInstance(alert).close();
            }
        }, 5000);
    });

    // Add loading state to buttons on form submission
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const buttons = this.querySelectorAll('button[type="submit"]');
            buttons.forEach(button => {
                button.disabled = true;
                const originalText = button.innerHTML;
                button.innerHTML = `
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Processing...
                `;
                button.setAttribute('data-original-text', originalText);
            });
        });
    });

    // Session check every 5 minutes
    setInterval(async () => {
        const sessionValid = await ResearchDB.checkSession();
        if (!sessionValid) {
            ResearchDB.showAlert('Your session has expired. Please log in again.', 'warning');
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 3000);
        }
    }, 300000); // 5 minutes
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ResearchDB;
}