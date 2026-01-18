<?php
/**
 * Login Page
 * User authentication entry point
 */

require_once __DIR__ . '/bootstrap.php';

// If already logged in, redirect to dashboard
if ($isLoggedIn) {
    redirect('/leavemgt/dashboard.php');
}

$errors = [];
$success = null;

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($username)) {
        $errors[] = 'Username or email is required';
    }
    if (empty($password)) {
        $errors[] = 'Password is required';
    }
    
    if (empty($errors)) {
        $auth = new Auth();
        $result = $auth->login($username, $password);
        
        if ($result['success']) {
            $_SESSION['session_id'] = $result['session_id'];
            setFlashMessage('success', 'Login successful! Welcome back.');
            redirect('/leavemgt/dashboard.php');
        } else {
            $errors[] = $result['message'];
        }
    }
}

// Get flash message if exists
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/styles.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/components.css">
    <style>
        body {
            background: linear-gradient(135deg, #1E88E5 0%, #1565C0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <div class="login-logo">📋</div>
                <h1 class="login-title"><?php echo APP_NAME; ?></h1>
                <p class="login-subtitle">Employee Leave Management</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <div>
                        <?php foreach ($errors as $error): ?>
                            <p style="margin: 0 0 5px 0;"><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($flash && $flash['type'] === 'success'): ?>
                <div class="alert alert-success">
                    <div><?php echo htmlspecialchars($flash['message']); ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        placeholder="Enter your username or email"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Enter your password"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary login-btn">
                    Sign In
                </button>
            </form>

            <div class="login-footer">
                <p>Don't have an account? Contact your administrator</p>
            </div>
        </div>
    </div>
</body>
</html>
