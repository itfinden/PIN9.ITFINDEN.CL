<?php
session_start();

// Manejar cambio de idioma ANTES de cualquier output
require_once __DIR__ . '/lang/language_handler.php';

// Manejar cambio de idioma
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    if (in_array($lang, ['es', 'en'])) {
        $_SESSION['lang'] = $lang;
    }
}

require_once 'db/functions.php';
require_once 'lang/Languaje.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

require_once 'security/check_access.php';

// Verificar permiso para gestionar servicios
verificarPermisoVista($_SESSION['id_user'], 10); // manage_services

require_once 'theme_handler.php';

$lang = Language::getInstance();
$current_lang = $_SESSION['lang'] ?? $lang->language ?? 'es';

function format_price($price, $lang) {
    if ($lang === 'es') {
        return '$' . number_format($price, 0, ',', '.');
    } else {
        return '$' . number_format($price, 2, '.', ',');
    }
}

$database = new Database();
$connection = $database->connection();
$id_company = $_SESSION['id_company'];

$sql = "SELECT * FROM services WHERE id_company = ? ORDER BY id_service DESC";
$stmt = $connection->prepare($sql);
$stmt->execute([$id_company]);
$services = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="es" <?php echo applyThemeToHTML(); ?>>
<head>
    <title>Servicios</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="js/theme-switcher.js"></script>
    <style>
        /* Estilos específicos para services.php usando CSS variables */
        .services-container {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            padding: 30px;
            margin: 0 auto;
            box-shadow: 0 4px 16px var(--shadow-light);
            transition: all var(--transition-speed) var(--transition-ease);
            max-width: 1200px;
        }
        
        .services-container:hover {
            box-shadow: 0 8px 25px var(--shadow-medium);
        }
        
        .services-title {
            color: var(--text-primary);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .services-title i {
            color: var(--primary-color);
        }
        
        .btn-new-service {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
            color: var(--text-light);
            border: none;
            border-radius: 25px;
            padding: 12px 25px;
            font-weight: 600;
            transition: all var(--transition-speed) var(--transition-ease);
            margin-bottom: 20px;
        }
        
        .btn-new-service:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px var(--shadow-medium);
            color: var(--text-light);
        }
        
        .table-services {
            background-color: var(--bg-card);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px var(--shadow-light);
        }
        
        .table-services thead th {
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            border-color: var(--border-color);
            font-weight: 600;
            padding: 15px 12px;
        }
        
        .table-services tbody td {
            color: var(--text-primary);
            border-color: var(--border-color);
            padding: 12px;
            vertical-align: middle;
        }
        
        .table-services tbody tr:hover {
            background-color: var(--bg-secondary);
        }
        
        .btn-action {
            border-radius: 6px;
            padding: 6px 12px;
            margin: 0 2px;
            transition: all var(--transition-speed) var(--transition-ease);
        }
        
        .btn-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px var(--shadow-medium);
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-active {
            background-color: var(--success-color);
            color: var(--text-light);
        }
        
        .status-inactive {
            background-color: var(--secondary-color);
            color: var(--text-light);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .services-container {
                padding: 20px;
                margin: 10px;
            }
            
            .services-title {
                font-size: 1.5rem;
            }
            
            .table-responsive {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body class="bg">
<?php require_once 'views/partials/modern_navbar.php'; ?>
<div style="height:80px;"></div>

<div class="container mt-4">
  <div class="services-container">
        <h2 class="services-title">
          <i class="fas fa-cogs"></i>
          Servicios
        </h2>
        <a href="new_service.php" class="btn btn-new-service">
          <i class="fas fa-plus mr-2"></i>Nuevo Servicio
        </a>
        <div class="table-responsive">
          <table class="table table-services table-hover">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Unidad</th>
                <th>Duración</th>
                <th>Precio</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($services as $service): ?>
                <tr>
                  <td><?= $service['id_service'] ?></td>
                  <td><?= htmlspecialchars($service['name']) ?></td>
                  <td><?= htmlspecialchars($service['type']) ?></td>
                  <td><?= htmlspecialchars($service['unit']) ?></td>
                  <td><?= htmlspecialchars($service['duration']) ?></td>
                  <td><?= format_price($service['price'], $current_lang) ?></td>
                  <td>
                    <span class="status-badge <?= $service['status'] === 'active' ? 'status-active' : 'status-inactive' ?>">
                      <?= htmlspecialchars($service['status']) ?>
                    </span>
                  </td>
                  <td>
                    <a href="edit_service.php?id_service=<?= $service['id_service'] ?>" class="btn btn-sm btn-warning btn-action" title="Editar">
                      <i class="fas fa-edit"></i>
                    </a>
                    <a href="delete_service.php?id_service=<?= $service['id_service'] ?>" class="btn btn-sm btn-danger btn-action" onclick="return confirm('¿Eliminar servicio?');" title="Eliminar">
                      <i class="fas fa-trash"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
  </div>
</div>
<?php require_once 'footer.php'; ?>

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