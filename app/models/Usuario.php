<?php
/**
 * Usuario Model
 * Handles staff users (superadmin, admin, recepcionista)
 */

require_once __DIR__ . '/Model.php';

class Usuario extends Model {
    protected $table = 'usuarios_staff';
    
    /**
     * Authenticate user
     */
    public function authenticate($email, $password) {
        $user = $this->whereOne('email = ? AND activo = 1', [$email]);
        
        if ($user && password_verify($password, $user['password'])) {
            // Update last login
            $this->update($user['id'], ['ultimo_login' => date('Y-m-d H:i:s')]);
            return $user;
        }
        
        return false;
    }
    
    /**
     * Create new user
     */
    public function create($data) {
        // Hash password
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
        }
        
        return $this->insert($data);
    }
    
    /**
     * Update user password
     */
    public function updatePassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
        return $this->update($userId, ['password' => $hashedPassword]);
    }
    
    /**
     * Get users by role
     */
    public function getByRole($role, $sucursalId = null) {
        if ($sucursalId) {
            return $this->where('rol = ? AND sucursal_id = ? AND activo = 1', [$role, $sucursalId]);
        }
        return $this->where('rol = ? AND activo = 1', [$role]);
    }
    
    /**
     * Get users by branch
     */
    public function getBySucursal($sucursalId) {
        return $this->where('sucursal_id = ? AND activo = 1', [$sucursalId]);
    }
    
    /**
     * Check if email exists
     */
    public function emailExists($email, $excludeId = null) {
        if ($excludeId) {
            $user = $this->whereOne('email = ? AND id != ?', [$email, $excludeId]);
        } else {
            $user = $this->whereOne('email = ?', [$email]);
        }
        return $user !== false;
    }
}
