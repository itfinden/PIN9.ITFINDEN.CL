<?php 
// Iniciar sesión al principio
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}



// Manejar cambio de idioma
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    if (in_array($lang, ['es', 'en'])) {
        $_SESSION['lang'] = $lang;
        
        // Forzar la escritura de la sesión antes de redirigir
        session_write_close();
        
        // Redirigir a la página sin parámetros para evitar bucle
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

require_once __DIR__ . '/lang/Languaje.php';

$lang = Language::autoDetect();


if (isset($_SESSION['id_user']) && !empty($_SESSION['id_user'])) {
    session_write_close();
    header('Location: content.php');
    exit();
}

// Checking if the form has been filled it up.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_form = filter_var(htmlspecialchars($_POST['user']), FILTER_SANITIZE_STRING);
    $password_form = filter_var(htmlspecialchars($_POST['password']), FILTER_SANITIZE_STRING);
    $password_form = hash('sha512', $password_form);
    $errors = '';

    // ----------------------- DATABASE CONNECTION ------------------------------------
    include 'db/functions.php';

    $database = new Database();
    $connection = $database->connection();

    
    $statement = $connection->prepare('SELECT * FROM users WHERE user =? AND password =?');
    $statement->execute(array($user_form, $password_form));
    $result = $statement->rowCount();
    if ($result == 1) {
        while ($id = $statement->fetch(PDO::FETCH_ASSOC)) {
            $id_user = $id['id_user'];
            $_SESSION['id_user'] = $id_user;
            $_SESSION['user'] = $user_form;
            $_SESSION['security'] = "FON";
            $info = GET_INFO($id_user);
            $_SESSION['user'] = $user_form;
            $_SESSION['id_company'] = $info[0]['id_company'];
            $_SESSION['name_company'] = $info[0]['name_company'];
            $_SESSION['rol_name'] = $info[0]['role_name'];
            $_SESSION['id_rol'] = $info[0]['id_role'];
            $_SESSION['mode'] = "";
            $_SESSION['enable_slidepanel'] = 1;
            // Verificar si es superadmin
            $_SESSION['is_superadmin'] = isSuperAdmin($id_user);


        }
        session_write_close();
        header('Location: index.php');
    } else {
        $errors = '<li>'. $lang->get('ERROR_001').'</li>';
    }
}

require 'views/login.view.php';

?>

