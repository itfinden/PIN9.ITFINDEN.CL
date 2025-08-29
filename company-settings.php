<?php
session_start();
require_once 'db/functions.php';

// Solo admin de empresa
if (!isset($_SESSION['id_user']) || !isset($_SESSION['id_company']) || $_SESSION['user_role'] !== 'admin') {
    die('Acceso restringido solo a administradores de empresa.');
}

$id_company = $_SESSION['id_company'];
$database = new Database();
$connection = $database->connection();

// Obtener datos actuales
$stmt = $connection->prepare("SELECT * FROM companies WHERE id_company = ?");
$stmt->execute([$id_company]);
$company = $stmt->fetch();

if (!$company) die('Empresa no encontrada.');

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'company_name', 'company_email', 'company_phone', 'company_address', 'company_website', 'company_tax_id'
    ];
    $data = [];
    foreach ($fields as $field) {
        $data[$field] = $_POST[$field] ?? '';
    }
    // Validación básica
    if (empty($data['company_name']) || empty($data['company_email'])) {
        $error = 'El nombre y el email son obligatorios.';
    } else {
        $sql = "UPDATE companies SET company_name=?, company_email=?, company_phone=?, company_address=?, company_website=?, company_tax_id=? WHERE id_company=?";
        $stmt = $connection->prepare($sql);
        $ok = $stmt->execute([
            $data['company_name'], $data['company_email'], $data['company_phone'], $data['company_address'],
            $data['company_website'], $data['company_tax_id'], $id_company
        ]);
        if ($ok) {
            audit_log('Editar empresa propia', 'Empresa ID: ' . $id_company . ', Nombre: ' . $data['company_name']);
            $success = 'Datos actualizados correctamente.';
            // Refrescar datos
            $stmt = $connection->prepare("SELECT * FROM companies WHERE id_company = ?");
            $stmt->execute([$id_company]);
            $company = $stmt->fetch();
        } else {
            $error = 'Error al actualizar los datos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Configuración de Empresa</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body class="bg">
<?php require_once 'views/partials/modern_navbar.php'; ?>

<div class="container bg-light text-dark rounded mt-4 p-4 shadow">
    <h2 class="mb-4"><i class="fas fa-cog mr-2"></i>Configuración de Empresa</h2>
    <a href="services.php" class="btn btn-primary mb-3"><i class="fas fa-cogs mr-1"></i>Gestionar Servicios</a>
    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
    <form method="post">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label>Nombre</label>
                <input type="text" name="company_name" class="form-control" value="<?php echo htmlspecialchars($company['company_name']); ?>" required>
            </div>
            <div class="form-group col-md-6">
                <label>Email</label>
                <input type="email" name="company_email" class="form-control" value="<?php echo htmlspecialchars($company['company_email']); ?>" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label>Teléfono</label>
                <input type="text" name="company_phone" class="form-control" value="<?php echo htmlspecialchars($company['company_phone']); ?>">
            </div>
            <div class="form-group col-md-6">
                <label>Dirección</label>
                <input type="text" name="company_address" class="form-control" value="<?php echo htmlspecialchars($company['company_address']); ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label>Website</label>
                <input type="url" name="company_website" class="form-control" value="<?php echo htmlspecialchars($company['company_website']); ?>">
            </div>
            <div class="form-group col-md-6">
                <label>RUT / Tax ID</label>
                <input type="text" name="company_tax_id" class="form-control" value="<?php echo htmlspecialchars($company['company_tax_id']); ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i> Guardar cambios</button>
    </form>
</div>

<?php require_once 'footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" crossorigin="anonymous"></script>
</body>
</html> 