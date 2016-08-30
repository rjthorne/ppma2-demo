<?php

include_once 'defs/db_connect.php';
include_once 'defs/functions.php';
require 'defs/inc_accountcheck.php';

if (isset($_POST['submit'])) {
	// print_r($_POST);
	$error = 0;	
	foreach ($_POST as $k => $v) {
		if ($k != 'submit') {
			if ($k == 'rooms') {
				if (!ctype_digit($v)) {
					$error |= 1;
				}							
			} else if ($k == 'no' || $k == 'street') {
				if (strlen(trim($v)) < 1) {
					$error |= 2;
				}							
			} else if ($k == 'sname') {		//autogen?
				if (strlen(trim($v)) < 1) {
					$error |= 4;
				}									
			}
		}
	}
	if ($error == 0) {
		$_POST['clientid'] = $clientid;
		$q = $db->prepare("
			INSERT INTO `properties` (
				`clientid`,
				`no`,
				`street`,
				`town`,
				`postcode`,
				`rooms`,
				`sname`,
				`xero_tracking1`,
				`xero_tracking2`
			) VALUES (
				:clientid,
				:no,
				:street,
				:town,
				:postcode,
				:rooms,
				:sname,
				:xero_tracking1,
				:xero_tracking2				
			)");
		foreach ($_POST as $k => $v) {
			if ($k != 'submit') {
				$q->bindValue(':'.$k, htmlentities(utf8_encode($v)));
			}
		}
		$q->execute();
		
		//ROOMS
		foreach ($db->query("SELECT `id`, `rooms` FROM `properties` ORDER BY `id` DESC LIMIT 1") as $row) {
			$pid = $row['id'];
			$rooms = $row['rooms'];
		}
		for ($r = 1; $r <= $rooms; $r++) {
			$q = $db->prepare("INSERT INTO `rooms` (`propertyid`, `no`) VALUES ('$pid', '$r')");
			$q->execute();
		}	

		header("Location: properties.php?s=detail&r=".$pid); //absolute?
					
	} else {
		$_SESSION['newprop'] = array();
		foreach ($_POST as $k => $v) {
			if ($k != 'submit') {
				$_SESSION['newprop'][$k] = $v;
			}
		}
		$_SESSION['error'] = $error;
		header("Location: properties.php?s=add");
	}
}
