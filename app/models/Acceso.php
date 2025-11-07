<?php
/**
 * Acceso Model
 * Handles access logs and control
 */

require_once __DIR__ . '/Model.php';

class Acceso extends Model {
    protected $table = 'accesos';
    
    /**
     * Register access attempt
     */
    public function registrar($socioId, $dispositivoId, $sucursalId, $tipo, $estado, $motivo = null, $usuarioAutorizo = null) {
        $data = [
            'socio_id' => $socioId,
            'dispositivo_id' => $dispositivoId,
            'sucursal_id' => $sucursalId,
            'tipo' => $tipo,
            'estado' => $estado,
            'motivo' => $motivo,
            'usuario_autorizo' => $usuarioAutorizo
        ];
        
        return $this->insert($data);
    }
    
    /**
     * Get access log with details
     */
    public function getAccessLog($filters = [], $limit = 100) {
        $sql = "SELECT a.*, 
                       CONCAT(s.nombre, ' ', s.apellido) as socio_nombre,
                       s.codigo as socio_codigo,
                       d.nombre as dispositivo_nombre,
                       suc.nombre as sucursal_nombre,
                       u.nombre as usuario_nombre
                FROM accesos a
                INNER JOIN socios s ON a.socio_id = s.id
                LEFT JOIN dispositivos_shelly d ON a.dispositivo_id = d.id
                INNER JOIN sucursales suc ON a.sucursal_id = suc.id
                LEFT JOIN usuarios_staff u ON a.usuario_autorizo = u.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['sucursal_id'])) {
            $sql .= " AND a.sucursal_id = ?";
            $params[] = $filters['sucursal_id'];
        }
        
        if (!empty($filters['socio_id'])) {
            $sql .= " AND a.socio_id = ?";
            $params[] = $filters['socio_id'];
        }
        
        if (!empty($filters['dispositivo_id'])) {
            $sql .= " AND a.dispositivo_id = ?";
            $params[] = $filters['dispositivo_id'];
        }
        
        if (!empty($filters['tipo'])) {
            $sql .= " AND a.tipo = ?";
            $params[] = $filters['tipo'];
        }
        
        if (!empty($filters['estado'])) {
            $sql .= " AND a.estado = ?";
            $params[] = $filters['estado'];
        }
        
        if (!empty($filters['fecha_inicio'])) {
            $sql .= " AND DATE(a.fecha_hora) >= ?";
            $params[] = $filters['fecha_inicio'];
        }
        
        if (!empty($filters['fecha_fin'])) {
            $sql .= " AND DATE(a.fecha_hora) <= ?";
            $params[] = $filters['fecha_fin'];
        }
        
        $sql .= " ORDER BY a.fecha_hora DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get today's access count
     */
    public function getTodayCount($sucursalId = null) {
        $today = date('Y-m-d');
        if ($sucursalId) {
            return $this->count('DATE(fecha_hora) = ? AND estado = ? AND sucursal_id = ?', [$today, 'permitido', $sucursalId]);
        }
        return $this->count('DATE(fecha_hora) = ? AND estado = ?', [$today, 'permitido']);
    }
    
    /**
     * Get access statistics
     */
    public function getStats($sucursalId = null, $days = 30) {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        
        $sql = "SELECT DATE(fecha_hora) as fecha, 
                       COUNT(*) as total,
                       SUM(CASE WHEN estado = 'permitido' THEN 1 ELSE 0 END) as permitidos,
                       SUM(CASE WHEN estado = 'denegado' THEN 1 ELSE 0 END) as denegados
                FROM accesos
                WHERE DATE(fecha_hora) >= ?";
        
        $params = [$startDate];
        
        if ($sucursalId) {
            $sql .= " AND sucursal_id = ?";
            $params[] = $sucursalId;
        }
        
        $sql .= " GROUP BY DATE(fecha_hora) ORDER BY fecha ASC";
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get most active members
     */
    public function getMostActive($sucursalId = null, $days = 30, $limit = 10) {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        
        $sql = "SELECT s.id, CONCAT(s.nombre, ' ', s.apellido) as nombre,
                       s.codigo, COUNT(*) as total_accesos
                FROM accesos a
                INNER JOIN socios s ON a.socio_id = s.id
                WHERE a.estado = 'permitido' AND DATE(a.fecha_hora) >= ?";
        
        $params = [$startDate];
        
        if ($sucursalId) {
            $sql .= " AND a.sucursal_id = ?";
            $params[] = $sucursalId;
        }
        
        $sql .= " GROUP BY s.id ORDER BY total_accesos DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get peak hours
     */
    public function getPeakHours($sucursalId = null, $days = 30) {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        
        $sql = "SELECT HOUR(fecha_hora) as hora, COUNT(*) as total
                FROM accesos
                WHERE estado = 'permitido' AND DATE(fecha_hora) >= ?";
        
        $params = [$startDate];
        
        if ($sucursalId) {
            $sql .= " AND sucursal_id = ?";
            $params[] = $sucursalId;
        }
        
        $sql .= " GROUP BY HOUR(fecha_hora) ORDER BY hora ASC";
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
}
