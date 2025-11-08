<?php
/**
 * DispositivoHikvision Model
 * Handles Hikvision devices with API integration
 */

require_once __DIR__ . '/Model.php';

class DispositivoHikvision extends Model {
    protected $table = 'dispositivos_hikvision';
    
    /**
     * Get devices by branch
     */
    public function getBySucursal($sucursalId) {
        return $this->where('sucursal_id = ? AND activo = 1', [$sucursalId]);
    }
    
    /**
     * Get device by device_id
     */
    public function findByDeviceId($deviceId) {
        return $this->whereOne('device_id = ?', [$deviceId]);
    }
    
    /**
     * Update device status
     */
    public function updateStatus($id, $status) {
        return $this->update($id, [
            'estado' => $status,
            'ultima_conexion' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Get online devices count
     */
    public function getOnlineCount($sucursalId = null) {
        if ($sucursalId) {
            return $this->count('estado = ? AND sucursal_id = ? AND activo = 1', ['online', $sucursalId]);
        }
        return $this->count('estado = ? AND activo = 1', ['online']);
    }
    
    /**
     * Get all devices with branch info
     */
    public function getAllWithSucursal($sucursalId = null, $includeDisabled = false) {
        $sql = "SELECT d.*, s.nombre as sucursal_nombre
                FROM dispositivos_hikvision d
                INNER JOIN sucursales s ON d.sucursal_id = s.id";
        
        $where = [];
        $params = [];
        
        if (!$includeDisabled) {
            $where[] = "d.activo = 1";
        }
        
        if ($sucursalId) {
            $where[] = "d.sucursal_id = ?";
            $params[] = $sucursalId;
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $sql .= " ORDER BY d.created_at DESC";
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get disabled devices
     */
    public function getDisabled($sucursalId = null) {
        $sql = "SELECT d.*, s.nombre as sucursal_nombre
                FROM dispositivos_hikvision d
                INNER JOIN sucursales s ON d.sucursal_id = s.id
                WHERE d.activo = 0";
        
        if ($sucursalId) {
            $sql .= " AND d.sucursal_id = ?";
            $stmt = $this->query($sql, [$sucursalId]);
        } else {
            $stmt = $this->query($sql);
        }
        
        return $stmt->fetchAll();
    }
}
