<?php 
if (isset($_SESSION['user'])) {
} else {
    header('Location: main.php');
    die();
}?>

<!-- SweetAlert2 CSS y JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<script>
    function modalShow() {
        $('#modalShow').modal('show');
    }
    
    // Función para mostrar notificaciones en la esquina superior derecha
    function showNotification(message, type = 'success') {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });
        
        Toast.fire({
            icon: type,
            title: message
        });
    }

    $(document).ready(function() {
        // MULTILANG FULLCALENDAR DEBUG: Mostrar valor de calendarLang
        console.log('calendarLang:', typeof calendarLang !== 'undefined' ? calendarLang : 'undefined');
        console.log('FullCalendar object:', typeof FullCalendar !== 'undefined' ? 'loaded' : 'not loaded');
        console.log('FullCalendar locales disponibles:', typeof FullCalendar !== 'undefined' && FullCalendar.globalLocales ? Object.keys(FullCalendar.globalLocales) : 'no locales loaded');
        
        // DEBUG: Mostrar eventos cargados desde PHP
        var eventosDebug = <?php echo json_encode($events); ?>;
        
        // FULLCALENDAR v6.x: Nueva API de inicialización
        var calendarEl = document.getElementById('calendar');
        window.id_calendar_active = window.id_calendar_active || ($('.calendar-box.selected').data('id') || null);
        window.calendar = new FullCalendar.Calendar(calendarEl, {
            timeZone: 'local',
            locale: typeof calendarLang !== 'undefined' ? calendarLang : 'es',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listYear'
            },
            initialDate:'<?php echo date('Y-m-d'); ?>',
            editable: true,
            navLinks: true,
            // dayMaxEvents: true, // Eliminado para mostrar todos los eventos
            selectable: true,
            selectMirror: true,
            select: function(arg) {
                // DEBUG: Ver la hora local
                console.log('arg.start:', arg.start, 'Hora local:', arg.start.getHours());
                // Obtener la fecha seleccionada
                var start = arg.start;
                var year = start.getFullYear();
                var month = (start.getMonth() + 1).toString().padStart(2, '0');
                var day = start.getDate().toString().padStart(2, '0');
                var hour = start.getHours();
                var minute = start.getMinutes();
                // Si la hora es 0 (vista mensual), usar la hora local actual redondeada
                if (hour === 0 && minute === 0) {
                    var now = new Date();
                    hour = now.getHours();
                    hour = Math.floor(hour);
                }
                var startTimeStr = hour.toString().padStart(2, '0') + ':00';
                var endHour = (hour + 1) % 24;
                var endTimeStr = endHour.toString().padStart(2, '0') + ':00';
                var dateStr = year + '-' + month + '-' + day;
                // Setear los campos en el modal
                $('#ModalAdd #start_date').val(dateStr);
                $('#ModalAdd #start_time').val(startTimeStr);
                $('#ModalAdd #end_date').val(dateStr);
                $('#ModalAdd #end_time').val(endTimeStr);
                $('#ModalAdd').modal('show');
            },
            eventDidMount: function(arg) {
                // FULLCALENDAR v6.x: eventDidMount en lugar de eventRender
                arg.el.addEventListener('click', function() {
                    $('#ModalEdit #id_event').val(arg.event.id);
                    $('#ModalEdit #title').val(arg.event.title);
                    // Usar Summernote para setear la descripción
                    $('#ModalEdit #description').summernote('code', arg.event.extendedProps.description || '');
                    $('#ModalEdit #colour').val(arg.event.backgroundColor);
                    // Fecha y hora de inicio
                    if (arg.event.start) {
                        var start = arg.event.start;
                        var startDateStr = start.getFullYear() + '-' +
                            (start.getMonth() + 1).toString().padStart(2, '0') + '-' +
                            start.getDate().toString().padStart(2, '0');
                        var startTimeStr = start.getHours().toString().padStart(2, '0') + ':' +
                            start.getMinutes().toString().padStart(2, '0');
                        $('#ModalEdit #start_date').val(startDateStr);
                        $('#ModalEdit #start_time').val(startTimeStr);
                    }
                    // Fecha y hora de fin
                    if (arg.event.end) {
                        var end = arg.event.end;
                        var endDateStr = end.getFullYear() + '-' +
                            (end.getMonth() + 1).toString().padStart(2, '0') + '-' +
                            end.getDate().toString().padStart(2, '0');
                        var endTimeStr = end.getHours().toString().padStart(2, '0') + ':' +
                            end.getMinutes().toString().padStart(2, '0');
                        $('#ModalEdit #end_date').val(endDateStr);
                        $('#ModalEdit #end_time').val(endTimeStr);
                    } else {
                        // Si no hay hora de fin, iguala a la de inicio
                        $('#ModalEdit #end_date').val($('#ModalEdit #start_date').val());
                        $('#ModalEdit #end_time').val($('#ModalEdit #start_time').val());
                    }
                    $('#ModalEdit').modal('show');
                });
                // Tooltip avanzado con Tippy.js
                var start = arg.event.start;
                var end = arg.event.end;
                var dateStr = start ? start.toLocaleDateString() : '';
                var startTime = start ? start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '';
                var endTime = end ? end.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '';
                var emoji = '🗓️';
                var emojiClock = '⏰';
                var tooltip = `<div>
                    <div>${emoji} <b>${dateStr}</b></div>
                    <div>${emojiClock} <b>${startTime} - ${endTime}</b></div>
                    <div>📝 ${arg.event.extendedProps.description || ''}</div>
                </div>`;
                if (typeof tippy !== 'undefined') {
                    tippy(arg.el, {
                        content: tooltip,
                        allowHTML: true,
                        theme: 'light-border',
                        placement: 'top',
                    });
                }
            },
            eventDrop: function(arg) { 
                edit(arg.event);
            },
                    
            eventResize: function(arg) { 
                edit(arg.event);
            },

            eventContent: function(arg) {
                // Formatea solo la hora de inicio en formato 24 horas
                var start = arg.event.start;
                var startTime = start ? start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', hour12: false}) : '';

                // Crea el nodo combinado y asigna el color de fondo
                var lineEl = document.createElement('div');
                lineEl.textContent = startTime + (startTime ? ' ' : '') + arg.event.title + '';
                lineEl.style.width = '100%';
                if (arg.event.backgroundColor) {
                    lineEl.style.backgroundColor = arg.event.backgroundColor;
                    lineEl.style.color = '#fff'; // Contraste para texto
                    lineEl.style.borderRadius = '4px';
                    lineEl.style.padding = '2px 4px';
                }
                return { domNodes: [lineEl] };
            },

            events: function(fetchInfo, successCallback, failureCallback) {
                var id_calendar = window.id_calendar_active || ($('.calendar-box.selected').data('id') || null);
                if (!id_calendar) {
                    successCallback([]);
                    return;
                }
                
                $.ajax({
                    url: 'events/get_events.php',
                    data: { id_calendar: id_calendar },
                    dataType: 'json',
                    success: function(response) {
                                            if (Array.isArray(response)) {
                        successCallback(response);
                    } else {
                        showNotification('Error: ' + (response.error || 'No se pudieron cargar los eventos.'), 'error');
                        successCallback([]);
                    }
                },
                error: function() {
                    showNotification('Error al cargar los eventos.', 'error');
                    successCallback([]);
                }
                });
            }
        });
        
        // FULLCALENDAR v6.x: Renderizar el calendario
        window.calendar.render();
        
        // DEBUG: Verificar que el locale se aplicó correctamente
        console.log('Calendario renderizado con locale:', window.calendar.getOption('locale'));
        console.log('Botones de la toolbar:', window.calendar.getOption('headerToolbar'));
            
        function edit(event){
            // FULLCALENDAR v6.x: Usar fechas nativas en lugar de moment
            var start = event.start.toLocaleDateString('es-ES') + ' ' + event.start.toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'});
            var end = event.end ? event.end.toLocaleDateString('es-ES') + ' ' + event.end.toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'}) : start;
            
            id_event =  event.id;
            
            Event = [];
            Event[0] = id_event;
            Event[1] = start;
            Event[2] = end;
            
            $.ajax({
                url: 'events/actions/eventEditData.php',
                type: "POST",
                data: {Event:Event},
                success: function(rep) {
                    if(rep == 'OK'){
                        showNotification('<?php echo $lang->get('DATA_UPDATE'); ?>', 'success');
                    }else{
                        showNotification('<?php echo $lang->get('SAVE_ERROR'); ?>', 'error'); 
                    }
                }
            });
        }
    });

</script>