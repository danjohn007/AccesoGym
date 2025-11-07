<?php
/**
 * Sucursal Model
 * Handles gym branches
 */

require_once __DIR__ . '/Model.php';

class Sucursal extends Model {
    protected $table = 'sucursales';
    
    /**
     * Get active branches
     */
    public function getActive() {
        return $this->where('activo = 1', []);
    }
    
    /**
     * Get branch with statistics
     */
    public function getWithStats($id) {
        $sucursal = $this->find($id);
        
        if (!$sucursal) {
            return false;
        }
        
        // Get member count
        $sql = "SELECT COUNT(*) as total FROM socios WHERE sucursal_id = ? AND estado = 'activo'";
        $stmt = $this->query($sql, [$id]);
        $result = $stmt->fetch();
        $sucursal['total_socios'] = $result['total'];
        
        // Get device count
        $sql = "SELECT COUNT(*) as total FROM dispositivos_shelly WHERE sucursal_id = ? AND activo = 1";
        $stmt = $this->query($sql, [$id]);
        $result = $stmt->fetch();
        $sucursal['total_dispositivos'] = $result['total'];
        
        // Get staff count
        $sql = "SELECT COUNT(*) as total FROM usuarios_staff WHERE sucursal_id = ? AND activo = 1";
        $stmt = $this->query($sql, [$id]);
        $result = $stmt->fetch();
        $sucursal['total_staff'] = $result['total'];
        
        return $sucursal;
    }
}
