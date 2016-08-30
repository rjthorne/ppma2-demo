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
				INSERT INTO `tenants` (
					`clientid`,
					`name`,
					`phone1`,
					`phone2`,
					`email1`,
					`email2`,
					`dob`
				) VALUES (
					:clientid,
					:name,
					:phone1,
					:phone2,
					:email1,
					:email2,
					:dob
				)
			");
		foreach ($_POST as $k => $v) {
			if ($k == 'dob') {
				$ukify = str_replace('/', '-', $v);
				if (strtotime($ukify) != 0) {
					$q->bindValue(':dob', date("Y-m-d", strtotime($ukify)));
				} else {
					$q->bindValue(':dob', null, PDO::PARAM_INT);
				}
			} else if ($k == 'name') {
				// $v = str_replace('\t', ' ', $v);
				$v = preg_replace('/[ ]{2,}|[\t]/', ' ', trim($v));
				$q->bindValue(':'.$k, htmlentities(utf8_encode($v)));
			} else if ($k != 'submit') {
				$q->bindValue(':'.$k, htmlentities(utf8_encode($v)));
			}
		}
		$q->execute();
		
		foreach ($db->query("SELECT `id` FROM `tenants` ORDER BY `id` DESC LIMIT 1") as $row) {
			$tid = $row['id'];
		}		
		header("Location: tenants.php?s=detail&r=".$tid); //absolute?
					
	} else {
		$_SESSION['newten'] = array();
		foreach ($_POST as $k => $v) {
			if ($k != 'submit') {
				$_SESSION['newten'][$k] = $v;
			}
		}
		$_SESSION['error'] = $error;
		header("Location: tenants.php?s=add");
	}
}
