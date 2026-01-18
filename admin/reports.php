<?php
/**
 * Admin Reports Page
 * Leave usage analytics and reporting
 */

require_once __DIR__ . '/../bootstrap.php';

if (!$isLoggedIn || $currentUser['role_id'] !== ROLE_ADMIN) {
    redirect('/leavemgt/login.php');
}

$db = Database::getInstance();
$currentYear = getCurrentFinancialYear();
$reportType = sanitize($_GET['type'] ?? 'overview');

// Get leave type stats for current year
$leaveTypeStats = $db->fetchAll(
    "SELECT 
        lt.id,
        lt.leave_name,
        SUM(lb.total_days) as total_allocated,
        SUM(lb.used_days) as total_used,
        SUM(lb.remaining_days) as total_remaining,
        COUNT(DISTINCT lb.user_id) as employees_using
     FROM leave_types lt
     LEFT JOIN leave_balances lb ON lt.id = lb.leave_type_id AND lb.year = ?
     WHERE lt.status = ?
     GROUP BY lt.id, lt.leave_name
     ORDER BY total_used DESC",
    [$currentYear, STATUS_ACTIVE],
    'is'
);

// Get department-wise stats
$departmentStats = $db->fetchAll(
    "SELECT 
        d.id,
        d.department_name,
        COUNT(DISTINCT u.id) as employee_count,
        SUM(lb.total_days) as total_days,
        SUM(lb.used_days) as used_days,
        SUM(lb.remaining_days) as remaining_days
     FROM departments d
     LEFT JOIN users u ON d.id = u.department_id AND u.role_id = ? AND u.status = ?
     LEFT JOIN leave_balances lb ON u.id = lb.user_id AND lb.year = ?
     WHERE d.status = ?
     GROUP BY d.id, d.department_name
     ORDER BY employee_count DESC",
    [ROLE_EMPLOYEE, STATUS_ACTIVE, $currentYear, STATUS_ACTIVE],
    'isiss'
);

// Get approval stats
$approvalStats = $db->fetchRow(
    "SELECT 
        COUNT(CASE WHEN status = ? THEN 1 END) as pending,
        COUNT(CASE WHEN status = ? THEN 1 END) as approved,
        COUNT(CASE WHEN status = ? THEN 1 END) as rejected,
        COUNT(*) as total,
        SUM(CASE WHEN status = ? THEN number_of_days ELSE 0 END) as total_approved_days
     FROM leave_requests
     WHERE YEAR(created_at) = ?",
    [STATUS_PENDING, STATUS_APPROVED, STATUS_REJECTED, STATUS_APPROVED, $currentYear],
    'sssssi'
);

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?php echo APP_NAME; ?></title>
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
        <div class="container-fluid">
            <!-- Breadcrumb -->
            <div class="breadcrumb">
                <a href="dashboard.php">Admin</a>
                <span class="breadcrumb-separator">/</span>
                <span class="breadcrumb-item active">Reports</span>
            </div>

            <!-- Flash Messages -->
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?> fade-in">
                    <div><?php echo htmlspecialchars($flash['message']); ?></div>
                    <button class="alert-close" onclick="this.parentElement.remove();">×</button>
                </div>
            <?php endif; ?>

            <h1 style="margin-bottom: var(--spacing-lg);">Leave Reports & Analytics</h1>
            <p style="color: var(--text-secondary); margin-bottom: var(--spacing-lg);">
                Financial Year: <?php echo $currentYear; ?> - <?php echo $currentYear + 1; ?>
            </p>

            <!-- Report Summary Stats -->
            <div class="grid grid-2 gap-3" style="margin-bottom: var(--spacing-xl);">
                <div class="stat-card">
                    <div style="font-size: 1.5rem;">📋</div>
                    <div class="stat-value"><?php echo $approvalStats['total'] ?? 0; ?></div>
                    <div class="stat-label">Total Requests</div>
                </div>
                <div class="stat-card">
                    <div style="font-size: 1.5rem;">✓</div>
                    <div class="stat-value"><?php echo $approvalStats['approved'] ?? 0; ?></div>
                    <div class="stat-label">Approved</div>
                </div>
                <div class="stat-card">
                    <div style="font-size: 1.5rem;">⏳</div>
                    <div class="stat-value"><?php echo $approvalStats['pending'] ?? 0; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card">
                    <div style="font-size: 1.5rem;">✖</div>
                    <div class="stat-value"><?php echo $approvalStats['rejected'] ?? 0; ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
            </div>

            <!-- Leave Type Report -->
            <section style="margin-bottom: var(--spacing-xl);">
                <h2 style="margin-bottom: var(--spacing-lg);">Leave Usage by Type</h2>
                
                <?php if (empty($leaveTypeStats)): ?>
                    <div class="card">
                        <div class="card-body text-center" style="padding: var(--spacing-xl);">
                            <p style="color: var(--text-secondary); margin: 0;">No data available</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Leave Type</th>
                                    <th>Employees</th>
                                    <th>Total Allocated</th>
                                    <th>Total Used</th>
                                    <th>Total Remaining</th>
                                    <th>Utilization</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leaveTypeStats as $stat): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($stat['leave_name']); ?></strong></td>
                                        <td><?php echo $stat['employees_using'] ?? 0; ?></td>
                                        <td><?php echo $stat['total_allocated'] ?? 0; ?></td>
                                        <td><?php echo $stat['total_used'] ?? 0; ?></td>
                                        <td><?php echo $stat['total_remaining'] ?? 0; ?></td>
                                        <td>
                                            <?php 
                                            $allocated = $stat['total_allocated'] ?? 0;
                                            $used = $stat['total_used'] ?? 0;
                                            $utilization = $allocated > 0 ? round(($used / $allocated) * 100, 1) : 0;
                                            ?>
                                            <div style="width: 100%; background-color: var(--border-color); border-radius: var(--radius-sm); height: 24px; overflow: hidden; position: relative;">
                                                <div style="
                                                    width: <?php echo $utilization; ?>%;
                                                    height: 100%;
                                                    background-color: <?php echo $utilization > 80 ? 'var(--danger-color)' : 'var(--success-color)'; ?>;
                                                    transition: width 0.3s ease;
                                                    display: flex;
                                                    align-items: center;
                                                    justify-content: center;
                                                    color: white;
                                                    font-size: 12px;
                                                    font-weight: 600;
                                                ">
                                                    <?php echo $utilization; ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Department Report -->
            <section>
                <h2 style="margin-bottom: var(--spacing-lg);">Leave Distribution by Department</h2>
                
                <?php if (empty($departmentStats)): ?>
                    <div class="card">
                        <div class="card-body text-center" style="padding: var(--spacing-xl);">
                            <p style="color: var(--text-secondary); margin: 0;">No departments available</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="grid grid-2">
                        <?php foreach ($departmentStats as $dept): ?>
                            <div class="card">
                                <div class="card-body">
                                    <h3 style="margin: 0 0 var(--spacing-lg) 0;">
                                        <?php echo htmlspecialchars($dept['department_name'] ?? 'Unassigned'); ?>
                                    </h3>
                                    
                                    <div style="display: grid; gap: var(--spacing-md);">
                                        <div>
                                            <div style="font-size: var(--font-size-sm); color: var(--text-secondary); margin-bottom: 5px;">
                                                Employees
                                            </div>
                                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary-color);">
                                                <?php echo $dept['employee_count'] ?? 0; ?>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <div style="font-size: var(--font-size-sm); color: var(--text-secondary); margin-bottom: 5px;">
                                                Total Days Allocated
                                            </div>
                                            <div style="font-size: 1.5rem; font-weight: 700;">
                                                <?php echo $dept['total_days'] ?? 0; ?>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <div style="font-size: var(--font-size-sm); color: var(--text-secondary); margin-bottom: 5px;">
                                                Used / Remaining
                                            </div>
                                            <div style="font-weight: 600;">
                                                <span style="color: var(--warning-color);"><?php echo $dept['used_days'] ?? 0; ?></span> 
                                                / 
                                                <span style="color: var(--success-color);"><?php echo $dept['remaining_days'] ?? 0; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>
</body>
</html>
