<?php
/**
 * Manage Leave Types
 * Configure organizational leave policies
 */

require_once __DIR__ . '/../bootstrap.php';

if (!$isLoggedIn || $currentUser['role_id'] !== ROLE_ADMIN) {
    redirect('/leavemgt/login.php');
}

$db = Database::getInstance();
$errors = [];
$success = null;

// Handle form submission for adding/editing leave type
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');
    $leaveName = sanitize($_POST['leave_name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $maxDays = (int)($_POST['max_days_per_year'] ?? 0);
    $carryForward = isset($_POST['carry_forward']) ? 1 : 0;
    $carryForwardDays = (int)($_POST['carry_forward_days'] ?? 0);
    $requireDocument = isset($_POST['require_document']) ? 1 : 0;
    $colorCode = sanitize($_POST['color_code'] ?? '#1E88E5');

    if (empty($leaveName)) {
        $errors[] = 'Leave name is required';
    }
    if ($maxDays <= 0) {
        $errors[] = 'Maximum days must be greater than 0';
    }

    if (empty($errors)) {
        try {
            if ($action === 'add') {
                $db->insert('leave_types', [
                    'leave_name' => $leaveName,
                    'description' => $description,
                    'max_days_per_year' => $maxDays,
                    'color_code' => $colorCode,
                    'carry_forward' => $carryForward,
                    'carry_forward_days' => $carryForwardDays,
                    'require_document' => $requireDocument,
                    'status' => STATUS_ACTIVE
                ]);
                $success = 'Leave type added successfully';
            }
        } catch (Exception $e) {
            $errors[] = 'An error occurred: ' . $e->getMessage();
        }
    }
}

// Get all leave types
$leaveTypes = $db->fetchAll(
    "SELECT * FROM leave_types ORDER BY leave_name"
);

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Types - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/styles.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/components.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-top container-fluid">
            <div class="brand">📋 <?php echo APP_NAME; ?></div>
            <div style="display: flex; align-items: center; gap: 20px;">
                <a href="dashboard.php" class="btn btn-outline btn-sm">Back to Admin</a>
                <a href="../logout.php" class="btn btn-outline btn-sm" style="color: var(--danger-color); border-color: var(--danger-color);">Logout</a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container" style="max-width: 800px;">
            <!-- Breadcrumb -->
            <div class="breadcrumb">
                <a href="dashboard.php">Admin</a>
                <span class="breadcrumb-separator">/</span>
                <span class="breadcrumb-item active">Leave Types</span>
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

            <h1 style="margin-bottom: var(--spacing-lg);">Configure Leave Types</h1>

            <!-- Add Leave Type Form -->
            <div class="card" style="margin-bottom: var(--spacing-xl);">
                <div class="card-header">
                    <h2 class="card-title">Add New Leave Type</h2>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add">

                        <div class="form-group">
                            <label for="leave_name">Leave Name *</label>
                            <input 
                                type="text" 
                                id="leave_name" 
                                name="leave_name" 
                                placeholder="e.g., Annual Leave, Sick Leave"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea 
                                id="description" 
                                name="description" 
                                placeholder="Describe this leave type"
                            ></textarea>
                        </div>

                        <div class="form-row form-row-2">
                            <div class="form-group">
                                <label for="max_days_per_year">Max Days Per Year *</label>
                                <input 
                                    type="number" 
                                    id="max_days_per_year" 
                                    name="max_days_per_year" 
                                    min="1"
                                    value="20"
                                    required
                                >
                            </div>
                            <div class="form-group">
                                <label for="color_code">Color Code</label>
                                <input 
                                    type="color" 
                                    id="color_code" 
                                    name="color_code" 
                                    value="#1E88E5"
                                >
                            </div>
                        </div>

                        <div style="background-color: var(--light-color); padding: var(--spacing-md); border-radius: var(--radius-md); margin-bottom: var(--spacing-lg);">
                            <label style="display: flex; align-items: center; gap: var(--spacing-md); cursor: pointer; margin: 0;">
                                <input type="checkbox" name="carry_forward">
                                <span>Allow Carry Forward of Unused Days</span>
                            </label>
                            <div id="carryForwardDays" style="margin-top: var(--spacing-md); display: none;">
                                <label for="carry_forward_days">Max Carry Forward Days</label>
                                <input 
                                    type="number" 
                                    id="carry_forward_days" 
                                    name="carry_forward_days" 
                                    min="0"
                                    value="5"
                                >
                            </div>
                        </div>

                        <label style="display: flex; align-items: center; gap: var(--spacing-md); cursor: pointer; margin-bottom: var(--spacing-lg);">
                            <input type="checkbox" name="require_document">
                            <span>Require Supporting Document</span>
                        </label>

                        <button type="submit" class="btn btn-primary">
                            Add Leave Type
                        </button>
                    </form>
                </div>
            </div>

            <!-- Leave Types List -->
            <h2 style="margin-bottom: var(--spacing-lg);">Current Leave Types</h2>
            
            <?php if (empty($leaveTypes)): ?>
                <div class="card">
                    <div class="card-body text-center" style="padding: var(--spacing-xl);">
                        <p style="color: var(--text-secondary); margin: 0;">No leave types configured yet</p>
                    </div>
                </div>
            <?php else: ?>
                <div style="display: grid; gap: var(--spacing-lg);">
                    <?php foreach ($leaveTypes as $type): ?>
                        <div class="card">
                            <div class="card-body">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: var(--spacing-md);">
                                    <div style="display: flex; align-items: center; gap: var(--spacing-md);">
                                        <div style="
                                            width: 30px;
                                            height: 30px;
                                            border-radius: 50%;
                                            background-color: <?php echo htmlspecialchars($type['color_code']); ?>;
                                        "></div>
                                        <div>
                                            <h3 style="margin: 0 0 5px 0;"><?php echo htmlspecialchars($type['leave_name']); ?></h3>
                                            <p style="margin: 0; font-size: var(--font-size-sm); color: var(--text-secondary);">
                                                <?php echo htmlspecialchars($type['description'] ?? 'No description'); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <span class="badge <?php echo getStatusBadgeClass($type['status']); ?>">
                                        <?php echo getStatusText($type['status']); ?>
                                    </span>
                                </div>

                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--spacing-md); margin-bottom: var(--spacing-md);">
                                    <div>
                                        <div style="font-size: var(--font-size-sm); color: var(--text-secondary);">Max Days Per Year</div>
                                        <div style="font-weight: 600; font-size: 1.25rem;">
                                            <?php echo htmlspecialchars($type['max_days_per_year']); ?>
                                        </div>
                                    </div>
                                    <div>
                                        <div style="font-size: var(--font-size-sm); color: var(--text-secondary);">Carry Forward</div>
                                        <div style="font-weight: 600; font-size: 1.25rem;">
                                            <?php echo $type['carry_forward'] ? 'Yes' : 'No'; ?>
                                        </div>
                                    </div>
                                </div>

                                <div style="display: flex; gap: var(--spacing-md);">
                                    <a href="edit-leave-type.php?id=<?php echo $type['id']; ?>" class="btn btn-outline btn-sm">Edit</a>
                                    <?php if ($type['status'] === STATUS_ACTIVE): ?>
                                        <button 
                                            onclick="deactivateLeaveType(<?php echo $type['id']; ?>)"
                                            class="btn btn-danger btn-sm"
                                        >
                                            Deactivate
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        document.querySelector('input[name="carry_forward"]')?.addEventListener('change', function() {
            document.getElementById('carryForwardDays').style.display = this.checked ? 'block' : 'none';
        });
    </script>
</body>
</html>
