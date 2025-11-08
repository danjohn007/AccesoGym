<?php
require_once 'bootstrap.php';
Auth::requireAuth();

header('Content-Type: application/json');

$query = sanitize($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

require_once __DIR__ . '/../app/models/Socio.php';
$socioModel = new Socio();

// SuperAdmin can search all branches, others only their branch
$sucursalId = Auth::isSuperadmin() ? null : Auth::sucursalId();

// Search by name, code, email, or phone
$results = $socioModel->search($query, $sucursalId);

echo json_encode($results);
