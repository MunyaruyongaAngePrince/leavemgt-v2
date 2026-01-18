<?php
/**
 * Employee Profile
 * View and edit personal profile
 */

require_once __DIR__ . '/bootstrap.php';

if (!$isLoggedIn) {
    redirect('/leavemgt/login.php');
}

$db = Database::getInstance();
$errors = [];
$success = null;

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName = sanitize($_POST['last_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');

    if (empty($firstName)) {
        $errors[] = 'First name is required';
    }
    if (empty($lastName)) {
        $errors[] = 'Last name is required';
    }

    if (empty($errors)) {
        try {
            $db->update('users',
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => $phone
                ],
                'id = ?',
                [$currentUser['id']]
            );
            $success = 'Profile updated successfully';
        } catch (Exception $e) {
            $errors[] = 'An error occurred: ' . $e->getMessage();
        }
    }
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/styles.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/components.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-top container-fluid">
            <div class="brand">📋 <?php echo APP_NAME; ?></div>
            <div style="display: flex; align-items: center; gap: 20px;">
                <a href="dashboard.php" class="btn btn-outline btn-sm">Back to Dashboard</a>
                <a href="logout.php" class="btn btn-outline btn-sm" style="color: var(--danger-color); border-color: var(--danger-color);">Logout</a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container" style="max-width: 600px;">
            <!-- Breadcrumb -->
            <div class="breadcrumb">
                <a href="dashboard.php">Dashboard</a>
                <span class="breadcrumb-separator">/</span>
                <span class="breadcrumb-item active">My Profile</span>
            </div>

            <!-- Flash Messages -->
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?> fade-in">
                    <div><?php echo htmlspecialchars($flash['message']); ?></div>
                    <button class="alert-close" onclick="this.parentElement.remove();">×</button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success fade-in">
                    <div><?php echo htmlspecialchars($success); ?></div>
                    <button class="alert-close" onclick="this.parentElement.remove();">×</button>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger fade-in">
                    <div>
                        <?php foreach ($errors as $error): ?>
                            <p style="margin: 0 0 5px 0;"><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php echo strtoupper($currentUser['first_name'][0] . $currentUser['last_name'][0]); ?>
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></h2>
                    <div class="profile-role"><?php echo htmlspecialchars($currentUser['role_name']); ?></div>
                </div>
            </div>

            <!-- Profile Form -->
            <div class="card" style="margin-top: var(--spacing-lg);">
                <div class="card-header">
                    <h2 class="card-title">Personal Information</h2>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-row form-row-2">
                            <div class="form-group">
                                <label for="first_name">First Name *</label>
                                <input 
                                    type="text" 
                                    id="first_name" 
                                    name="first_name"
                                    value="<?php echo htmlspecialchars($currentUser['first_name']); ?>"
                                    required
                                >
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name *</label>
                                <input 
                                    type="text" 
                                    id="last_name" 
                                    name="last_name"
                                    value="<?php echo htmlspecialchars($currentUser['last_name']); ?>"
                                    required
                                >
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">Email (Read-only)</label>
                            <input 
                                type="email" 
                                id="email" 
                                value="<?php echo htmlspecialchars($currentUser['email']); ?>"
                                disabled
                            >
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input 
                                type="tel" 
                                id="phone" 
                                name="phone"
                                value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>"
                            >
                        </div>

                        <div style="display: flex; gap: var(--spacing-md);">
                            <button type="submit" class="btn btn-primary">
                                Save Changes
                            </button>
                            <a href="dashboard.php" class="btn btn-outline">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Account Information -->
            <div class="card" style="margin-top: var(--spacing-lg);">
                <div class="card-header">
                    <h2 class="card-title">Account Information</h2>
                </div>
                <div class="card-body">
                    <div style="display: grid; gap: var(--spacing-lg);">
                        <div>
                            <div style="font-size: var(--font-size-sm); color: var(--text-secondary); margin-bottom: 5px;">Username</div>
                            <div style="font-weight: 600;"><?php echo htmlspecialchars($currentUser['username']); ?></div>
                        </div>
                        <div>
                            <div style="font-size: var(--font-size-sm); color: var(--text-secondary); margin-bottom: 5px;">Role</div>
                            <div style="font-weight: 600;"><?php echo htmlspecialchars($currentUser['role_name']); ?></div>
                        </div>
                        <div>
                            <div style="font-size: var(--font-size-sm); color: var(--text-secondary); margin-bottom: 5px;">Last Login</div>
                            <div>
                                <?php echo $currentUser['last_login'] ? formatDate($currentUser['last_login'], 'M d, Y \a\t H:i') : 'Never'; ?>
                            </div>
                        </div>
                        <div>
                            <div style="font-size: var(--font-size-sm); color: var(--text-secondary); margin-bottom: 5px;">Member Since</div>
                            <div>
                                <?php echo formatDate($currentUser['created_at'], 'M d, Y'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
