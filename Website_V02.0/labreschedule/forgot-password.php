<?php
session_start();

$submitted = false;
$email = '';

if ($_POST) {
    // Just capture the email for display, no validation needed
    $email = trim($_POST['email'] ?? '');
    $submitted = true;
    
    // Redirect to success page with the email
    if ($email) {
        header("Location: password-reset-sent.php?email=" . urlencode($email));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - EduPortal</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .forgot-password-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        
        .forgot-password-card {
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
        
        .forgot-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .forgot-header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
            font-weight: 600;
        }
        
        .forgot-header p {
            color: #666;
            font-size: 16px;
            line-height: 1.5;
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
        }
        
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .submit-btn {
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
        
        .submit-btn:hover {
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
    </style>
</head>
<body>
    <div class="forgot-password-container">
        <div class="forgot-password-card">
            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Back to Login
            </a>
            
            <div class="forgot-header">
                <h1><i class="fas fa-key"></i> Forgot Password</h1>
                <p>Enter your details below to reset your password</p>
            </div>

            <div class="info-text">
                <i class="fas fa-info-circle"></i>
                Please provide your details and we'll send a password reset link to your email address.
            </div>

            <form method="POST" action="">
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

                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i>
                    Submit Reset Request
                </button>
            </form>
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
    </script>
</body>
</html>
