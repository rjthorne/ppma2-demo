<?php
include_once 'defs/register-inc.php';
include_once 'defs/functions.php';
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<title>PPMA: New user register</title>
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
		<!-- Registration form to be output if the POST variables are not
		set or if the registration script caused an error. -->
		<div id="main">
			<div id="templogin">
				<div class="bankaccount">
					<h3>Register new user</h3>
					<?php
					if (!empty($error_msg)) {
						echo $error_msg;
					}
					?>
					<ul>
						<li>Usernames may contain only digits, upper and lower case letters and underscores</li>
						<li>Emails must have a valid email format</li>
						<li>Passwords must be at least 6 characters long</li>
						<li>Passwords must contain
							<ul>
								<li>At least one upper case letter (A..Z)</li>
								<li>At least one lower case letter (a..z)</li>
								<li>At least one number (0..9)</li>
							</ul>
						</li>
						<li>Your password and confirmation must match exactly</li>
					</ul>
					<form action="<?php echo esc_url($_SERVER['PHP_SELF']); ?>" method="post" name="registration_form">
						<label class="zoomlabel" for="reg_username"> Username:</label> <input id="reg_username" type='text' name='username' id='username' /><br>
						<label class="zoomlabel" for="reg_email">Email:</label> <input id="reg_email"type="text" name="email" id="email" /><br>
						<label class="zoomlabel" for="reg_pass">Password:</label> <input id="reg_pass" type="password" name="password" id="password"/><br>
						<label class="zoomlabel" for="reg_cpass">Confirm password:</label> <input id="reg_cpass" type="password" name="confirmpwd" id="confirmpwd" /><br><br>
						<input type="button" class="button" value="Register" onclick="return regformhash(this.form, this.form.username, this.form.email, this.form.password, this.form.confirmpwd);" /> 
					</form>
					<p>Once registered, an administrator will need to approve your account before you can log in.</p>
					<p>Return to the <a href="index.php">login page</a>.</p>
				</div>
			</div>
		</div>
	</body>
</html>