<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>" <?php require_once __DIR__ . '/../theme_handler.php'; echo applyThemeToHTML(); ?>>
<?php
require_once __DIR__ . '/../lang/JsonLanguage.php';
$lang = JsonLanguage::autoDetect();
?>
<head>
	<?php $title= "projects"; ?>
    <?php require 'head.php'; ?>

    <title><?php $title ?></title>
    <?php require 'events/modals/newProject.php'; ?> 
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.standalone.min.css" rel="stylesheet" type="text/css" />
    
    <!-- Removido el include del modal newTask.php ya que usamos formulario inline -->
    
    <!-- CSS con parámetro de versión para forzar recarga -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    
    <style>
    /* Estilos globales para todos los modales */
    .modal {
        z-index: 9999 !important;
    }
    
    .modal-dialog {
        max-width: 600px;
        margin: 1.75rem auto;
    }
    
    .modal-content {
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }
    
    /* Asegurar que el modal esté por encima de todo */
    .modal-backdrop {
        z-index: 9998 !important;
    }
    
    /* Forzar que el modal se muestre fuera del contenedor */
    .modal {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 100% !important;
        overflow: auto !important;
        transition: none !important;
    }
    
    /* Asegurar que los modales no se corten */
    .modal-dialog-centered {
        display: flex !important;
        align-items: center !important;
        min-height: calc(100% - 3.5rem) !important;
    }
    
    /* Prevenir que los modales se cierren automáticamente */
    .modal {
        overflow: hidden !important;
        transition: none !important;
    }
    
    .modal-backdrop {
        pointer-events: none !important;
    }
    
    .modal-backdrop.show {
        pointer-events: auto !important;
    }
    
    /* Asegurar que los modales se mantengan abiertos */
    .modal.show {
        display: block !important;
        background-color: rgba(0, 0, 0, 0.5) !important;
        transition: none !important;
    }
    
    /* Prevenir conflictos con otros elementos */
    .modal-dialog {
        pointer-events: auto !important;
        z-index: 10000 !important;
        transition: none !important;
    }
    
    /* Optimizar transiciones para evitar parpadeo */
    .btn-transition {
        transition: transform 0.2s ease-out, box-shadow 0.2s ease-out !important;
    }
    
    .btn-transition:hover {
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2) !important;
    }
    
    /* Estilos para el formulario inline */
    #inline-task-form {
        border-radius: 8px;
        margin: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    #inline-task-form .form-control {
        border-radius: 6px;
        border: 1px solid #ced4da;
        transition: border-color 0.2s ease-out;
    }
    
    #inline-task-form .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
    }
    
    #inline-task-form .btn {
        border-radius: 6px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    #inline-task-form .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    /* Animación para el botón toggle */
    #toggle-task-form {
        transition: all 0.3s ease;
    }
    
    #toggle-task-form:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    /* Estilos para el panel deslizante */
    .slide-panel {
        position: fixed;
        top: 0;
        right: -400px;
        width: 400px;
        height: 100vh;
        background: white;
        box-shadow: -5px 0 15px rgba(0,0,0,0.3);
        z-index: 10001;
        transition: right 0.2s ease-out;
        overflow-y: auto;
    }
    
    .slide-panel.active {
        right: 0;
    }
    
    .slide-panel-header {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #dee2e6;
    }
    
    .slide-panel-header h4 {
        margin: 0;
        font-size: 1.2rem;
        font-weight: 600;
    }
    
    .close-panel {
        background: none;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 5px;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.2s ease-out;
    }
    
    .close-panel:hover {
        background-color: rgba(255,255,255,0.2);
    }
    
    .slide-panel-content {
        padding: 20px;
    }
    
    .slide-panel-content .form-group {
        margin-bottom: 20px;
    }
    
    .slide-panel-content .form-label {
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
        display: block;
    }
    
    .slide-panel-content .form-control {
        border-radius: 8px;
        border: 2px solid #e9ecef;
        padding: 12px;
        transition: border-color 0.2s ease-out;
    }
    
    .slide-panel-content .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
    }
    
    .status-options {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .status-options .form-check {
        padding: 10px;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        transition: background-color 0.2s ease-out, border-color 0.2s ease-out;
    }
    
    .status-options .form-check:hover {
        background-color: #f8f9fa;
        border-color: #007bff;
    }
    
    .form-actions {
        position: sticky;
        bottom: 0;
        background: white;
        padding: 20px 0;
        border-top: 1px solid #dee2e6;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }
    
    .form-actions .btn {
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 500;
        transition: transform 0.2s ease-out, box-shadow 0.2s ease-out;
    }
    
    .form-actions .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    /* Overlay para el panel */
    .panel-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 10000;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.2s ease-out, visibility 0.2s ease-out;
    }
    
    .panel-overlay.active {
        opacity: 1;
        visibility: visible;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .slide-panel {
            width: 100%;
            right: -100%;
        }
    }
    
    /* Prevenir scroll del body cuando el panel está abierto */
    body.panel-open {
        overflow: hidden;
    }
    

    </style>
    
    <!-- Scripts se cargan al final del documento -->
    
</head>

<body class="bg">
<?php require_once __DIR__ . '/partials/modern_navbar.php'; ?>

<div class="row d-flex m-0 p-0 mt-4">

    <!-- --------------------------------- SHOWING LIST OF PROJECTS --------------------------------- -->
    <div class="col-3 p-0 pl-3 pr-1">
        <div class="card-hover-shadow-2x mb-3 card text-dark">
            <div class="card-header-tab card-header d-flex flex-nowrap justify-content-between">
                <h4 class="card-header-title font-weight-normal"><i class="fa fa-suitcase mr-3"></i><?php echo $lang->get('projects.title', ['USER' => strtoupper($_SESSION['user'])]) ?></h4>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#project-modal">+</button>
            </div>
            <div class="scroll-area">
                <perfect-scrollbar class="ps-show-limits">
                    <div style="position: static;" class="ps ps--active-y">
                        <div class="ps-content">
                            <ul class=" list-group list-group-flush">
                                
                                <?php if (isset($projects)) {	
                                    $i = 1;
                                    foreach ($projects as $p): 
                                    ?>                         
                                    <li class="accordion list-group-item pe-auto" id="project-p-<?php echo $i; ?>">        
                                    <div class="todo-indicator" style="background-color:<?php echo $p['project_colour'];?>;">
                                        </div>
                                        <div class="widget-content p-0">
                                            <div class="widget-content-wrapper">                                            
                                                <form name="id_project_task" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="GET" role="form">
                                                    <input hidden name="idProject" value=<?php echo $p['id_project']; ?> >                                                    
                                                    <button class="btn" type="submit">
                                                        <div class="widget-content-left">
                                                            <div class="text-left widget-heading text-primary">
                                                                <?php echo $p['project_name'];?>                                                                
                                                            </div>                                                            
                                                            <div class="widget-subheading text-muted"><i>Start: <?php echo $p['start_date'];?></i></div>
                                                            <div class="widget-subheading text-muted"><i>End: <?php echo $p['end_date'];?></i></div>                                                       
                                                           
                                                        </div>
                                                    </button>
                                                </form>                                               
                                             
                                                <div class="widget-content-right ml-auto d-flex flex-nowrap"> 
                                                    <button type="button" class="border-0 btn-transition btn btn-outline-success" data-toggle="modal" data-target="#project-edit-<?php echo $i; ?>"> <i class="fas fa-pencil-alt"></i></button> 
                                                    <?php require 'events/modals/editProject.php'; ?>
                                                    <button type="button" class="border-0 btn-transition btn btn-outline-danger" data-toggle="modal" data-target="#project-delete-<?php echo $i; ?>"> <i class="fas fa-trash-alt"></i> </button> 
                                                    <?php require 'events/modals/delProject.php'; ?>
                                                </div>                                                
                                            </div>
                                            <?php if($p['project_description'] !== ''){ ?>
                                            <a class="d-flex justify-content-center nav-link text-primary p-0" data-toggle="collapse" data-target="#collapse-p-<?php echo $i; ?>" aria-expanded="true">
                                                <span class="accicon"><i class="fa fa-angle-down rotate-icon pl-2 pr-2"></i></span>
                                                <div  id="collapse-p-<?php echo $i; ?>" class="collapse" data-parent="#project-p-<?php echo $i; ?>">                                                    
                                                    <p class="font-small text-dark pt-1"><?php echo $p['project_description'];?></p> 
                                                </div>
                                            </a>
                                            <?php  }; ?>
                                        </div>                                    
                                    </li>

                                    
                                
                                <?php $i++;
                                endforeach; }  ?>

                            </ul>
                        </div>
                    </div>
                </perfect-scrollbar>
            </div>
        </div>
    </div>

    <!-- --------------------------------- SHOWING LIST OF TASKS --------------------------------- -->



    <div class="col-9 p-0 pr-3 pl-1">
        <div class="card-hover-shadow-2x mb-3 card text-dark">
            <div class="card-header-tab card-header d-flex justify-content-between">
                <h4 class="card-header-title font-weight-normal"><i class="fas fa-clipboard-list pr-3"></i><?php echo $lang->get('projects.task')?> <?php echo " : " . html_entity_decode(htmlspecialchars($nombre_proyecto_actual, ENT_QUOTES, 'UTF-8')); ?></h4>
                <?php if (isset($show_tasks)) { 
                    echo"<button type='button' class='btn btn-primary' id='open-task-panel'><i class='fas fa-plus mr-2'></i>".$lang->get('projects.new_task')."</button>";} ?>
            </div>
            <div class="scroll-area">
                <perfect-scrollbar class="ps-show-limits">
                    <div style="position: static;" class="ps ps--active-y">
                        <div class="ps-content">
                            <div class="row m-2 mt-4">
                                <div class="col-4">
                                    <div class="card-hover-shadow-2x mb-3 card text-dark">
                                        <div class="card-header-tab card-header">
                                            <h5 class="card-header-title font-weight-normal"><i class="fas fa-list mr-3"></i><?php echo $lang->get('projects.to_do')?></h5>                                            
                                        </div>
                                        <div class="scroll-area-sm">
                                            <perfect-scrollbar class="ps-show-limits">
                                                <div style="position: static;" class="ps ps--active-y">
                                                    <div class="ps-content">
                                                        <ul class=" list-group list-group-flush">                                                          

                                                        <?php if (isset($show_tasks)) {	
                                                            $i = 1;
                                                            foreach ($show_tasks as $s): 
                                                                if($s['task_status'] == '1'){
                                                        ?>  
                                                            <li class="accordion list-group-item pe-auto" id="task-todo-<?php echo $i; ?>">                                                                      
                                                                <div class="todo-indicator" style="background-color:<?php echo $s['task_colour'];?>;">
                                                                </div>
                                                                <div class="widget-content p-0">
                                                                    <div class="widget-content-wrapper">
                                                                        <a class="col-8 nav-link text-primary p-0" data-toggle="collapse" data-target="#collapse-todo-<?php echo $i; ?>" aria-expanded="true">
                                                                            <div class="widget-content-left p-2 pl-3">
                                                                                <div class="widget-heading d-flex">                                                                                    
                                                                                <?php echo $s['task_name'];?>                                                                                                                                                             
                                                                                    <span class="accicon"><i class="fa fa-angle-down rotate-icon pl-2"></i></span>
                                                                                </div>                                                                                  
                                                                                <div  id="collapse-todo-<?php echo $i; ?>" class="collapse" data-parent="#task-todo-<?php echo $i; ?>">  
                                                                                    <div class="widget-subheading text-muted"><i> <?php if( $s['deadline'] !== '1970-01-01'){
                                                                                                                                                echo "Deadline:";
                                                                                                                                                echo $s['deadline'];} ?></i></div>  
                                                                                    <p class="font-small text-dark pt-1"><?php echo $s['task_description'];?></p>                                                                                                                                                                                                                   
                                                                                </div>
                                                                            </div>
                                                                        </a>
                                                                        <div class="widget-content-right ml-auto"> 
                                                                            <button type="button" class="border-0 btn-transition btn btn-outline-success edit-task-btn" data-task-id="<?php echo $s['id_task']; ?>" data-task-name="<?php echo htmlspecialchars($s['task_name']); ?>" data-task-description="<?php echo htmlspecialchars($s['task_description']); ?>" data-task-colour="<?php echo $s['task_colour']; ?>" data-task-deadline="<?php echo $s['deadline']; ?>" data-task-status="<?php echo $s['task_status']; ?>"> <i class="fas fa-pencil-alt"></i></button> 
                                                                            <button type="button" class="border-0 btn-transition btn btn-outline-danger" data-toggle="modal" data-target="#task-delete-<?php echo $i; ?>"> <i class="fas fa-trash-alt"></i> </button> 
                                                                        <?php  require 'events/modals/delTask.php' ?>
                                                                        </div>
                                                                    </div>
                                                                </div> 
                                                                <div class="d-flex justify-content-center">                                                                    
                                                                    <button type="submit" class="border-0 btn-transition btn btn-outline-secondary" disabled> <i class="fa fa-arrow-left"></i></button>
                                                                    <form name="id_task_right-<?php echo $i; ?>" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" role="form">
                                                                        <input hidden name="id_task_right" value=<?php echo $s['id_task']; ?> >
                                                                        <input hidden name="task_status" value=<?php echo $s['task_status']; ?> >           
                                                                        <input hidden name="id_project_right" value=<?php echo $s['id_project']; ?> >                                          
                                                                        <button type="submit" class="border-0 btn-transition btn btn-outline-primary"> <i class="fa fa-arrow-right"></i></button>
                                                                    </form>     
                                                                </div>                                    
                                                            </li>

                                                            <?php $i++; }
                                                            endforeach; }  ?>                                                            
                                                        </ul>
                                                    </div>
                                                </div>
                                            </perfect-scrollbar>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="card-hover-shadow-2x mb-3 card text-dark">
                                        <div class="card-header-tab card-header d-flex justify-content-between">
                                            <h5 class="card-header-title font-weight-normal"><i class="fas fa-cogs mr-3"></i><?php echo $lang->get('projects.in_progress')?></h5>                                            
                                        </div>
                                        <div class="scroll-area-sm">
                                            <perfect-scrollbar class="ps-show-limits">
                                                <div style="position: static;" class="ps ps--active-y">
                                                    <div class="ps-content">
                                                        <ul class=" list-group list-group-flush">
                                                        
                                                        <?php if (isset($show_tasks)) {	
                                                            $i = 1000000;
                                                            foreach ($show_tasks as $s): 
                                                                if($s['task_status'] == '2'){
                                                        ?>  
                                                        
                                                        <li class="accordion list-group-item pe-auto" id="task-ip-<?php echo $i; ?>">        
                                                                <div class="todo-indicator" style="background-color:<?php echo $s['task_colour'];?>;">
                                                                </div>
                                                                <div class="widget-content p-0">
                                                                    <div class="widget-content-wrapper">
                                                                        <a class="col-8 nav-link text-primary p-0" data-toggle="collapse" data-target="#collapse-ip-<?php echo $i; ?>" aria-expanded="true">
                                                                            <div class="widget-content-left p-2 pl-3">
                                                                                <div class="widget-heading d-flex">                                                                                    
                                                                                <?php echo $s['task_name'];?>                                                                                                                                                             
                                                                                    <span class="accicon"><i class="fa fa-angle-down rotate-icon pl-2"></i></span>
                                                                                </div>                                                                                  
                                                                                <div  id="collapse-ip-<?php echo $i; ?>" class="collapse" data-parent="#task-ip-<?php echo $i; ?>">  
                                                                                    <div class="widget-subheading text-muted"><i><?php if( $s['deadline'] !== '1970-01-01'){
                                                                                                                                                echo "Deadline:";
                                                                                                                                                echo $s['deadline'];} ?></i></div>  
                                                                                    <p class="font-small text-dark pt-1"><?php echo $s['task_description'];?></p>                                                                                                                                                                                                                   
                                                                                </div>
                                                                            </div>
                                                                        </a>
                                                                        <div class="widget-content-right ml-auto"> 
                                                                            <button type="button" class="border-0 btn-transition btn btn-outline-success edit-task-btn" data-task-id="<?php echo $s['id_task']; ?>" data-task-name="<?php echo htmlspecialchars($s['task_name']); ?>" data-task-description="<?php echo htmlspecialchars($s['task_description']); ?>" data-task-colour="<?php echo $s['task_colour']; ?>" data-task-deadline="<?php echo $s['deadline']; ?>" data-task-status="<?php echo $s['task_status']; ?>"> <i class="fas fa-pencil-alt"></i></button> 
                                                                            <button type="button" class="border-0 btn-transition btn btn-outline-danger" data-toggle="modal" data-target="#task-delete-<?php echo $i; ?>"> <i class="fas fa-trash-alt"></i> </button> 
                                                                        <?php  require 'events/modals/delTask.php' ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="d-flex justify-content-center">   
                                                                    <form name="id_task_left-<?php echo $i; ?>" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" role="form">
                                                                        <input hidden name="id_task_left" value=<?php echo $s['id_task']; ?> >
                                                                        <input hidden name="task_status" value=<?php echo $s['task_status']; ?> >           
                                                                        <input hidden name="id_project_left" value=<?php echo $s['id_project']; ?> >                                          
                                                                        <button type="submit" class="border-0 btn-transition btn btn-outline-primary"> <i class="fa fa-arrow-left"></i></button>
                                                                    </form>
                                                                    <form name="id_task_right-<?php echo $i; ?>" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" role="form">
                                                                        <input hidden name="id_task_right" value=<?php echo $s['id_task']; ?> >
                                                                        <input hidden name="task_status" value=<?php echo $s['task_status']; ?> >           
                                                                        <input hidden name="id_project_right" value=<?php echo $s['id_project']; ?> >                                          
                                                                        <button type="submit" class="border-0 btn-transition btn btn-outline-primary"> <i class="fa fa-arrow-right"></i></button>
                                                                    </form>       
                                                                </div>                                     
                                                            </li>

                                                            <?php $i++; }
                                                            endforeach; }  ?>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </perfect-scrollbar>
                                        </div>
                                    </div>
                                </div> 
                                <div class="col-4">
                                    <div class="card-hover-shadow-2x mb-3 card text-dark">
                                        <div class="card-header-tab card-header d-flex justify-content-between">
                                            <h5 class="card-header-title font-weight-normal"><i class="fas fa-check mr-3"></i><?php echo $lang->get('projects.complete')?></h5>                                            
                                        </div>
                                        <div class="scroll-area-sm">
                                            <perfect-scrollbar class="ps-show-limits">
                                                <div style="position: static;" class="ps ps--active-y">
                                                    <div class="ps-content">
                                                        <ul class=" list-group list-group-flush"> 
                                                        <?php if (isset($show_tasks)) {	
                                                            $i = 2000000;
                                                            foreach ($show_tasks as $s): 
                                                                if($s['task_status'] == '3'){
                                                        ?>  
                                                        
                                                            <li class="accordion list-group-item pe-auto" id="task-c-<?php echo $i; ?>">        
                                                                <div class="todo-indicator" style="background-color:<?php echo $s['task_colour'];?>;">
                                                                </div>
                                                                <div class="widget-content p-0">
                                                                    <div class="widget-content-wrapper">
                                                                        <a class="col-8 nav-link text-primary p-0" data-toggle="collapse" data-target="#collapse-c-<?php echo $i; ?>" aria-expanded="true">
                                                                            <div class="widget-content-left p-2 pl-3">
                                                                                <div class="widget-heading d-flex">                                                                                    
                                                                                <?php echo $s['task_name'];?>                                                                                                                                                             
                                                                                    <span class="accicon"><i class="fa fa-angle-down rotate-icon pl-2"></i></span>
                                                                                </div>                                                                                  
                                                                                <div  id="collapse-c-<?php echo $i; ?>" class="collapse" data-parent="#task-c-<?php echo $i; ?>">  
                                                                                    <div class="widget-subheading text-muted"><i><?php if( $s['deadline'] !== '1970-01-01'){
                                                                                                                                                echo "Deadline:";
                                                                                                                                                echo $s['deadline'];} ?></i></div>  
                                                                                    <p class="font-small text-dark pt-1"><?php echo $s['task_description'];?></p>                                                                                                                                                                                                                   
                                                                                </div>
                                                                            </div>
                                                                        </a>
                                                                        <div class="widget-content-right ml-auto"> 
                                                                            <button type="button" class="border-0 btn-transition btn btn-outline-success edit-task-btn" data-task-id="<?php echo $s['id_task']; ?>" data-task-name="<?php echo htmlspecialchars($s['task_name']); ?>" data-task-description="<?php echo htmlspecialchars($s['task_description']); ?>" data-task-colour="<?php echo $s['task_colour']; ?>" data-task-deadline="<?php echo $s['deadline']; ?>" data-task-status="<?php echo $s['task_status']; ?>"> <i class="fas fa-pencil-alt"></i></button> 
                                                                            <button type="button" class="border-0 btn-transition btn btn-outline-danger" data-toggle="modal" data-target="#task-delete-<?php echo $i; ?>"> <i class="fas fa-trash-alt"></i> </button> 
                                                                        <?php  require 'events/modals/delTask.php' ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="d-flex justify-content-center">   
                                                                    <form name="id_task_left-<?php echo $i; ?>" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" role="form">
                                                                        <input hidden name="id_task_left" value=<?php echo $s['id_task']; ?> >
                                                                        <input hidden name="task_status" value=<?php echo $s['task_status']; ?> >           
                                                                        <input hidden name="id_project_left" value=<?php echo $s['id_project']; ?> >                                          
                                                                        <button type="submit" class="border-0 btn-transition btn btn-outline-primary"> <i class="fa fa-arrow-left"></i></button>
                                                                    </form>
                                                                    <button type="submit" class="border-0 btn-transition btn btn-outline-secondary" disabled> <i class="fa fa-arrow-right"></i></button>       
                                                                </div>                                                                                                    
                                                            </li>

                                                            <?php $i++; }
                                                            endforeach; }  ?>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </perfect-scrollbar>
                                        </div>
                                    </div>
                                </div>                                 
                            </div>                            
                        </div>
                    </div>
                </perfect-scrollbar>
            </div>
        </div>
    </div>
</div>

<!-- Panel deslizante para nueva tarea -->
<div id="task-slide-panel" class="slide-panel">
    <div class="slide-panel-header">
        <h4><i class="fas fa-plus mr-2"></i>Nueva Tarea</h4>
        <button type="button" class="close-panel" id="close-task-panel">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="slide-panel-content">
        <form name="task_todo" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" role="form">
            <div class="form-group">
                <label class="form-label"><strong>Nombre de la Tarea <span class="text-danger">*</span></strong></label>
                <input class="form-control" type="text" name="task_name" required placeholder="Ingresa el nombre de la tarea">
            </div>
            
            <div class="form-group">
                <label class="form-label"><strong>Descripción</strong></label>
                <textarea class="form-control" name="task_description" rows="4" placeholder="Describe la tarea..."></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label"><strong>Prioridad</strong></label>
                <select name="task_colour" class="form-control">
                    <option value="">Seleccionar prioridad</option>
                    <option style="color:#5cb85c" value="#5cb85c">🟢 Baja</option>					  
                    <option style="color:#f0ad4e" value="#f0ad4e">🟡 Media</option>
                    <option style="color:#d9534f" value="#d9534f">🔴 Alta</option>						  
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label"><strong>Fecha Límite</strong></label>
                <input type="date" class="form-control" name="deadline" min="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label"><strong>Estado</strong></label>
                <div class="status-options">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="task_status" id="task_status_1" value="1" checked>
                        <label class="form-check-label" for="task_status_1">📋 Por Hacer</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="task_status" id="task_status_2" value="2">
                        <label class="form-check-label" for="task_status_2">⚙️ En Progreso</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="task_status" id="task_status_3" value="3">
                        <label class="form-check-label" for="task_status_3">✅ Completada</label>
                    </div>
                </div>
            </div>
            
            <input hidden id="id_task_project" name="id_task_project" value="<?php echo $id_project_for_task; ?>">
            <input hidden id="id_user" name="id_user" value="<?php echo $_SESSION['id_user']; ?>">
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" id="cancel-task-panel">
                    <i class="fas fa-times mr-1"></i>Cancelar
                </button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save mr-1"></i>Crear Tarea
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Overlay para el panel -->
<div id="panel-overlay" class="panel-overlay"></div>

<!-- Panel deslizante para editar tarea -->
<div id="edit-task-slide-panel" class="slide-panel">
    <div class="slide-panel-header">
        <h4><i class="fas fa-edit mr-2"></i>Editar Tarea</h4>
        <button type="button" class="close-panel" id="close-edit-task-panel">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="slide-panel-content">
        <form name="edit_task_form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" role="form">
            <div class="form-group">
                <label class="form-label"><strong>Nombre de la Tarea</strong></label>
                <input type="text" class="form-control" name="edit_task_name" id="edit_task_name" required>
            </div>
            
            <div class="form-group">
                <label class="form-label"><strong>Descripción</strong></label>
                <textarea class="form-control" name="edit_task_description" id="edit_task_description" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label"><strong>Color</strong></label>
                <select class="form-control" name="edit_task_colour" id="edit_task_colour">
                    <option value="#5cb85c">🟢 Verde</option>
                    <option value="#f0ad4e">🟡 Naranja</option>
                    <option value="#d9534f">🔴 Rojo</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label"><strong>Fecha Límite</strong></label>
                <input type="date" class="form-control" name="edit_deadline" id="edit_deadline" min="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label"><strong>Estado</strong></label>
                <div class="status-options">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="edit_task_status" id="edit_task_status_1" value="1">
                        <label class="form-check-label" for="edit_task_status_1">📋 Por Hacer</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="edit_task_status" id="edit_task_status_2" value="2">
                        <label class="form-check-label" for="edit_task_status_2">⚙️ En Progreso</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="edit_task_status" id="edit_task_status_3" value="3">
                        <label class="form-check-label" for="edit_task_status_3">✅ Completada</label>
                    </div>
                </div>
            </div>
            
            <input hidden id="edit_id_task" name="edit_id_task" value="">
            <input hidden id="edit_id_task_project" name="edit_id_task_project" value="<?php echo $id_project_for_task; ?>">
            <input hidden id="edit_id_user" name="edit_id_user" value="<?php echo $_SESSION['id_user']; ?>">
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" id="cancel-edit-task-panel">
                    <i class="fas fa-times mr-1"></i>Cancelar
                </button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save mr-1"></i>Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<!-- -------------------------- FOOTER --------------------------- -->
<?php require 'footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.1.10/dist/sweetalert2.all.min.js"></script>

    
<script>
    $(document).ready(function () {
        $("#startAdd").datepicker({
            todayBtn: 1,
            autoclose: true,
        }).on('changeDate', function (selected) {
            var minDate = new Date(selected.date.valueOf());
            $('#endAdd').datepicker('setStartDate', minDate);
        });
        $("#endAdd").datepicker()
            .on('changeDate', function (selected) {
                var minDate = new Date(selected.date.valueOf());
                $('#startAdd').datepicker('setEndDate', minDate);
            });

        $("#startAdd1").datepicker({
            todayBtn: 1,
            autoclose: true,
        }).on('changeDate', function (selected) {
            var minDate = new Date(selected.date.valueOf());
            $('#endAdd1').datepicker('setStartDate', minDate);
        });
        $("#endAdd1").datepicker()
            .on('changeDate', function (selected) {
                var minDate = new Date(selected.date.valueOf());
                $('#startAdd1').datepicker('setEndDate', minDate);
            });

        $("#startAdd2").datepicker({
            todayBtn: 1,
            autoclose: true,
            minDate: 0,
        }).on('changeDate', function (selected) {
            var minDate = new Date(selected.date.valueOf());
            $('#endAdd2').datepicker('setStartDate', minDate);
        });
        $("#endAdd2").datepicker()
            .on('changeDate', function (selected) {
                var minDate = new Date(selected.date.valueOf());
                $('#startAdd2').datepicker('setEndDate', minDate);
            });       
    });    
    
    // Script unificado para todos los botones
    $(document).ready(function() {
        // Verificar que jQuery esté disponible
        if (typeof $ === 'undefined') {
            console.error('jQuery no está disponible');
            return;
        }
        
        // 1. Botón para abrir el panel de nueva tarea
        $('#open-task-panel').on('click', function() {
            $('#task-slide-panel').addClass('active');
            $('#panel-overlay').addClass('active');
            $('body').addClass('panel-open');
            setTimeout(function() {
                $('#task-slide-panel input[name="task_name"]').focus();
            }, 350);
        });
        
        // 2. Botones para cerrar el panel
        $('#close-task-panel, #cancel-task-panel, #panel-overlay').on('click', function() {
            closeTaskPanel();
        });
        
        // 3. Función para cerrar el panel
        function closeTaskPanel() {
            $('#task-slide-panel').removeClass('active');
            $('#panel-overlay').removeClass('active');
            $('body').removeClass('panel-open');
            $('form[name="task_todo"]')[0].reset();
        }
        
        // 4. ESC key para cerrar panel
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#task-slide-panel').hasClass('active')) {
                closeTaskPanel();
            }
        });
        
        // 5. Manejar envío del formulario de nueva tarea
        $('form[name="task_todo"]').on('submit', function() {
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.html();
            submitBtn.html('<i class="fas fa-spinner fa-spin mr-1"></i>Creando...');
            submitBtn.prop('disabled', true);
            setTimeout(function() {
                closeTaskPanel();
                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
            }, 1000);
        });
        
        // 6. Botones de modales de editar y eliminar
        $('[data-toggle="modal"][data-target^="#task-edit-"], [data-toggle="modal"][data-target^="#task-delete-"]').on('click', function(e) {
            e.preventDefault();
            const target = $(this).data('target');
            $(target).modal('show');
        });
        
        // 7. Configurar modales para que no se cierren automáticamente
        $('[id^="task-edit-"], [id^="task-delete-"]').on('show.bs.modal', function() {
            $(this).data('bs.modal').options.backdrop = 'static';
            $(this).data('bs.modal').options.keyboard = false;
        });
        
        
        
        // Panel de editar tarea
        $('.edit-task-btn').on('click', function() {
            // Obtener datos de la tarea
            const taskId = $(this).data('task-id');
            const taskName = $(this).data('task-name');
            const taskDescription = $(this).data('task-description');
            const taskColour = $(this).data('task-colour');
            const taskDeadline = $(this).data('task-deadline');
            const taskStatus = $(this).data('task-status');
            
            // Llenar el formulario
            $('#edit_id_task').val(taskId);
            $('#edit_task_name').val(taskName);
            $('#edit_task_description').val(taskDescription);
            $('#edit_task_colour').val(taskColour);
            
            // Formatear fecha para input HTML5 date
            let formattedDate = taskDeadline;
            if (taskDeadline && taskDeadline !== '1970-01-01') {
                // Si la fecha es válida, usarla tal como está
                formattedDate = taskDeadline;
            } else {
                // Si no hay fecha o es 1970-01-01, dejar vacío
                formattedDate = '';
            }
            $('#edit_deadline').val(formattedDate);
            $(`#edit_task_status_${taskStatus}`).prop('checked', true);
            
            // Abrir el panel
            $('#edit-task-slide-panel').addClass('active');
            $('#panel-overlay').addClass('active');
            $('body').addClass('panel-open');
            
            setTimeout(function() {
                $('#edit_task_name').focus();
            }, 350);
        });
        
        // Cerrar panel de editar tarea
        $('#close-edit-task-panel, #cancel-edit-task-panel, #panel-overlay').on('click', function() {
            closeEditTaskPanel();
        });
        
        function closeEditTaskPanel() {
            $('#edit-task-slide-panel').removeClass('active');
            $('#panel-overlay').removeClass('active');
            $('body').removeClass('panel-open');
            $('form[name="edit_task_form"]')[0].reset();
        }
        
        // ESC key para cerrar panel de editar
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#edit-task-slide-panel').hasClass('active')) {
                closeEditTaskPanel();
            }
        });
        
        // Manejar envío del formulario de editar tarea
        $('form[name="edit_task_form"]').on('submit', function() {
            
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.html();
            submitBtn.html('<i class="fas fa-spinner fa-spin mr-1"></i>Guardando...');
            submitBtn.prop('disabled', true);
            setTimeout(function() {
                closeEditTaskPanel();
                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
            }, 1000);
        });
    });
    

</script>

</body>
</html>
