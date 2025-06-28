<?php
session_start();

$submitted = false;
$formData = [];

if ($_POST) {
    // Just capture the form data for display, no validation needed
    $formData = [
        'name' => trim($_POST['name'] ?? ''),
        'reg_number' => trim($_POST['reg_number'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'role' => $_POST['role'] ?? ''
    ];
    
    // Redirect to success page
    if ($formData['name'] && $formData['email']) {
        header("Location: registration-success.php?name=" . urlencode($formData['name']) . "&email=" . urlencode($formData['email']));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - EduPortal</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 500px;
            position: relative;
            z-index: 10;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            color: #667eea;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .back-link:hover {
            color: #764ba2;
        }
        
        .back-link i {
            margin-right: 8px;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .register-header .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .register-header .logo i {
            font-size: 40px;
            color: #667eea;
            margin-right: 15px;
        }
        
        .register-header .logo h1 {
            color: #333;
            margin: 0;
            font-size: 32px;
            font-weight: 700;
        }
        
        .register-header p {
            color: #666;
            font-size: 16px;
            line-height: 1.5;
            margin: 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-label i {
            margin-right: 8px;
            color: #667eea;
        }
        
        .form-input, .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
            box-sizing: border-box;
        }
        
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .password-input-wrapper {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 5px;
        }
        
        .register-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .register-btn:hover {
            transform: translateY(-2px);
        }
        
        .info-text {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            color: #0369a1;
            padding: 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .info-text i {
            color: #0369a1;
            margin-right: 8px;
        }
        
        .register-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e1e5e9;
        }
        
        .register-footer p {
            color: #666;
            margin: 0;
        }
        
        .register-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-footer a:hover {
            color: #764ba2;
        }
        
        .terms-checkbox {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #666;
        }
        
        .terms-checkbox input[type="checkbox"] {
            margin-top: 2px;
        }
        
        .terms-checkbox a {
            color: #667eea;
            text-decoration: none;
        }
        
        .terms-checkbox a:hover {
            text-decoration: underline;
        }
        
        .background-decoration {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }
        
        .shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 6s ease-in-out infinite;
        }
        
        .shape-1 {
            width: 200px;
            height: 200px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape-2 {
            width: 150px;
            height: 150px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }
        
        .shape-3 {
            width: 100px;
            height: 100px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Back to Login
            </a>
            
            <div class="register-header">
                <div class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <h1>EduPortal</h1>
                </div>
                <p>Create your account to get started</p>
            </div>

            <div class="info-text">
                <i class="fas fa-info-circle"></i>
                Please fill in all the required information to create your EduPortal account.
            </div>

            <form method="POST" action="" class="register-form">
                <div class="form-group">
                    <label for="role" class="form-label">
                        <i class="fas fa-user-tag"></i>
                        Your Role
                    </label>
                    <select id="role" name="role" class="form-select" required>
                        <option value="">Select your role</option>
                        <option value="student" <?php echo (isset($_POST['role']) && $_POST['role'] == 'student') ? 'selected' : ''; ?>>Student</option>
                        <option value="instructor" <?php echo (isset($_POST['role']) && $_POST['role'] == 'instructor') ? 'selected' : ''; ?>>Instructor</option>
                        <option value="coordinator" <?php echo (isset($_POST['role']) && $_POST['role'] == 'coordinator') ? 'selected' : ''; ?>>Coordinator</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="name" class="form-label">
                        <i class="fas fa-user"></i>
                        Full Name
                    </label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        class="form-input" 
                        placeholder="Enter your full name"
                        value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="reg_number" class="form-label">
                        <i class="fas fa-id-card"></i>
                        <span id="reg-label">Registration Number</span>
                    </label>
                    <input 
                        type="text" 
                        id="reg_number" 
                        name="reg_number" 
                        class="form-input" 
                        placeholder="Enter your registration/ID number"
                        value="<?php echo htmlspecialchars($_POST['reg_number'] ?? ''); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i>
                        Email Address
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        placeholder="Enter your email address"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <div class="password-input-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="Create a strong password"
                            required
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <i class="fas fa-eye" id="password-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">
                        <i class="fas fa-lock"></i>
                        Confirm Password
                    </label>
                    <div class="password-input-wrapper">
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="form-input" 
                            placeholder="Confirm your password"
                            required
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye" id="confirm-password-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="terms-checkbox">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">
                        I agree to the <a href="#" onclick="return false;">Terms of Service</a> and 
                        <a href="#" onclick="return false;">Privacy Policy</a>
                    </label>
                </div>

                <button type="submit" class="register-btn">
                    <i class="fas fa-user-plus"></i>
                    Create Account
                </button>
            </form>

            <div class="register-footer">
                <p>Already have an account? <a href="index.php">Sign in here</a></p>
            </div>
        </div>

        <div class="background-decoration">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
    </div>

    <script>
        // Update label based on role selection
        document.getElementById('role').addEventListener('change', function() {
            const regLabel = document.getElementById('reg-label');
            const regInput = document.getElementById('reg_number');
            
            switch(this.value) {
                case 'student':
                    regLabel.textContent = 'Student ID';
                    regInput.placeholder = 'Enter your Student ID';
                    break;
                case 'instructor':
                    regLabel.textContent = 'Instructor ID';
                    regInput.placeholder = 'Enter your Instructor ID';
                    break;
                case 'coordinator':
                    regLabel.textContent = 'Coordinator ID';
                    regInput.placeholder = 'Enter your Coordinator ID';
                    break;
                default:
                    regLabel.textContent = 'Registration Number';
                    regInput.placeholder = 'Enter your registration/ID number';
            }
        });

        // Password toggle functionality
        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const eyeIcon = document.getElementById(fieldId + '-eye');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        // Form validation
        document.querySelector('.register-form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match. Please try again.');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
                return false;
            }
        });
    </script>
</body>
</html>
