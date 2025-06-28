// Password visibility toggle
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const passwordEye = document.getElementById('password-eye');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordEye.className = 'fas fa-eye-slash';
    } else {
        passwordInput.type = 'password';
        passwordEye.className = 'fas fa-eye';
    }
}

// Form validation and enhancement
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.login-form');
    const inputs = form.querySelectorAll('.form-input, .form-select');
    const submitBtn = document.querySelector('.login-btn');
    const roleSelect = document.getElementById('role');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    
    // Add loading state to submit button
    form.addEventListener('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
            return false;
        }
        
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
        submitBtn.disabled = true;
        
        // Re-enable button after 10 seconds as fallback
        setTimeout(() => {
            submitBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In';
            submitBtn.disabled = false;
        }, 10000);
    });
    
    // Enhanced form validation
    inputs.forEach(input => {
        input.addEventListener('blur', validateField);
        input.addEventListener('input', clearErrors);
        input.addEventListener('keydown', handleKeyPress);
    });
    
    // Role-based placeholder updates
    roleSelect.addEventListener('change', function() {
        updatePlaceholders(this.value);
        clearErrors({ target: this });
    });
    
    function updatePlaceholders(role) {
        switch(role) {
            case 'student':
                usernameInput.placeholder = 'Enter your student ID or username';
                break;
            case 'instructor':
                usernameInput.placeholder = 'Enter your instructor ID or username';
                break;
            case 'coordinator':
                usernameInput.placeholder = 'Enter your coordinator ID or username';
                break;
            default:
                usernameInput.placeholder = 'Enter your username';
        }
    }
    
    function handleKeyPress(e) {
        // Submit form on Enter key
        if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') {
            e.preventDefault();
            form.dispatchEvent(new Event('submit'));
        }
        
        // Clear errors on typing
        if (e.target.classList.contains('error')) {
            clearErrors(e);
        }
    }
    
    function validateForm() {
        let isValid = true;
        
        // Validate all fields
        inputs.forEach(input => {
            if (!validateField({ target: input })) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    function validateField(e) {
        const field = e.target;
        const value = field.value.trim();
        let isValid = true;
        
        // Remove existing error styling
        field.classList.remove('error');
        
        // Validate based on field type
        if (field.name === 'role' && !value) {
            showFieldError(field, 'Please select your role');
            isValid = false;
        } else if (field.name === 'username') {
            if (!value) {
                showFieldError(field, 'Username is required');
                isValid = false;
            } else if (value.length < 3) {
                showFieldError(field, 'Username must be at least 3 characters');
                isValid = false;
            } else if (!/^[a-zA-Z0-9_.-]+$/.test(value)) {
                showFieldError(field, 'Username can only contain letters, numbers, dots, hyphens, and underscores');
                isValid = false;
            }
        } else if (field.name === 'password') {
            if (!value) {
                showFieldError(field, 'Password is required');
                isValid = false;
            } else if (value.length < 4) {
                showFieldError(field, 'Password must be at least 4 characters');
                isValid = false;
            }
        }
        
        return isValid;
    }
    
    function showFieldError(field, message) {
        field.classList.add('error');
        
        // Remove existing error message
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
        
        // Add new error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message}`;
        field.parentNode.appendChild(errorDiv);
        
        // Add shake animation
        field.style.animation = 'shake 0.5s ease-in-out';
        setTimeout(() => {
            field.style.animation = '';
        }, 500);
    }
    
    function clearErrors(e) {
        const field = e.target;
        field.classList.remove('error');
        const errorMsg = field.parentNode.querySelector('.field-error');
        if (errorMsg) {
            errorMsg.style.opacity = '0';
            setTimeout(() => errorMsg.remove(), 200);
        }
    }
    
    // Auto-hide alerts after 5 seconds with animation
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        // Make alert dismissible
        const closeBtn = document.createElement('button');
        closeBtn.className = 'alert-close';
        closeBtn.innerHTML = '<i class="fas fa-times"></i>';
        closeBtn.onclick = () => dismissAlert(alert);
        alert.appendChild(closeBtn);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            dismissAlert(alert);
        }, 5000);
    });
    
    function dismissAlert(alert) {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 300);
    }
    
    // Caps Lock detection
    passwordInput.addEventListener('keydown', function(e) {
        const capsLockOn = e.getModifierState && e.getModifierState('CapsLock');
        const capsWarning = document.getElementById('caps-warning');
        
        if (capsLockOn && !capsWarning) {
            const warning = document.createElement('div');
            warning.id = 'caps-warning';
            warning.className = 'caps-lock-warning';
            warning.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Caps Lock is on';
            passwordInput.parentNode.appendChild(warning);
        } else if (!capsLockOn && capsWarning) {
            capsWarning.remove();
        }
    });
    
    // Remember me functionality
    const rememberCheckbox = document.querySelector('input[name="remember_me"]');
    const savedUsername = localStorage.getItem('rememberedUsername');
    const savedRole = localStorage.getItem('rememberedRole');
    
    // Load saved credentials
    if (savedUsername && savedRole) {
        usernameInput.value = savedUsername;
        roleSelect.value = savedRole;
        rememberCheckbox.checked = true;
        updatePlaceholders(savedRole);
    }
    
    // Save credentials on form submit
    form.addEventListener('submit', function() {
        if (rememberCheckbox.checked) {
            localStorage.setItem('rememberedUsername', usernameInput.value);
            localStorage.setItem('rememberedRole', roleSelect.value);
        } else {
            localStorage.removeItem('rememberedUsername');
            localStorage.removeItem('rememberedRole');
        }
    });
    
    // Smooth focus transitions
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentNode.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            if (!this.value) {
                this.parentNode.classList.remove('focused');
            }
        });
        
        // Check if field has value on load
        if (input.value) {
            input.parentNode.classList.add('focused');
        }
    });
    
    // Prevent double submission
    let isSubmitting = false;
    form.addEventListener('submit', function(e) {
        if (isSubmitting) {
            e.preventDefault();
            return false;
        }
        isSubmitting = true;
        
        // Reset after 3 seconds
        setTimeout(() => {
            isSubmitting = false;
        }, 3000);
    });
    
    // Add keyboard navigation for better accessibility
    document.addEventListener('keydown', function(e) {
        // Alt + L to focus on login form
        if (e.altKey && e.key === 'l') {
            e.preventDefault();
            roleSelect.focus();
        }
        
        // Escape to clear form
        if (e.key === 'Escape') {
            clearForm();
        }
    });
    
    function clearForm() {
        inputs.forEach(input => {
            input.value = '';
            clearErrors({ target: input });
        });
        rememberCheckbox.checked = false;
        roleSelect.focus();
    }
    
    // Add visual feedback for network status
    window.addEventListener('online', function() {
        showNetworkStatus('Connected', 'success');
    });
    
    window.addEventListener('offline', function() {
        showNetworkStatus('No internet connection', 'error');
    });
    
    function showNetworkStatus(message, type) {
        const existing = document.querySelector('.network-status');
        if (existing) existing.remove();
        
        const statusDiv = document.createElement('div');
        statusDiv.className = `network-status ${type}`;
        statusDiv.innerHTML = `<i class="fas fa-${type === 'success' ? 'wifi' : 'wifi-slash'}"></i> ${message}`;
        document.body.appendChild(statusDiv);
        
        setTimeout(() => {
            if (statusDiv.parentNode) {
                statusDiv.style.opacity = '0';
                setTimeout(() => statusDiv.remove(), 300);
            }
        }, 3000);
    }
});

// Add additional CSS for enhanced features
const enhancedStyles = document.createElement('style');
enhancedStyles.textContent = `
    .form-input.error,
    .form-select.error {
        border-color: var(--error-color);
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }
    
    .field-error {
        color: var(--error-color);
        font-size: 0.75rem;
        margin-top: 0.25rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        opacity: 1;
        transition: opacity 0.2s ease-in-out;
    }
    
    .alert-close {
        background: none;
        border: none;
        color: inherit;
        cursor: pointer;
        padding: 0.25rem;
        border-radius: var(--radius-sm);
        margin-left: auto;
        opacity: 0.7;
        transition: opacity 0.2s ease-in-out;
    }
    
    .alert-close:hover {
        opacity: 1;
    }
    
    .caps-lock-warning {
        color: var(--warning-color);
        font-size: 0.75rem;
        margin-top: 0.25rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        animation: fadeIn 0.3s ease-in-out;
    }
    
    .form-group.focused .form-label {
        color: var(--primary-color);
    }
    
    .network-status {
        position: fixed;
        top: 1rem;
        right: 1rem;
        padding: 0.75rem 1rem;
        border-radius: var(--radius-md);
        font-size: 0.875rem;
        font-weight: 500;
        z-index: 1000;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: opacity 0.3s ease-in-out;
        box-shadow: var(--shadow-lg);
    }
    
    .network-status.success {
        background-color: #f0fdf4;
        color: var(--success-color);
        border: 1px solid #bbf7d0;
    }
    
    .network-status.error {
        background-color: #fef2f2;
        color: var(--error-color);
        border: 1px solid #fecaca;
    }
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-5px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @media (max-width: 480px) {
        .network-status {
            top: auto;
            bottom: 1rem;
            left: 1rem;
            right: 1rem;
        }
    }
`;
document.head.appendChild(enhancedStyles);
