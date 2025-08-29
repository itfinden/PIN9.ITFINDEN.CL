<?php
session_start();

require_once __DIR__ . '/../../theme_handler.php';
require_once __DIR__ . '/../../db/functions.php';
require_once __DIR__ . '/../../security/check_access.php';
require_once __DIR__ . '/../../Class/Class_EvolutionApi.php';
require_once __DIR__ . '/../../config/setting.php';

// Solo Admins y Superadmins
if (!isset($_SESSION['id_user'])) {
    header('Location: /login.php');
    exit;
}

$id_user = $_SESSION['id_user'];
$is_superadmin_flag = !empty($_SESSION['is_superadmin']);
$id_rol = isset($_SESSION['id_rol']) ? (int)$_SESSION['id_rol'] : 0;
$is_admin_flag = $id_rol === 2 || $is_superadmin_flag;

if (!$is_admin_flag) {
    mostrarErrorAcceso();
    exit;
}

$db = new Database();
$pdo = $db->connection();

// Obtener empresa del usuario para scope
$company = obtenerEmpresaUsuario($id_user);
$id_company = $company['id_company'] ?? null;

// Crear nuevo bot
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $phone_number = limpiarString($_POST['phone_number'] ?? '');
    $instance_name = limpiarString($_POST['instance_name'] ?? '');
    $evo_base = limpiarString($_POST['evolutionapi_base_url'] ?? '');
    $evo_token = limpiarString($_POST['evolutionapi_token'] ?? '');

    if ($phone_number && $instance_name) {
        try {
            // Usar configuración global del módulo
            $evo_base = EVOLUTION_API_BASE_URL;
            $evo_token = EVOLUTION_API_GLOBAL_KEY;
            
            // Usar la clase EvolutionAPI para crear la instancia
            $evolution = new EvolutionAPI($evo_base, $evo_token, $instance_name);
            
            // Obtener configuración por defecto para la instancia
            $instance_data = getDefaultInstanceConfig($instance_name);
            
            $result = $evolution->createInstance($instance_data);
            
            if ($result['status'] === 200 && !isset($result['data']['error'])) {
                // Instancia creada exitosamente, guardar en BD
                $sql = "INSERT INTO whatsapp_bots (id_company, phone_number, instance_name, evolutionapi_base_url, evolutionapi_token, status) 
                        VALUES (:id_company, :phone_number, :instance_name, :base, :token, 'inactive')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id_company' => $id_company,
                    ':phone_number' => $phone_number,
                    ':instance_name' => $instance_name,
                    ':base' => $evo_base,
                    ':token' => $evo_token
                ]);
                audit_log('botwhatsapp_create', json_encode(['phone' => $phone_number, 'instance' => $instance_name, 'evolution_response' => $result['data']]));
                header('Location: manage.php?created=1');
                exit;
            } else {
                $error_message = 'Error de Evolution API: ' . ($result['data']['error'] ?? 'Error desconocido');
                header('Location: manage.php?error=' . urlencode($error_message));
                exit;
            }
        } catch (Exception $e) {
            $error_message = 'Error de conexión: ' . $e->getMessage();
            header('Location: manage.php?error=' . urlencode($error_message));
            exit;
        }
    }
}

// Verificar estado de conexión
if (isset($_GET['check_status'])) {
    $id_bot = (int)$_GET['check_status'];
    if ($id_bot > 0) {
        $stmt = $pdo->prepare("SELECT * FROM whatsapp_bots WHERE id_bot = :id_bot AND (id_company IS NULL OR id_company = :id_company)");
        $stmt->execute([':id_bot' => $id_bot, ':id_company' => $id_company]);
        $bot = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($bot) {
            try {
                $evolution = new EvolutionAPI($bot['evolutionapi_base_url'], $bot['evolutionapi_token'], $bot['instance_name']);
                $result = $evolution->connectionState();
                
                $status_message = 'Estado: ' . ($result['data']['state'] ?? 'Desconocido');
                header('Location: manage.php?status=' . urlencode($status_message));
                exit;
            } catch (Exception $e) {
                header('Location: manage.php?error=' . urlencode('Error verificando estado: ' . $e->getMessage()));
                exit;
            }
        }
    }
}

// Enviar mensaje de prueba
if (isset($_GET['test_message'])) {
    $id_bot = (int)$_GET['test_message'];
    if ($id_bot > 0) {
        $stmt = $pdo->prepare("SELECT * FROM whatsapp_bots WHERE id_bot = :id_bot AND (id_company IS NULL OR id_company = :id_company)");
        $stmt->execute([':id_bot' => $id_bot, ':id_company' => $id_company]);
        $bot = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($bot) {
            try {
                $evolution = new EvolutionAPI($bot['evolutionapi_base_url'], $bot['evolutionapi_token'], $bot['instance_name']);
                $result = $evolution->sendText($bot['phone_number'], 'Mensaje de prueba desde PIN9 BOTWhatsapp - ' . date('Y-m-d H:i:s'));
                
                if ($result['status'] === 200) {
                    header('Location: manage.php?success=' . urlencode('Mensaje enviado correctamente'));
                } else {
                    header('Location: manage.php?error=' . urlencode('Error enviando mensaje: ' . ($result['data']['error'] ?? 'Error desconocido')));
                }
                exit;
            } catch (Exception $e) {
                header('Location: manage.php?error=' . urlencode('Error enviando mensaje: ' . $e->getMessage()));
                exit;
            }
        }
    }
}

// Eliminar bot
if (isset($_GET['delete'])) {
    $id_bot = (int)$_GET['delete'];
    if ($id_bot > 0) {
        // Obtener información del bot antes de eliminar
        $stmt = $pdo->prepare("SELECT * FROM whatsapp_bots WHERE id_bot = :id_bot AND (id_company IS NULL OR id_company = :id_company)");
        $stmt->execute([':id_bot' => $id_bot, ':id_company' => $id_company]);
        $bot = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($bot) {
            try {
                // Usar la clase EvolutionAPI para eliminar la instancia
                $evolution = new EvolutionAPI($bot['evolutionapi_base_url'], $bot['evolutionapi_token'], $bot['instance_name']);
                $result = $evolution->deleteInstance();
                
                // Eliminar de la BD independientemente del resultado de la API
                $stmt = $pdo->prepare("DELETE FROM whatsapp_bots WHERE id_bot = :id_bot");
                $stmt->execute([':id_bot' => $id_bot]);
                audit_log('botwhatsapp_delete', json_encode(['id_bot' => $id_bot, 'instance' => $bot['instance_name'], 'evolution_response' => $result['data']]));
                header('Location: manage.php?deleted=1');
                exit;
            } catch (Exception $e) {
                // Si hay error en la API, igual eliminar de BD
                $stmt = $pdo->prepare("DELETE FROM whatsapp_bots WHERE id_bot = :id_bot");
                $stmt->execute([':id_bot' => $id_bot]);
                audit_log('botwhatsapp_delete', json_encode(['id_bot' => $id_bot, 'instance' => $bot['instance_name'], 'error' => $e->getMessage()]));
                header('Location: manage.php?deleted=1');
                exit;
            }
        }
    }
}

// Listar bots del scope
if ($is_superadmin_flag && !$id_company) {
    $bots = $pdo->query("SELECT * FROM whatsapp_bots ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("SELECT * FROM whatsapp_bots WHERE id_company = :id_company ORDER BY created_at DESC");
    $stmt->execute([':id_company' => $id_company]);
    $bots = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es" <?php echo applyThemeToHTML(); ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOT WhatsApp</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="/js/theme-switcher.js"></script>
</head>
<body class="bg">
<?php require_once __DIR__ . '/../../views/partials/modern_navbar.php'; ?>
<div style="height:80px;"></div>

<div class="container mt-4">
    <div class="card" style="background-color: var(--bg-card); border: 1px solid var(--border-color);">
        <div class="card-body">
            <h3 class="mb-3" style="color: var(--text-primary);"><i class="fab fa-whatsapp mr-2" style="color: var(--primary-color);"></i> BOT WhatsApp</h3>

            <?php if (isset($_GET['created'])): ?>
                <div class="alert alert-success" style="background-color: var(--success-color-alpha); color: var(--success-color); border: 1px solid var(--success-color);">Bot creado exitosamente en Evolution API.</div>
            <?php endif; ?>
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-warning" style="background-color: var(--warning-color-alpha); color: var(--warning-color); border: 1px solid var(--warning-color);">Bot eliminado.</div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger" style="background-color: var(--danger-color-alpha); color: var(--danger-color); border: 1px solid var(--danger-color);">Error: <?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['status'])): ?>
                <div class="alert alert-info" style="background-color: var(--info-color-alpha); color: var(--info-color); border: 1px solid var(--info-color);"><?php echo htmlspecialchars($_GET['status']); ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success" style="background-color: var(--success-color-alpha); color: var(--success-color); border: 1px solid var(--success-color);"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>

            <form method="post" class="mb-4">
                <input type="hidden" name="action" value="create">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label style="color: var(--text-primary);">Teléfono</label>
                        <input type="text" name="phone_number" class="form-control" placeholder="+56912345678" required style="background-color: var(--bg-input); color: var(--text-primary); border: 1px solid var(--border-color);">
                    </div>
                    <div class="form-group col-md-6">
                        <label style="color: var(--text-primary);">Nombre de Instancia</label>
                        <input type="text" name="instance_name" class="form-control" placeholder="mi-instancia" required style="background-color: var(--bg-input); color: var(--text-primary); border: 1px solid var(--border-color);">
                    </div>
                </div>
                <div class="alert alert-info" style="background-color: var(--info-color-alpha); color: var(--info-color); border: 1px solid var(--info-color);">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Configuración automática:</strong> Se usará la URL base y API Key configuradas en el módulo.
                    <br><small>URL: <?php echo htmlspecialchars(EVOLUTION_API_BASE_URL); ?></small>
                </div>
                <button type="submit" class="btn btn-success" style="background-color: var(--success-color); border-color: var(--success-color);"><i class="fas fa-plus mr-1"></i> Agregar Bot</button>
            </form>

            <div class="table-responsive">
                <table class="table table-striped" style="background-color: var(--bg-card); border: 1px solid var(--border-color);">
                    <thead>
                        <tr style="background-color: var(--bg-secondary);">
                            <th style="color: var(--text-primary); border-color: var(--border-color);">ID</th>
                            <th style="color: var(--text-primary); border-color: var(--border-color);">Teléfono</th>
                            <th style="color: var(--text-primary); border-color: var(--border-color);">Instancia</th>
                            <th style="color: var(--text-primary); border-color: var(--border-color);">Estado</th>
                            <th style="color: var(--text-primary); border-color: var(--border-color);">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bots as $bot): ?>
                            <tr style="background-color: var(--bg-card);">
                                <td style="color: var(--text-primary); border-color: var(--border-color);"><?php echo (int)$bot['id_bot']; ?></td>
                                <td style="color: var(--text-primary); border-color: var(--border-color);"><?php echo htmlspecialchars($bot['phone_number']); ?></td>
                                <td style="color: var(--text-primary); border-color: var(--border-color);"><?php echo htmlspecialchars($bot['instance_name']); ?></td>
                                <td style="border-color: var(--border-color);"><span class="badge badge-<?php echo $bot['status']==='active'?'success':'secondary'; ?>"><?php echo htmlspecialchars($bot['status']); ?></span></td>
                                <td style="border-color: var(--border-color);">
                                    <div class="btn-group" role="group">
                                        <a class="btn btn-sm btn-info" href="?check_status=<?php echo (int)$bot['id_bot']; ?>" title="Ver estado">
                                            <i class="fas fa-info-circle"></i>
                                        </a>
                                        <a class="btn btn-sm btn-success" href="?test_message=<?php echo (int)$bot['id_bot']; ?>" title="Enviar mensaje de prueba" onclick="return confirm('¿Enviar mensaje de prueba?');">
                                            <i class="fas fa-paper-plane"></i>
                                        </a>
                                        <a class="btn btn-sm btn-danger" href="?delete=<?php echo (int)$bot['id_bot']; ?>" title="Eliminar bot" onclick="return confirm('¿Eliminar bot?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($bots)): ?>
                            <tr style="background-color: var(--bg-card);"><td colspan="5" class="text-center" style="color: var(--text-muted); border-color: var(--border-color);">Sin bots registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../views/partials/slidepanel_menu.php'; ?>

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


