<?php 
if (isset($_SESSION['user'])) {
} else {
	header('Location: main.php');
	die();
}?>

<script>
	function modalShow() {
		$('#modalShow').modal('show');
	}

	$(document).ready(function() {
		// MULTILANG FULLCALENDAR DEBUG: Mostrar valor de calendarLang
		console.log('FullCalendar locale:', calendarLang);
		
		// FULLCALENDAR v6.x: Nueva API de inicialización
		var calendarEl = document.getElementById('calendar');
		var calendar = new FullCalendar.Calendar(calendarEl, {
        // MULTILANG FULLCALENDAR: Usar idioma dinámico
        locale: typeof calendarLang !== 'undefined' ? calendarLang : 'es',

		headerToolbar: {
			left: 'prev,next today',
			center: 'title',
			right: 'dayGridMonth,timeGridWeek,timeGridDay,listYear'
		},

		initialDate:'<?php echo date('Y-m-d'); ?>',
		editable: true,
		navLinks: true,
		dayMaxEvents: true,
		selectable: true,
		selectMirror: true,
		select: function(arg) {
			// FULLCALENDAR v6.x: Usar fechas nativas en lugar de moment
			var startDate = arg.start.toLocaleDateString('es-ES') + ' ' + arg.start.toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'});
			var endDate = arg.end.toLocaleDateString('es-ES') + ' ' + arg.end.toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'});
			$('#ModalAdd #start_date').val(startDate);
			$('#ModalAdd #end_date').val(endDate);
			$('#ModalAdd').modal('show');
		},
		eventDidMount: function(arg) {
			// FULLCALENDAR v6.x: eventDidMount en lugar de eventRender
			arg.el.addEventListener('click', function() {
				$('#ModalEdit #id_event').val(arg.event.id);
				$('#ModalEdit #title').val(arg.event.title);
				$('#ModalEdit #description').val(arg.event.extendedProps.description);
				$('#ModalEdit #colour').val(arg.event.backgroundColor);
				var startDate = arg.event.start.toLocaleDateString('es-ES') + ' ' + arg.event.start.toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'});
				var endDate = arg.event.end ? arg.event.end.toLocaleDateString('es-ES') + ' ' + arg.event.end.toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'}) : startDate;
				$('#ModalEdit #start_date').val(startDate);
				$('#ModalEdit #end_date').val(endDate);
				$('#ModalEdit').modal('show');
			});
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

		events: [
					<?php foreach($events as $event): 
						$start = explode(" ", $event['start_date']);
						$end = explode(" ", $event['end_date']);
						if($start[1] == '00:00:00'){
							$start = $start[0];
						}else{
							$start = $event['start_date'];
						}
						if($end[1] == '00:00:00'){
							$end = $end[0];
						}else{
							$end = $event['end_date'];
						}
					?>
					{
						id: '<?php echo $event['id_event']; ?>',
						title: '<?php echo $event['title']; ?>',
						description: '<?php echo $event['description']; ?>',
						start: '<?php echo $start; ?>',
						end: '<?php echo $end; ?>',
						backgroundColor: '<?php echo $event['colour']; ?>',
					},
					<?php endforeach; ?>
				]
			});
			
		// FULLCALENDAR v6.x: Renderizar el calendario
		calendar.render();
				
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
						alert('Data successfully updated');
					}else{
						alert('There was a problem while saving, please try again!'); 
					}
				}
		});
		}
		});

</script>