<?php
#header
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pin9 - Sistema de Gestión</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Summernote CSS -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
</head>
<body>

<header class="m-0 p-0">
    <nav class="navbar navbar-expand-lg pt-3 text-dark">
        <div class="menu container">
            <a href="index.php" class="navbar-brand">
                <!-- Logo Image -->
                <img src="img/logo.png" width="45" alt="Pin9" class="d-inline-block align-middle mr-2">
                <!-- Logo Text -->
                <span class="logo_text align-middle">Pin 9x</span>
            </a>
            
            <ul class="navbar-nav ml-auto">
                <li><span class="btn text-primary mx-2"><i class="far fa-user pr-2"></i><?php echo isset($_SESSION['user']) ? $_SESSION['user'] : 'Usuario'; ?></span></li>
                <li><span class="btn text-primary mx-2"><i class="far fa-calendar-alt pr-2"></i>Fecha: <span class="pl-2 date"></span></li>	
                <li><span class="btn text-primary mr-2"><i class="far fa-clock pr-2"></i>Hora: <span class="pl-2 clock"></span></li>				
            </ul>	

            <button type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation" class="navbar-toggler">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div id="navbarSupportedContent" class="collapse navbar-collapse">
                <ul class="navbar-nav ml-auto">
                    <li><a href="logout.php" class="btn text-primary mr-2">Cerrar Sesión</a></li>	
                    <li><a href="login.php" class="btn text-primary mr-2">Iniciar Sesión</a></li>   			
                </ul>
            </div>
        </div>
    </nav>
</header>

<script type="text/javascript">
    function clock() {
        var time = new Date(),          
            hours = time.getHours(),    
            minutes = time.getMinutes(),
            seconds = time.getSeconds();
        var date = time.getFullYear()+'-'+(time.getMonth()+1)+'-'+time.getDate();
        
        document.querySelectorAll('.clock')[0].innerHTML = harold(hours) + ":" + harold(minutes) + ":" + harold(seconds);
        document.querySelectorAll('.date')[0].innerHTML = date;
        
        function harold(standIn) {
            if (standIn < 10) {
                standIn = '0' + standIn
            }
            return standIn;
        }
    }
    setInterval(clock, 1000);
    clock(); // Ejecutar inmediatamente
</script>

<?php
#Fin Header
?>
