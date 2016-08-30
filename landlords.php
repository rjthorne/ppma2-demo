<?php
include_once 'defs/db_connect.php';
include_once 'defs/functions.php';
require 'defs/inc_accountcheck.php';
 
if ($displaycontent == true) {
	$pagetitle = 'Landlords';

	$headers = array('Name', 'Address Line 1', 'Address Line 2', 'Town', 'Postcode', 'Mobile No.', 'Secondary/ Landline No.', 'Email address #1', 'Email address #2');
	$dbheaders = array('name', 'address1', 'address2', 'town', 'postcode', 'phone1', 'phone2', 'email1', 'email2');
?>
<!DOCTYPE html>
<html>
	<head>
		<?php require 'defs/inc_jscss.php' ?>
		<script type="text/javascript" src="js/landlords.js"></script>
	</head>
	<body>
		<?php require 'defs/inc_header.php' ?>
		<div id="subheader">
			<div id="innersubheader">
				<?php if (isset($_GET['s'])) {
					if ($_GET['s'] == 'detail') {
						$s = 'detail';
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
			<p>Double-click on a landlord below to view details.</p>
			<table id="datatable">
				<thead>
					<tr>
						<th>Name</th>
						<th>Mobile No.</th>
						<th>Secondary/Landline No.</th>
						<th>Email address #1</th>
						<th>Email address #2</th>
					</tr>
				</thead>
				<tbody>
					<?php 

					if ($q = $db->prepare("
						SELECT
							`id` as 'xID',
							`name` as 'tbl_name',
							`phone1` as 'tbl_phone1',
							`phone2` as 'tbl_phone2',
							`email1` as 'tbl_email1',
							`email2` as 'tbl_email2'
						FROM `landlords`
						WHERE `clientid` = '$clientid'
						AND `del` = 'n'
						ORDER BY `name`
					")) {
						$q->execute();
						$r = $q->fetchAll(PDO::FETCH_ASSOC);
						foreach ($r as $row) {
							echo "					<tr id=\"".$row['xID']."\">\n";
							foreach ($row as $k => $v) {
								if ($k != 'xID') {
									echo "					<td>".$v."</td>\n";
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
				foreach ($db->query("SELECT `id` FROM `landlords` WHERE `clientid` = '$clientid' ORDER BY `id` DESC LIMIT 1") as $row) {
					echo "<script> document.location = '".$_SERVER['REQUEST_URI']."&d=".$row['id']."' </script>";
				}

			}

			if (isset($_POST['submit'])) {
				// print "<pre>";
				// print_r($_POST);
				// print "</pre>";
				$id = $_POST['input_id'];	//

				$landlordfields = array('input_name', 'input_address1', 'input_address2', 'input_town', 'input_postcode', 'input_phone1', 'input_phone2', 'input_email1', 'input_email2');
				$mcfields = array('propertyid', 'startdate', 'enddate', 'mgmt', 'lease', 'obal', 'obaldate');

				$error = 0;
				foreach ($_POST as $k => $v) {
					if (in_array($k, $landlordfields)) {
						$f = str_replace('input_', '', $k);
						$q = $db->prepare("UPDATE `landlords` SET `".$f."` =? WHERE `id` =?");
						if ($f == 'name') {
							if (strlen(trim($v)) >= 1) {
								$q->execute(array(htmlentities(utf8_encode($v)), $id));
							} else {
								$error |= 1;
							}
						} else {
							$q->execute(array(htmlentities(utf8_encode($v)), $id));
						}
					}
				}

				// old foreach
				// foreach ($_POST as $k => $v) {
					// if ($k != 'submit' && $k != 'id') {
						// $f = str_replace('input_', '', $k);
						// if ($f == 'name') {
							// if (strlen(trim($v)) >= 1) {
								// $q = $db->prepare("UPDATE `landlords` SET `".$f."` =? WHERE `id` =?");
								// $q->execute(array(htmlentities(utf8_encode($v)), $id));
							// } else {
								// $error |= 1;
							// }
						// } else {
							// $q = $db->prepare("UPDATE `landlords` SET `".$f."` =? WHERE `id` =?");
							// $q->execute(array(htmlentities(utf8_encode($v)), $id));
						// }
					// }
				// }

				if (isset($_POST['addmc'])) {
					$addmc = $_POST['addmc'];
				} else {
					$addmc = 0;
				}

				if ($q = $db->prepare("SELECT `id` FROM `mgmtcontracts` WHERE `landlordid` = ?")) {
					$q->bindValue(1, $id, PDO::PARAM_INT);
					$q->execute();
					$r = $q->fetchAll(PDO::FETCH_ASSOC);
					foreach ($r as $row) { // each mc belonging to landlord
						if ($row['id'] != $addmc) {
							$mcerror = 0;
							$mcid = $row['id'];
							// then loop through mcfields array, assigning each entry to a column name in a prepared query. perform validation on each field (as in imports) before executing.
							foreach ($mcfields as $mcf) {

								$$mcf = $_POST['t'.$mcid.'_'.$mcf];
								$q2 = $db->prepare("UPDATE `mgmtcontracts` SET $mcf =? WHERE `id` = $mcid");

								if ($mcf == 'propertyid') { // begin validation!
									$q2->bindValue(1, $propertyid);
									$q2->execute();
								} else if ($mcf == 'startdate') {
									$ukify = str_replace('/', '-', $startdate);
									if (strtotime($ukify) != 0) {
										$q2->bindValue(1, date("Y-m-d", strtotime($ukify)));
										$q2->execute();
									} else {
										$mcerror |= 8;
									}
								} else if ($mcf == 'enddate') {
									$ukify = str_replace('/', '-', $enddate);
									if (strtotime($ukify) != 0) {
										$q2->bindValue(1, date("Y-m-d", strtotime($ukify)));
										$q2->execute();
									} else {
										$q2->bindValue(1, null, PDO::PARAM_INT);
										$q2->execute();
									}
								} else if ($mcf == 'mgmt') {		//PERIOD IS MGMT THIS TIME - 16
									$mgmt = preg_replace("/([^0-9\\.])/i", "", $mgmt); //convert to number
									if ($mgmt == 0) {
										$mgmt = 0;
									} else if (strlen($mgmt == 0)) {
										$mgmt = 100;	//default if none entered
									}
									if ($mgmt >= 0 && $mgmt <= 100) {
										$q2->bindValue(1, $mgmt);
										$q2->execute();
									} else {
										$mcerror |= 16;
									}
								} else if ($mcf == 'lease') {		//RENT IS LEASE - 32
									if (strlen($lease == 0)) {
										$lease = 0;	//default if none entered
									}
									if (strlen(preg_replace("/([^0-9\\.])/i", "", str_replace(',','',$lease))) >= 1) {
										$q2->bindValue(1, preg_replace("/([^0-9\\.])/i", "", str_replace(',','',$lease) * 100));
										$q2->execute();
									} else {
										$mcerror |= 32;
									}
								} else if ($mcf == 'obal') {
									if (strlen(preg_replace("/([^0-9\\.])/i", "", $obal)) >= 1) {
										$q2->bindValue(1, preg_replace("/([^0-9\\.])/i", "", $obal));
										$q2->execute();
									} else {
										$q2->bindValue(1, 0);
										$q2->execute();
									}
								} else if ($mcf == 'obaldate') {
									$ukify = str_replace('/', '-', $obaldate);
									if (strtotime($ukify) != 0) {
										$q2->bindValue(1, date("Y-m-d", strtotime($ukify)));
										$q2->execute();
									} else {
										$q2->bindValue(1, null, PDO::PARAM_INT);
										$q2->execute();
									}
								}
							}

							if ($mcerror & 8) {
								$tenancyerrors8 = array();
								$tenancyerrors8[] = $mcid;
							} else if ($mcerror & 16) {
								$tenancyerrors16 = array();
								$tenancyerrors16[] = $mcid;
							} else if ($mcerror & 32) {
								$tenancyerrors32 = array();
								$tenancyerrors32[] = $mcid;
							}
						}
					}
				} else {
					//there was an error with the database
				}


				if ($error == 0 && !isset($mcerrors8) && !isset($mcerrors16) && !isset($mcerrors32) ) {
					echo "<p>All changes submitted successfully!</p>\n";
				} else {
					echo "		<p class=\"error\">ERROR</p>";
					if ($error & 1) {
						echo "		<p>'Name' must not be empty.</p>";
					}
					if (isset($mcerrors8)) {
						foreach ($mcerrors8 as $erroneousmc) {
							echo "		<p>'Start date' was missing or invalid on management contract #$erroneousmc.</p>";
						}
					}
					if (isset($mcerrors16)) {
						foreach ($mcerrors16 as $erroneousmc) {
							echo "		<p>Management % must be between 0 and 100 - management contract #$erroneousmc.</p>";
						}
					}
					if (isset($mcerrors32)) {
						foreach ($mcerrors32 as $erroneousmc) {
							echo "		<p>Invalid lease amount on management contract #$erroneousmc.</p>";
						}
					}
					echo "		<p>Please amend and re-submit changes below.</p>";
				}

				//old error section
				// if ($error == 0) {
					// echo "<p>All changes submitted successfully!</p>\n";
				// } else {
					// echo "		<p class=\"error\">ERROR</p>";
					// if ($error & 1) {
						// echo "		<p>'Name' must not be empty.</p>";
					// }
					// echo "		<p>Please amend and re-submit changes below.</p>";
				// }
			}

			?>

			<form action="<?php echo $_SERVER['REQUEST_URI'] ?>" method="POST">
				<fieldset class="fieldset">
					<legend>Edit landlord details</legend>
					<input type="hidden" name="input_id" id="input_id" value="<?php echo $_GET['d'] ?>" />
					<?php 

					if ($q = $db->prepare("
						SELECT
							`name` as 'input_name',
							`address1` as 'input_address1',
							`address2` as 'input_address2',
							`town` as 'input_town',
							`postcode` as 'input_postcode',
							`phone1` as 'input_phone1',
							`phone2` as 'input_phone2',
							`email1` as 'input_email1',
							`email2` as 'input_email2'
						FROM `landlords`
						WHERE `id` =?
					")) {
						$q->bindValue(1, $_GET['d']);
						$q->execute();
						$r = $q->fetchAll(PDO::FETCH_ASSOC);
						// print_r ($r);
						foreach ($r as $row) {
							$fieldcount = -1;
							foreach ($row as $k => $v) {
								$fieldcount++;
								echo "					<p>\n";
								echo "						<label for=\"".$k."\" class=\"zoomlabel\">".$headers[$fieldcount].":</label>\n";
								echo "						<input id=\"".$k."\" name=\"".$k."\" value=\"".$v."\" />  \n";
								echo "					</p>\n";
							}
						}
					} else {
						echo "<p>There was an error with the database</p>";
					}
					?>
				</fieldset>

				<div class="rfieldset">
					<h2>Balances & Reports</h2>
					<a href="reports.php?s=landlord&d=<?php echo $_GET['d'] ?>">View landlord statement</a>
				</div>

				<div class="lfieldset">
					<h2>Management contracts</h2>
					<div class="tenancycont">
						<input type="hidden" id="id0" value="0" />
						<h3>[Add new management contract]</h3>
						<div class="tenancyhide">
							<div class="tenancycol1 tenancyhide">
								<p>
									<label class="tenancylabel" for="t0_propertyid">Property:</label>
									<select class="tenancyinput1" id="t0_propertyid">
										<option value=""></option>
										<?php
										foreach ($db->query("SELECT	id, no,	street FROM `properties` WHERE clientid = $clientid ORDER BY `no`") as $p) {
											echo "									<option value=\"".$p['id']."\">".$p['no']." ".$p['street']."</option>\n";
										}
										?>
									</select>
								</p>
								<p>
									<span class="datecont">
										<label class="tenancylabel" for="t0_startdate">Start date:</label>
										<input class="genericdate" id="t0_startdate" />
									</span>
								</p>
								<p>
									<label class="tenancylabel" for="t0_mgmt">Management %:</label>
									<input class="tenancyinput3a" id="t0_mgmt" />
								</p>
								<p>
									<label class="tenancylabel" for="t0_obal">Opening balance:</label>
									<input class="tenancyinput3a" id="t0_obal" />
								</p>
							</div>
							<div class="tenancycol2 tenancyhide">
								<p>
									&nbsp;
								</p>
								<p>
									<span class="datecont">
										<label class="tenancylabel2" for="t0_enddate">End date:</label>
										<input class="genericdate" id="t0_enddate"  />
									</span>
								</p>
								<p>
									<label class="tenancylabel2" for="t0_lease">Lease:</label>
									<input class="tenancyinput3a" id="t0_lease" />
								</p>
								<p>
									<span class="datecont">
										<label class="tenancylabel2" for="t0_obaldate">Opening date:</label>
										<input class="genericdate" id="t0_obaldate" />
									</span>
								</p>
							</div>


							<div class="tenancyfoot tenancyhide">
								<input type="button" class="button" id="addmc" value="Add management contract" />
							</div>
						</div>
					</div>
				<?php

					if ($q = $db->prepare("
						SELECT 
							mgmtcontracts.id as `mcid`
						FROM `mgmtcontracts` LEFT JOIN `landlords` ON mgmtcontracts.landlordid = landlords.id
						WHERE landlords.id = ?
						AND clientid = '$clientid'
						ORDER BY mgmtcontracts.enddate IS NULL DESC, mgmtcontracts.enddate DESC
					")) {
						$q->bindValue(1, $_GET['d']);
						$q->execute();
						$r = $q->fetchAll(PDO::FETCH_ASSOC);
						$rownum = 0;
						foreach ($r as $row) {
							$rownum++;
							$mcid = $row['mcid'];
							$q2 = $db->query("
							SELECT 
								mgmtcontracts.id as `mc_id`,
								properties.id as `p_id`,
								properties.no as `p_no`,
								properties.street as `p_street`,
								mgmtcontracts.startdate as `mc_startdate`,
								mgmtcontracts.enddate as `mc_enddate`,
								mgmtcontracts.mgmt as `mc_mgmt`,
								mgmtcontracts.lease as `mc_lease`,
								mgmtcontracts.obal as `mc_obal`,
								mgmtcontracts.obaldate as `mc_obaldate`
							FROM `mgmtcontracts` LEFT JOIN `properties` ON mgmtcontracts.propertyid = properties.id
							WHERE mgmtcontracts.id = ".$row['mcid']."
							");
							$r2 = $q2->fetch(PDO::FETCH_ASSOC);
							$mc = $r2['mc_id'];
				?>

					<div class="tenancycont">
						<input type="hidden" class="mcid" id="id<?php echo $rownum ?>" value="<?php echo $mc ?>" />
						<h3>[#<?php echo $mc ?>] <?php echo date('j M Y', strtotime ($r2['mc_startdate'])) ?> - <?php echo (strtotime($r2['mc_enddate']) != 0 ? date("j M Y", strtotime($r2['mc_enddate'])) : "present") ?> :: <?php echo $r2['p_no']." ".$r2['p_street'] ?></h3>
						<div class="<?php echo (strtotime($r2['mc_enddate']) != 0 && strtotime($r2['mc_enddate']) < strtotime(date())  ? "tenancyhide" : "tenancyshow") ?>">
							<div class="tenancycol1 <?php echo (strtotime($r2['mc_enddate']) != 0 && strtotime($r2['mc_enddate']) < strtotime(date())  ? "tenancyhide" : "tenancyshow") ?>">
								<p>
									<label class="tenancylabel" for="t<?php echo $mc ?>_propertyid">Property:</label>
									<select class="tenancyinput1" name="t<?php echo $mc ?>_propertyid" id="t<?php echo $mc ?>_propertyid">
										<?php
										foreach ($db->query("SELECT	id, no,	street FROM `properties` WHERE clientid = $clientid") as $p) {
											echo "									<option value=\"".$p['id']."\"";
											if ($p['id'] == $r2['p_id']) {
												echo "selected=\"selected\"";
											}
											echo ">".$p['no']." ".$p['street']."</option>\n";
										}
										?>
									</select>
								</p>
								<p>
									<span class="datecont">
										<label class="tenancylabel" for="t<?php echo $mc ?>_startdate">Start date:</label>
										<input class="genericdate" name="t<?php echo $mc ?>_startdate" id="t<?php echo $mc ?>_startdate" value="<?php echo date('j M Y', strtotime ($r2['mc_startdate'])) ?>" />
									</span>
								</p>
								<p>
									<label class="tenancylabel" for="t<?php echo $mc ?>_mgmt">Management %:</label>
									<input class="tenancyinput3a" name="t<?php echo $mc ?>_mgmt" id="t<?php echo $mc ?>_mgmt" value="<?php echo $r2['mc_mgmt'] ?>" />
								</p>
								<p>
									<label class="tenancylabel" for="t<?php echo $mc ?>_obal">Opening balance:</label>
									<input class="tenancyinput3a" name="t<?php echo $mc ?>_obal" id="t<?php echo $mc ?>_obal" value="<?php echo number_format($r2['mc_obal'] / 100, 2, '.', ',') ?>" />
								</p>
							</div>
							<div class="tenancycol2 <?php echo (strtotime($r2['mc_enddate']) != 0 && strtotime($r2['mc_enddate']) < strtotime(date())  ? "tenancyhide" : "tenancyshow") ?>">
								<p>
									&nbsp;
								</p>
								<p>
									<span class="datecont">
										<label class="tenancylabel2" for="t<?php echo $mc ?>_enddate">End date:</label>
										<input class="genericdate enddate" name="t<?php echo $mc ?>_enddate" id="t<?php echo $mc ?>_enddate" value="<?php echo (strtotime($r2['mc_enddate']) != 0 ? date("j M Y", strtotime($r2['mc_enddate'])) : "") ?>" />
									</span>
								</p>
								<p>
									<label class="tenancylabel2" for="t<?php echo $mc ?>_lease">Lease:</label>
									<input class="tenancyinput3a" name="t<?php echo $mc ?>_lease" id="t<?php echo $mc ?>_lease" value="<?php echo number_format($r2['mc_lease'] / 100, 2, '.', ',') ?>" />
								</p>
								<p>
									<span class="datecont">
										<label class="tenancylabel2" for="t<?php echo $mc ?>_obaldate">Opening date:</label>
										<input class="genericdate" name="t<?php echo $mc ?>_obaldate" id="t<?php echo $mc ?>_obaldate" value="<?php echo (strtotime($r2['mc_obaldate']) != 0 ? date("j M Y", strtotime($r2['mc_obaldate'])) : "") ?>" />
									</span>
								</p>
							</div>
						</div>
					</div>
					<?php
						}
					}
					?>

				</div>

				<p class="detailsubmit"><input type="submit" name="submit" id="submit" value="Submit changes" class="button"/></p>
			</form>

			<?php } else if ($s == 'add') { ?>

			<?php


			if (isset($_SESSION['error'])) {
				$error = $_SESSION['error'];
				echo "		<p class=\"error\">ERROR</p>";
				if ($error & 1) {
					echo "		<p>'Name' must not be empty.</p>";
				}
				echo "		<p>Please amend and re-submit changes below.</p>";

				foreach ($_SESSION['newll'] as $k => $v) {
					$_POST[$k] = $v;
				}
				unset($_SESSION['error']);
				unset($_SESSION['newll']);
			}

			?>


			<form action="landlord_add.php" method="POST">
				<fieldset class="fieldset">
					<legend>Add a new landlord</legend>
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
				<p class="detailsubmit"><input type="submit" name="submit" value="Add landlord" class="button"/></p>
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
					if (count($testarr) == 9) {	?>
						<p>
							CSV contains correct number of columns! Please check data below before continuing. If incorrect, amend and <a href="<?php echo $_SERVER['REQUEST_URI'] ?>">click here to re-upload</a>.
						</p>

						<table id="csvex">
							<thead>
								<tr><?php

									echo "\n";

									foreach ($headers as $header) {
										echo "									<th>$header</th>\n";
									}

									?>
								</tr>
							</thead>
							<tbody>

						<?php $rows = array_map("str_getcsv", $csv);

						$error = 0;
						foreach ($rows as $arr) {
							echo "			<tr>\n";
							for ($x = 0; $x < count($arr); $x++) {
								if ($x == 0) {	//check if not empty
									if (strlen(trim(utf8_encode($arr[$x]))) >= 1) {
										echo "				<td>".utf8_encode($arr[$x])."</td>\n";
									} else {
										echo "				<td class=\"error\">".utf8_encode($arr[$x])."</td>\n";
										$error |= 1;
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
							echo "		<p><input type=\"button\" class=\"button\" id=\"importbtn\" value=\"Import Landlords!\" /></p>\n";
							echo "		<div id=\"output\"></div>\n";
						} else {
							echo "		<p class=\"error\">ERROR</p>";
							if ($error & 1) {
								echo "		<p>'Name' must not be empty.</p>";
							}
							echo "		<p>Please amend and <a href=\"".$_SERVER['REQUEST_URI']."\">click here to re-upload</a>.</p>";

						}
					} else {
						echo "		<p>Incorrect number of columns - please check the syntax requirements and <a href=\"properties.php?s=import\">click here to try again</a>.</p>\n";
					}
				}
			} else {
			?>
			<p>To import a list of landlords, first create a CSV file with the following layout, including the headings in row 1:</p>
			<table id="csvex">
				<thead>
					<tr><?php

						echo "\n";

						foreach ($headers as $header) {
							echo "						<th>$header</th>\n";
						}

						?>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>Mr Bob Smith</td>
						<td>18 Placeholder Road</td>
						<td>The Suburbs</td>
						<td>Exampleton</td>
						<td>EX2 8RD</td>
						<td>07123 034987</td>
						<td>0111 1234567</td>
						<td>bobsmith@examplemail.com</td>
						<td></td>
					</tr>
					<tr>
						<td>Example Investments Ltd</td>
						<td>110 Illustration Street</td>
						<td>Eastside</td>
						<td>Exampleton</td>
						<td>EX6 1FG</td>
						<td>07987 654321 (Mike)</td>
						<td>07789 456123 (Liz)</td>
						<td>mike@exampleinvest.com</td>
						<td>liz@exampleinvest.com</td>
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

			<?php } ?>

		</div>

		<input type="hidden" id="lastfocus" value="input_ref" />

	</body>
</html>
<?php } ?>