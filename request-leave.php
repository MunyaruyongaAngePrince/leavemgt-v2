<?php
/**
 * Request Leave Page
 * Employee leave request submission
 */

require_once __DIR__ . '/bootstrap.php';

if (!$isLoggedIn) {
    redirect('/leavemgt/login.php');
}

$db = Database::getInstance();
$errors = [];
$success = null;

// Get active leave types
$leaveTypes = $db->fetchAll(
    "SELECT id, leave_name, max_days_per_year, require_document FROM leave_types WHERE status = ? ORDER BY leave_name",
    [STATUS_ACTIVE],
    's'
);

$currentYear = getCurrentFinancialYear();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leaveTypeId = (int)($_POST['leave_type_id'] ?? 0);
    $startDate = sanitize($_POST['start_date'] ?? '');
    $endDate = sanitize($_POST['end_date'] ?? '');
    $reason = sanitize($_POST['reason'] ?? '');
    
    // Validation
    if (!$leaveTypeId) {
        $errors[] = 'Please select a leave type';
    }
    if (empty($startDate)) {
        $errors[] = 'Start date is required';
    }
    if (empty($endDate)) {
        $errors[] = 'End date is required';
    }
    if (empty($reason)) {
        $errors[] = 'Reason is required';
    }
    
    if (empty($errors)) {
        // Validate date range
        $dateErrors = validateDateRange($startDate, $endDate);
        if (!empty($dateErrors)) {
            $errors = array_merge($errors, $dateErrors);
        } else {
            // Check for overlapping leave
            if (hasOverlappingLeave($currentUser['id'], $startDate, $endDate)) {
                $errors[] = 'You already have overlapping leave requests';
            }
        }
    }
    
    if (empty($errors)) {
        // Calculate number of days
        $numberOfDays = calculateWorkingDays($startDate, $endDate);
        
        // Check balance
        $balance = getUserLeaveBalance($currentUser['id'], $leaveTypeId, $currentYear);
        if ($numberOfDays > $balance['remaining_days']) {
            $errors[] = "Insufficient leave balance. You have {$balance['remaining_days']} days remaining.";
        }
    }
    
    if (empty($errors)) {
        try {
            // Start transaction
            $db->beginTransaction();
            
            // Insert leave request
            $requestId = $db->insert('leave_requests', [
                'user_id' => $currentUser['id'],
                'leave_type_id' => $leaveTypeId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'number_of_days' => $numberOfDays,
                'reason' => $reason,
                'status' => STATUS_PENDING,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Update leave balance
            $newUsedDays = $balance['used_days'] + $numberOfDays;
            $db->update('leave_balances',
                ['used_days' => $newUsedDays],
                'user_id = ? AND leave_type_id = ? AND year = ?',
                [$currentUser['id'], $leaveTypeId, $currentYear]
            );
            
            $db->commit();
            
            setFlashMessage('success', 'Leave request submitted successfully!');
            redirect('/leavemgt/dashboard.php');
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = 'An error occurred while submitting your request';
            logMessage('ERROR', 'Leave Request Error: ' . $e->getMessage());
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
    <title>Request Leave - <?php echo APP_NAME; ?></title>
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
                <span class="breadcrumb-item active">Request Leave</span>
            </div>

            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" style="margin-bottom: var(--spacing-lg);">
                    <div>
                        <?php foreach ($errors as $error): ?>
                            <p style="margin: 0 0 5px 0;"><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Success Message -->
            <?php if ($flash && $flash['type'] === 'success'): ?>
                <div class="alert alert-success" style="margin-bottom: var(--spacing-lg);">
                    <div><?php echo htmlspecialchars($flash['message']); ?></div>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <div class="leave-form">
                <div class="card-header">
                    <h2 class="card-title">Request Leave</h2>
                </div>

                <form method="POST" class="card-body" style="padding: var(--spacing-lg);">
                    <div class="form-section">
                        <div class="form-group">
                            <label for="leave_type_id">Leave Type *</label>
                            <select id="leave_type_id" name="leave_type_id" required>
                                <option value="">-- Select Leave Type --</option>
                                <?php foreach ($leaveTypes as $type): ?>
                                    <option 
                                        value="<?php echo $type['id']; ?>"
                                        <?php echo (isset($_POST['leave_type_id']) && $_POST['leave_type_id'] == $type['id']) ? 'selected' : ''; ?>
                                    >
                                        <?php echo htmlspecialchars($type['leave_name']); ?> (Max: <?php echo $type['max_days_per_year']; ?> days)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-row form-row-2">
                            <div class="form-group">
                                <label for="start_date">Start Date *</label>
                                <input 
                                    type="date" 
                                    id="start_date" 
                                    name="start_date"
                                    value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>"
                                    required
                                >
                            </div>
                            <div class="form-group">
                                <label for="end_date">End Date *</label>
                                <input 
                                    type="date" 
                                    id="end_date" 
                                    name="end_date"
                                    value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>"
                                    required
                                >
                            </div>
                        </div>

                        <div style="background-color: var(--light-color); padding: var(--spacing-md); border-radius: var(--radius-md); margin-top: var(--spacing-md);">
                            <div style="font-size: var(--font-size-sm); color: var(--text-secondary);">Estimated Leave Days</div>
                            <div id="estimatedDays" style="font-size: 1.5rem; font-weight: 700; color: var(--primary-color);">-</div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-group">
                            <label for="reason">Reason for Leave *</label>
                            <textarea 
                                id="reason" 
                                name="reason" 
                                placeholder="Please provide a reason for your leave request"
                                required
                            ><?php echo htmlspecialchars($_POST['reason'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div style="display: flex; gap: var(--spacing-md); margin-top: var(--spacing-xl);">
                        <button type="submit" class="btn btn-primary">
                            Submit Request
                        </button>
                        <a href="dashboard.php" class="btn btn-outline">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        // Calculate estimated days
        function calculateEstimatedDays() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            
            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                
                if (start <= end) {
                    let days = 0;
                    let currentDate = new Date(start);
                    
                    while (currentDate <= end) {
                        const dayOfWeek = currentDate.getDay();
                        // 0 = Sunday, 6 = Saturday
                        if (dayOfWeek !== 0 && dayOfWeek !== 6) {
                            days++;
                        }
                        currentDate.setDate(currentDate.getDate() + 1);
                    }
                    
                    document.getElementById('estimatedDays').textContent = days;
                }
            }
        }

        document.getElementById('start_date').addEventListener('change', calculateEstimatedDays);
        document.getElementById('end_date').addEventListener('change', calculateEstimatedDays);
    </script>
</body>
</html>
