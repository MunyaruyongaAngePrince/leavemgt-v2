<?php
/**
 * Authentication Handler
 * Manages login, logout, and session verification
 */

class Auth {
    private $db;
    private $sessionTimeout = SESSION_TIMEOUT;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Hash password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Login user
     */
    public function login($username, $password) {
        try {
            // Check if user exists and is active
            $user = $this->db->fetchRow(
                "SELECT u.*, r.role_name FROM users u 
                 JOIN roles r ON u.role_id = r.id 
                 WHERE (u.username = ? OR u.email = ?) AND u.status = ?",
                [$username, $username, STATUS_ACTIVE],
                'sss'
            );

            if (!$user) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }

            // Verify password
            if (!self::verifyPassword($password, $user['password_hash'])) {
                $this->recordFailedLogin($user['id']);
                return ['success' => false, 'message' => 'Invalid credentials'];
            }

            // Create session
            $sessionId = $this->createSession($user['id']);

            // Update last login
            $this->db->update('users', 
                ['last_login' => date('Y-m-d H:i:s')],
                'id = ?',
                [$user['id']]
            );

            // Log action
            $this->logAction($user['id'], 'LOGIN', 'users', $user['id']);

            return [
                'success' => true,
                'message' => 'Login successful',
                'user_id' => $user['id'],
                'session_id' => $sessionId
            ];
        } catch (Exception $e) {
            error_log('Login Error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred'];
        }
    }

    /**
     * Create session
     */
    private function createSession($userId) {
        try {
            $sessionId = bin2hex(random_bytes(64));
            $expiresAt = date('Y-m-d H:i:s', time() + $this->sessionTimeout);

            $this->db->insert('sessions', [
                'id' => $sessionId,
                'user_id' => $userId,
                'ip_address' => $this->getClientIp(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'last_activity' => date('Y-m-d H:i:s'),
                'expires_at' => $expiresAt
            ]);

            return $sessionId;
        } catch (Exception $e) {
            error_log('Session Creation Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verify session
     */
    public function verifySession($sessionId) {
        try {
            $session = $this->db->fetchRow(
                "SELECT s.*, u.id, u.username, u.email, u.role_id, u.first_name, u.last_name, 
                        u.department_id, u.employee_id, r.role_name
                 FROM sessions s
                 JOIN users u ON s.user_id = u.id
                 JOIN roles r ON u.role_id = r.id
                 WHERE s.id = ? AND s.expires_at > NOW() AND u.status = ?",
                [$sessionId, STATUS_ACTIVE],
                'ss'
            );

            if (!$session) {
                return null;
            }

            // Update last activity
            $this->db->update('sessions',
                ['last_activity' => date('Y-m-d H:i:s')],
                'id = ?',
                [$sessionId]
            );

            return $session;
        } catch (Exception $e) {
            error_log('Session Verification Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Logout user
     */
    public function logout($sessionId, $userId) {
        try {
            $this->db->delete('sessions', 'id = ?', [$sessionId]);
            $this->logAction($userId, 'LOGOUT', 'users', $userId);
            return true;
        } catch (Exception $e) {
            error_log('Logout Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Record failed login attempt
     */
    private function recordFailedLogin($userId) {
        try {
            $this->logAction($userId, 'FAILED_LOGIN', 'users', $userId);
        } catch (Exception $e) {
            error_log('Failed Login Record Error: ' . $e->getMessage());
        }
    }

    /**
     * Log action
     */
    private function logAction($userId, $action, $entityType = null, $entityId = null, $oldValues = null, $newValues = null) {
        try {
            $this->db->insert('audit_logs', [
                'user_id' => $userId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'old_values' => $oldValues ? json_encode($oldValues) : null,
                'new_values' => $newValues ? json_encode($newValues) : null,
                'ip_address' => $this->getClientIp(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (Exception $e) {
            error_log('Action Logging Error: ' . $e->getMessage());
        }
    }

    /**
     * Get client IP address
     */
    private function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    /**
     * Check if user has role
     */
    public function hasRole($userId, $roleId) {
        try {
            $user = $this->db->fetchRow(
                "SELECT role_id FROM users WHERE id = ?",
                [$userId],
                'i'
            );
            return $user && $user['role_id'] == $roleId;
        } catch (Exception $e) {
            error_log('Role Check Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate password strength
     */
    public static function validatePasswordStrength($password) {
        $errors = [];

        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            $errors[] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters";
        }

        if (PASSWORD_REQUIRE_SPECIAL_CHARS && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one digit";
        }

        return $errors;
    }

    /**
     * Generate temporary password
     */
    public static function generateTempPassword($length = 12) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }
}
