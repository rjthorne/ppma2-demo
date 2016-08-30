<?php

include_once 'defs/db_connect.php';
include_once 'defs/functions.php';
require 'defs/inc_accountcheck.php';

if (isset($_POST['submit'])) {
	// print_r($_POST);
	$error = 0;	
	foreach ($_POST as $k => $v) {
		if ($k != 'submit') {
			if ($k == 'name') {
				if (strlen(trim($v)) < 1) {
					$error |= 1;
				}															
			}
		}
	}
	if ($error == 0) {
		$_POST['clientid'] = $clientid;
			$q = $db->prepare("
				INSERT INTO `landlords` (
					`clientid`,
					`name`,
					`address1`,
					`address2`,
					`town`,
					`postcode`,
					`phone1`,
					`phone2`,
					`email1`,
					`email2`
				) VALUES (
					:clientid,
					:name,
					:address1,
					:address2,
					:town,
					:postcode,
					:phone1,
					:phone2,
					:email1,
					:email2
				)
			");
		foreach ($_POST as $k => $v) {
			if ($k != 'submit') {
				$q->bindValue(':'.$k, htmlentities(utf8_encode($v)));
			}
		}
		$q->execute();
		
		foreach ($db->query("SELECT `id` FROM `landlords` ORDER BY `id` DESC LIMIT 1") as $row) {
			$lid = $row['id'];
		}		
		header("Location: landlords.php?s=detail&r=".$lid); //absolute?
					
	} else {
		$_SESSION['newll'] = array();
		foreach ($_POST as $k => $v) {
			if ($k != 'submit') {
				$_SESSION['newll'][$k] = $v;
			}
		}
		$_SESSION['error'] = $error;
		header("Location: landlords.php?s=add");
	}
}
