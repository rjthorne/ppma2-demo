<?php

// define("DB", "ppma2");
// define("USER", "root");
// define("PASS", "bastardman1");
define("DB", "ppma2");
define("USER", "ppmasystem");
define("PASS", "WTeLVEZV4mfqz8Kb");



// include('ppwc/class.db.php');
// $db = new db("mysql:host=localhost;dbname=".DB, USER, PASS, array(\PDO::MYSQL_ATTR_INIT_COMMAND =>  'SET NAMES utf8_spanish_ci'));
// $db = new db("mysql:host=localhost;dbname=".DB.";charset=utf8_spanish_ci", USER, PASS);

$db = new PDO('mysql:host=localhost;dbname='.DB.';charset=utf8mb4', USER, PASS);


if (!is_object($db)) 
	die("<p>There was a problem connecting to the database. This may only be temporary. If this is your first time seeing this message, try visiting the site again one hour from now. If the problem persists, please contact <a href=\"mailto:accounts@ppn-uk.co.uk\">accounts@ppn-uk.co.uk</a>.</p><p>We apologise for any inconvenience caused.</p>");
?>