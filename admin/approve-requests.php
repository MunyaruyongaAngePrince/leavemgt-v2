<?php
/**
 * Approve Leave Requests
 * Review and approve/reject leave requests
 */

require_once __DIR__ . '/../bootstrap.php';

if (!$isLoggedIn || $currentUser['role_id'] !== ROLE_ADMIN) {
    redirect('/leavemgt/login.php');
}

$db = Database::getInstance();
$requestId = (int)($_GET['id'] ?? 0);
$currentRequest = null;
$errors = [];

// Get filter parameters
$filterStatus = sanitize($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = sanitize($_POST['action']);
    $requestId = (int)$_POST['request_id'];
    $comments = sanitize($_POST['comments'] ?? '');

    try {
        $db->beginTransaction();

        // Get the leave request
        $request = $db->fetchRow(
            "SELECT * FROM leave_requests WHERE id = ?",
            [$requestId],
            'i'
        );

        if (!$request) {
            throw new Exception('Request not found');
        }

        if ($action === 'approve') {
            // Update request status
            $db->update('leave_requests',
                [
                    'status' => STATUS_APPROVED,
                    'approver_id' => $currentUser['id'],
                    'approval_date' => date('Y-m-d H:i:s'),
                    'approval_comments' => $comments
                ],
                'id = ?',
                [$requestId]
            );
        } else {
            // Rejection - restore used days
            $year = date('Y', strtotime($request['start_date']));
            $newUsedDays = max(0, $db->fetchRow(
                "SELECT used_days FROM leave_balances WHERE user_id = ? AND leave_type_id = ? AND year = ?",
                [$request['user_id'], $request['leave_type_id'], $year],
                'iii'
            )['used_days'] - $request['number_of_days']);

            $db->update('leave_balances',
                ['used_days' => $newUsedDays],
                'user_id = ? AND leave_type_id = ? AND year = ?',
                [$request['user_id'], $request['leave_type_id'], $year]
            );

            $db->update('leave_requests',
                [
                    'status' => STATUS_REJECTED,
                    'approver_id' => $currentUser['id'],
                    'approval_date' => date('Y-m-d H:i:s'),
                    'approval_comments' => $comments
                ],
                'id = ?',
                [$requestId]
            );
        }

        $db->commit();
        setFlashMessage('success', 'Leave request ' . strtolower($action) . 'ed successfully');
        redirect('/leavemgt/admin/approve-requests.php?status=' . STATUS_PENDING);
    } catch (Exception $e) {
        $db->rollback();
        $errors[] = 'An error occurred: ' . $e->getMessage();
        logMessage('ERROR', 'Leave Approval Error: ' . $e->getMessage());
    }
}

// Build query for listing requests
$where = "lr.status = ?";
$params = [STATUS_PENDING];
$types = 's';

if (!empty($filterStatus)) {
    $where = "lr.status = ?";
    $params = [$filterStatus];
    $types = 's';
}

// Get total count
$countResult = $db->fetchRow(
    "SELECT COUNT(*) as total FROM leave_requests lr WHERE $where",
    $params,
    $types
);
$total = $countResult['total'] ?? 0;

// Get pagination data
$pagination = getPaginationData($total, $page);

// Get leave requests
$requests = $db->fetchAll(
    "SELECT lr.*, u.first_name, u.last_name, u.email, lt.leave_name, d.department_name
     FROM leave_requests lr
     JOIN users u ON lr.user_id = u.id
     JOIN leave_types lt ON lr.leave_type_id = lt.id
     LEFT JOIN departments d ON u.department_id = d.id
     WHERE $where
     ORDER BY lr.created_at DESC
     LIMIT ? OFFSET ?",
    array_merge($params, [$pagination['limit'], $pagination['offset']]),
    $types . 'ii'
);

// Get specific request if ID provided
if ($requestId > 0) {
    $currentRequest = $db->fetchRow(
        "SELECT lr.*, u.first_name, u.last_name, u.email, lt.leave_name, d.department_name
         FROM leave_requests lr
         JOIN users u ON lr.user_id = u.id
         JOIN leave_types lt ON lr.leave_type_id = lt.id
         LEFT JOIN departments d ON u.department_id = d.id
         WHERE lr.id = ?",
        [$requestId],
        'i'
    );
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Leave Requests - <?php echo APP_NAME; ?></title>
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
                <span class="breadcrumb-item active">Approve Requests</span>
            </div>

            <!-- Flash Messages -->
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?> fade-in">
                    <div><?php echo htmlspecialchars($flash['message']); ?></div>
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

            <h1 style="margin-bottom: var(--spacing-lg);">Leave Request Approval</h1>

            <!-- Detail View if Request ID specified -->
            <?php if ($currentRequest && !empty($errors) === false): ?>
                <div style="display: grid; gap: var(--spacing-lg);">
                    <!-- Request Details Card -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Request Details</h2>
                        </div>
                        <div class="card-body">
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--spacing-lg); margin-bottom: var(--spacing-lg);">
                                <div>
                                    <div style="font-size: var(--font-size-sm); color: var(--text-secondary); margin-bottom: 5px;">Employee Name</div>
                                    <div style="font-weight: 600;">
                                        <?php echo htmlspecialchars($currentRequest['first_name'] . ' ' . $currentRequest['last_name']); ?>
                                    </div>
                                </div>
                                <div>
                                    <div style="font-size: var(--font-size-sm); color: var(--text-secondary); margin-bottom: 5px;">Email</div>
                                    <div><?php echo htmlspecialchars($currentRequest['email']); ?></div>
                                </div>
                                <div>
                                    <div style="font-size: var(--font-size-sm); color: var(--text-secondary); margin-bottom: 5px;">Department</div>
                                    <div><?php echo htmlspecialchars($currentRequest['department_name'] ?? 'N/A'); ?></div>
                                </div>
                                <div>
                                    <div style="font-size: var(--font-size-sm); color: var(--text-secondary); margin-bottom: 5px;">Leave Type</div>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($currentRequest['leave_name']); ?></div>
                                </div>
                                <div>
                                    <div style="font-size: var(--font-size-sm); color: var(--text-secondary); margin-bottom: 5px;">Duration</div>
                                    <div>
                                        <?php echo formatDate($currentRequest['start_date']); ?> to <?php echo formatDate($currentRequest['end_date']); ?>
                                    </div>
                                </div>
                                <div>
                                    <div style="font-size: var(--font-size-sm); color: var(--text-secondary); margin-bottom: 5px;">Number of Days</div>
                                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--warning-color);">
                                        <?php echo htmlspecialchars($currentRequest['number_of_days']); ?>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div style="font-size: var(--font-size-sm); color: var(--text-secondary); margin-bottom: 5px;">Reason</div>
                                <div style="background-color: var(--light-color); padding: var(--spacing-md); border-radius: var(--radius-md);">
                                    <?php echo htmlspecialchars($currentRequest['reason']); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Approval Form -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Action</h2>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="request_id" value="<?php echo $currentRequest['id']; ?>">

                                <div class="form-group">
                                    <label for="comments">Approval Comments (Optional)</label>
                                    <textarea 
                                        id="comments" 
                                        name="comments" 
                                        placeholder="Add any comments for the employee..."
                                        style="min-height: 100px;"
                                    ></textarea>
                                </div>

                                <div style="display: flex; gap: var(--spacing-md);">
                                    <button 
                                        type="submit" 
                                        name="action" 
                                        value="approve" 
                                        class="btn btn-success"
                                        onclick="return confirm('Approve this leave request?');"
                                    >
                                        ✓ Approve Request
                                    </button>
                                    <button 
                                        type="submit" 
                                        name="action" 
                                        value="reject" 
                                        class="btn btn-danger"
                                        onclick="return confirm('Reject this leave request?');"
                                    >
                                        ✖ Reject Request
                                    </button>
                                    <a href="approve-requests.php" class="btn btn-outline">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- List View -->
                <div class="filter-panel" style="margin-bottom: var(--spacing-lg);">
                    <form method="GET" style="display: flex; gap: var(--spacing-md);">
                        <select name="status" onchange="this.form.submit()" style="min-width: 200px;">
                            <option value="">-- All Status --</option>
                            <option value="<?php echo STATUS_PENDING; ?>" <?php echo $filterStatus === STATUS_PENDING ? 'selected' : ''; ?>>Pending</option>
                            <option value="<?php echo STATUS_APPROVED; ?>" <?php echo $filterStatus === STATUS_APPROVED ? 'selected' : ''; ?>>Approved</option>
                            <option value="<?php echo STATUS_REJECTED; ?>" <?php echo $filterStatus === STATUS_REJECTED ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </form>
                </div>

                <?php if (empty($requests)): ?>
                    <div class="card">
                        <div class="card-body text-center" style="padding: var(--spacing-xl);">
                            <p style="color: var(--text-secondary); margin: 0;">No requests found</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Leave Type</th>
                                    <th>Dates</th>
                                    <th>Days</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $req): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($req['department_name'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($req['leave_name']); ?></td>
                                        <td><?php echo formatDate($req['start_date']); ?> - <?php echo formatDate($req['end_date']); ?></td>
                                        <td><?php echo htmlspecialchars($req['number_of_days']); ?></td>
                                        <td>
                                            <span class="badge <?php echo getStatusBadgeClass($req['status']); ?>">
                                                <?php echo getStatusText($req['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="approve-requests.php?id=<?php echo $req['id']; ?>" class="btn btn-primary btn-sm">
                                                Review
                                            </a>
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
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
