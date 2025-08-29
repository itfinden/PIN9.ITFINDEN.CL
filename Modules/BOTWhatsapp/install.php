<?php
session_start();

require_once __DIR__ . '/../../db/functions.php';
require_once __DIR__ . '/../../security/check_access.php';

// Solo Superadmins pueden instalar el módulo
verificarPermisoVista($_SESSION['id_user'] ?? null, 50); // 1 suele mapear a admin_panel/superadmin

$db = new Database();
$pdo = $db->connection();

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS whatsapp_bots (
        id_bot INT AUTO_INCREMENT PRIMARY KEY,
        id_company INT NULL,
        phone_number VARCHAR(32) NOT NULL,
        instance_name VARCHAR(128) NOT NULL,
        evolutionapi_base_url VARCHAR(255) NOT NULL,
        evolutionapi_token VARCHAR(255) NOT NULL,
        status VARCHAR(32) NOT NULL DEFAULT 'inactive',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_company_phone (id_company, phone_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    echo 'Módulo BOTWhatsapp instalado correctamente.';
} catch (Exception $e) {
    echo 'Error instalando módulo BOTWhatsapp: ' . htmlspecialchars($e->getMessage());
}


