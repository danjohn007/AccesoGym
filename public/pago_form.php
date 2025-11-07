<?php
require_once 'bootstrap.php';
Auth::requireAuth();

require_once __DIR__ . '/../app/models/Pago.php';
require_once __DIR__ . '/../app/models/Socio.php';
require_once __DIR__ . '/../app/models/TipoMembresia.php';

$pagoModel = new Pago();
$socioModel = new Socio();
$tipoMembresiaModel = new TipoMembresia();

$user = Auth::user();
$sucursalId = Auth::isSuperadmin() ? null : Auth::sucursalId();

$isEdit = isset($_GET['id']) && !empty($_GET['id']);
$pagoId = $isEdit ? (int)$_GET['id'] : null;
$socioId = isset($_GET['socio_id']) ? (int)$_GET['socio_id'] : null;

$pago = $isEdit ? $pagoModel->find($pagoId) : null;
$socio = $socioId ? $socioModel->getWithMembresia($socioId) : ($pago ? $socioModel->getWithMembresia($pago['socio_id']) : null);
$tiposMembresia = $tipoMembresiaModel->getActive();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de seguridad inválido';
    } else {
        $socio_id = (int)$_POST['socio_id'];
        $tipo_membresia_id = (int)$_POST['tipo_membresia_id'];
        $monto = (float)$_POST['monto'];
        $metodo_pago = sanitize($_POST['metodo_pago'] ?? '');
        $referencia = sanitize($_POST['referencia'] ?? '');
        $estado = $_POST['estado'] ?? 'completado';
        $notas = sanitize($_POST['notas'] ?? '');
        $actualizar_membresia = isset($_POST['actualizar_membresia']);
        
        if (empty($socio_id)) $errors[] = 'Debe seleccionar un socio';
        if (empty($tipo_membresia_id)) $errors[] = 'Debe seleccionar un tipo de membresía';
        if ($monto <= 0) $errors[] = 'El monto debe ser mayor a cero';
        if (empty($metodo_pago)) $errors[] = 'Debe seleccionar un método de pago';
        
        if (empty($errors)) {
            $socio = $socioModel->find($socio_id);
            $tipoMembresia = $tipoMembresiaModel->find($tipo_membresia_id);
            
            $data = [
                'socio_id' => $socio_id,
                'tipo_membresia_id' => $tipo_membresia_id,
                'monto' => $monto,
                'metodo_pago' => $metodo_pago,
                'referencia' => $referencia,
                'estado' => $estado,
                'notas' => $notas,
                'usuario_registro' => Auth::id(),
                'sucursal_id' => $socio['sucursal_id']
            ];
            
            if ($isEdit) {
                $pagoModel->update($pagoId, $data);
                logEvent('pago', "Pago actualizado: {$socio['nombre']} {$socio['apellido']} - " . formatMoney($monto), 
                         Auth::id(), $socio_id, $socio['sucursal_id']);
            } else {
                $pagoId = $pagoModel->insert($data);
                logEvent('pago', "Nuevo pago registrado: {$socio['nombre']} {$socio['apellido']} - " . formatMoney($monto), 
                         Auth::id(), $socio_id, $socio['sucursal_id']);
                
                // Update membership if payment is completed and checkbox is checked
                if ($estado === 'completado' && $actualizar_membresia) {
                    $fecha_inicio = date('Y-m-d');
                    
                    // If member has active membership, extend from current expiration
                    if ($socio['estado'] === 'activo' && $socio['fecha_vencimiento'] >= $fecha_inicio) {
                        $fecha_inicio = date('Y-m-d', strtotime($socio['fecha_vencimiento'] . ' +1 day'));
                    }
                    
                    $fecha_vencimiento = date('Y-m-d', strtotime($fecha_inicio . ' +' . $tipoMembresia['duracion_dias'] . ' days'));
                    
                    $socioModel->update($socio_id, [
                        'tipo_membresia_id' => $tipo_membresia_id,
                        'fecha_inicio' => $fecha_inicio,
                        'fecha_vencimiento' => $fecha_vencimiento,
                        'estado' => 'activo'
                    ]);
                    
                    logEvent('modificacion', "Membresía actualizada: {$socio['nombre']} {$socio['apellido']}", 
                             Auth::id(), $socio_id, $socio['sucursal_id']);
                }
            }
            
            $success = true;
            $successMessage = $isEdit ? 'Pago actualizado correctamente' : 'Pago registrado correctamente';
            
            if (!$isEdit) {
                header("Location: pago_detalle.php?id={$pagoId}");
                exit;
            }
        }
    }
}

$pageTitle = $isEdit ? 'Editar Pago' : 'Registrar Pago';
$csrfToken = Auth::generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100">
    <?php include __DIR__ . '/../app/views/partials/navbar.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><?php echo $pageTitle; ?></h1>
                <p class="text-gray-600">Complete los datos del pago</p>
            </div>
            <a href="pagos.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Volver
            </a>
        </div>
        
        <!-- Messages -->
        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Se encontraron los siguientes errores:</h3>
                        <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800"><?php echo $successMessage; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Member Info (if selected) -->
        <?php if ($socio): ?>
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-user text-blue-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-blue-800">
                            Socio: <?php echo htmlspecialchars($socio['nombre'] . ' ' . $socio['apellido']); ?> 
                            (<?php echo htmlspecialchars($socio['codigo']); ?>)
                        </p>
                        <?php if ($socio['tipo_membresia_nombre']): ?>
                            <p class="text-xs text-blue-700 mt-1">
                                Membresía actual: <?php echo htmlspecialchars($socio['tipo_membresia_nombre']); ?> 
                                - Vence: <?php echo formatDate($socio['fecha_vencimiento']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Form -->
        <form method="POST" class="bg-white rounded-lg shadow p-6" x-data="{ membresiaId: <?php echo $pago['tipo_membresia_id'] ?? ($socio['tipo_membresia_id'] ?? 'null'); ?> }">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="col-span-2">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">
                        <i class="fas fa-file-invoice-dollar mr-2"></i>Datos del Pago
                    </h2>
                </div>
                
                <?php if (!$socioId): ?>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Socio *</label>
                    <select name="socio_id" required onchange="this.form.submit()"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccione un socio</option>
                        <?php 
                        $socios = $socioModel->getAllWithMembresia($sucursalId);
                        foreach ($socios as $s): 
                        ?>
                            <option value="<?php echo $s['id']; ?>" 
                                    <?php echo ($pago['socio_id'] ?? $socio['id'] ?? 0) == $s['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['codigo'] . ' - ' . $s['nombre'] . ' ' . $s['apellido']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                    <input type="hidden" name="socio_id" value="<?php echo $socioId; ?>">
                <?php endif; ?>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Membresía *</label>
                    <select name="tipo_membresia_id" required x-model="membresiaId" @change="updatePrice"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccione</option>
                        <?php foreach ($tiposMembresia as $tipo): ?>
                            <option value="<?php echo $tipo['id']; ?>" data-precio="<?php echo $tipo['precio']; ?>"
                                    <?php echo ($pago['tipo_membresia_id'] ?? '') == $tipo['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tipo['nombre']); ?> - <?php echo formatMoney($tipo['precio']); ?> 
                                (<?php echo $tipo['duracion_dias']; ?> días)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Monto *</label>
                    <input type="number" name="monto" step="0.01" min="0" required id="montoInput"
                           value="<?php echo htmlspecialchars($pago['monto'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Método de Pago *</label>
                    <select name="metodo_pago" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccione</option>
                        <option value="efectivo" <?php echo ($pago['metodo_pago'] ?? '') == 'efectivo' ? 'selected' : ''; ?>>Efectivo</option>
                        <option value="tarjeta" <?php echo ($pago['metodo_pago'] ?? '') == 'tarjeta' ? 'selected' : ''; ?>>Tarjeta</option>
                        <option value="transferencia" <?php echo ($pago['metodo_pago'] ?? '') == 'transferencia' ? 'selected' : ''; ?>>Transferencia</option>
                        <option value="stripe" <?php echo ($pago['metodo_pago'] ?? '') == 'stripe' ? 'selected' : ''; ?>>Stripe</option>
                        <option value="mercadopago" <?php echo ($pago['metodo_pago'] ?? '') == 'mercadopago' ? 'selected' : ''; ?>>MercadoPago</option>
                        <option value="conekta" <?php echo ($pago['metodo_pago'] ?? '') == 'conekta' ? 'selected' : ''; ?>>Conekta</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Referencia</label>
                    <input type="text" name="referencia"
                           value="<?php echo htmlspecialchars($pago['referencia'] ?? ''); ?>"
                           placeholder="Número de transacción, folio, etc."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                    <select name="estado"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="completado" <?php echo ($pago['estado'] ?? 'completado') == 'completado' ? 'selected' : ''; ?>>Completado</option>
                        <option value="pendiente" <?php echo ($pago['estado'] ?? '') == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="cancelado" <?php echo ($pago['estado'] ?? '') == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                    </select>
                </div>
                
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notas</label>
                    <textarea name="notas" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($pago['notas'] ?? ''); ?></textarea>
                </div>
                
                <?php if (!$isEdit): ?>
                <div class="col-span-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="actualizar_membresia" checked
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <span class="ml-2 text-sm text-gray-700">Actualizar membresía del socio al registrar el pago</span>
                    </label>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Actions -->
            <div class="mt-6 flex justify-end space-x-3">
                <a href="pagos.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded">
                    Cancelar
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                    <i class="fas fa-save mr-2"></i>
                    <?php echo $isEdit ? 'Actualizar' : 'Registrar'; ?>
                </button>
            </div>
        </form>
    </div>
    
    <script>
        function updatePrice() {
            const select = document.querySelector('[name="tipo_membresia_id"]');
            const montoInput = document.getElementById('montoInput');
            const selectedOption = select.options[select.selectedIndex];
            
            if (selectedOption && selectedOption.dataset.precio) {
                montoInput.value = selectedOption.dataset.precio;
            }
        }
    </script>
</body>
</html>
