<?php
require_once 'bootstrap.php';
header('Content-Type: text/css');

$db = Database::getInstance();
$conn = $db->getConnection();

// Load configuration
$config = [];
try {
    $stmt = $conn->query("SELECT clave, valor FROM configuracion WHERE grupo = 'estilos'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $config[$row['clave']] = $row['valor'];
    }
} catch (Exception $e) {
    // If table doesn't exist, use defaults
}

$colorPrimario = $config['color_primario'] ?? '#3B82F6';
$colorSecundario = $config['color_secundario'] ?? '#10B981';
$colorAccento = $config['color_acento'] ?? '#F59E0B';
$fuentePrincipal = $config['fuente_principal'] ?? 'system';
$borderRadius = $config['border_radius'] ?? 'medium';

// Font imports
$fontUrl = '';
switch($fuentePrincipal) {
    case 'inter':
        $fontUrl = "@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');";
        $fontFamily = "'Inter', sans-serif";
        break;
    case 'roboto':
        $fontUrl = "@import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');";
        $fontFamily = "'Roboto', sans-serif";
        break;
    case 'opensans':
        $fontUrl = "@import url('https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap');";
        $fontFamily = "'Open Sans', sans-serif";
        break;
    case 'poppins':
        $fontUrl = "@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');";
        $fontFamily = "'Poppins', sans-serif";
        break;
    default:
        $fontFamily = "-apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
}

// Border radius values
$radiusValues = [
    'none' => '0',
    'small' => '0.25rem',
    'medium' => '0.5rem',
    'large' => '1rem'
];
$radiusValue = $radiusValues[$borderRadius] ?? '0.5rem';

echo $fontUrl;
?>

:root {
    --color-primary: <?php echo $colorPrimario; ?>;
    --color-secondary: <?php echo $colorSecundario; ?>;
    --color-accent: <?php echo $colorAccento; ?>;
    --font-family: <?php echo $fontFamily; ?>;
    --border-radius: <?php echo $radiusValue; ?>;
}

body {
    font-family: var(--font-family);
}

/* Apply primary color to buttons */
.bg-blue-600 {
    background-color: var(--color-primary) !important;
}

.bg-blue-700 {
    filter: brightness(0.9);
    background-color: var(--color-primary) !important;
}

.hover\:bg-blue-700:hover {
    filter: brightness(0.9);
    background-color: var(--color-primary) !important;
}

.text-blue-600 {
    color: var(--color-primary) !important;
}

.text-blue-700 {
    color: var(--color-primary) !important;
}

.border-blue-500 {
    border-color: var(--color-primary) !important;
}

.ring-blue-500 {
    --tw-ring-color: var(--color-primary) !important;
}

.focus\:ring-blue-500:focus {
    --tw-ring-color: var(--color-primary) !important;
}

.focus\:border-blue-500:focus {
    border-color: var(--color-primary) !important;
}

/* Apply secondary color */
.bg-green-600 {
    background-color: var(--color-secondary) !important;
}

.text-green-600 {
    color: var(--color-secondary) !important;
}

/* Apply accent color */
.bg-yellow-500 {
    background-color: var(--color-accent) !important;
}

.text-yellow-600 {
    color: var(--color-accent) !important;
}

/* Apply border radius */
.rounded,
.rounded-md,
.rounded-lg {
    border-radius: var(--border-radius) !important;
}

.rounded-full {
    border-radius: 9999px !important;
}
