<?php
include_once 'defs/db_connect.php';
include_once 'defs/functions.php';
require 'defs/inc_accountcheck.php';
 
if ($displaycontent == true) {

?>
<!DOCTYPE html>
<html>
	<head>
		<title>Tenant Statement</title>
		<link rel="stylesheet" type="text/css" href="css/css.css" />
		<style type="text/css" media="print">
			<?php /*
			@page:first {size: auto; margin: 0mm 0mm 15mm 0mm;}
			@page:not(:first) {size: auto; margin: 15mm 0mm 15mm 0mm; }
			*/ ?>
			@page {size: auto; margin: 15mm 0mm 0mm 0mm;}
			@page:first {margin: 0mm 0mm 0mm 0mm;}
			tr, .nobreak {page-break-inside: avoid;}
		</style>
		<script type="text/javascript" src="js/_jquery.js"></script>

	</head>
	<body id="llr">
		<?php  
			if (isset($_GET['d'])) {
				$q = $db->prepare("SELECT * FROM `tenants` WHERE `clientid` = $clientid AND `id` = ?");
				$q->execute(array($_GET['d']));
				$rc = $q->rowCount();
				if ($rc == 1) {
					$r = $q->fetch();
				} else {
					$q = $db->query("SELECT * FROM `tenants` WHERE `clientid` = $clientid ORDER BY `id` DESC LIMIT 1");
					$r = $q->fetch();
				}
			} else {
				$q = $db->query("SELECT * FROM `tenants` WHERE `clientid` = $clientid ORDER BY `id` DESC LIMIT 1");
				$r = $q->fetch();
			}

			$tid = $r['id'];
			$tname = $r['name'];

			if (isset($_GET['t'])) {
				$q = $db->prepare("SELECT * FROM `tenancies` WHERE `tenantid` = $tid AND `id` = ?");
				$q->execute(array($_GET['t']));
				$rc = $q->rowCount();
				if ($rc == 1) {
					$r = $q->fetch();
					$tstart = $r['startdate'];
				} else {
					$q2 = $db->query("SELECT * FROM `tenancies` WHERE `tenantid` = $tid");
					$r2 = $db->fetchAll();
					foreach ($r2 as $row) {
						if (!isset($earliesttenancy)) {
							$earliesttenancy = $row['startdate'];
							$earliesttenancyid = $row['id'];
							$earliesttenancyobal = $row['obal'];
							$earliesttenancyobaldate = $row['obaldate'];
						} else {
							if ($row['startdate'] < $earliesttenancy) {
								$earliesttenancy = $row['startdate'];
								$earliesttenancyid = $row['id'];
								$earliesttenancyobal = $row['obal'];
								$earliesttenancyobaldate = $row['obaldate'];
							}
						}
					}
					if (isset($earliesttenancy)) {
						$tstart = $earliesttenancy;
					} else {
						$tstart = date('Y-m-d');
					}
				}
			} else {
				$q2 = $db->prepare("SELECT * FROM `tenancies` WHERE `tenantid` = $tid");
				$q2->execute();
				$r2 = $q2->fetchAll(PDO::FETCH_ASSOC);
				// p($r2);
				foreach ($r2 as $row) {
					if (!isset($earliesttenancy)) {
						$earliesttenancy = $row['startdate'];
						$earliesttenancyid = $row['id'];
						$earliesttenancyobal = $row['obal'];
						$earliesttenancyobaldate = $row['obaldate'];
					} else {
						if ($row['startdate'] < $earliesttenancy) {
							$earliesttenancy = $row['startdate'];
							$earliesttenancyid = $row['id'];
							$earliesttenancyobal = $row['obal'];
							$earliesttenancyobaldate = $row['obaldate'];
						}
					}
				}
				if (isset($earliesttenancy)) {
					$tstart = $earliesttenancy;
				} else {
					$tstart = date('Y-m-d');
				}
				$_GET['t'] = 0; //so hacky
			}

			if (isset($_GET['start'])) {
				if (strtotime($_GET['start']) != 0) {
					$start = date('j M Y', strtotime($_GET['start']));
				} else {
					$start = date('j M Y', strtotime($tstart));
				}
			} else {
				$start = date('j M Y', strtotime($tstart));
			}
			$dbstart = date('Y-m-d', strtotime($start));

			if (isset($_GET['end'])) {
				if (strtotime($_GET['end']) != 0) {
					$end = date('j M Y', strtotime($_GET['end']));
				} else {
					$end = date('j M Y');
				}
			} else {
				$end = date('j M Y');
			}
			$dbend = date('Y-m-d', strtotime($end));

	
		
		?>
		<img id="reportheader" src="img/reportheaders/2.jpg" />
		<input type="hidden" id="tname" value="<?php echo $tname ?>" />
		
		<?php

			$pq = $db->prepare("
				(
					SELECT
						CONCAT ('payment') AS `type`,
						CONCAT ('payment') AS `link`,
						payments.id AS `id`,
						payments.date AS `date`,
						payments.amount AS `amount`,
						CONCAT ('Payment: rent') AS `desc`,
						tenancies.id AS `tenancy`
					FROM `payments` LEFT JOIN (`tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) ON payments.contactid = tenancies.id
					WHERE payments.contacttype = 't'
					AND tenants.id = $tid
				) UNION (
					SELECT
						CONCAT ('payment') AS `type`,
						CONCAT ('payment') AS `link`,
						payments.id AS `id`,
						payments.date AS `date`,
						payments.amount AS `amount`,
						CONCAT ('Payment: ', tenantfees.desc) AS `desc`,
						tenancies.id AS `tenancy`
					FROM `payments` LEFT JOIN (`tenantfees` LEFT JOIN (`tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) ON tenantfees.tenancyid = tenancies.id) ON payments.contactid = tenantfees.id
					WHERE payments.contacttype = 'f'
					AND tenants.id = $tid
				)
			");

			$pq->execute();
			$apr = $pq->fetchAll(PDO::FETCH_ASSOC);	//all payments, all tenancies
			$pr = array();
			if (isset($_GET['t']) && $_GET['t'] != 0) {
				$tenancycheck = $db->prepare("SELECT * FROM `tenancies` WHERE `id` = ? AND `tenantid` = $tid");
				$tenancycheck->execute(array($_GET['t']));
				$rc = $tenancycheck->rowCount();
				if ($rc == 1) {
					$tcr = $tenancycheck->fetch();
					foreach ($apr as $ap) {
						if ($ap['tenancy'] == $tcr['id']){
							$pr[] = $ap;
						}
					}
				} else {
					$pr = $apr;
				}
			} else {
				$pr = $apr;
			}

			if (!isset($pr)) {
				$pr = $apr;
			}

			//pre-sort ensures payments always come first when matching a due date - tenants paying on time shouldn't have negative balances:

			function pre(array $a, array $b) { 
				if ($a['type'] == 'due') {
					return 1;
				} else {
					return -1;
				}
			}
			
			// sort by date

			function cmp(array $a, array $b) {
				if ($a['date'] < $b['date']) {
					return -1;
				} else if ($a['date'] > $b['date']) {
					return 1;
				} else {
					return 1;
				}
			}

			usort($pr, 'cmp');

			// p($pr);		// sorted array of actual payments. could have done this in SQL but just practising


			if (isset($_GET['t']) && $_GET['t'] != 0) {
				$tq = $db->prepare("SELECT * FROM `tenancies` WHERE `tenantid` = $tid AND `id` = ?");
				$tq->execute(array($_GET['t']));
			} else {
				$tq = $db->prepare("SELECT * FROM `tenancies` WHERE `tenantid` = $tid");
				$tq->execute();

			}
			$tr = $tq->fetchAll(PDO::FETCH_ASSOC);

			$due = array();

			foreach ($tr as $t) {
				if (is_null($t['enddate'])) {
					$t['enddate'] = '9999-12-31';
				}
				$duedate = $t['startdate'];

				if ($t['period'] == 'W') {
					$period = '1 week';
					$dayrate = $t['rent'] / 7;
				} else if ($t['period'] == 'F') {
					$period = '2 weeks';
					$dayrate = $t['rent'] / 14;
				} else if ($t['period'] == '4') {
					$period = '4 weeks';
					$dayrate = $t['rent'] / 28;
				} else {
					$period = '1 month';
					$dayrate = $t['rent'] * 12/365;
				}
				
				//due step 1 - rent due based on period

				while ($duedate <= $dbend && $duedate <= $t['enddate']) {
					
					$nextduedate = date('Y-m-d', strtotime('+'.$period, strtotime($duedate)));
					if ($period == '1 month') {
						if (date('d', strtotime($nextduedate)) !== date('d', strtotime($t['startdate']))) {	//day mismatch
							if (date('d', strtotime($nextduedate)) < 5) {	//jumped to start of next month
								// echo "1: ".$nextduedate;
								$nextduedate = date('Y-m-d', strtotime('last day of previous month', strtotime($nextduedate)));
								// echo "; 2: ".$nextduedate;
							} else {	//dropped days
								$correctday = date('d', strtotime($t['startdate']));
								$correctmonth = date('m', strtotime($nextduedate));
								$correctyear = date('Y', strtotime($nextduedate));
								$nextduedate = $correctyear.'-'.$correctmonth.'-'.$correctday;
							}
						}
					}

					// echo "Comparing ".date('Y-m-d', strtotime($duedate.' +'.$period.' -1 day'))." to ".$t['enddate']."<br>";
					
					if (date('Y-m-d', strtotime($nextduedate.' -1 day')) <= $t['enddate']) {
						$due[] = array(
							'type' => 'due',
							'link' => 'tenant',
							'id' => $t['id'],
							'date' => $duedate,
							'amount' => $t['rent'],
							'desc' => 'Rent due ('.date('j M Y', strtotime($duedate)).' - '.date('j M Y', strtotime($nextduedate.' -1 day')).')',
							'tenancy' => $t['id']
						);
					} else {
						$daysremaining = floor((strtotime('+1 day', strtotime($t['enddate'])) - strtotime($duedate)) / (60*60*24));
						// echo "Days remaining: ".$daysremaining;
						$due[] = array(
							'type' => 'due',
							'link' => 'tenant',
							'id' => $t['id'],
							'date' => $duedate,
							'amount' => round($daysremaining * $dayrate),
							'desc' => 'Rent due ('.date('j M Y', strtotime($duedate)).' - '.date('j M Y', strtotime($t['enddate'])).')',
							'tenancy' => $t['id']
						);
					}			
					$duedate = $nextduedate;
				}
				
				//due step 2 - tenant fees

				foreach ($db->query("SELECT * FROM `tenantfees` LEFT JOIN `tenancies` ON tenantfees.tenancyid = tenancies.id WHERE tenancies.id = ".$t['id']." AND tenantfees.date BETWEEN '$dbstart' AND '$dbend'") as $tf) {
					$due[] = array(
						'type' => 'due',
						'link' => 'tenantfees',
						'id' => $tf['id'],
						'date' => $tf['date'],
						'amount' => $tf['amount'],
						'desc' => 'Fee/expense: '.$tf['desc']
					);
				}
				
				//due step 3 - adjustments
				
				foreach ($db->query("SELECT * FROM `adjustments` LEFT JOIN `tenancies` ON adjustments.tenancyid = tenancies.id WHERE tenancies.id = ".$t['id']." AND adjustments.date BETWEEN '$dbstart' AND '$dbend'") as $a) {
					$due[] = array(
						'type' => 'due',
						'link' => 'adjustments',
						'id' => $a['id'],
						'date' => $a['date'],
						'amount' => $a['amount'] * -1,
						'desc' => 'Adjustment: '.$a['desc']
					);
				}				

			}
			usort($due, 'cmp');

			// p($due);
			// echo "<hr>";
			?>
			<div id="reportcont">
				<h1>Tenant statement for <a href="tenants.php?s=detail&d=<?php echo $tid ?>"><?php echo $tname?></a></h1>
				<h2>Date range: <?php echo date('j M Y', strtotime($start)) ?> - <?php echo date('j M Y', strtotime($end)) ?></h2>
				<?php // opening balance
				
				if (isset($earliesttenancyobal)) {
					$bal = $earliesttenancyobal;
					$odate = date('Y-m-d', strtotime($earliesttenancyobaldate));
				} else {
					$bal = 0;
					$odate = '1970-01-01';
				}
				
				
				foreach ($pr as $p) {
					if ($p['date'] < $dbstart && $p['date'] >= $odate) {
						$bal += $p['amount'];
					}
				}
				foreach ($due as $d) {
					if ($d['date'] < $dbstart && $d['date'] >= $odate) {
						$bal -= $d['amount'];
					}
				}

				?>
				<h3>Statement opening balance: <?php echo number_format($bal / 100, 2, '.', ',') ?></h3>

				<br />
				<table>
					<thead>
						<tr>
							<th><h4>Date</h4></th>
							<th><h4>Description</h4></th>
							<th class="alignright"><h4>Due</h4></th>
							<th class="alignright"><h4>Paid</h4></th>
							<th class="alignright"><h4>Balance</h4></th>
						</tr>
					</thead>
					</tbody><?php echo "\n";
					$list = array();
					foreach ($pr as $p) {
						if ($p['date'] >= $dbstart && $p['date'] <= $dbend) {
							$list[] = $p;
						}
					}
					foreach ($due as $d) {
						if ($d['date'] >= $dbstart && $d['date'] <= $dbend) {
							$list[] = $d;
						}
					}
					// usort($list, 'pre');
					usort($list, 'cmp');
					
					// p($list);
					
					$listcount = 0;
					foreach ($list as $li) {
						if ($li['date'] >= $odate) {
							//@@@@@@@@@ look at link value, assign link to page location and give desc <a> tags
							echo "						<tr>\n";
							echo "							<td>".date('j M Y', strtotime($li['date']))."</td>";
							if ($li['link'] == 'payment') {
								echo "							<td><a href=\"bank.php?s=detail&d=".$li['id']."\">".$li['desc']."</a></td>";
							} else {
								echo "							<td>".$li['desc']."</td>";
							}
							echo "							".($li['type'] == 'due' ? "<td class=\"alignright\">".number_format($li['amount'] / 100, 2, '.', ',')."</td>\n" : "<td>&nbsp</td>\n");
							echo "							".($li['type'] == 'payment' ? "<td class=\"alignright\">".number_format($li['amount'] / 100, 2, '.', ',')."</td>\n" : "<td>&nbsp</td>\n");
							echo "							".($li['type'] == 'due' ? "<td class=\"alignright\">".number_format(($bal - $li['amount']) / 100, 2, '.', ',')."</td>\n" : "<td class=\"alignright\">".number_format(($bal + $li['amount']) / 100, 2, '.', ',')."</td>\n");
							echo "						</tr>\n";
							if ($li['type'] == 'due') {
								$bal -= $li['amount'];
							} else {
								$bal += $li['amount'];
							}
						}
						// $li['bal'] = $bal;	//feeding balance back into array for arrears calculation - php doesn't assign variables this way, see next line
						$list[$listcount]['bal'] = $bal;
						$listcount++;
					}
			?>
					</tbody>
				</table>
				<h3>Statement closing balance: <?php echo number_format($bal / 100, 2, '.', ',') ?></h3>
				<?php 
				$list = array_reverse($list);
				
				// p($list);
				
				$whilebal = $list[0]['bal'];
				$whilecount = 0;
				while ($whilebal < 0 && $whilecount < 1000) {	//debugging
					$whilebal = $list[$whilecount]['bal'];
					if ($whilebal < 0) {
						$arrearsdate = new DateTime($list[$whilecount]['date']);
					}
					if (array_key_exists($whilecount + 1, $list)) {
						if ($list[$whilecount + 1]['date'] >= $odate) {
							$whilecount++;
						} else {
							$whilebal = 0;
						}
					} else {
						$whilebal = 0;
					}
				}
				// echo $arrearsdate;
				$curdate = new DateTime(date('Y-m-d'));
				if (!isset($arrearsdate)) {
					$arrearsdate = new DateTime(date('Y-m-d'));
				}
				$daysinarrears = $arrearsdate->diff($curdate);
				
				?>
				<h3>Days in arrears (current date): <?php echo $daysinarrears->days ?></h3>

			</div>

		
		

		<script type="text/javascript">
			setTimeout(function() {
				document.title = "Tenant statement for "+$('#tname').val();
				window.print();
			}, 2000);
			
		</script>			
			
	</body>
</html>
<?php } ?>