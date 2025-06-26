<?php
session_start();

// Get email from URL parameter
$email = $_GET['email'] ?? '';
if (!$email) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Sent - EduPortal</title>
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
            animation: pulse 2s infinite;
        }
        
        .success-icon i {
            font-size: 40px;
            color: white;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .success-header h1 {
            color: #333;
            margin-bottom: 15px;
            font-size: 28px;
            font-weight: 600;
        }
        
        .success-message {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .email-display {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            font-weight: 600;
            color: #2d3748;
            word-break: break-all;
        }
        
        .info-box {
            background: #fef3cd;
            border: 1px solid #fde68a;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
        
        .info-box h3 {
            color: #92400e;
            margin-bottom: 10px;
            font-size: 16px;
            font-weight: 600;
        }
        
        .info-box ul {
            color: #92400e;
            margin: 0;
            padding-left: 20px;
        }
        
        .info-box li {
            margin-bottom: 5px;
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
        }
        
        .countdown {
            margin-top: 20px;
            padding: 15px;
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 10px;
            color: #0369a1;
        }
        
        .countdown strong {
            font-size: 18px;
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
                <h1>Password Reset Email Sent!</h1>
            </div>
            
            <div class="success-message">
                <p>We have sent a password reset link to your email address:</p>
                <div class="email-display">
                    <i class="fas fa-envelope"></i>
                    <?php echo htmlspecialchars($email); ?>
                </div>
                <p>Please check your email and follow the instructions to reset your password.</p>
            </div>
            
            <div class="info-box">
                <h3><i class="fas fa-info-circle"></i> Important Notes:</h3>
                <ul>
                    <li>The reset link will expire in <strong>1 hour</strong></li>
                    <li>Check your spam/junk folder if you don't see the email</li>
                    <li>The link can only be used once</li>
                    <li>If you don't receive the email, you can request another reset</li>
                </ul>
            </div>
            
            <div class="countdown">
                <i class="fas fa-clock"></i>
                <strong>Link expires in: <span id="countdown">60:00</span></strong>
            </div>
            
            <div class="action-buttons">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    Back to Login
                </a>
                <a href="forgot-password.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i>
                    Send Another Reset
                </a>
            </div>
        </div>
    </div>

    <script>
        // Countdown timer (60 minutes = 3600 seconds)
        let timeLeft = 3600;
        
        function updateCountdown() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            
            document.getElementById('countdown').textContent = 
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft > 0) {
                timeLeft--;
            } else {
                document.getElementById('countdown').textContent = 'EXPIRED';
                document.querySelector('.countdown').style.background = '#fee';
                document.querySelector('.countdown').style.borderColor = '#fed7d7';
                document.querySelector('.countdown').style.color = '#c53030';
            }
        }
        
        // Update countdown every second
        setInterval(updateCountdown, 1000);
        updateCountdown(); // Initial call
        
        // Auto-redirect to login after 5 minutes if user is inactive
        setTimeout(() => {
            if (confirm('You have been inactive for 5 minutes. Would you like to return to the login page?')) {
                window.location.href = 'index.php';
            }
        }, 300000); // 5 minutes
    </script>
</body>
</html>
