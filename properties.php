<?php
include_once 'defs/db_connect.php';
include_once 'defs/functions.php';
require 'defs/inc_accountcheck.php';
 
if ($displaycontent == true) {
	$pagetitle = 'Properties';

	$headers = array('House No./Name', 'Street Name', 'Town', 'Postcode', 'No. of Rooms', 'Short Name', 'Xero Tracking Name 1', 'Xero Tracking Name 2');
	$dbheaders = array('no', 'street', 'town', 'postcode', 'rooms', 'sname', 'xero_tracking1', 'xero_tracking2');

?>
<!DOCTYPE html>
<html>
	<head>
		<?php require 'defs/inc_jscss.php' ?>
		<script type="text/javascript" src="js/properties.js"></script>
	</head>
	<body>
		<?php require 'defs/inc_header.php' ?>
		<div id="subheader">
			<div id="innersubheader">
				<?php if (isset($_GET['s'])) {
					if ($_GET['s'] == 'detail') {
						$s = 'detail';
					} else if ($_GET['s'] == 'feesexp') {
						$s = 'feesexp';
						} else if ($_GET['s'] == 'add') {
						$s = 'add';
					} else if ($_GET['s'] == 'import') {
						$s = 'import';
					} else {
						$s = 'list';
					}
				} else {
					$s = 'list';
				}?>
				<span class="<?php echo ($s == 'list' ? 'subtab_active' : 'subtab') ?>" id="list">
					List
				</span>
				<span class="<?php echo ($s == 'detail' ? 'subtab_active' : 'subtab') ?>" id="detail">
					Details
				</span>
				<span class="<?php echo ($s == 'feesexp' ? 'subtab_active' : 'subtab') ?>" id="feesexp">
					Fees & Expenses
				</span>
				<span class="<?php echo ($s == 'add' ? 'subtab_active' : 'subtab') ?>" id="add">
					Add
				</span>
				<span class="<?php echo ($s == 'import' ? 'subtab_active' : 'subtab') ?>" id="import">
					Import
				</span>

			</div>
		</div>
		<div id="main">
			<?php if ($s == 'list') { ?>
			<p>Double-click on a property below to view details.</p>
			<table id="datatable">
				<thead>
					<tr><?php
					
						echo "\n";
						
						foreach ($headers as $header) {
							if ( $header != 'Xero Tracking Name 1' &&  $header != 'Xero Tracking Name 2') {
								echo "						<th>$header</th>\n";
							}
						}
						echo "						<th>Xero Tracking Name 1</th>\n";
						?>
					</tr>
				</thead>
				<tbody>
					<?php 

					if ($q = $db->prepare("
						SELECT
							`id` as 'xID',
							`no` as 'tbl_no',
							`street` as 'tbl_street',
							`town` as 'tbl_town',
							`postcode` as 'tbl_postcode',
							`rooms` as 'tbl_rooms',
							`sname` as 'tbl_sname',
							`xero_tracking1` as 'xero_tracking1'
						FROM `properties`
						WHERE `clientid` = '$clientid'
						AND `del` = 'n'
						ORDER BY `no`
					")) {
						$q->execute();
						$r = $q->fetchAll(PDO::FETCH_ASSOC);
						foreach ($r as $row) {
							echo "					<tr id=\"".$row['xID']."\">\n";
							foreach ($row as $k => $v) {
								if ($k != 'xID') {
									echo "					<td>".htmlentities(utf8_encode($v))."</td>\n";
								}
							}
							echo "					</tr>\n";
						}
					}
					?>
				</tbody>
				<input type="hidden" id="selectedid" />
			</table>

			<?php } else if ($s == 'detail') { ?>

			<?php 

			if (!isset($_GET['d'])) {
				foreach ($db->query("SELECT `id` FROM `properties` WHERE `clientid` = '$clientid' ORDER BY `id` DESC LIMIT 1") as $row) {
					echo "<script> document.location = '".$_SERVER['REQUEST_URI']."&d=".$row['id']."' </script>";
				}
			}

			if (isset($_POST['submit'])) {
				// print_r($_POST);
				$id = $_POST['input_id'];
				$error = 0;
				foreach ($_POST as $k => $v) {
					if ($k != 'submit' && $k != 'id') {
						$f = str_replace('input_', '', $k);
						if ($f == 'rooms') {
							if (ctype_digit($v)) {
								// need to check if different so rooms db can be updated
								$q2 = $db->prepare("SELECT `rooms` from `properties` WHERE `id` =?");
								$q2->bindValue(1, $id, PDO::PARAM_INT);
								$q2->execute();
								$r2 = $q2->fetch(PDO::FETCH_ASSOC);
								$rooms = $r2['rooms'];
								if ($v < $rooms) { // delete rooms
									$delrooms = $rooms - $v;
									$v2 = $v;
									for ($x = 1; $x <= $delrooms; $x++) {
										$v2++;
										$q3 = $db->prepare("UPDATE `rooms` SET `del` = 'y' WHERE `propertyid` ='$id' AND `no` = '$v2'");
										$q3->execute();
									}
									$q = $db->prepare("UPDATE `properties` SET `".$f."` =? WHERE `id` =?");
									$q->execute(array($v, $id));									
								} else if ($v > $rooms) { // add rooms
									$newrooms = $v - $rooms;
									for ($x = 1; $x <= $newrooms; $x++) {
										$rooms++;
										$q3 = $db->prepare("INSERT INTO `rooms` (`propertyid`, `no`) VALUES ('$id', '$rooms')");
										$q3->execute();
									}
									$q = $db->prepare("UPDATE `properties` SET `".$f."` =? WHERE `id` =?");
									$q->execute(array($v, $id));									
								} // else do nothing
							} else {
								$error |= 1;
							}
						} else if ($f == 'no' || $f == 'street') {
							if (strlen(trim($v)) >= 1) {
								$q = $db->prepare("UPDATE `properties` SET `".$f."` =? WHERE `id` =?");
								$q->execute(array(htmlentities(utf8_encode($v)), $id));
							} else {
								$error |= 2;
							}
						} else if ($f == 'sname') {		//autogen?
							if (strlen(trim($v)) >= 1) {
								$q = $db->prepare("UPDATE `properties` SET `".$f."` =? WHERE `id` =?");
								$q->execute(array(htmlentities(utf8_encode($v)), $id));
							} else {
								$error |= 4;
							}
						} else {
							if (in_array($f, $dbheaders)) {
								$q = $db->prepare("UPDATE `properties` SET `".$f."` =? WHERE `id` =?");
								$q->execute(array(htmlentities(utf8_encode($v)), $id));
							}
						}
					}
				}
				if ($error == 0) {
					echo "<p>All changes submitted successfully!</p>\n";
				} else {
					echo "		<p class=\"error\">ERROR</p>";
					if ($error & 1) {
						echo "		<p>'No. of Rooms' must be an integer.</p>";
					}
					if ($error & 2) {
						echo "		<p>'House No./Name' and 'Street Name' must not be empty.</p>";
					}
					if ($error & 4) {
						echo "		<p>'Short Name' must not be empty.</p>";
					}
					echo "		<p>Please amend and re-submit changes below.</p>";
				}
			}

			?>

			<form action="<?php echo $_SERVER['REQUEST_URI'] ?>" method="POST">
				<fieldset class="fieldset">
					<legend>Edit property details:</legend>
					<input type="hidden" name="input_id" value="<?php echo $_GET['d'] ?>" />
					<?php 
					// this whole conditional is a mess, plz sort
					if ($q = $db->prepare("
						SELECT
							`no` as 'input_no',
							`street` as 'input_street',
							`town` as 'input_town',
							`postcode` as 'input_postcode',
							`rooms` as 'input_rooms',
							`sname` as 'input_sname',
							`xero_tracking1` as 'input_xero_tracking1',
							`xero_tracking2` as 'input_xero_tracking2'
						FROM `properties`
						WHERE `id` =?
						AND `clientid` = '$clientid'
					")) {
						$q->bindValue(1, $_GET['d']);
						$q->execute();
						$r = $q->fetchAll(PDO::FETCH_ASSOC);	//should just be fetch
						// print_r ($r);
						foreach ($r as $row) {
							$fieldcount = -1;
							foreach ($row as $k => $v) {
								if ($k == 'input_landlordid') {
									//depreciated
								} else {
									$fieldcount++;
									echo "					<p>\n";
									echo "						<label for=\"".$k."\" class=\"zoomlabel\">".$headers[$fieldcount].":</label>\n";
									echo "						<input id=\"".$k."\" name=\"".$k."\" value=\"".$v."\" />  \n";
									echo "					</p>\n";
								}
							}
						}
					} else {
						echo "<p>There was an error with the database</p>";
					}
					?>
				</fieldset>
				<p class="detailsubmit"><input type="submit" name="submit" value="Submit changes" class="button"/></p>
			</form>
			
			<input type="hidden" id="getf" value="<?php
			
			if (!isset($_GET['d'])) {
				$_GET['d'] = 0;
			}
			
			$q2 = $db->prepare("SELECT * FROM `mgmtcontracts` WHERE `propertyid` = ? ORDER BY (`enddate` IS NOT NULL), `enddate` DESC LIMIT 1");
			$q2->execute(array($_GET['d']));
			$r2 = $q2->fetch();
			echo $r2['id'];
			//find latest MCID for fees tab
			
			?>" />

			<?php } else if ($s == 'add') { ?>

			<?php


			if (isset($_SESSION['error'])) {
				$error = $_SESSION['error'];
				echo "		<p class=\"error\">ERROR</p>";
				if ($error & 1) {
					echo "		<p>'No. of Rooms' must be an integer.</p>";
				}
				if ($error & 2) {
					echo "		<p>'House No./Name' and 'Street Name' must not be empty.</p>";
				}
				if ($error & 4) {
					echo "		<p>'Short Name' must not be empty.</p>";
				}
				echo "		<p>Please amend and re-submit changes below.</p>";

				foreach ($_SESSION['newprop'] as $k => $v) {
					$_POST[$k] = $v;
				}
				unset($_SESSION['error']);
				unset($_SESSION['newprop']);
			}

			?>


			<form action="property_add.php" method="POST">
				<fieldset class="fieldset">
					<legend>Add a new property</legend>
					<?php

					for ($x = 0; $x < sizeof($headers); $x++) {
						if (isset($_POST[$dbheaders[$x]])) {
							$v = $_POST[$dbheaders[$x]];
						} else {
							$v = '';
						}
						echo "					<p>\n";
						echo "						<label for\"".$dbheaders[$x]."\" class=\"zoomlabel\">".$headers[$x].":</label>\n";
						echo "						<input id=\"".$dbheaders[$x]."\" name=\"".$dbheaders[$x]."\" value=\"".$v."\" />  \n";
						echo "					</p>\n";
					}

					?>
				</fieldset>
				<p class="detailsubmit"><input type="submit" name="submit" value="Add property" class="button"/></p>
			</form>

			<?php } else if ($s == 'import') { ?>

			<?php 
			if (isset($_FILES["file"]))	{
				if ($_FILES["file"]["error"] > 0) {
					echo "<p>Error: " . $_FILES["file"]["error"] . "</p>\n";
				} else {
					$csv_mimetypes = array(
						'text/csv',
						'text/plain',
						'application/csv',
						'text/comma-separated-values',
						'application/excel',
						'application/vnd.ms-excel',
						'application/vnd.msexcel',
						'text/anytext',
						'application/octet-stream',
						'application/txt',
					);
					if (in_array($_FILES['file']['type'], $csv_mimetypes)) {
						// possible CSV file
						echo "		<p class=\"italic\">[Upload: " . $_FILES["file"]["name"] . "]</p>\n";
						echo "		<p class=\"italic\">[Type: " . $_FILES["file"]["type"] . "]</p>\n";
						echo "		<p class=\"italic\">[Size: " . ($_FILES["file"]["size"]) . " B]</p>\n";
						echo "		<p class=\"italic\">[Upload successful. Checking file contents...]</p>\n";

						$csv = file($_FILES['file']['tmp_name']);
						array_shift($csv); // only with headers

						// $testarr = explode(",", $csv[0]); // NO!
						
						$testrows = array_map("str_getcsv", $csv);
						$testarr = $testrows[0];
						
					} else {
						echo "		<p>Invalid file format - check file is saved as a CSV.</p>\n";
					}
					
					if (!isset($testarr)) {
						$testarr = array();
					}
					
					if (count($testarr) == 6) {	?>
						<p>
							CSV contains correct number of columns! Please check data below before continuing. If incorrect, amend and <a href="<?php echo $_SERVER['REQUEST_URI'] ?>">click here to re-upload</a>.
						</p>


						<table id="csvex" class="narrow">
							<thead>
								<tr>
									<th>House No./Name</th>
									<th>Street Name</th>
									<th>Town</th>
									<th>Postcode</th>
									<th>No. of Rooms</th>
									<th>Short Name</th>
								</tr>
							</thead>
							<tbody>

						<?php $rows = array_map("str_getcsv", $csv);

						$error = 0;
						foreach ($rows as $arr) {
							echo "			<tr>\n";
							for ($x = 0; $x < count($arr); $x++) {
								if ($x == 4) {	// check if int
									if (ctype_digit($arr[$x])) {
										echo "				<td>".utf8_encode($arr[$x])."</td>\n";
									} else {
										echo "				<td class=\"error\">".utf8_encode($arr[$x])."</td>\n";
										$error |= 1;
									}
								} else if ($x == 0 || $x == 1) {	//check if not empty
									if (strlen(trim(utf8_encode($arr[$x]))) >= 1) {
										echo "				<td>".utf8_encode($arr[$x])."</td>\n";
									} else {
										echo "				<td class=\"error\">".utf8_encode($arr[$x])."</td>\n";
										$error |= 2;
									}
								} else if ($x == 5) {	//check if not empty - want to autogen at some point
									if (strlen(trim(utf8_encode($arr[$x]))) >= 1) {
										echo "				<td>".utf8_encode($arr[$x])."</td>\n";
									} else {
										echo "				<td class=\"error\">".utf8_encode($arr[$x])."</td>\n";
										$error |= 4;
									}
								} else {
									echo "				<td>".utf8_encode($arr[$x])."</td>\n";
								}
							}
							echo "			</tr>\n";
						}
						echo "			</tbody>\n";
						echo "		</table>\n";

						$rand = rand();
						echo "		<input type=\"hidden\" id=\"uploadid\" value=\"".$rand."\" /> ";
						file_put_contents("db/upload/upload".$rand, $csv, LOCK_EX);

						if ($error == 0) {
							echo "		<input type=\"hidden\" id=\"importopeningdate\" value=\"null\" />\n";
							echo "		<p><input type=\"button\" class=\"button\" id=\"importbtn\" value=\"Import Properties!\" /></p>\n";
							echo "		<div id=\"output\"></div>\n";
						} else {
							echo "		<p class=\"error\">ERROR</p>";
							if ($error & 1) {
								echo "		<p>'No. of Rooms' must be an integer.</p>";
							}
							if ($error & 2) {
								echo "		<p>'House No./Name' and 'Street Name' must not be empty.</p>";
							}
							if ($error & 4) {
								echo "		<p>'Short Name' must not be empty.</p>";
							}
							echo "		<p>Please amend and <a href=\"".$_SERVER['REQUEST_URI']."\">click here to re-upload</a>.</p>";

						}
					} else {
						echo "		<p>Incorrect number of columns - please check the syntax requirements and <a href=\"properties.php?s=import\">click here to try again</a>.</p>\n";
					}
				}
			} else {
			?>
			<p>To import a list of properties, first create a CSV file with the following layout, including the headings in row 1:</p>
			<table id="csvex" class="narrow">
				<thead>
					<tr>
						<th>House No./Name</th>
						<th>Street Name</th>
						<th>Town</th>
						<th>Postcode</th>
						<th>No. of Rooms</th>
						<th>Short Name</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>15</td>
						<td>Example Road</td>
						<td>Exampleton</td>
						<td>EX4 9LE</td>
						<td>4</td>
						<td>15ER</td>
					</tr>
					<tr>
						<td>75</td>
						<td>Demonstration Street</td>
						<td>Exampleton</td>
						<td>EX4 7RF</td>
						<td>5</td>
						<td>75DS</td>
					</tr>
				</tbody>
			</table>
			<p><span id="yourBtn" class="button" onclick="getFile()">Then click here to upload the CSV</span></p>
			<form action="<?php echo $_SERVER['REQUEST_URI'] ?>" method="POST" enctype="multipart/form-data" name="myForm">
				<span class="hidden"><input id="upfile" name="file" type="file" value="upload" onchange="sub(this)"/></span>
			</form>
			<p>Please note: the columns need to be in the same order as the above example for the import to work.</p>
			<?php
			}
			?>
			
			<?php } else if ($s == 'feesexp') { ?>
			
			<?php 
			
			// if (isset($_GET['d'])) {
				// $q = $db->prepare("SELECT * FROM `properties` WHERE `id` = ? AND `clientid` = ?");
				// $q->execute(array($_GET['d'], $clientid));
				// $rc = $q->rowCount();
				// if ($rc == 1) {
					// $r = $q->fetch();
					// $prop = $r['no']." ".$r['street'];
					// $propid = $r['id'];
				// } else {
					// $prop = 'all properties';
					// $propid = 0;
				// }
			// } else {
				// $prop = 'all properties';
				// $propid = 0;
			// }
			
			
			if (isset($_GET['d'])) {
				$q = $db->prepare("
					SELECT
						ml.mcid AS `id`,
						ml.landlordname AS `name`,
						ml.landlordid AS `lid`,
						properties.sname AS `sname`,
						CONCAT (properties.no,' ',properties.street) AS `lname`
					FROM (
						SELECT
							mgmtcontracts.id AS `mcid`,
							mgmtcontracts.landlordid AS `landlordid`,
							mgmtcontracts.propertyid AS `propertyid`,
							landlords.name AS `landlordname`,
							mgmtcontracts.enddate AS `mcenddate`
						FROM `mgmtcontracts` LEFT JOIN `landlords` ON mgmtcontracts.landlordid = landlords.id) ml
					LEFT JOIN `properties` ON ml.propertyid = properties.id
					WHERE properties.clientid = $clientid
					AND ml.mcid = ?
					ORDER BY ml.landlordname, (ml.mcenddate IS NOT NULL), ml.mcenddate DESC;
				");
				$q->execute(array($_GET['d']));
				$rc = $q->rowCount();
				if ($rc == 1) {
					$r = $q->fetch();
					$prop = $r['lname']." (".$r['name'].") [#".$r['id']."]";
					$mcid = $r['id'];
					$landlordid = $r['lid'];
				} else {
					$prop = 'all properties';
					$mcid = 0;
					$landlordid = 0;
				}				
			} else {
				$prop = 'all properties';
				$mcid = 0;
				$landlordid = 0;
			}			
			
			?>
			<input type="hidden" id="getd" value="<?php echo $mcid ?>" />
			<input type="hidden" id="getl" value="<?php echo $landlordid ?>" />
			
			<div class="lfieldset">
				<h2>Fees and Expenses for <?php echo $prop ?></h2>
				<?php /* <label for="feespropertyselect">Choose property:</label>
				<select id="feespropertyselect">
					<option value="0">All properties</option><?php echo "\n";
					foreach ($db->query("SELECT * FROM `properties` WHERE `clientid` = $clientid") as $row) {
						echo "					<option value=\"".$row['id']."\"";
						echo ($row['id'] == $propid ? " selected=\"selected\"" : "");
						echo ">".$row['no']." ".$row['street']."</option>";
					} ?>
				</select> */ ?>
				<label for="propfeesxac">Select property:</label>
				<span class="xaccont">
					<input id="propfeesxac" />
					<div class="xacmenu">
						Javascript fail
					</div>
				</span>
				<span id="propfeesxacinfo"></span>	
				<br />
				<span style="display:none">Fees are assigned to management contracts. Properties with multiple contracts will have multiple entries in the above list.</span>				
			</div>
			<?php if ($mcid == 0) {
					echo "				<div id=\"pleaseselecttenant\">Please select a property from the above drop-down list to add a new item.</div>\n";
				} else { ?>			
			<div class="lfieldset" id="addfeediv">
				<h2>Add a new item</h2>
				<p>
					<span class="datecont">
						<label for="addfeedate">Date:</label>
						<input id="addfeedate" class="genericdate" />
					</span>
					<label for="addfeeamount">Amount:</label>
					<input id="addfeeamount" />

					<?php /*
					<?php if ($prop == 0) { ?>
					<label for="addfeeproperty">Property:</label>
					<select id="addfeeproperty"> <?php echo "\n";
					foreach ($db->query("SELECT * FROM `properties` WHERE `clientid` = $clientid") as $row) {
						echo "					<option value=\"".$row['id']."\"";
						echo ($row['id'] == $propid ? " selected=\"selected\"" : "");
						echo ">".$row['no']." ".$row['street']."</option>";
					} ?>
					</select>
					<?php } else { ?>
					<input type="hidden" id="addfeeproperty" value="<?php echo $propid ?>" />
					<?php } ?> */ ?>
					<input type="hidden" id="addfeeproperty" value="<?php echo $propid ?>" />
					<label for="addfeedesc">Description:</label>
					<input id="addfeedesc" />
					<label for="addfeetype">Type:</label>
					<select id="addfeetype">
						<option value="m">Maintenance</option>
						<option value="o">Other</option>
					</select>
				<p>
				</p>
					&nbsp;
					<input type="button" class="button" id="addfee" value="Add"/>
				</p>
			</div>
			<?php } ?>	
			<div class="lfieldset" id="editfeediv">
				<h2>Edit fee</h2>
				<p>
					<input type="hidden" id="editfeeid" value="" />
					<span class="datecont">
						<label for="editfeedate">Date:</label>
						<input id="editfeedate" class="genericdate" />
					</span>
					<label for="editfeeamount">Amount:</label>
					<input id="editfeeamount" />
					<label for="editfeedesc">Description:</label>
					<input id="editfeedesc" />
					<label for="editfeetype">Type:</label>
					<select id="editfeetype">
						<option value="l">Letting fee</option>
						<option value="m">Maintenance</option>
						<option value="o">Other</option>
					</select>					
				</p>
				<p>
					&nbsp;
					<span id="editfeebuttons">
						<input type="button" class="button" id="editfee" value="Submit changes"/>
						<input type="button" class="button" id="deletefee" value="Delete"/>
						<input type="button" class="button" id="canceleditfee" value="Cancel"/>
					</span>
				</p>
			</div>			
			<table>
				<thead>
					<tr>
						<th>Date</th>
						<th>Property</th>
						<th>Amount</th>
						<th>Description</th>
						<th>Type</th>
						<th>Edit</th>
					</tr>
				</thead>
				<tbody><?php echo "\n";
				if ($mcid == 0) {
					$q = $db->prepare("
						SELECT 
							propertyfees.id AS `id`,
							propertyfees.date AS `date`,
							CONCAT(properties.no, ' ', properties.street) AS `property`,
							propertyfees.amount AS `amount`,
							propertyfees.desc AS `desc`,
							propertyfees.type AS `type`
						FROM `propertyfees` LEFT JOIN (`mgmtcontracts` LEFT JOIN `properties` ON mgmtcontracts.propertyid = properties.id) ON propertyfees.mcid = mgmtcontracts.id WHERE properties.clientid = $clientid
						ORDER BY propertyfees.date DESC
					");
				} else {
					$q = $db->prepare("
						SELECT 	
							propertyfees.id AS `id`,
							propertyfees.date AS `date`,
							CONCAT(properties.no, ' ', properties.street) AS `property`,
							propertyfees.amount AS `amount`,
							propertyfees.desc AS `desc`,
							propertyfees.type AS `type`							
						FROM `propertyfees` LEFT JOIN (`mgmtcontracts` LEFT JOIN `properties` ON mgmtcontracts.propertyid = properties.id) ON propertyfees.mcid = mgmtcontracts.id WHERE mgmtcontracts.id = ? AND properties.clientid = $clientid
						ORDER BY propertyfees.date DESC
					");
					$q->bindValue(1, $mcid, PDO::PARAM_INT);
				}
				$q->execute();
				$r = $q->fetchAll(PDO::FETCH_ASSOC);
				foreach ($r as $row) {
					//FIXEDME!
					if ($row['type'] == 'm') {
						$type = 'Maintenance';
					} else if ($row['type'] == 'l') {
						$type = 'Letting fee';
					} else {
						$type = 'Other';
					}
					
					echo "					<tr data-id=\"".$row['id']."\">\n";
					echo "						<td>".date('j M Y', strtotime($row['date']))."</td>\n";
					echo "						<td>".$row['property']."</td>\n";
					echo "						<td>".number_format($row['amount'] / 100, 2, '.', ',')."</td>\n";
					echo "						<td>".$row['desc']."</td>\n";
					echo "						<td>".$type."</td>\n";
					echo "						<td class=\"tdedit\"><img src=\"img/edit-icon.png\" /></td>\n";
					echo "					</tr>\n";
				}
				
				?></tbody>
			</table>

			<div id="xaclookuplist">
				<ul><?php
				echo "\n";
				foreach ($db->query("
					SELECT
						mp.mcid AS `mcid`,
						mp.landlordname AS `llname`,
						properties.sname AS `sname`,
						mp.landlordid AS `lid`,
						CONCAT (properties.no,' ',properties.street) AS `lname`
					FROM (
						SELECT
							mgmtcontracts.id AS `mcid`,
							mgmtcontracts.landlordid AS `landlordid`,
							mgmtcontracts.propertyid AS `propertyid`,
							landlords.name AS `landlordname`,
							mgmtcontracts.enddate AS `mcenddate`
						FROM `mgmtcontracts` LEFT JOIN `landlords` ON mgmtcontracts.landlordid = landlords.id
					) mp LEFT JOIN `properties` ON mp.propertyid = properties.id
					WHERE properties.clientid = $clientid
					ORDER BY mp.landlordname, (mp.mcenddate IS NOT NULL), mp.mcenddate DESC;
				") as $lrow) {
					echo "				<li data-contacttype=\"l\" data-mcid=\"".$lrow['mcid']."\" data-landlordid=\"".$lrow['lid']."\" data-address=\"".$lrow['lname']."\">".$lrow['lname']." (".$lrow['llname'].") [#".$lrow['mcid']."]</li>\n";
				}			
				?>
				</ul>
			</div> 
			
			<?php } ?>

		</div>

		<input type="hidden" id="lastfocus" value="input_ref" />

	</body>
</html>
<?php } ?>