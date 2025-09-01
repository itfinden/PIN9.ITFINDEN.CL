<?php
/**
 * Panel de administración WYSIWYG para editar contenido de la página principal
 * Solo accesible para superadmins
 */

session_start();
require_once '../db/connection.php';
require_once '../security/check_access.php';

// Verificar que sea superadmin
if (!isset($_SESSION['user']) || $_SESSION['user_role'] !== 'superadmin') {
    header('Location: ../login.php');
    die('Acceso denegado');
}

// Obtener idiomas disponibles
$languages = ['es', 'en'];
$current_lang = $_GET['lang'] ?? 'es';

// Procesar formulario de edición
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_content') {
        try {
            $stmt = $pdo->prepare("
                UPDATE main_page_content 
                SET title = ?, subtitle = ?, description = ?, icon = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND language = ?
            ");
            
            $stmt->execute([
                $_POST['title'],
                $_POST['subtitle'],
                $_POST['description'],
                $_POST['icon'],
                $_POST['content_id'],
                $current_lang
            ]);
            
            // Registrar en historial
            $stmt_history = $pdo->prepare("
                INSERT INTO content_history (content_id, user_id, action, old_content, new_content, language)
                VALUES (?, ?, 'update', ?, ?, ?)
            ");
            
            $stmt_history->execute([
                $_POST['content_id'],
                $_SESSION['user_id'],
                json_encode([
                    'title' => $_POST['old_title'],
                    'subtitle' => $_POST['old_subtitle'],
                    'description' => $_POST['old_description'],
                    'icon' => $_POST['old_icon']
                ]),
                json_encode([
                    'title' => $_POST['title'],
                    'subtitle' => $_POST['subtitle'],
                    'description' => $_POST['description'],
                    'icon' => $_POST['icon']
                ]),
                $current_lang
            ]);
            
            $success_message = "Contenido actualizado exitosamente!";
        } catch (PDOException $e) {
            $error_message = "Error al actualizar: " . $e->getMessage();
        }
    }
}

// Obtener contenido actual
try {
    $stmt = $pdo->prepare("
        SELECT * FROM main_page_content 
        WHERE language = ? 
        ORDER BY sort_order ASC
    ");
    $stmt->execute([$current_lang]);
    $content_sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error al obtener contenido: " . $e->getMessage();
    $content_sections = [];
}
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor de Contenido - PIN9</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Quill Editor CSS -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    
    <style>
        .content-section {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background: #f8f9fa;
        }
        
        .content-section:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: box-shadow 0.3s ease;
        }
        
        .section-header {
            background: #007bff;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .btn-save {
            background: #28a745;
            border-color: #28a745;
        }
        
        .btn-save:hover {
            background: #218838;
            border-color: #1e7e34;
        }
        
        .language-selector {
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 15px;
            margin-bottom: 20px;
        }
        
        .preview-section {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .ql-editor {
            min-height: 100px;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-edit text-primary"></i> Editor de Contenido WYSIWYG</h1>
                    <div>
                        <a href="../admin/dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al Dashboard
                        </a>
                        <a href="../main.php" target="_blank" class="btn btn-info">
                            <i class="fas fa-eye"></i> Ver Página
                        </a>
                    </div>
                </div>
                
                <!-- Selector de idioma -->
                <div class="mb-4">
                    <label for="language-select" class="form-label fw-bold">Idioma:</label>
                    <select id="language-select" class="form-select language-selector" onchange="changeLanguage(this.value)">
                        <option value="es" <?php echo $current_lang === 'es' ? 'selected' : ''; ?>>Español</option>
                        <option value="en" <?php echo $current_lang === 'en' ? 'selected' : ''; ?>>English</option>
                    </select>
                </div>
                
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Secciones de contenido -->
                <?php foreach ($content_sections as $section): ?>
                    <div class="content-section">
                        <div class="section-header">
                            <h5 class="mb-0">
                                <i class="fas fa-<?php echo $section['section_key'] === 'hero' ? 'star' : ($section['section_key'] === 'business_takeoff' ? 'rocket' : 'calendar'); ?>"></i>
                                <?php echo ucfirst(str_replace('_', ' ', $section['section_key'])); ?>
                            </h5>
                        </div>
                        
                        <form method="POST" class="content-form">
                            <input type="hidden" name="action" value="update_content">
                            <input type="hidden" name="content_id" value="<?php echo $section['id']; ?>">
                            <input type="hidden" name="old_title" value="<?php echo htmlspecialchars($section['title']); ?>">
                            <input type="hidden" name="old_subtitle" value="<?php echo htmlspecialchars($section['subtitle']); ?>">
                            <input type="hidden" name="old_description" value="<?php echo htmlspecialchars($section['description']); ?>">
                            <input type="hidden" name="old_icon" value="<?php echo htmlspecialchars($section['icon']); ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label fw-bold">Título:</label>
                                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($section['title']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label fw-bold">Icono (clase Font Awesome):</label>
                                        <input type="text" name="icon" class="form-control" value="<?php echo htmlspecialchars($section['icon']); ?>" placeholder="fas fa-rocket">
                                        <small class="form-text text-muted">Ejemplo: fas fa-rocket, far fa-calendar-check</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label fw-bold">Subtítulo/Descripción:</label>
                                <textarea name="subtitle" class="form-control" rows="3" required><?php echo htmlspecialchars($section['subtitle']); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label fw-bold">Descripción Extendida (HTML):</label>
                                <div id="editor-<?php echo $section['id']; ?>" class="quill-editor"><?php echo $section['description']; ?></div>
                                <input type="hidden" name="description" id="description-<?php echo $section['id']; ?>">
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" class="btn btn-save">
                                    <i class="fas fa-save"></i> Guardar Cambios
                                </button>
                            </div>
                        </form>
                        
                        <!-- Vista previa -->
                        <div class="preview-section">
                            <h6 class="text-muted mb-3"><i class="fas fa-eye"></i> Vista Previa:</h6>
                            <div class="preview-content">
                                <h4 class="text-primary">
                                    <?php if ($section['icon']): ?>
                                        <i class="<?php echo htmlspecialchars($section['icon']); ?>"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($section['title']); ?>
                                </h4>
                                <p class="text-muted"><?php echo htmlspecialchars($section['subtitle']); ?></p>
                                <?php if ($section['description']): ?>
                                    <div class="mt-2"><?php echo $section['description']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    
    <script>
        // Inicializar editores Quill
        const editors = {};
        document.querySelectorAll('.quill-editor').forEach((editorElement, index) => {
            const sectionId = editorElement.id.replace('editor-', '');
            const quill = new Quill(editorElement, {
                theme: 'snow',
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['link', 'image'],
                        ['clean']
                    ]
                }
            });
            
            editors[sectionId] = quill;
            
            // Sincronizar contenido con el formulario
            quill.on('text-change', function() {
                document.getElementById('description-' + sectionId).value = quill.root.innerHTML;
            });
        });
        
        // Función para cambiar idioma
        function changeLanguage(lang) {
            window.location.href = '?lang=' + lang;
        }
        
        // Sincronizar contenido antes de enviar formularios
        document.querySelectorAll('.content-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const contentId = this.querySelector('[name="content_id"]').value;
                if (editors[contentId]) {
                    document.getElementById('description-' + contentId).value = editors[contentId].root.innerHTML;
                }
            });
        });
    </script>
</body>
</html>
