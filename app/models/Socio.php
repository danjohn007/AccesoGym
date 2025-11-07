<?php
/**
 * Socio Model
 * Handles gym members
 */

require_once __DIR__ . '/Model.php';

class Socio extends Model {
    protected $table = 'socios';
    
    /**
     * Get socio with membership details
     */
    public function getWithMembresia($id) {
        $sql = "SELECT s.*, tm.nombre as tipo_membresia_nombre, tm.color as membresia_color,
                       suc.nombre as sucursal_nombre
                FROM socios s
                LEFT JOIN tipos_membresia tm ON s.tipo_membresia_id = tm.id
                LEFT JOIN sucursales suc ON s.sucursal_id = suc.id
                WHERE s.id = ?";
        $stmt = $this->query($sql, [$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get all socios with membership details
     */
    public function getAllWithMembresia($sucursalId = null) {
        $sql = "SELECT s.*, tm.nombre as tipo_membresia_nombre, tm.color as membresia_color,
                       suc.nombre as sucursal_nombre
                FROM socios s
                LEFT JOIN tipos_membresia tm ON s.tipo_membresia_id = tm.id
                LEFT JOIN sucursales suc ON s.sucursal_id = suc.id";
        
        if ($sucursalId) {
            $sql .= " WHERE s.sucursal_id = ?";
            $stmt = $this->query($sql, [$sucursalId]);
        } else {
            $stmt = $this->query($sql);
        }
        
        return $stmt->fetchAll();
    }
    
    /**
     * Find socio by code
     */
    public function findByCode($code) {
        return $this->whereOne('codigo = ?', [$code]);
    }
    
    /**
     * Find socio by phone
     */
    public function findByPhone($phone) {
        return $this->whereOne('telefono = ?', [$phone]);
    }
    
    /**
     * Generate unique member code
     */
    public function generateCode($sucursalId) {
        $prefix = 'GYM';
        $year = date('y');
        $counter = 1;
        
        // Get last code for this year
        $sql = "SELECT codigo FROM {$this->table} 
                WHERE codigo LIKE ? 
                ORDER BY id DESC LIMIT 1";
        $stmt = $this->query($sql, ["{$prefix}{$year}%"]);
        $last = $stmt->fetch();
        
        if ($last) {
            $lastNumber = (int)substr($last['codigo'], -4);
            $counter = $lastNumber + 1;
        }
        
        return $prefix . $year . str_pad($counter, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Update membership status based on expiration
     */
    public function updateStatus($id) {
        $socio = $this->find($id);
        
        if (!$socio || !$socio['fecha_vencimiento']) {
            return false;
        }
        
        $today = date('Y-m-d');
        $vencimiento = $socio['fecha_vencimiento'];
        
        if ($vencimiento < $today) {
            $this->update($id, ['estado' => 'vencido']);
        } elseif ($socio['estado'] == 'vencido') {
            $this->update($id, ['estado' => 'activo']);
        }
        
        return true;
    }
    
    /**
     * Get active members count
     */
    public function getActiveCount($sucursalId = null) {
        if ($sucursalId) {
            return $this->count('estado = ? AND sucursal_id = ?', ['activo', $sucursalId]);
        }
        return $this->count('estado = ?', ['activo']);
    }
    
    /**
     * Get members expiring soon
     */
    public function getExpiringSoon($days = 7, $sucursalId = null) {
        $today = date('Y-m-d');
        $futureDate = date('Y-m-d', strtotime("+{$days} days"));
        
        $sql = "SELECT s.*, tm.nombre as tipo_membresia_nombre
                FROM socios s
                LEFT JOIN tipos_membresia tm ON s.tipo_membresia_id = tm.id
                WHERE s.fecha_vencimiento BETWEEN ? AND ?
                AND s.estado = 'activo'";
        
        $params = [$today, $futureDate];
        
        if ($sucursalId) {
            $sql .= " AND s.sucursal_id = ?";
            $params[] = $sucursalId;
        }
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Validate access permission
     */
    public function canAccess($id) {
        $socio = $this->getWithMembresia($id);
        
        if (!$socio || $socio['estado'] != 'activo') {
            return ['allowed' => false, 'reason' => 'Membresía no activa'];
        }
        
        // Check expiration
        if ($socio['fecha_vencimiento'] < date('Y-m-d')) {
            return ['allowed' => false, 'reason' => 'Membresía vencida'];
        }
        
        // Check schedule if membership type has restrictions
        if ($socio['tipo_membresia_id']) {
            $sql = "SELECT acceso_horario_inicio, acceso_horario_fin, dias_semana 
                    FROM tipos_membresia WHERE id = ?";
            $stmt = $this->query($sql, [$socio['tipo_membresia_id']]);
            $membresia = $stmt->fetch();
            
            if ($membresia) {
                $currentTime = date('H:i:s');
                $currentDay = date('N'); // 1 (Monday) to 7 (Sunday)
                
                // Check if current day is allowed
                $allowedDays = explode(',', $membresia['dias_semana']);
                if (!in_array($currentDay, $allowedDays)) {
                    return ['allowed' => false, 'reason' => 'Día no permitido'];
                }
                
                // Check if current time is within allowed hours
                if ($currentTime < $membresia['acceso_horario_inicio'] || 
                    $currentTime > $membresia['acceso_horario_fin']) {
                    return ['allowed' => false, 'reason' => 'Fuera del horario permitido'];
                }
            }
        }
        
        return ['allowed' => true, 'socio' => $socio];
    }
}
