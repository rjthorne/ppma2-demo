<?php
include_once 'defs/db_connect.php';
include_once 'defs/functions.php';
require 'defs/inc_accountcheck.php';
 
if ($displaycontent == true) {

?>
<!DOCTYPE html>
<html>
	<head>
		<title>Landlord Report temp</title>
		<link rel="stylesheet" type="text/css" href="css/css.css" />
		<style type="text/css" media="print">
			<?php /*
			@page:first {size: auto; margin: 0mm 0mm 15mm 0mm;}
			@page:not(:first) {size: auto; margin: 15mm 0mm 15mm 0mm; }
			*/ ?>
			@page {size: auto; margin: 15mm 0mm 0mm 0mm;}
			@page:first {margin: 0mm 0mm 0mm 0mm;}
			table, .nobreak {page-break-inside: avoid;}
		</style>
		<script type="text/javascript" src="js/_jquery.js"></script>

	</head>
	<body id="llr">
		<?php  
			if (isset($_GET['d'])) {
				$q = $db->prepare("SELECT * FROM `landlords` WHERE `clientid` = $clientid AND `id` = ?");
				$q->execute(array($_GET['d']));
				$rc = $q->rowCount();
				if ($rc == 1) {
					$r = $q->fetch();
					// $d = $r['id'];
				} else {
					$q = $db->query("SELECT * FROM `landlords` WHERE `clientid` = $clientid ORDER BY `id` DESC LIMIT 1");
					$r = $q->fetch();
					// $d = $r['id'];
				}
			} else {
				$q = $db->query("SELECT * FROM `landlords` WHERE `clientid` = $clientid ORDER BY `id` DESC LIMIT 1");
				$r = $q->fetch();
				// $d = $r['id'];
			}
			
			$lid = $r['id']; //temp (?)

			if (isset($_GET['start'])) {
				if (strtotime($_GET['start']) != 0) {
					$start = date('j M Y', strtotime($_GET['start']));
				} else {
					$start = date('j M Y', strtotime("first day of previous month"));
				}
			} else {
				$start = date('j M Y', strtotime("first day of previous month"));
			}
			$dbstart = date('Y-m-d', strtotime($start));

			if (isset($_GET['end'])) {
				if (strtotime($_GET['end']) != 0) {
					$end = date('j M Y', strtotime($_GET['end']));
				} else {
					$end = date('j M Y', strtotime("last day of previous month"));
				}
			} else {
				$end = date('j M Y', strtotime("last day of previous month"));
			}			
			$dbend = date('Y-m-d', strtotime($end));

	
		
		?>
		<img id="reportheader" src="img/reportheaders/2.jpg" />
		
		
			<?php // ===========================================================================================   start copy ?>
			<div id="reportcont">
				<p id="reportaddress" ><?php echo "\n";
					echo "				".$r['name']."<br />\n";
					echo "				".$r['address1']."<br />\n";
					if (strlen(trim($r['address2'])) != 0 ) {
						echo "				".$r['address2']."<br />\n";
					}
					echo "				".$r['town']."<br />\n";
					echo "				".$r['postcode']."<br />\n";
				?>
				</p>
				<p id="reportdate">
					<?php echo date('j M Y', strtotime($end)) ?>
				</p>
				<h1>Landlord Report for <?php echo $r['name']?></h1>
				<input type="hidden" id="llname" value="<?php echo $r['name']?>" /><?php //omg gay
				if (!empty($r['sname'])) {
					$lastname = $r['sname'];
				} else {
					$namearr = explode(' ', strtolower($r['name']));
					$lastname = array_pop($namearr);						
				}		
				?>		
				<input type="hidden" id="invref" value="<?php echo 'lr-'.date('Ymd', strtotime($end)).'-'.$lastname; ?>" />
				<h2>Date range: <?php echo date('j M Y', strtotime($start)) ?> - <?php echo date('j M Y', strtotime($end)) ?></h2><?php echo "\n";
				$pbalances = array();
				

				$mcquery = $db->prepare("
					SELECT
						mgmtcontracts.id AS `id`,
						mgmtcontracts.propertyid AS `pid`,
						mgmtcontracts.startdate AS `sdate`,
						mgmtcontracts.enddate AS `edate`,
						mgmtcontracts.mgmt AS `mgmt`,
						mgmtcontracts.lease AS `lease`
					FROM `mgmtcontracts` LEFT JOIN `landlords` ON mgmtcontracts.landlordid = landlords.id
					WHERE landlords.id = ?
				");
				$mcquery->execute(array($lid));
				$mcr = $mcquery->fetchAll(PDO::FETCH_ASSOC);
				$mcps = array();	//properties array
				// p($mcr);
				$debugcount = 0;
				foreach ($mcr as $mc) {
					if (!array_key_exists($mc['pid'], $mcps)) {
						// echo $debugcount."<br>";
						$mcps[$mc['pid']] = array();	//each entry contains a subarray for each property
					}
					// echo "if (".strtotime($mc['sdate'])." <= ".strtotime($end)." && (".strtotime($mc['edate'])." >= ".strtotime($start)." || ".strtotime($mc['edate'])." == 0 ) )<br>";
					if (strtotime($mc['sdate']) <= strtotime($end) && (strtotime($mc['edate']) >= strtotime($start) || strtotime($mc['edate']) == 0 ) ) { // if (started yet AND (not finished OR no end date))
						// p($mcps);
						// p($mc);
						// echo $debugcount."<br>";
						$mcps[$mc['pid']][$mc['id']] = array(); //which in turn contains a subarray for each mgmtcontract IF it's in date
						$mcps[$mc['pid']][$mc['id']]['sdate'] = $mc['sdate'];
						$mcps[$mc['pid']][$mc['id']]['edate'] = $mc['edate'];
						$mcps[$mc['pid']][$mc['id']]['mgmt'] = $mc['mgmt'];
						$mcps[$mc['pid']][$mc['id']]['lease'] = $mc['lease'];
						// p($mcps);
					}
					// p($mcps);
					$debugcount++;
				}
				// p($mcps);

				$ptlarray = array();
				$pcount = 0;
				// p($mcps);
				foreach($mcps as $pid => $p) { 	//property loop FOR EACH [PORTFOLIO] AS [PROPERTY ID] [IN DATE MC ARRAY]
					if (count($p) > 0) {
						// p($p);
						// echo count($p);
						$pcount++; 
						foreach ($db->query("SELECT * FROM `properties` WHERE `id` = $pid") as $pinfo) {
							echo "					<hr /> \n";
							echo "					<div class=\"nobreak\">\n";
							echo "					<h3>".$pinfo['no']." ".$pinfo['street']."</h3>";
						}

						?>
						
						<table>
							<thead>
								<tr>
									<th colspan="3"><h4>Tenant payments</h4></th>
									<input type="hidden" class="pid" value="<?php echo $pid ?>" />
								</tr>
							</thead>
							<tbody>
							<?php $tptotal = 0;
							$tpstartbal = 0;
							// AND payments.date BETWEEN '$dbstart' AND '$dbend'
							// AND payments.date BETWEEN '$dbstart' AND '$dbend'
							// ==== NEW 5 JAN ====
							unset($mcstart_tp);
							unset($mcend_tp);

							foreach ($p as $mcid => $mc) {
							// p($mc);
								if (!isset($mcstart_tp)) {
									$mcstart_tp = $mc['sdate'];
								} else {
									if ($mc['sdate'] < $mcstart_tp) {
										$mcstart_tp = $mc['sdate'];
									}
								}
								if (!isset($mcend_tp)) {
									$mcend_tp = (strtotime($mc['edate']) > 0 ? $mc['edate'] : '9999-12-31');
								} else {
									if ($mc['edate'] > $mcend_tp) {
										$mcend_tp = (strtotime($mc['edate']) > 0 ? $mc['edate'] : '9999-12-31');
									}
								}	
							}
							if (!isset($mcstart_tp)) {
								$mcstart_tp = 0;
							}
							if (!isset($mcend_tp)) {
								$mcend_tp = '9999-12-31';
							}
							
							
							// echo $mcstart;
							// echo "<br>";
							// echo $mcend;
							

							// ==== NEW 5 JAN ====
							foreach ($db->query("
								(
									SELECT
										CONCAT ('rent payment') AS `desc`,
										tt.tenantname AS `name`,
										tt.tenantid AS `tenantid`,
										rooms.no AS `room`,
										payments.id AS `paymentid`,
										payments.date AS `date`,
										payments.amount AS `amount`
									FROM `payments`
										LEFT JOIN ((
											SELECT
												tenancies.id AS `tenancyid`,
												tenancies.roomid AS `roomid`,
												tenants.name AS `tenantname`,
												tenants.id AS `tenantid`,
												tenancies.startdate AS `startdate`
											FROM `tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) tt
											LEFT JOIN (`rooms` 
												LEFT JOIN `properties` 
												ON rooms.propertyid = properties.id)
											ON tt.roomid = rooms.id)
										ON payments.contactid = tt.tenancyid
									WHERE properties.id = $pid
									AND payments.contacttype = 't'

								) UNION (
									SELECT
										CONCAT ('fee: ', tenantfees.desc) AS `desc`,
										tt.tenantname AS `name`,
										tt.tenantid AS `tenantid`,
										rooms.no AS `room`,
										payments.id AS `paymentid`,
										payments.date AS `date`,
										payments.amount AS `amount`
									FROM `payments`	LEFT JOIN (
										`tenantfees` LEFT JOIN (
											(
												SELECT
													tenancies.id AS `tenancyid`,
													tenancies.roomid AS `roomid`,
													tenants.name AS `tenantname`,
													tenants.id AS `tenantid`,
													tenancies.startdate AS `startdate`
												FROM `tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id
											) tt LEFT JOIN (
												`rooms` LEFT JOIN `properties` ON rooms.propertyid = properties.id
											) ON tt.roomid = rooms.id
										) ON tenantfees.tenancyid = tt.tenancyid
									) ON payments.contactid = tenantfees.id
									WHERE properties.id = $pid
									AND payments.contacttype = 'f'
									AND tenantfees.payableto = 'l'

								)
								ORDER BY `date`
							") as $payment) {

								if ($payment['date'] >= $mcstart_tp && $payment['date'] <= $mcend_tp ) {		//mc range
									if ($payment['date'] >= $dbstart && $payment['date'] <= $dbend ) {		//report range
										$tptotal += $payment['amount'];
								?>
									<tr data-type="rent">
										<td class="date"><?php echo date('j M Y', strtotime($payment['date']))?></td>
										<td><?php echo "Room ".$payment['room']." - <a href=\"reports.php?s=tenant&d=".$payment['tenantid']."\">".$payment['name']."</a> (".$payment['desc'].")" ?></td>
										<td class="amount"><a href="bank.php?s=detail&d=<?php echo $payment['paymentid'] ?>"><?php echo number_format($payment['amount'] / 100, 2, '.', ',') ?></a></td>
									</tr>
								<?php 
									} else if ($payment['date'] < $dbstart) {
										$tpstartbal += $payment['amount'];
									}
								} else if ($payment['date'] < $mcstart_tp) {		//fixing bernhard error
									$tpstartbal += $payment['amount'];
								}

							} ?>
							</tbody>
							<tfoot>
								<tr>
									<td class="total date"><?php echo date('j M Y', strtotime($end))?></td>
									<td class="total">Total tenant payments:</td>
									<td class="total amount"><?php echo number_format($tptotal / 100, 2, '.', ',') ?></td>
								</tr>
							</tfoot>
						</table>
						</div>

						<br />

						<p class="nobreak">
						<table>
							<thead>
								<tr>
									<th colspan="3"><h4>Management fees</h4></th>
									<input type="hidden" class="pid" value="<?php echo $pid ?>" />
								</tr>
							</thead>
							<tbody>
							<?php  
							// p($p);
							$mgmttotal = 0;
							$mgmtstartbal = 0;
							foreach ($p as $mcid => $mc) {
								if ($mc['mgmt'] != 100) {	//@@@@@@@@@to come back to leases
									$mcstart = $mc['sdate'];
									$mcend = (strtotime($mc['edate']) > 0 ? $mc['edate'] : '9999-12-31');
									$mcpaymenttotal = 0;
									$mcpaymentstartbal = 0;
									// echo $mcstart;
									// echo "<br>";
									// echo $mcend;
									foreach ($db->query("
										SELECT
											payments.date AS `date`,
											payments.amount AS `amount`
										FROM `payments`
											LEFT JOIN ((
												SELECT
													tenancies.id AS `tenancyid`,
													tenancies.roomid AS `roomid`
												FROM `tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) tt
												LEFT JOIN (`rooms` 
													LEFT JOIN `properties` 
													ON rooms.propertyid = properties.id)
												ON tt.roomid = rooms.id)
											ON payments.contactid = tt.tenancyid
										WHERE properties.id = $pid
										AND payments.contacttype = 't'
										AND (payments.date BETWEEN '$mcstart' AND '$mcend')
									") as $payment) {
										if ($payment['date'] >= $dbstart && $payment['date'] <= $dbend ) {		//report range
											$mcpaymenttotal += $payment['amount'] * $mc['mgmt'] / 100;
										} else if ($payment['date'] < $dbstart) {
											// $mcpaymentstartbal += $payment['amount'] * $mc['mgmt'] / 100;	//does not look for MCs before report date, new function after p loop
										}
									}
									
							
								

									?>
								<tr data-type="man">
									<td class="date"><?php echo date('j M Y', strtotime($end))?></td>
									<td>Management fees at <?php echo $mc['mgmt'] ?>% on rent taken</td>
									<td class="amount"><?php echo number_format($mcpaymenttotal / 100 * -1, 2, '.', ',') ?></td>
								</tr>
									<?php

									// ADJUSTMENTS
									foreach ($db->query("
										SELECT
											adjustments.date AS `date`,
											adjustments.amount AS `amount`,
											adjustments.desc AS `desc`,
											tt.tenantname AS `tenantname`
										FROM `adjustments`
											LEFT JOIN ((
												SELECT
													tenancies.id AS `tenancyid`,
													tenants.name AS `tenantname`,
													tenancies.roomid AS `roomid`
												FROM `tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) tt
												LEFT JOIN (`rooms` 
													LEFT JOIN `properties` 
													ON rooms.propertyid = properties.id)
												ON tt.roomid = rooms.id)
											ON adjustments.tenancyid = tt.tenancyid
										WHERE properties.id = $pid
										AND (adjustments.date BETWEEN '$mcstart' AND '$mcend')
										AND adjustments.applymgmt = 'y'
										ORDER BY adjustments.date
									") as $adjustment) {
										if ($adjustment['date'] >= $dbstart && $adjustment['date'] <= $dbend ) {		//report range
								?>		
								<tr data-type="man">
									<td class="date"><?php echo date('j M Y', strtotime($adjustment['date']))?></td>
									<td>Management fee at <?php echo $mc['mgmt'] ?>% on adjustment: <?php echo $adjustment['tenantname'] ?> - <?php echo $adjustment['desc'] ?> </td>
									<td class="amount"><?php echo number_format($adjustment['amount'] * $mc['mgmt'] / 100 / 100 * -1, 2, '.', ',') ?></td>
								</tr>									
								<?php		
											$mcpaymenttotal += $adjustment['amount'] * $mc['mgmt'] / 100;
										} else if ($adjustment['date'] < $dbstart) {
											// $mcpaymentstartbal += $adjustment['amount'] * $mc['mgmt'] / 100;		//does not look for MCs before report date, new function after p loop
										}
									}										
									$mgmttotal += $mcpaymenttotal;
									// $mgmtstartbal += $mcpaymentstartbal;			//does not look for MCs before report date, new function after p loop					
								}
							}	
							
							//new mc startbal function
							$allrentpayments = array();
							foreach ($db->query("
								SELECT
									payments.date AS `date`,
									payments.amount AS `amount`
								FROM `payments`
									LEFT JOIN ((
										SELECT
											tenancies.id AS `tenancyid`,
											tenancies.roomid AS `roomid`
										FROM `tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) tt
										LEFT JOIN (`rooms` 
											LEFT JOIN `properties` 
											ON rooms.propertyid = properties.id)
										ON tt.roomid = rooms.id)
									ON payments.contactid = tt.tenancyid
								WHERE properties.id = $pid
								AND payments.contacttype = 't'
							") as $propertyrentpayment) {		//this gives me a list of all payments against the property, need to save it as an array then loop through all mgmtcontracts checking the array
								$allrentpayments[] = $propertyrentpayment;
							}
							// p($allrentpayments);
							
							$alladjustments = array();
							foreach ($db->query("
								SELECT
									adjustments.date AS `date`,
									adjustments.amount AS `amount`,
									adjustments.desc AS `desc`,
									tt.tenantname AS `tenantname`
								FROM `adjustments`
									LEFT JOIN ((
										SELECT
											tenancies.id AS `tenancyid`,
											tenants.name AS `tenantname`,
											tenancies.roomid AS `roomid`
										FROM `tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) tt
										LEFT JOIN (`rooms` 
											LEFT JOIN `properties` 
											ON rooms.propertyid = properties.id)
										ON tt.roomid = rooms.id)
									ON adjustments.tenancyid = tt.tenancyid
								WHERE properties.id = $pid
								AND adjustments.applymgmt = 'y'
							") as $propertyadjustment) {
								$alladjustments[] = $propertyadjustment;
							}
							
							//	this next bit originally had:
							//	WHERE landlords.id = ".$r.['id']."
							//	...at the end. taken it out as it might mess some other balances out, to access and possibly correct when we get a property transfer
							foreach ($db->query("
								SELECT
									mp.startdate AS `startdate`,
									mp.enddate AS `enddate`,
									mp.mgmt AS `mgmt`
								FROM (
									SELECT 
										mgmtcontracts.landlordid AS `landlordid`,
										mgmtcontracts.startdate AS `startdate`,
										mgmtcontracts.enddate AS `enddate`,
										mgmtcontracts.mgmt AS `mgmt`
									FROM `mgmtcontracts` LEFT JOIN `properties` ON mgmtcontracts.propertyid = properties.id WHERE properties.id = $pid
								) mp LEFT JOIN `landlords` ON mp.landlordid = landlords.id
							") as $mcrentpaymentcheck) {
								// echo "meep";
								if (empty($mcrentpaymentcheck['enddate'])) {
									$mcrentpaymentcheck['enddate'] = '9999-12-31';
								}
								foreach ($allrentpayments as $propertyrentpayment) {
									if ($propertyrentpayment['date'] >= $mcrentpaymentcheck['startdate'] && $propertyrentpayment['date'] <= $mcrentpaymentcheck['enddate'] && $propertyrentpayment['date'] < $dbstart) {
										if ($mcrentpaymentcheck['mgmt'] != 100) {
											$mcpaymentstartbal += $propertyrentpayment['amount'] * $mcrentpaymentcheck['mgmt'] / 100; 
										}
									}
								}
								foreach ($alladjustments as $propertyadjustment) {
									if ($propertyadjustment['date'] >= $mcrentpaymentcheck['startdate'] && $propertyadjustment['date'] <= $mcrentpaymentcheck['enddate'] && $propertyadjustment['date'] < $dbstart) {
										if ($mcrentpaymentcheck['mgmt'] != 100) {
											$mcpaymentstartbal += $propertyadjustment['amount'] * $mcrentpaymentcheck['mgmt'] / 100; 
										}
									}								
								}
							}
							// p($mcrentpaymentcheck);
							$mgmtstartbal += $mcpaymentstartbal;
							
							?>
							</tbody>
							<tfoot>
								<tr>
									<td class="total date"><?php echo date('j M Y', strtotime($end))?></td>
									<td class="total">Total management fee deduction:</td>
									<td class="total amount"><?php echo number_format($mgmttotal / 100 * -1, 2, '.', ','); ?></td>
								</tr>
							</tfoot>
						</table>
						</p>

						

						<p class="nobreak">
						<table>
							<thead>
								<tr>
									<th colspan="3"><h4>Letting fees</h4></th>
									<input type="hidden" class="pid" value="<?php echo $pid ?>" />
								</tr>
							</thead>
							<tbody>
							<?php $lftotal = 0; $lfstartbal = 0;
							/* foreach ($db->query("SELECT * FROM `propertyfees` WHERE `propertyid` = $pid AND type = 'l' AND `date` between '$dbstart' AND '$dbend' ORDER BY `date`") as $lfee) { */
							foreach ($db->query("
								SELECT 
									propertyfees.id AS `id`,
									propertyfees.date AS `date`,
									CONCAT(properties.no, ' ', properties.street) AS `property`,
									propertyfees.amount AS `amount`,
									propertyfees.desc AS `desc`
								FROM `propertyfees` LEFT JOIN (`mgmtcontracts` LEFT JOIN `properties` ON mgmtcontracts.propertyid = properties.id) ON propertyfees.mcid = mgmtcontracts.id
								WHERE properties.id = $pid
								AND propertyfees.type = 'l'
								ORDER BY propertyfees.date
							") as $lfee) {	// @@@@@@@@@@@@@@@@@@@@ NEED TO ADD THE $mcstart_tp / $mcend_tp check in here! @@@@@@@@@@@@@@@@@@@@
								if ($lfee['date'] >= $dbstart && $lfee['date'] <= $dbend) {
									$lftotal += $lfee['amount'];
							?>
								<tr data-type="let">
									<td class="date"><?php echo date('j M Y', strtotime($lfee['date']))?></td>
									<td><?php echo $lfee['desc'] ?></td>
									<td class="amount"><?php echo number_format($lfee['amount'] / 100 * -1, 2, '.', ',') ?></td></td>
								</tr>
							<?php
								} else if ($lfee['date'] < $dbstart) {
									$lfstartbal += $lfee['amount'];
								}
							} ?>
							</tbody>
							<tfoot>
								<tr>
									<td class="total date"><?php echo date('j M Y', strtotime($end))?></td>
									<td class="total">Total letting fees:</td>
									<td class="total amount"><?php echo number_format($lftotal / 100 * -1, 2, '.', ',') ?></td>
								</tr>
							</tfoot>
						</table>
						</p>

						

						<p class="nobreak">
						<table>
							<thead>
								<tr>
									<th colspan="3"><h4>Other fees & expenses</h4></th>
									<input type="hidden" class="pid" value="<?php echo $pid ?>" />
								</tr>
							</thead>
							<tbody>
							<?php $ftotal = 0; $fstartbal = 0;
							/* foreach ($db->query("SELECT * FROM `propertyfees` WHERE `propertyid` = $pid AND type = 'o' AND `date` between '$dbstart' AND '$dbend' ORDER BY `date`") as $fee) { */
							foreach ($db->query("
								SELECT 
									propertyfees.id AS `id`,
									propertyfees.date AS `date`,
									propertyfees.amount AS `amount`,
									propertyfees.desc AS `desc`,
									propertyfees.type AS `type`
								FROM `propertyfees` LEFT JOIN (`mgmtcontracts` LEFT JOIN `properties` ON mgmtcontracts.propertyid = properties.id) ON propertyfees.mcid = mgmtcontracts.id
								WHERE properties.id = $pid
								AND (propertyfees.type = 'o' OR propertyfees.type = 'm')
								ORDER BY propertyfees.date
							") as $fee) {	// @@@@@@@@@@@@@@@@@@@@ NEED TO ADD THE $mcstart_tp / $mcend_tp check in here! @@@@@@@@@@@@@@@@@@@@
								if ($fee['date'] >= $dbstart && $fee['date'] <= $dbend) {
								$ftotal += $fee['amount'];
							?>
								<tr data-type="<?php echo ($fee['type'] == 'm' ? 'main' : 'other') ?>">
									<td class="date"><?php echo date('j M Y', strtotime($fee['date']))?></td>
									<td><?php echo $fee['desc'] ?></td>
									<td class="amount"><?php echo number_format($fee['amount'] / 100 * -1, 2, '.', ',') ?></td></td>
								</tr>
							<?php
								} else if ($fee['date'] < $dbstart) {
									$fstartbal += $fee['amount'];
								}
							} ?>
							</tbody>
							<tfoot>
								<tr>
									<td class="total date"><?php echo date('j M Y', strtotime($end))?></td>
									<td class="total">Total other fees & expenses:</td>
									<td class="total amount"><?php echo number_format($ftotal / 100 * -1, 2, '.', ',') ?></td>
								</tr>
							</tfoot>
						</table>
						</p>

						<br />
						<?php 	//landlord payments here so we can use them for start balances
						$llpayments = 0; $llpaystartbal = 0;
						foreach ($db->query("
							SELECT
								payments.amount AS `amount`,
								payments.date AS `date`
							FROM `payments` LEFT JOIN (
								(
									SELECT
										mgmtcontracts.id AS `mcid`,
										mgmtcontracts.landlordid AS `lid`,
										properties.id AS `pid`
									FROM `mgmtcontracts` LEFT JOIN `properties` ON mgmtcontracts.propertyid = properties.id
								) mp LEFT JOIN `landlords` ON mp.lid = landlords.id
							) ON payments.contactid = mp.mcid
							WHERE payments.contacttype = 'l'
							AND landlords.id = $lid
							AND mp.pid = $pid
						") as $llpayment) {
							if ($llpayment['date'] >= $dbstart && $llpayment['date'] <= $dbend) {
								$llpayments += $llpayment['amount'];
							} else if ($llpayment['date'] < $dbstart) {
								$llpaystartbal += $llpayment['amount'];
							}
						}
						?>
						<table>

							<tr>
								<td class="borderless total alignright" colspan="2">Property total income:</td>
								<td class="borderless total amount"><?php echo number_format($tptotal / 100, 2, '.', ',') ?></td>
							</tr>
							<tr>
								<td class="borderless total alignright" colspan="2">Property total deductions:</td>
								<td class="borderless total amount"><?php echo number_format((($mgmttotal / 100) + ($lftotal / 100) + ($ftotal / 100)) * -1 , 2, '.', ',') ?></td>
							</tr>
							<tr>
								<td class="borderless total alignright" colspan="2">Property report total:</td>
								<td class="borderless total amount"><?php
								$prb = round(($tptotal / 100) - ($mgmttotal / 100) - ($lftotal / 100) - ($ftotal / 100), 2);
								echo number_format($prb, 2, '.', ',') ?> <br /><br /></td>
							</tr>
							<tr>
								<td class="borderless total alignright" colspan="2">Property opening balance:</td>
								<td class="borderless total amount"><?php
								// $pob = 610.24; //dave
								// $pob = 1388.99; //andrew
								// $pob = 1489.00; //niraj
								// $pob = 166372; //tsen
								$pob = round($tpstartbal - $mgmtstartbal - $lfstartbal - $fstartbal + $llpaystartbal);
								// echo "$tpstartbal - $mgmtstartbal - $lfstartbal - $fstartbal + $llpaystartbal";
								echo number_format($pob / 100, 2, '.', ',');

								?></td>
							</tr>
							<tr>
								<td class="borderless total alignright" colspan="2">Less payments made to Landlord:</td>
								<td class="borderless total amount"><?php 

								echo number_format($llpayments / 100, 2, '.', ',');

								?></td>
							</tr>
							<tr>
								<td class="borderless total alignright" colspan="2">Payable to Landlord:</td>
								<td class="borderless total amount"><?php 
								$ptl = ($pob / 100) + $prb + ($llpayments / 100);
								echo number_format($ptl, 2, '.', ',');
								$ptlarray[$pinfo['no']." ".$pinfo['street']] = number_format($ptl, 2, '.', '');
								?></td>
							</tr>
						</table>

						
						

						<?php
						// echo "prb: ".$prb." <br>";
						// echo "pob: ".$pob." <br>";
						// echo "llp: ".$llpayments." <br>";

						// d(($tptotal / 100) ." ". ($mgmttotal / 100) ." ". ($lftotal / 100) ." ". ($ftotal / 100));

						$pbalance = array($pinfo['no']." ".$pinfo['street'], ($tptotal / 100) - ($mgmttotal / 100) - ($lftotal / 100) - ($ftotal / 100));
						$pbalances[] = $pbalance;
					}
				}	// property loop

				if ($pcount > 1) { ?>

				<table style="display:none"> 	<!-- depreciated, being lazy here -->
					<tr>
						<th colspan="3"><h4>Total balances</h4></th>
					</tr>
					<?php $cbalance = 0;
					foreach ($pbalances as $pbalance) { ?>
					<tr>
						<td class="date"><?php echo date('j M Y', strtotime($end))?></td>
						<td><?php echo $pbalance[0] ?></td>
						<td class="amount"><?php echo number_format($pbalance[1], 2, '.', ',') ?></td>
					</tr>
					<?php  $cbalance += $pbalance[1]; }	?>
					<tr>
						<td class="total date"><?php echo date('j M Y', strtotime($end))?></td>
						<td class="total">Cumulative balance:</td>
						<td class="total amount"><?php echo number_format($cbalance, 2, '.', ',') ?></td>
					</tr>
				</table>						<!-- end of depreciated table -->
				
				<table>
					<tr>
						<th colspan="3"><h4>Total payable to landlord</h4></th>
					</tr>
					<?php $tptl = 0;
					foreach ($ptlarray as $ptlprop => $ptlamount) { ?>
					<tr>
						<td class="date"><?php echo date('j M Y', strtotime($end))?></td>
						<td><?php echo $ptlprop ?></td>
						<td class="amount"><?php echo number_format($ptlamount, 2, '.', ',') ?></td>
					</tr>
					<?php  $tptl += $ptlamount; }	?>		
					<tr>
						<td class="total date"><?php echo date('j M Y', strtotime($end))?></td>
						<td class="total">Total:</td>
						<td class="total amount"><?php echo number_format($tptl, 2, '.', ',') ?></td>
					</tr>					
				</table>

				<br />

				<table style="display:none"> <!-- depreciated, being lazy here -->
					<tr>
						<td class="borderless total alignright" colspan="2">Total payable to Landlord:</td>
						<td class="borderless total amount"><?php echo number_format($cbalance, 2, '.', ',') ?></td>
					</tr>
				</table>
				<?php } ?>
			</div>
			<?php // =================================================================================================== end copy ?>

		
		

		<script type="text/javascript">
			setTimeout(function() {
				document.title = $('#invref').val();
				window.print();
			}, 2000);
			
		</script>			
			
	</body>
</html>
<?php } ?>