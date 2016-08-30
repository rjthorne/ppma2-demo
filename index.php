<?php 


include_once 'defs/db_connect.php';
include_once 'defs/functions.php';

sec_session_start();

if (login_check($db) == true) { 
	header('Location: bank.php');
} else {


?>
<!DOCTYPE html>
<html>
	<head>
		<title>PPMA: Log In</title>
		<script type="text/JavaScript" src="js/_sha512.js"></script> 
		<script type="text/JavaScript" src="js/_formhash.js"></script> 
		<meta charset="UTF-8">
		<!-- jQuery -->
		<link rel="stylesheet" type="text/css" href="css/jmetro/_jui.css" />
		<script type="text/javascript" src="js/_jquery.js"></script>
		<script type="text/javascript" src="js/_jui.js"></script>
		<!-- jQuery upload -->
		<link rel="stylesheet" type="text/css" href="css/_jupload.css" />
		<script type="text/javascript" src="js/_jupload.js"></script>
		<!-- Colorbox -->
		<link rel="stylesheet" type="text/css" href="css/colorbox/colorbox.css" />
		<script type="text/javascript" src="js/_colorbox.js"></script>			
		<!-- General -->
		<link href='http://fonts.googleapis.com/css?family=Play:400,700' rel='stylesheet' type='text/css'>
		<link rel="stylesheet" type="text/css" href="css/css.css" />
		<link rel="stylesheet" type="text/css" href="css/_dropdown.css" />
		<script type="text/javascript" src="js/_mousetrap.js"></script>
		<script type="text/javascript" src="js/shared.js"></script>	
	</head>
	<body>
		<div id="header">
			<div id="innerheader_login">
				<img src="img/loading2.gif" style="display:none" />
				<img src="img/ppma24.png" id="ppma_login" />
				<div id="topbar">

				</div>
			</div>
		</div>
		<div id="main">
		<?php
			if (isset($_GET['error'])) {
				echo '<p>There was an error processing your login details. Please re-enter them below:</p>';
			}
			?> 
			
			<div id="templogin">
				<div class="bankaccount">
					<h3>Login</h3>
					<br>			
					<form action="defs/process_login.php" method="post" name="login_form">				  
						Email: <input type="text" name="email" id="focusonme" />
						Password: <input type="password" name="password" id="password" />
						<input type="button" value="Login" onclick="formhash(this.form, this.form.password);" /> 
					</form>
					<p>If you don't have a login, please <a href="register.php">register</a></p>
				</div>
			</div>
		</div>
		<script type="text/javascript">
			$('#focusonme').focus();
		
		</script>
	</body>
</html>
<?php }
// print_r($db);

 ?>