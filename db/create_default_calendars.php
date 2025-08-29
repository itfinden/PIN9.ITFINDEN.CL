<?php
require_once __DIR__ . '/functions.php';
$database = new Database();
$db = $database->connection();

// Obtener todas las empresas
$companies = $db->query('SELECT id_company, company_name FROM companies')->fetchAll(PDO::FETCH_ASSOC);

$count = 0;
foreach ($companies as $company) {
    $idc = $company['id_company'];
    // ¿Ya tiene calendario por defecto?
    $stmt = $db->prepare('SELECT COUNT(*) as cnt FROM calendar_companies WHERE id_company = ? AND is_default = 1');
    $stmt->execute([$idc]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    if (!$exists) {
        $stmt2 = $db->prepare('INSERT INTO calendar_companies (id_company, id_calendar, calendar_name, colour, is_default, is_active) VALUES (?, NULL, ?, ?, 1, 1)');
        $stmt2->execute([$idc, 'Calendario Principal', '#0275d8']);
        $count++;
        echo "Creado calendario por defecto para empresa #$idc ({$company['company_name']})\n";
    }
}
echo "Listo. Se crearon $count calendarios por defecto.\n"; 