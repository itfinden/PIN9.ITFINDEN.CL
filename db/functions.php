<?php
// Archivo de funciones para el sistema de permisos
// Incluye funciones para verificar permisos y roles

// Clase Database para conexión
class Database{
    private $hostname = 'localhost';
    private $username = 'itfinden_pin9';
    private $password = 'on5A5oR0zLG69eKS';
    private $database = 'itfinden_pin9';
    private $connection;

    public function connection(){
        $this->connection = null;
        try
        {
            $this->connection = new PDO('mysql:host=' . $this->hostname . ';dbname=' . $this->database . ';charset=utf8', 
            $this->username, $this->password);
        }
        catch(Exception $e)
        {
            die('Err : '.$e->getMessage());
        }

        return $this->connection;
    }
}

// Función para verificar si un usuario tiene un permiso específico
function tienePermiso($id_user, $id_permission) {
    $database = new Database();
    $connection = $database->connection();
    
    try {
        $sql = "SELECT COUNT(*) as tiene_permiso 
                FROM GET_ACCESS 
                WHERE id_user = :id_user AND id_permission = :id_permission";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([
            ':id_user' => $id_user,
            ':id_permission' => $id_permission
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['tiene_permiso'] > 0;
        
    } catch (PDOException $e) {
        error_log("Error verificando permiso: " . $e->getMessage());
        return false;
    }
}

// Función para verificar si un usuario tiene un rol específico
function tieneRol($id_user, $id_role) {
    $database = new Database();
    $connection = $database->connection();
    
    try {
        // Primero obtener el nombre del rol por ID
        $sql_rol = "SELECT name FROM roles WHERE id_role = :id_role";
        $stmt_rol = $connection->prepare($sql_rol);
        $stmt_rol->execute([':id_role' => $id_role]);
        $rol = $stmt_rol->fetch(PDO::FETCH_ASSOC);
        
        if (!$rol) {
            return false;
        }
        
        // Verificar si el usuario tiene ese rol en company_users
        $sql = "SELECT COUNT(*) as tiene_rol 
                FROM company_users 
                WHERE id_user = :id_user AND role = :role AND status = 'active'";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([
            ':id_user' => $id_user,
            ':role' => $rol['name']
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['tiene_rol'] > 0;
        
    } catch (PDOException $e) {
        error_log("Error verificando rol: " . $e->getMessage());
        return false;
    }
}

// Función para obtener todos los permisos de un usuario
function obtenerPermisosUsuario($id_user) {
    $database = new Database();
    $connection = $database->connection();
    
    try {
        $sql = "SELECT p.id_permission, p.name, p.Titulo 
                FROM GET_ACCESS ga
                JOIN permissions p ON ga.id_permission = p.id_permission
                WHERE ga.id_user = :id_user
                ORDER BY p.id_permission";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([':id_user' => $id_user]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error obteniendo permisos: " . $e->getMessage());
        return [];
    }
}

// Nota: Las funciones verificarPermisoVista(), mostrarErrorAcceso() y verificarRolUsuario()
// están definidas en security/check_access.php para evitar duplicación

// Función para obtener el rol principal del usuario
function obtenerRolUsuario($id_user) {
    $database = new Database();
    $connection = $database->connection();
    
    try {
        $sql = "SELECT cu.role as rol
                FROM company_users cu
                WHERE cu.id_user = :id_user AND cu.status = 1
                ORDER BY cu.id_company_user ASC
                LIMIT 1";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([':id_user' => $id_user]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['rol'] : null;
        
    } catch (PDOException $e) {
        error_log("Error obteniendo rol: " . $e->getMessage());
        return null;
    }
}

// Función para obtener información de la empresa del usuario
function obtenerEmpresaUsuario($id_user) {
    $database = new Database();
    $connection = $database->connection();
    
    try {
        $sql = "SELECT c.id_company, c.company_name, c.subscription_status
                FROM company_users cu
                JOIN companies c ON cu.id_company = c.id_company
                WHERE cu.id_user = :id_user AND (cu.status = 'active' OR cu.status = 1)
                LIMIT 1";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([':id_user' => $id_user]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error obteniendo empresa: " . $e->getMessage());
        return null;
    }
}

// Función para verificar si un usuario tiene un permiso específico por nombre
function user_has_permission($id_user, $permission_name) {
    $database = new Database();
    $connection = $database->connection();
    
    try {
        $sql = "SELECT COUNT(*) as tiene_permiso 
                FROM GET_ACCESS ga
                JOIN permissions p ON ga.id_permission = p.id_permission
                WHERE ga.id_user = :id_user AND p.name = :permission_name";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([
            ':id_user' => $id_user,
            ':permission_name' => $permission_name
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['tiene_permiso'] > 0;
        
    } catch (PDOException $e) {
        error_log("Error verificando permiso por nombre: " . $e->getMessage());
        return false;
    }
}

// Función para registrar logs de auditoría
function audit_log($action, $details) {
    $database = new Database();
    $connection = $database->connection();
    
    try {
        $id_user = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : null;
        
        $sql = "INSERT INTO audit_logs (id_user, action, details) 
                VALUES (:id_user, :action, :details)";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([
            ':id_user' => $id_user,
            ':action' => $action,
            ':details' => $details
        ]);
        
        return true;
        
    } catch (PDOException $e) {
        error_log("Error en audit_log: " . $e->getMessage());
        return false;
    }
}

// Función para obtener información extendida de usuario, rol y empresa desde la vista
function GET_INFO($id_user) {
    $database = new Database();
    $connection = $database->connection();
    try {
        $sql = "select * from GET_INFO WHERE id_user = :id_user";
        $stmt = $connection->prepare($sql);
        $stmt->execute([':id_user' => $id_user]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error en GET_INFO: " . $e->getMessage());
        return [];
    }
}

// Obtener los calendarios activos de una empresa
function getActiveCalendarsByCompany($id_company) {
    $database = new Database();
    $connection = $database->connection();
    try {
        $sql = "SELECT * FROM calendar_companies WHERE id_company = :id_company AND is_active = 1 ORDER BY calendar_name ASC";
        $stmt = $connection->prepare($sql);
        $stmt->execute([':id_company' => $id_company]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error obteniendo calendarios: " . $e->getMessage());
        return [];
    }
}

// Sanitiza un string eliminando espacios y escapando caracteres especiales HTML
function limpiarString($valor) {
    return htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
}

// Verificar si un usuario es superadmin
function isSuperAdmin($id_user) {
    $database = new Database();
    $connection = $database->connection();
    try {
        $sql = "SELECT COUNT(*) as count FROM GET_ACCESS ga 
                JOIN permissions p ON ga.id_permission = p.id_permission 
                WHERE ga.id_user = :id_user AND p.name = 'admin_panel'";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([':id_user' => $id_user]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        

        
        return $result['count'] > 0;
        
    } catch (PDOException $e) {
        error_log("Error verificando superadmin: " . $e->getMessage());
        return false;
    }
}

// Obtener todas las empresas y calendarios para superadmins
function getAllCompaniesAndCalendars() {
    $database = new Database();
    $connection = $database->connection();
    try {
        $sql = "SELECT 
                    c.id_company,
                    c.company_name,
                    cc.id_calendar_companies,
                    cc.calendar_name,
                    cc.colour,
                    cc.is_default,
                    cc.is_active
                FROM companies c
                LEFT JOIN calendar_companies cc ON c.id_company = cc.id_company AND cc.is_active = 1
                WHERE 1=1
                ORDER BY c.company_name ASC, cc.calendar_name ASC";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organizar por empresa
        $companies = [];
        foreach ($results as $row) {
            $company_id = $row['id_company'];
            if (!isset($companies[$company_id])) {
                $companies[$company_id] = [
                    'id_company' => $company_id,
                    'company_name' => $row['company_name'],
                    'calendars' => []
                ];
            }
            
            // Agregar calendario si existe
            if ($row['id_calendar_companies'] && $row['calendar_name']) {
                $companies[$company_id]['calendars'][] = [
                    'id_calendar_companies' => $row['id_calendar_companies'],
                    'calendar_name' => $row['calendar_name'],
                    'colour' => $row['colour'] ?? '#007bff',
                    'is_default' => $row['is_default'] ?? 0,
                    'is_active' => $row['is_active'] ?? 1
                ];
            }
        }
        
        return $companies;
        
    } catch (PDOException $e) {
        error_log("Error obteniendo empresas y calendarios: " . $e->getMessage());
        return [];
    }
}
?>
