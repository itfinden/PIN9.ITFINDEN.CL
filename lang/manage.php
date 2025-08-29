<?php
session_start();

// Manejar cambio de idioma
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    if (in_array($lang, ['es', 'en'])) {
        $_SESSION['lang'] = $lang;
    }
}

require_once '../db/functions.php';
require_once '../security/check_access.php';
require_once '../theme_handler.php';

// Verificar permiso para gestionar permisos por rol
require_once __DIR__ . '/../config/setting.php';
if (!isset($_SESSION['id_user'])) {
    // Usuario no autenticado, redirigir a login
     header('Location: ' . SITE_URL . 'login.php');
    exit();
}
// Verificar permiso para gestionar idiomas
verificarPermisoVista($_SESSION['id_user'], 7); // Idiomas

// Función para aplanar arrays de idioma y detectar duplicados
function flattenLangArray($array, $prefix = '', &$duplicates = []) {
    $result = [];
    foreach ($array as $key => $value) {
        $fullKey = $prefix === '' ? $key : $prefix . '.' . $key;
        if (isset($result[$fullKey])) {
            $duplicates[] = $fullKey;
        }
        if (is_array($value)) {
            $result += flattenLangArray($value, $fullKey, $duplicates);
        } else {
            if (isset($result[$fullKey])) {
                $duplicates[] = $fullKey;
            }
            $result[$fullKey] = $value;
        }
    }
    return $result;
}

// Función para ordenar arrays recursivamente por clave
function ksortRecursive(&$array) {
    if (!is_array($array)) return;
    ksort($array);
    foreach ($array as &$value) {
        if (is_array($value)) {
            ksortRecursive($value);
        }
    }
}

// Función para reconstruir arrays anidados desde claves tipo 'A.B.C'
function setNestedArrayValue(array &$array, $path, $value) {
    $keys = explode('.', $path);
    $ref = &$array;
    foreach ($keys as $key) {
        if (!isset($ref[$key]) || !is_array($ref[$key])) {
            $ref[$key] = [];
        }
        $ref = &$ref[$key];
    }
    $ref = $value;
}

// Función para eliminar claves anidadas
function unsetNestedArrayKey(array &$array, $path) {
    $keys = explode('.', $path);
    $ref = &$array;
    foreach ($keys as $i => $key) {
        if ($i === count($keys) - 1) {
            unset($ref[$key]);
        } elseif (isset($ref[$key]) && is_array($ref[$key])) {
            $ref = &$ref[$key];
        } else {
            return; // No existe la clave
        }
    }
}

// Función para fusionar claves de ambos idiomas
function fusionarIdiomas($flat_es, $flat_en) {
    $todas_las_claves = array_unique(array_merge(array_keys($flat_es), array_keys($flat_en)));
    sort($todas_las_claves);
    $fusion = [];
    foreach ($todas_las_claves as $clave) {
        $fusion[$clave] = [
            'es' => $flat_es[$clave] ?? '',
            'en' => $flat_en[$clave] ?? ''
        ];
    }
    return $fusion;
}

// Función para contar claves faltantes en cada idioma
function contarFaltantes($fusion) {
    $faltan_es = 0;
    $faltan_en = 0;
    foreach ($fusion as $val) {
        if ($val['es'] === '') $faltan_es++;
        if ($val['en'] === '') $faltan_en++;
    }
    return [$faltan_es, $faltan_en];
}

// Lógica para fusionar archivos si se solicita
$duplicates = [];
$es = include __DIR__ . '/ES.php';
$en = include __DIR__ . '/EN.php';
$flat_es = flattenLangArray($es, '', $duplicates);
$flat_en = flattenLangArray($en, '', $duplicates);

// Mostrar advertencia si hay claves duplicadas
if (!empty($duplicates)) {
    echo '<div class="alert alert-danger"><b>¡Claves duplicadas detectadas!</b><br>Las siguientes claves están repetidas:<br><ul>';
    foreach (array_unique($duplicates) as $dup) {
        echo '<li>' . htmlspecialchars($dup) . '</li>';
    }
    echo '</ul>Corrige los archivos de idioma para continuar.</div>';
    exit;
}

$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $key = trim($_POST['key'] ?? '');
    $es_val = trim($_POST['es'] ?? '');
    $en_val = trim($_POST['en'] ?? '');
    if (!$key) {
        $error = 'La clave es obligatoria.';
    } else {
        setNestedArrayValue($es, $key, $es_val);
        setNestedArrayValue($en, $key, $en_val);
        ksortRecursive($es);
        ksortRecursive($en);
        file_put_contents(__DIR__ . '/ES.php', "<?php\nreturn " . var_export($es, true) . ";\n");
        file_put_contents(__DIR__ . '/EN.php', "<?php\nreturn " . var_export($en, true) . ";\n");
        $success = 'Clave guardada correctamente.';
        $flat_es = flattenLangArray($es);
        $flat_en = flattenLangArray($en);
    }
}

if (isset($_GET['delete'])) {
    $del_key = $_GET['delete'];
    unsetNestedArrayKey($es, $del_key);
    unsetNestedArrayKey($en, $del_key);
    ksortRecursive($es);
    ksortRecursive($en);
    file_put_contents(__DIR__ . '/ES.php', "<?php\nreturn " . var_export($es, true) . ";\n");
    file_put_contents(__DIR__ . '/EN.php', "<?php\nreturn " . var_export($en, true) . ";\n");
    $success = 'Clave eliminada correctamente.';
    $flat_es = flattenLangArray($es);
    $flat_en = flattenLangArray($en);
}

if (isset($_POST['update_key'])) {
    $key = $_POST['update_key'];
    $es_val = $_POST['update_es'] ?? '';
    $en_val = $_POST['update_en'] ?? '';
    setNestedArrayValue($es, $key, $es_val);
    setNestedArrayValue($en, $key, $en_val);
    ksortRecursive($es);
    ksortRecursive($en);
    file_put_contents(__DIR__ . '/ES.php', "<?php\nreturn " . var_export($es, true) . ";\n");
    file_put_contents(__DIR__ . '/EN.php', "<?php\nreturn " . var_export($en, true) . ";\n");
    $success = 'Clave actualizada correctamente.';
    $flat_es = flattenLangArray($es);
    $flat_en = flattenLangArray($en);
}

// Siempre recalcula la fusión y los contadores antes del HTML
$fusion = fusionarIdiomas($flat_es, $flat_en);
list($faltan_es, $faltan_en) = contarFaltantes($fusion);
$hay_diferencias = $faltan_es > 0 || $faltan_en > 0;

?>
<!DOCTYPE html>
<html lang="es" <?php echo applyThemeToHTML(); ?>>
<head>
    <title>Gestor de Idiomas</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Theme Switcher JS -->
    <script src="../js/theme-switcher.js"></script>
    
    <style>
    /* Estilos específicos para el gestor de idiomas con tema */
    .language-manager-container {
        background: var(--bg-card);
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 10px 30px var(--shadow-light);
        border: 1px solid var(--border-color);
        transition: all var(--transition-speed) var(--transition-ease);
    }
    
    .language-manager-title {
        color: var(--text-primary);
        font-weight: 700;
        margin-bottom: 25px;
    }
    
    .form-control {
        border-radius: 8px;
        border: 2px solid var(--border-color);
        padding: 10px 15px;
        transition: all var(--transition-speed) var(--transition-ease);
        background-color: var(--bg-card);
        color: var(--text-primary);
    }
    
    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        background-color: var(--bg-card);
        color: var(--text-primary);
    }
    
    .form-control::placeholder {
        color: var(--text-muted);
    }
    
    .form-control-plaintext {
        color: var(--text-primary) !important;
        font-weight: 600;
    }
    
    .btn {
        border-radius: 8px;
        transition: all var(--transition-speed) var(--transition-ease);
        font-weight: 500;
    }
    
    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px var(--shadow-medium);
    }
    
    .table {
        background: var(--bg-card);
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 15px var(--shadow-light);
    }
    
    .table thead th {
        background: var(--primary-color);
        color: var(--text-light);
        border-color: var(--border-color);
        font-weight: 600;
    }
    
    .table tbody tr {
        transition: all var(--transition-speed) var(--transition-ease);
    }
    
    .table tbody tr:hover {
        background: var(--bg-secondary);
    }
    
    .table td {
        border-color: var(--border-color);
        color: var(--text-primary);
    }
    
    .alert {
        border-radius: 10px;
        border: none;
        padding: 15px 20px;
    }
    
    .badge {
        font-size: 0.85rem;
        padding: 8px 12px;
        border-radius: 20px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .language-manager-container {
            padding: 20px;
            margin: 10px;
        }
        
        .form-row {
            margin: 0;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
    }
    </style>
</head>
<body class="bg">
<?php require_once '../views/partials/modern_navbar.php'; ?>

<div class="container mt-4">
    <div class="language-manager-container">
        <h2 class="language-manager-title mb-4"><i class="fas fa-language mr-2"></i>Gestor de Idiomas</h2>
    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
    <form method="post" class="mb-4">
        <div class="form-row align-items-end">
            <div class="form-group col-md-3">
                <label>Clave (MAYÚSCULAS)</label>
                <input type="text" name="key" class="form-control text-uppercase" required pattern="[A-Z0-9_]+" oninput="this.value = this.value.toUpperCase();">
            </div>
            <div class="form-group col-md-3">
                <label>ES</label>
                <input type="text" name="es" class="form-control">
            </div>
            <div class="form-group col-md-3">
                <label>EN</label>
                <input type="text" name="en" class="form-control">
            </div>
            <div class="form-group col-md-3">
                <button type="submit" class="btn btn-success btn-block"><i class="fas fa-save mr-1"></i> Guardar</button>
            </div>
        </div>
    </form>
    <div class="mb-3">
        <span class="badge badge-warning">Faltan en ES: <?php echo $faltan_es; ?></span>
        <span class="badge badge-info">Faltan en EN: <?php echo $faltan_en; ?></span>
    </div>
    <form method="post" class="mb-4 d-inline-block">
        <button type="submit" name="fusionar" class="btn btn-warning mb-3" <?php echo $hay_diferencias ? '' : 'disabled'; ?>>
            <i class="fas fa-compress-arrows-alt"></i> Fusionar idiomas
        </button>
    </form>
    <h4 class="mb-3">Claves existentes</h4>
    <div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead class="thead-dark">
            <tr>
                <th>Clave</th>
                <th>ES</th>
                <th>EN</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($fusion as $key => $val): ?>
            <tr>
                <form method="post">
                    <td><input type="text" readonly class="form-control-plaintext font-weight-bold text-uppercase" value="<?php echo htmlspecialchars($key); ?>" name="update_key"></td>
                    <td><input type="text" name="update_es" class="form-control<?php echo $val['es'] === '' ? ' bg-warning' : ''; ?>" value="<?php echo htmlspecialchars($val['es']); ?>"></td>
                    <td><input type="text" name="update_en" class="form-control<?php echo $val['en'] === '' ? ' bg-warning' : ''; ?>" value="<?php echo htmlspecialchars($val['en']); ?>"></td>
                    <td>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-sync-alt"></i> Actualizar</button>
                        <a href="?delete=<?php echo urlencode($key); ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar clave?');"><i class="fas fa-trash"></i> Eliminar</a>
                    </td>
                </form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
        </div>
    </div>
</div>

<?php require_once '../footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" crossorigin="anonymous"></script>

<script>
$(document).ready(function() {
    // Inicializar theme switcher
    if (typeof ThemeSwitcher !== 'undefined') {
        const themeSwitcher = new ThemeSwitcher();
        themeSwitcher.init();
    }
});
</script>
</body>
</html> 