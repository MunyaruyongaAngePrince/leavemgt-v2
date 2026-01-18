<?php
/**
 * My Leave Requests
 * View all leave requests for employee
 */

require_once __DIR__ . '/bootstrap.php';

if (!$isLoggedIn) {
    redirect('/leavemgt/login.php');
}

$db = Database::getInstance();

// Get filter parameters
$filterStatus = sanitize($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));

// Build query
$where = "user_id = ?";
$params = [$currentUser['id']];
$types = 'i';

if (!empty($filterStatus)) {
    $where .= " AND status = ?";
    $params[] = $filterStatus;
    $types .= 's';
}

// Get total count
$countResult = $db->fetchRow(
    "SELECT COUNT(*) as total FROM leave_requests WHERE $where",
    $params,
    $types
);
$total = $countResult['total'] ?? 0;

// Get pagination data
$pagination = getPaginationData($total, $page);

// Get leave requests
$requests = $db->fetchAll(
    "SELECT lr.*, lt.leave_name, lt.color_code, u.first_name as approver_name
     FROM leave_requests lr
     JOIN leave_types lt ON lr.leave_type_id = lt.id
     LEFT JOIN users u ON lr.approver_id = u.id
     WHERE $where
     ORDER BY lr.created_at DESC
     LIMIT ? OFFSET ?",
    array_merge($params, [$pagination['limit'], $pagination['offset']]),
    $types . 'ii'
);

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Leave Requests - <?php echo APP_NAME; ?></title>
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
        <div class="container-fluid">
            <!-- Breadcrumb -->
            <div class="breadcrumb">
                <a href="dashboard.php">Dashboard</a>
                <span class="breadcrumb-separator">/</span>
                <span class="breadcrumb-item active">My Requests</span>
            </div>

            <!-- Flash Messages -->
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?> fade-in">
                    <div><?php echo htmlspecialchars($flash['message']); ?></div>
                    <button class="alert-close" onclick="this.parentElement.remove();">×</button>
                </div>
            <?php endif; ?>

            <!-- Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-lg); flex-wrap: wrap; gap: var(--spacing-md);">
                <h1 style="margin: 0;">My Leave Requests</h1>
                <a href="request-leave.php" class="btn btn-primary btn-sm">+ New Request</a>
            </div>

            <!-- Filter Panel -->
            <div class="filter-panel">
                <form method="GET" style="display: flex; gap: var(--spacing-md); flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <select name="status" onchange="this.form.submit()">
                            <option value="">-- All Status --</option>
                            <option value="<?php echo STATUS_PENDING; ?>" <?php echo $filterStatus === STATUS_PENDING ? 'selected' : ''; ?>>Pending</option>
                            <option value="<?php echo STATUS_APPROVED; ?>" <?php echo $filterStatus === STATUS_APPROVED ? 'selected' : ''; ?>>Approved</option>
                            <option value="<?php echo STATUS_REJECTED; ?>" <?php echo $filterStatus === STATUS_REJECTED ? 'selected' : ''; ?>>Rejected</option>
                            <option value="<?php echo STATUS_CANCELLED; ?>" <?php echo $filterStatus === STATUS_CANCELLED ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                </form>
            </div>

            <!-- Requests Table -->
            <?php if (empty($requests)): ?>
                <div class="card">
                    <div class="card-body text-center" style="padding: var(--spacing-xl);">
                        <p style="color: var(--text-secondary); margin: 0;">No leave requests found</p>
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
                                <th>Submitted</th>
                                <th>Approved By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['leave_name']); ?></td>
                                    <td><?php echo formatDate($request['start_date']); ?></td>
                                    <td><?php echo formatDate($request['end_date']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($request['number_of_days']); ?></strong></td>
                                    <td>
                                        <span class="badge <?php echo getStatusBadgeClass($request['status']); ?>">
                                            <?php echo getStatusText($request['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($request['created_at'], 'M d, Y'); ?></td>
                                    <td><?php echo $request['approver_name'] ? htmlspecialchars($request['approver_name']) : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($pagination['totalPages'] > 1): ?>
                    <div class="pagination">
                        <?php if ($pagination['hasPrevious']): ?>
                            <a href="?status=<?php echo urlencode($filterStatus); ?>&page=1" class="pagination-item">First</a>
                            <a href="?status=<?php echo urlencode($filterStatus); ?>&page=<?php echo $pagination['previousPage']; ?>" class="pagination-item">← Previous</a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $pagination['page'] - 2); $i <= min($pagination['totalPages'], $pagination['page'] + 2); $i++): ?>
                            <a 
                                href="?status=<?php echo urlencode($filterStatus); ?>&page=<?php echo $i; ?>"
                                class="pagination-item <?php echo $i === $pagination['page'] ? 'active' : ''; ?>"
                            >
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($pagination['hasNext']): ?>
                            <a href="?status=<?php echo urlencode($filterStatus); ?>&page=<?php echo $pagination['nextPage']; ?>" class="pagination-item">Next →</a>
                            <a href="?status=<?php echo urlencode($filterStatus); ?>&page=<?php echo $pagination['totalPages']; ?>" class="pagination-item">Last</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
