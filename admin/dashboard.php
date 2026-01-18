<?php
/**
 * Admin Dashboard
 * Administrator main interface
 */

require_once __DIR__ . '/../bootstrap.php';

if (!$isLoggedIn) {
    redirect('/leavemgt/login.php');
}

// Only admins can access
if ($currentUser['role_id'] !== ROLE_ADMIN) {
    redirect('/leavemgt/dashboard.php');
}

$db = Database::getInstance();
$currentYear = getCurrentFinancialYear();

// Get statistics
$totalEmployees = $db->fetchRow(
    "SELECT COUNT(*) as count FROM users WHERE role_id = ? AND status = ?",
    [ROLE_EMPLOYEE, STATUS_ACTIVE],
    'is'
);

$totalDepartments = $db->fetchRow(
    "SELECT COUNT(*) as count FROM departments WHERE status = ?",
    [STATUS_ACTIVE],
    's'
);

$pendingRequests = $db->fetchRow(
    "SELECT COUNT(*) as count FROM leave_requests WHERE status = ?",
    [STATUS_PENDING],
    's'
);

$approvedToday = $db->fetchRow(
    "SELECT COUNT(*) as count FROM leave_requests 
     WHERE status = ? AND DATE(approval_date) = CURDATE()",
    [STATUS_APPROVED],
    's'
);

// Recent pending requests
$recentRequests = $db->fetchAll(
    "SELECT lr.*, u.first_name, u.last_name, lt.leave_name
     FROM leave_requests lr
     JOIN users u ON lr.user_id = u.id
     JOIN leave_types lt ON lr.leave_type_id = lt.id
     WHERE lr.status = ?
     ORDER BY lr.created_at DESC
     LIMIT 5",
    [STATUS_PENDING],
    's'
);

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/styles.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/components.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-top container-fluid">
            <div class="brand">📋 <?php echo APP_NAME; ?></div>
            <div style="display: flex; align-items: center; gap: 20px;">
                <div style="font-size: 14px; color: var(--text-secondary);">
                    Admin: <?php echo htmlspecialchars($currentUser['first_name']); ?>
                </div>
                <div style="position: relative;">
                    <button class="btn btn-outline btn-sm" onclick="toggleDropdown()">
                        👤 Menu
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
                        <a href="settings.php" style="display: block; padding: 10px 15px; border-bottom: 1px solid var(--border-color);">Settings</a>
                        <a href="../logout.php" style="display: block; padding: 10px 15px; color: var(--danger-color);">Logout</a>
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
                    <h1 class="dashboard-title">Admin Dashboard</h1>
                    <p class="dashboard-subtitle">
                        Overview and quick actions - FY <?php echo $currentYear; ?>-<?php echo $currentYear + 1; ?>
                    </p>
                </div>

                <!-- Statistics Grid -->
                <div class="grid grid-2 gap-3">
                    <div class="stat-card">
                        <div style="font-size: 1.5rem;">👥</div>
                        <div class="stat-value"><?php echo $totalEmployees['count'] ?? 0; ?></div>
                        <div class="stat-label">Total Employees</div>
                    </div>
                    <div class="stat-card">
                        <div style="font-size: 1.5rem;">🏢</div>
                        <div class="stat-value"><?php echo $totalDepartments['count'] ?? 0; ?></div>
                        <div class="stat-label">Departments</div>
                    </div>
                    <div class="stat-card">
                        <div style="font-size: 1.5rem;">⏳</div>
                        <div class="stat-value"><?php echo $pendingRequests['count'] ?? 0; ?></div>
                        <div class="stat-label">Pending Requests</div>
                    </div>
                    <div class="stat-card">
                        <div style="font-size: 1.5rem;">✓</div>
                        <div class="stat-value"><?php echo $approvedToday['count'] ?? 0; ?></div>
                        <div class="stat-label">Approved Today</div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <section style="margin-top: var(--spacing-xl);">
                    <h2 style="margin-bottom: var(--spacing-lg);">Quick Actions</h2>
                    <div class="grid grid-2 gap-3">
                        <a href="employees.php" class="card" style="text-decoration: none; display: flex; align-items: center; padding: var(--spacing-lg); cursor: pointer; transition: var(--transition);">
                            <div style="font-size: 2rem; margin-right: var(--spacing-lg);">👤</div>
                            <div>
                                <h3 style="margin: 0 0 5px 0;">Manage Employees</h3>
                                <p style="margin: 0; color: var(--text-secondary); font-size: var(--font-size-sm);">Add, edit, or deactivate users</p>
                            </div>
                        </a>
                        <a href="leave-types.php" class="card" style="text-decoration: none; display: flex; align-items: center; padding: var(--spacing-lg); cursor: pointer; transition: var(--transition);">
                            <div style="font-size: 2rem; margin-right: var(--spacing-lg);">📋</div>
                            <div>
                                <h3 style="margin: 0 0 5px 0;">Leave Types</h3>
                                <p style="margin: 0; color: var(--text-secondary); font-size: var(--font-size-sm);">Configure leave policies</p>
                            </div>
                        </a>
                        <a href="approve-requests.php" class="card" style="text-decoration: none; display: flex; align-items: center; padding: var(--spacing-lg); cursor: pointer; transition: var(--transition);">
                            <div style="font-size: 2rem; margin-right: var(--spacing-lg);">✓</div>
                            <div>
                                <h3 style="margin: 0 0 5px 0;">Approve Requests</h3>
                                <p style="margin: 0; color: var(--text-secondary); font-size: var(--font-size-sm);">Review pending leave requests</p>
                            </div>
                        </a>
                        <a href="reports.php" class="card" style="text-decoration: none; display: flex; align-items: center; padding: var(--spacing-lg); cursor: pointer; transition: var(--transition);">
                            <div style="font-size: 2rem; margin-right: var(--spacing-lg);">📊</div>
                            <div>
                                <h3 style="margin: 0 0 5px 0;">Reports</h3>
                                <p style="margin: 0; color: var(--text-secondary); font-size: var(--font-size-sm);">View analytics and trends</p>
                            </div>
                        </a>
                    </div>
                </section>

                <!-- Recent Pending Requests -->
                <section style="margin-top: var(--spacing-xl);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-lg);">
                        <h2 style="margin: 0;">Recent Pending Requests</h2>
                        <a href="approve-requests.php" class="btn btn-outline btn-sm">View All</a>
                    </div>
                    
                    <?php if (empty($recentRequests)): ?>
                        <div class="card">
                            <div class="card-body text-center" style="padding: var(--spacing-xl);">
                                <p style="color: var(--text-secondary); margin: 0;">No pending requests</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Leave Type</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Days</th>
                                        <th>Submitted</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentRequests as $request): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($request['leave_name']); ?></td>
                                            <td><?php echo formatDate($request['start_date']); ?></td>
                                            <td><?php echo formatDate($request['end_date']); ?></td>
                                            <td><?php echo htmlspecialchars($request['number_of_days']); ?></td>
                                            <td><?php echo formatDate($request['created_at'], 'M d, Y'); ?></td>
                                            <td>
                                                <a href="approve-requests.php?id=<?php echo $request['id']; ?>" class="btn btn-primary btn-sm">
                                                    Review
                                                </a>
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
