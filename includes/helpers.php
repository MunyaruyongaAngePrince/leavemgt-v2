<?php
/**
 * Helper Functions
 * Common utilities used throughout the application
 */

/**
 * Redirect user
 */
function redirect($path, $code = 302) {
    header("Location: " . APP_URL . $path, true, $code);
    exit();
}

/**
 * Sanitize input
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Format date
 */
function formatDate($date, $format = 'M d, Y') {
    if (empty($date)) {
        return '';
    }
    return date($format, strtotime($date));
}

/**
 * Calculate working days between dates
 */
function calculateWorkingDays($startDate, $endDate) {
    $startDate = new DateTime($startDate);
    $endDate = new DateTime($endDate);
    $endDate->modify('+1 day');
    
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($startDate, $interval, $endDate);
    
    $workingDays = 0;
    foreach ($period as $date) {
        if (!in_array($date->format('w'), WEEKEND_DAYS)) {
            $workingDays++;
        }
    }
    
    return $workingDays;
}

/**
 * Get current financial year
 */
function getCurrentFinancialYear() {
    $today = new DateTime();
    $startDate = new DateTime(FINANCIAL_YEAR_START);
    $endDate = new DateTime(FINANCIAL_YEAR_END);
    
    if ($today < $startDate->modify('this year')) {
        return $today->format('Y') - 1;
    }
    return $today->format('Y');
}

/**
 * Validate date range
 */
function validateDateRange($startDate, $endDate) {
    $errors = [];
    
    if (strtotime($startDate) >= strtotime($endDate)) {
        $errors[] = "Start date must be before end date";
    }
    
    if (strtotime($startDate) < strtotime('today')) {
        $errors[] = "Start date cannot be in the past";
    }
    
    return $errors;
}

/**
 * Generate CSRF token
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get status badge class
 */
function getStatusBadgeClass($status) {
    $classes = [
        'pending' => 'badge-warning',
        'approved' => 'badge-success',
        'rejected' => 'badge-danger',
        'cancelled' => 'badge-secondary',
        'active' => 'badge-success',
        'inactive' => 'badge-secondary'
    ];
    return $classes[$status] ?? 'badge-secondary';
}

/**
 * Get status display text
 */
function getStatusText($status) {
    $texts = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
        'active' => 'Active',
        'inactive' => 'Inactive'
    ];
    return $texts[$status] ?? ucfirst($status);
}

/**
 * Flash message functions
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Pagination helper
 */
function getPaginationData($total, $page = 1, $limit = ITEMS_PER_PAGE) {
    $page = max(1, (int)$page);
    $offset = ($page - 1) * $limit;
    $totalPages = ceil($total / $limit);
    
    return [
        'page' => $page,
        'limit' => $limit,
        'offset' => $offset,
        'total' => $total,
        'totalPages' => $totalPages,
        'hasPrevious' => $page > 1,
        'hasNext' => $page < $totalPages,
        'previousPage' => $page - 1,
        'nextPage' => $page + 1
    ];
}

/**
 * Format bytes to human readable
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Log message
 */
function logMessage($level, $message) {
    if (!ENABLE_LOGGING) {
        return;
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    
    $logFile = LOG_DIR . strtolower($level) . '_' . date('Y-m-d') . '.log';
    error_log($logMessage, 3, $logFile);
}

/**
 * Get user's leave balance
 */
function getUserLeaveBalance($userId, $leaveTypeId, $year = null) {
    if ($year === null) {
        $year = getCurrentFinancialYear();
    }
    
    try {
        $db = Database::getInstance();
        $balance = $db->fetchRow(
            "SELECT * FROM leave_balances WHERE user_id = ? AND leave_type_id = ? AND year = ?",
            [$userId, $leaveTypeId, $year],
            'iii'
        );
        
        return $balance ?? [
            'total_days' => 0,
            'used_days' => 0,
            'remaining_days' => 0
        ];
    } catch (Exception $e) {
        logMessage('ERROR', 'Get Leave Balance Error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Initialize leave balances for new employee
 */
function initializeLeaveBalances($userId, $year = null) {
    if ($year === null) {
        $year = getCurrentFinancialYear();
    }
    
    try {
        $db = Database::getInstance();
        $leaveTypes = $db->fetchAll(
            "SELECT id, max_days_per_year FROM leave_types WHERE status = ?",
            [STATUS_ACTIVE],
            's'
        );
        
        foreach ($leaveTypes as $leaveType) {
            $existingBalance = $db->fetchRow(
                "SELECT id FROM leave_balances WHERE user_id = ? AND leave_type_id = ? AND year = ?",
                [$userId, $leaveType['id'], $year],
                'iii'
            );
            
            if (!$existingBalance) {
                $db->insert('leave_balances', [
                    'user_id' => $userId,
                    'leave_type_id' => $leaveType['id'],
                    'year' => $year,
                    'total_days' => $leaveType['max_days_per_year'],
                    'used_days' => 0
                ]);
            }
        }
        
        return true;
    } catch (Exception $e) {
        logMessage('ERROR', 'Initialize Leave Balances Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Check for overlapping leave requests
 */
function hasOverlappingLeave($userId, $startDate, $endDate, $excludeRequestId = null) {
    try {
        $db = Database::getInstance();
        
        $sql = "SELECT id FROM leave_requests 
                WHERE user_id = ? 
                AND status IN ('pending', 'approved')
                AND start_date <= ? 
                AND end_date >= ?";
        
        $params = [$userId, $endDate, $startDate];
        $types = 'iss';
        
        if ($excludeRequestId) {
            $sql .= " AND id != ?";
            $params[] = $excludeRequestId;
            $types .= 'i';
        }
        
        $result = $db->fetchRow($sql, $params, $types);
        return $result !== null;
    } catch (Exception $e) {
        logMessage('ERROR', 'Check Overlapping Leave Error: ' . $e->getMessage());
        return null;
    }
}
