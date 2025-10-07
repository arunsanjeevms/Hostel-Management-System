<?php
require_once 'config.php';

// Simple authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Mess Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h2 {
            color: #333;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-floating {
            margin-bottom: 20px;
        }
        
        .form-control {
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .alert {
            border: none;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .college-logo {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .college-logo i {
            font-size: 24px;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="college-logo">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h2>Welcome Back</h2>
            <p>Mess Management System</p>
        </div>
        
        <div id="alert-container"></div>
        
        <form id="loginForm">
            <div class="form-floating">
                <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                <label for="username"><i class="fas fa-user me-2"></i>Username</label>
            </div>
            
            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
            </div>
            
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="rememberMe">
                <label class="form-check-label" for="rememberMe">
                    Remember me
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary btn-login w-100">
                <i class="fas fa-sign-in-alt me-2"></i>Sign In
            </button>
        </form>
        
        <div class="text-center mt-4">
            <small class="text-muted">
                © 2025 Mess Management System. All rights reserved.
            </small>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const username = formData.get('username');
            const password = formData.get('password');
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Signing In...';
            submitBtn.disabled = true;
            
            try {
                const response = await fetch('api.php/auth', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'login',
                        username: username,
                        password: password
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', 'Login successful! Redirecting...');
                    
                    // Store user info if remember me is checked
                    if (document.getElementById('rememberMe').checked) {
                        localStorage.setItem('remembered_username', username);
                    }
                    
                    // Redirect to dashboard
                    setTimeout(() => {
                        window.location.href = 'enhanced_index.php';
                    }, 1000);
                } else {
                    showAlert('danger', result.message || 'Login failed. Please try again.');
                }
            } catch (error) {
                console.error('Login error:', error);
                showAlert('danger', 'Connection error. Please try again.');
            } finally {
                // Reset button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });
        
        function showAlert(type, message) {
            const alertContainer = document.getElementById('alert-container');
            alertContainer.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        }
        
        // Check for remembered username
        document.addEventListener('DOMContentLoaded', function() {
            const rememberedUsername = localStorage.getItem('remembered_username');
            if (rememberedUsername) {
                document.getElementById('username').value = rememberedUsername;
                document.getElementById('rememberMe').checked = true;
            }
        });
        
        // Add input validation
        document.getElementById('username').addEventListener('input', function() {
            this.value = this.value.replace(/[^a-zA-Z0-9_]/g, '');
        });
    </script>
</body>
</html>