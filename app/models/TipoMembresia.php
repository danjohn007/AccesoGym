<?php
/**
 * TipoMembresia Model
 * Handles membership types
 */

require_once __DIR__ . '/Model.php';

class TipoMembresia extends Model {
    protected $table = 'tipos_membresia';
    
    /**
     * Get active membership types
     */
    public function getActive() {
        return $this->where('activo = 1', []);
    }
    
    /**
     * Get membership type with member count
     */
    public function getWithMemberCount() {
        $sql = "SELECT tm.*, COUNT(s.id) as total_socios
                FROM tipos_membresia tm
                LEFT JOIN socios s ON tm.id = s.tipo_membresia_id AND s.estado = 'activo'
                WHERE tm.activo = 1
                GROUP BY tm.id
                ORDER BY tm.nombre ASC";
        
        $stmt = $this->query($sql);
        return $stmt->fetchAll();
    }
}
