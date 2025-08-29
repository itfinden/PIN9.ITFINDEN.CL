<!DOCTYPE html>
<html lang="en">
<?php
require_once __DIR__ . '/../lang/Languaje.php';
$lang = Language::autoDetect();
?>
<head>
	<?php $title= "Calendar"; ?>
	<?php require 'head.php'; ?>
	<?php require_once __DIR__ . '/partials/modern_navbar.php'; ?>

	<!-- Trix  -->
 
	<!-- FullCalendar v6.x -->
	<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet' />
    <!-- MULTILANG FULLCALENDAR: Pasar idioma PHP a JS -->
    <script>
      // MULTILANG FULLCALENDAR FIX 2: Obtener idioma directamente de sesión
      var calendarLang = '<?php echo isset($_SESSION['lang']) ? strtolower($_SESSION['lang']) : 'es'; ?>';
    </script>
</head>

<body  class="bg">
<?php require 'header.php'; ?>

<!-- Page Content -->
<div class="container bg-light text-dark rounded mt-4">
	<div class="row m-0 p-0">
		<div class="col-lg-12 text-center">
			<p class="lead"></p>
			<div id="calendar" class="col-centered mb-4">
			</div>
		</div>
	</div>


<!-- MODALS -->
<script type="text/javascript" class="d-print-none">
	function validaForm(erro) {
		console.log("validaForm ejecutándose");
		console.log("start_date value:", erro.start_date.value);
		console.log("end_date value:", erro.end_date.value);
		
		if(erro.start_date.value>erro.end_date.value){
			alert('The start date has to be before the end date.');
			return false;
		}else if(erro.start_date.value==erro.end_date.value){
			alert('Start time and end time has to be defined');
			return false;
		}
		console.log("validaForm pasó la validación");
		return true;
	}
</script>

<?php include ('events/modals/modalAdd.php'); ?>
<?php include ('events/modals/modalEdit.php'); ?>
</div>
<div class="row m-0 p-0">
	<div class="col sm-3 d-flex justify-content-center d-print-none">
		<button onclick="javascript:window.print()" class="btn btn-primary m-4 hiddenprint">Print</button>   
	</div>
</div>
<!-- -------------------------- FOOTER --------------------------- -->
<?php require 'footer.php'; ?>


	<!-- jQuery  -->
	<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

	<!-- Bootstrap Core JavaScript -->
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>

	<!-- FullCalendar v6.x -->
	<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>

	<?php include ('calendar2.php'); ?>



<!-- -------------------------- TRIX --------------------------- -->



</body>
</html>