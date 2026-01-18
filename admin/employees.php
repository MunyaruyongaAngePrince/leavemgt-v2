<?php
/**
 * Manage Employees
 * Add, edit, and deactivate employees
 */

require_once __DIR__ . '/../bootstrap.php';

if (!$isLoggedIn || $currentUser['role_id'] !== ROLE_ADMIN) {
    redirect('/leavemgt/login.php');
}

$db = Database::getInstance();
$action = sanitize($_GET['action'] ?? '');
$employeeId = (int)($_GET['id'] ?? 0);
$errors = [];
$success = null;

// Get departments
$departments = $db->fetchAll(
    "SELECT id, department_name FROM departments WHERE status = ? ORDER BY department_name",
    [STATUS_ACTIVE],
    's'
);

// Get employees with pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$search = sanitize($_GET['search'] ?? '');

$where = "role_id = ?";
$params = [ROLE_EMPLOYEE];
$types = 'i';

if (!empty($search)) {
    $where .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    $types .= 'sss';
}

$countResult = $db->fetchRow(
    "SELECT COUNT(*) as total FROM users WHERE $where",
    $params,
    $types
);
$total = $countResult['total'] ?? 0;
$pagination = getPaginationData($total, $page);

$employees = $db->fetchAll(
    "SELECT u.*, d.department_name FROM users u
     LEFT JOIN departments d ON u.department_id = d.id
     WHERE $where
     ORDER BY u.first_name
     LIMIT ? OFFSET ?",
    array_merge($params, [$pagination['limit'], $pagination['offset']]),
    $types . 'ii'
);

// Handle employee deactivation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'deactivate') {
        $userId = (int)$_POST['user_id'];
        $db->update('users', ['status' => STATUS_INACTIVE], 'id = ?', [$userId]);
        setFlashMessage('success', 'Employee deactivated successfully');
        redirect('/leavemgt/admin/employees.php');
    }
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employees - <?php echo APP_NAME; ?></title>
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
                <span class="breadcrumb-item active">Manage Employees</span>
            </div>

            <!-- Flash Messages -->
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?> fade-in">
                    <div><?php echo htmlspecialchars($flash['message']); ?></div>
                    <button class="alert-close" onclick="this.parentElement.remove();">×</button>
                </div>
            <?php endif; ?>

            <!-- Header and Search -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-lg); flex-wrap: wrap; gap: var(--spacing-md);">
                <h1 style="margin: 0;">Manage Employees</h1>
                <a href="add-employee.php" class="btn btn-primary btn-sm">+ Add Employee</a>
            </div>

            <!-- Search Filter -->
            <div class="filter-panel">
                <form method="GET" style="display: flex; gap: var(--spacing-md);">
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="Search by name or email..."
                        value="<?php echo htmlspecialchars($search); ?>"
                        style="flex: 1; min-width: 250px;"
                    >
                    <button type="submit" class="btn btn-primary btn-sm">Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="employees.php" class="btn btn-outline btn-sm">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Employees Table -->
            <?php if (empty($employees)): ?>
                <div class="card">
                    <div class="card-body text-center" style="padding: var(--spacing-xl);">
                        <p style="color: var(--text-secondary); margin: 0;">No employees found</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Employee ID</th>
                                <th>Department</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $emp): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                    <td><?php echo htmlspecialchars($emp['employee_id'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($emp['department_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($emp['phone'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge <?php echo getStatusBadgeClass($emp['status']); ?>">
                                            <?php echo getStatusText($emp['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit-employee.php?id=<?php echo $emp['id']; ?>" class="btn btn-outline btn-sm">Edit</a>
                                        <?php if ($emp['status'] === STATUS_ACTIVE): ?>
                                            <button 
                                                type="button" 
                                                onclick="deactivateEmployee(<?php echo $emp['id']; ?>)"
                                                class="btn btn-danger btn-sm"
                                            >
                                                Deactivate
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($pagination['totalPages'] > 1): ?>
                    <div class="pagination">
                        <?php if ($pagination['hasPrevious']): ?>
                            <a href="?search=<?php echo urlencode($search); ?>&page=1" class="pagination-item">First</a>
                            <a href="?search=<?php echo urlencode($search); ?>&page=<?php echo $pagination['previousPage']; ?>" class="pagination-item">← Previous</a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $pagination['page'] - 2); $i <= min($pagination['totalPages'], $pagination['page'] + 2); $i++): ?>
                            <a 
                                href="?search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>"
                                class="pagination-item <?php echo $i === $pagination['page'] ? 'active' : ''; ?>"
                            >
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($pagination['hasNext']): ?>
                            <a href="?search=<?php echo urlencode($search); ?>&page=<?php echo $pagination['nextPage']; ?>" class="pagination-item">Next →</a>
                            <a href="?search=<?php echo urlencode($search); ?>&page=<?php echo $pagination['totalPages']; ?>" class="pagination-item">Last</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Hidden Deactivate Form -->
    <form id="deactivateForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="deactivate">
        <input type="hidden" name="user_id" id="user_id_input">
    </form>

    <script>
        function deactivateEmployee(userId) {
            if (confirm('Are you sure you want to deactivate this employee?')) {
                document.getElementById('user_id_input').value = userId;
                document.getElementById('deactivateForm').submit();
            }
        }
    </script>
</body>
</html>
