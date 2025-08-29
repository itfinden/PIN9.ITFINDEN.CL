<?php
#header
?>

<header class="m-0 p-0">
	<nav class="navbar navbar-expand-lg pt-3 text-dark">
		<div class="menu container">
			<a href="index.php" class="navbar-brand">
			<!-- Logo Image -->
			<img src="img/logo.png" width="45" alt="Kalendar" class="d-inline-block align-middle mr-2">
			<!-- Logo Text -->
			<span class="logo_text align-middle">Pin 9x</span>
			</a>
            <ul class="navbar-nav ml-auto">
                <li><span class="btn text-primary mx-2"><i class="far fa-user pr-2"></i><?php echo $lang->get('GREETING', ['name' => ($_SESSION['user'])]) ?></li>
                <li><span class="btn text-primary mx-2"><i class="far fa-calendar-alt pr-2"></i><?php echo $lang->get('DATE');?>:<span class="pl-2 date"></span></li>	
                <li><span class="btn text-primary mr-2"><i class="far fa-clock pr-2"></i><?php echo $lang->get('TIME');?>:<span class="pl-2 clock"></span></li>				
            </ul>	

			<button type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation" class="navbar-toggler"><span class="navbar-toggler-icon"></span></button>
			<div id="navbarSupportedContent" class="collapse navbar-collapse">
				<ul class="navbar-nav ml-auto">
					<li><a href="logout.php" class="btn text-primary mr-2"><?php echo $lang->get('LOGOUT');?></a></li>	
                    <li><a href="login.php" class="btn text-primary mr-2"></i><?php echo $lang->get('LOGIN');?></a></li>   			
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
    var date =time.getFullYear()+'-'+(time.getMonth()+1)+'-'+time.getDate();
        

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
</script>

<?php
#Fin Header
?>