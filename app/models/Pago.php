<?php
/**
 * Pago Model
 * Handles payment records
 */

require_once __DIR__ . '/Model.php';

class Pago extends Model {
    protected $table = 'pagos';
    
    /**
     * Get payments with details
     */
    public function getWithDetails($sucursalId = null) {
        $sql = "SELECT p.*, 
                       CONCAT(s.nombre, ' ', s.apellido) as socio_nombre,
                       s.codigo as socio_codigo,
                       tm.nombre as tipo_membresia_nombre,
                       u.nombre as usuario_nombre
                FROM pagos p
                INNER JOIN socios s ON p.socio_id = s.id
                INNER JOIN tipos_membresia tm ON p.tipo_membresia_id = tm.id
                LEFT JOIN usuarios_staff u ON p.usuario_registro = u.id
                WHERE 1=1";
        
        if ($sucursalId) {
            $sql .= " AND p.sucursal_id = ?";
            $stmt = $this->query($sql, [$sucursalId]);
        } else {
            $stmt = $this->query($sql);
        }
        
        $sql .= " ORDER BY p.fecha_pago DESC";
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get payment by ID with details
     */
    public function getPaymentDetails($id) {
        $sql = "SELECT p.*, 
                       CONCAT(s.nombre, ' ', s.apellido) as socio_nombre,
                       s.codigo as socio_codigo,
                       s.email as socio_email,
                       tm.nombre as tipo_membresia_nombre,
                       tm.duracion_dias,
                       u.nombre as usuario_nombre,
                       suc.nombre as sucursal_nombre
                FROM pagos p
                INNER JOIN socios s ON p.socio_id = s.id
                INNER JOIN tipos_membresia tm ON p.tipo_membresia_id = tm.id
                LEFT JOIN usuarios_staff u ON p.usuario_registro = u.id
                INNER JOIN sucursales suc ON p.sucursal_id = suc.id
                WHERE p.id = ?";
        
        $stmt = $this->query($sql, [$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get total income
     */
    public function getTotalIncome($sucursalId = null, $startDate = null, $endDate = null) {
        $sql = "SELECT SUM(monto) as total 
                FROM pagos 
                WHERE estado = 'completado'";
        
        $params = [];
        
        if ($sucursalId) {
            $sql .= " AND sucursal_id = ?";
            $params[] = $sucursalId;
        }
        
        if ($startDate) {
            $sql .= " AND DATE(fecha_pago) >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND DATE(fecha_pago) <= ?";
            $params[] = $endDate;
        }
        
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }
    
    /**
     * Get today's income
     */
    public function getTodayIncome($sucursalId = null) {
        $today = date('Y-m-d');
        return $this->getTotalIncome($sucursalId, $today, $today);
    }
    
    /**
     * Get monthly income statistics
     */
    public function getMonthlyStats($sucursalId = null, $months = 12) {
        $startDate = date('Y-m-01', strtotime("-{$months} months"));
        
        $sql = "SELECT DATE_FORMAT(fecha_pago, '%Y-%m') as mes,
                       SUM(monto) as total,
                       COUNT(*) as cantidad
                FROM pagos
                WHERE estado = 'completado' AND DATE(fecha_pago) >= ?";
        
        $params = [$startDate];
        
        if ($sucursalId) {
            $sql .= " AND sucursal_id = ?";
            $params[] = $sucursalId;
        }
        
        $sql .= " GROUP BY DATE_FORMAT(fecha_pago, '%Y-%m') ORDER BY mes ASC";
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get payments by method
     */
    public function getByMethod($sucursalId = null, $startDate = null, $endDate = null) {
        $sql = "SELECT metodo_pago, 
                       SUM(monto) as total,
                       COUNT(*) as cantidad
                FROM pagos
                WHERE estado = 'completado'";
        
        $params = [];
        
        if ($sucursalId) {
            $sql .= " AND sucursal_id = ?";
            $params[] = $sucursalId;
        }
        
        if ($startDate) {
            $sql .= " AND DATE(fecha_pago) >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND DATE(fecha_pago) <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " GROUP BY metodo_pago";
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
}
