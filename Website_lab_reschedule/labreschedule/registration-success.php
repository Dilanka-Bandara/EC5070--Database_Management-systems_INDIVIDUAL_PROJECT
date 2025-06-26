<?php
session_start();

// Get data from URL parameters
$name = $_GET['name'] ?? 'User';
$email = $_GET['email'] ?? 'your email address';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful - EduPortal</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .success-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        
        .success-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 500px;
            text-align: center;
            position: relative;
            z-index: 10;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: bounce 1s ease-in-out;
        }
        
        .success-icon i {
            font-size: 40px;
            color: white;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }
        
        .success-header h1 {
            color: #333;
            margin-bottom: 15px;
            font-size: 28px;
            font-weight: 600;
        }
        
        .success-message {
            color: #666;
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .user-info {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
        
        .user-info h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
            font-weight: 600;
            text-align: center;
        }
        
        .user-info .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding: 8px 0;
        }
        
        .user-info .info-item i {
            color: #667eea;
            margin-right: 12px;
            width: 20px;
        }
        
        .user-info .info-item strong {
            color: #333;
            margin-right: 8px;
        }
        
        .next-steps {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
        
        .next-steps h3 {
            color: #0369a1;
            margin-bottom: 15px;
            font-size: 16px;
            font-weight: 600;
        }
        
        .next-steps ul {
            color: #0369a1;
            margin: 0;
            padding-left: 20px;
        }
        
        .next-steps li {
            margin-bottom: 8px;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-secondary {
            background: #f8fafc;
            color: #4a5568;
            border: 2px solid #e2e8f0;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            text-decoration: none;
        }
        
        .btn-primary:hover {
            color: white;
        }
        
        .btn-secondary:hover {
            color: #4a5568;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-card">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            
            <div class="success-header">
                <h1>Registration Successful!</h1>
            </div>
            
            <div class="success-message">
                <p>Congratulations! Your EduPortal account has been created successfully.</p>
            </div>
            
            <div class="user-info">
                <h3><i class="fas fa-user-circle"></i> Account Details</h3>
                <div class="info-item">
                    <i class="fas fa-user"></i>
                    <strong>Name:</strong> <?php echo htmlspecialchars($name); ?>
                </div>
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <strong>Email:</strong> <?php echo htmlspecialchars($email); ?>
                </div>
                <div class="info-item">
                    <i class="fas fa-check-circle"></i>
                    <strong>Status:</strong> <span style="color: #10b981;">Active</span>
                </div>
            </div>
            
            <div class="next-steps">
                <h3><i class="fas fa-list-check"></i> What's Next?</h3>
                <ul>
                    <li>Your account is now ready to use</li>
                    <li>You can log in with your credentials</li>
                    <li>Access your dashboard and explore features</li>
                    <li>Contact support if you need any assistance</li>
                </ul>
            </div>
            
            <div class="action-buttons">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    Login Now
                </a>
                <a href="register.php" class="btn btn-secondary">
                    <i class="fas fa-user-plus"></i>
                    Register Another
                </a>
            </div>
        </div>
    </div>

    <script>
        // Auto-redirect to login after 10 seconds
        setTimeout(() => {
            if (confirm('You will be redirected to the login page. Click OK to continue or Cancel to stay.')) {
                window.location.href = 'index.php';
            }
        }, 10000);
    </script>
</body>
</html>
