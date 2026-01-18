<?php
/**
 * Employee Dashboard
 * Main employee interface
 */

require_once __DIR__ . '/bootstrap.php';

// Redirect to login if not authenticated
if (!$isLoggedIn) {
    redirect('/leavemgt/login.php');
}

// Only employees and managers can access
if (!in_array($currentUser['role_id'], [ROLE_EMPLOYEE, ROLE_MANAGER])) {
    redirect('/leavemgt/admin/dashboard.php');
}

$db = Database::getInstance();

// Get current year
$currentYear = getCurrentFinancialYear();

// Get employee's leave balances
$balances = $db->fetchAll(
    "SELECT lb.*, lt.leave_name, lt.color_code 
     FROM leave_balances lb
     JOIN leave_types lt ON lb.leave_type_id = lt.id
     WHERE lb.user_id = ? AND lb.year = ? AND lt.status = ?
     ORDER BY lt.leave_name",
    [$currentUser['id'], $currentYear, STATUS_ACTIVE],
    'iis'
);

// Get recent leave requests
$requests = $db->fetchAll(
    "SELECT lr.*, lt.leave_name, lt.color_code
     FROM leave_requests lr
     JOIN leave_types lt ON lr.leave_type_id = lt.id
     WHERE lr.user_id = ?
     ORDER BY lr.created_at DESC
     LIMIT 5",
    [$currentUser['id']],
    'i'
);

// Get statistics
$stats = $db->fetchRow(
    "SELECT 
        COUNT(CASE WHEN status = ? THEN 1 END) as pending_count,
        COUNT(CASE WHEN status = ? THEN 1 END) as approved_count,
        COUNT(CASE WHEN status = ? THEN 1 END) as rejected_count,
        SUM(CASE WHEN status = ? THEN number_of_days ELSE 0 END) as total_approved_days
     FROM leave_requests
     WHERE user_id = ? AND YEAR(created_at) = ?",
    [STATUS_PENDING, STATUS_APPROVED, STATUS_REJECTED, STATUS_APPROVED, $currentUser['id'], $currentYear],
    'ssssii'
);

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/styles.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/components.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-top container-fluid">
            <div class="brand">📋 <?php echo APP_NAME; ?></div>
            <div style="display: flex; align-items: center; gap: 20px;">
                <span style="font-size: 14px; color: var(--text-secondary);">
                    Welcome, <?php echo htmlspecialchars($currentUser['first_name']); ?>
                </span>
                <div style="position: relative;">
                    <button class="btn btn-outline btn-sm" onclick="toggleDropdown()">
                        👤 Profile
                    </button>
                    <div id="profileDropdown" style="
                        position: absolute;
                        top: 100%;
                        right: 0;
                        background: white;
                        border: 1px solid var(--border-color);
                        border-radius: var(--radius-md);
                        min-width: 150px;
                        box-shadow: var(--shadow);
                        display: none;
                        z-index: 100;
                    ">
                        <a href="profile.php" style="display: block; padding: 10px 15px; border-bottom: 1px solid var(--border-color);">My Profile</a>
                        <a href="logout.php" style="display: block; padding: 10px 15px; color: var(--danger-color);">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div style="display: grid; grid-template-columns: 1fr;">
        <!-- Main Content -->
        <main class="main-content">
            <div class="container-fluid">
                <!-- Flash Messages -->
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type']; ?> fade-in">
                        <div><?php echo htmlspecialchars($flash['message']); ?></div>
                        <button class="alert-close" onclick="this.parentElement.remove();">×</button>
                    </div>
                <?php endif; ?>

                <!-- Dashboard Header -->
                <div class="dashboard-header">
                    <h1 class="dashboard-title">Dashboard</h1>
                    <p class="dashboard-subtitle">
                        Financial Year: <?php echo $currentYear; ?> - <?php echo $currentYear + 1; ?>
                    </p>
                </div>

                <!-- Statistics -->
                <div class="grid grid-2 gap-3">
                    <div class="stat-card">
                        <div style="font-size: 1.5rem;">⏳</div>
                        <div class="stat-value"><?php echo $stats['pending_count'] ?? 0; ?></div>
                        <div class="stat-label">Pending Requests</div>
                    </div>
                    <div class="stat-card">
                        <div style="font-size: 1.5rem;">✓</div>
                        <div class="stat-value"><?php echo $stats['approved_count'] ?? 0; ?></div>
                        <div class="stat-label">Approved Requests</div>
                    </div>
                    <div class="stat-card">
                        <div style="font-size: 1.5rem;">📊</div>
                        <div class="stat-value"><?php echo $stats['total_approved_days'] ?? 0; ?></div>
                        <div class="stat-label">Total Approved Days</div>
                    </div>
                    <div class="stat-card">
                        <div style="font-size: 1.5rem;">✖</div>
                        <div class="stat-value"><?php echo $stats['rejected_count'] ?? 0; ?></div>
                        <div class="stat-label">Rejected Requests</div>
                    </div>
                </div>

                <!-- Leave Balances -->
                <section style="margin-top: var(--spacing-xl);">
                    <h2 style="margin-bottom: var(--spacing-lg);">Leave Balances</h2>
                    
                    <?php if (empty($balances)): ?>
                        <div class="card">
                            <div class="card-body text-center" style="padding: var(--spacing-xl);">
                                <p style="color: var(--text-secondary); margin: 0;">No leave types configured for your account</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-2">
                            <?php foreach ($balances as $balance): ?>
                                <div class="card">
                                    <div class="card-body">
                                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: var(--spacing-md);">
                                            <h3 style="margin: 0;"><?php echo htmlspecialchars($balance['leave_name']); ?></h3>
                                            <span style="
                                                display: inline-block;
                                                width: 20px;
                                                height: 20px;
                                                border-radius: 50%;
                                                background-color: <?php echo htmlspecialchars($balance['color_code']); ?>;
                                            "></span>
                                        </div>
                                        
                                        <div style="display: grid; gap: var(--spacing-md);">
                                            <div>
                                                <div style="font-size: var(--font-size-sm); color: var(--text-secondary); margin-bottom: 5px;">Total Days</div>
                                                <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary-color);">
                                                    <?php echo htmlspecialchars($balance['total_days']); ?>
                                                </div>
                                            </div>
                                            <div>
                                                <div style="font-size: var(--font-size-sm); color: var(--text-secondary); margin-bottom: 5px;">Used Days</div>
                                                <div style="font-size: 1.5rem; font-weight: 700; color: var(--warning-color);">
                                                    <?php echo htmlspecialchars($balance['used_days']); ?>
                                                </div>
                                            </div>
                                            <div>
                                                <div style="font-size: var(--font-size-sm); color: var(--text-secondary); margin-bottom: 5px;">Remaining Days</div>
                                                <div style="font-size: 1.5rem; font-weight: 700; color: var(--success-color);">
                                                    <?php echo htmlspecialchars($balance['remaining_days']); ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Progress Bar -->
                                        <div style="margin-top: var(--spacing-md);">
                                            <div style="
                                                width: 100%;
                                                height: 8px;
                                                background-color: var(--border-color);
                                                border-radius: var(--radius-sm);
                                                overflow: hidden;
                                            ">
                                                <div style="
                                                    width: <?php echo ($balance['total_days'] > 0 ? ($balance['used_days'] / $balance['total_days'] * 100) : 0); ?>%;
                                                    height: 100%;
                                                    background-color: var(--warning-color);
                                                    transition: width 0.3s ease;
                                                "></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- Action Buttons -->
                <div style="margin-top: var(--spacing-xl); display: flex; gap: var(--spacing-md); flex-wrap: wrap;">
                    <a href="request-leave.php" class="btn btn-primary">
                        + New Leave Request
                    </a>
                    <a href="my-requests.php" class="btn btn-outline">
                        View All Requests
                    </a>
                </div>

                <!-- Recent Requests -->
                <section style="margin-top: var(--spacing-xl);">
                    <h2 style="margin-bottom: var(--spacing-lg);">Recent Requests</h2>
                    
                    <?php if (empty($requests)): ?>
                        <div class="card">
                            <div class="card-body text-center" style="padding: var(--spacing-xl);">
                                <p style="color: var(--text-secondary); margin: 0;">No leave requests yet</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Leave Type</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Days</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requests as $request): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($request['leave_name']); ?></td>
                                            <td><?php echo formatDate($request['start_date']); ?></td>
                                            <td><?php echo formatDate($request['end_date']); ?></td>
                                            <td><?php echo htmlspecialchars($request['number_of_days']); ?></td>
                                            <td>
                                                <span class="badge <?php echo getStatusBadgeClass($request['status']); ?>">
                                                    <?php echo getStatusText($request['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>

    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById('profileDropdown');
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('profileDropdown');
            const button = event.target.closest('.btn');
            if (!button) {
                dropdown.style.display = 'none';
            }
        });
    </script>
</body>
</html>
