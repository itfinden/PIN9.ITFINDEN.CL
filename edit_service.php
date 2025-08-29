<?php
session_start();
require_once 'db/functions.php';
require_once 'lang/Languaje.php';
require_once 'security/check_access.php';

$lang = Language::getInstance();
$current_lang = $lang->language ?? 'es';

function format_price($price, $lang) {
    if ($lang === 'es') {
        return '$' . number_format($price, 0, ',', '.');
    } else {
        return '$' . number_format($price, 2, '.', ',');
    }
}

// Verificar permiso para editar servicios
verificarPermisoVista($_SESSION['id_user'], 20); // edit_service
$database = new Database();
$connection = $database->connection();
$id_company = $_SESSION['id_company'];
$id_service = $_GET['id_service'] ?? null;
if (!$id_service) die('Servicio no especificado.');
$stmt = $connection->prepare("SELECT * FROM services WHERE id_service = ? AND id_company = ?");
$stmt->execute([$id_service, $id_company]);
$service = $stmt->fetch();
if (!$service) die('Servicio no encontrado.');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';
    if (!$name || !$type) {
        $error = 'Nombre y tipo son obligatorios.';
    } else {
        $stmt = $connection->prepare("UPDATE services SET name=?, type=?, unit=?, duration=?, price=?, description=?, status=? WHERE id_service=? AND id_company=?");
        $ok = $stmt->execute([$name, $type, $unit, $duration, $price, $description, $status, $id_service, $id_company]);
        if ($ok) {
            audit_log('Editar servicio', 'Servicio: ' . $name);
            header('Location: services.php');
            exit;
        } else {
            $error = 'Error al actualizar el servicio.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Editar Servicio</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body class="bg">
<?php require_once 'views/partials/modern_navbar.php'; ?>
<div class="container bg-light text-dark rounded mt-4 p-4 shadow">
    <h2 class="mb-4"><i class="fas fa-edit mr-2"></i>Editar Servicio</h2>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
    <form method="post">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label>Nombre</label>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($service['name']); ?>" required>
            </div>
            <div class="form-group col-md-6">
                <label>Tipo</label>
                <input type="text" name="type" class="form-control" value="<?php echo htmlspecialchars($service['type']); ?>" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-4">
                <label>Unidad</label>
                <input type="text" name="unit" class="form-control" value="<?php echo htmlspecialchars($service['unit']); ?>">
            </div>
            <div class="form-group col-md-4">
                <label>Duración</label>
                <input type="text" name="duration" class="form-control" value="<?php echo htmlspecialchars($service['duration']); ?>">
            </div>
            <div class="form-group col-md-4">
                <label>Precio</label>
                <input type="number" step="0.01" name="price" class="form-control" value="<?php echo htmlspecialchars($service['price']); ?>">
                <small class="form-text text-muted">Actual: <?php echo format_price($service['price'], $current_lang); ?></small>
            </div>
        </div>
        <div class="form-group">
            <label>Descripción</label>
            <textarea name="description" class="form-control"><?php echo htmlspecialchars($service['description']); ?></textarea>
        </div>
        <div class="form-group">
            <label>Estado</label>
            <select name="status" class="form-control">
                <option value="active" <?php if($service['status']==='active') echo 'selected'; ?>>Activo</option>
                <option value="inactive" <?php if($service['status']==='inactive') echo 'selected'; ?>>Inactivo</option>
                <option value="suspended" <?php if($service['status']==='suspended') echo 'selected'; ?>>Suspendido</option>
            </select>
        </div>
        <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i>Guardar</button>
        <a href="services.php" class="btn btn-secondary ml-2">Cancelar</a>
    </form>
</div>
</body>
</html> 