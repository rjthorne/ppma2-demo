<?php 

/*
// require('mysqldump.php');

$dumpSettings = array(
    'include-tables' => array('config', 'list'),
    'no-data' => false,            
    'add-drop-table' => true,      
    'single-transaction' => false,   
    'lock-tables' => false,        
    'add-locks' => true,            
    'extended-insert' => true      
);

// $dump = new Ifsnop\Mysqldump\MySQLDump(DB,USER,PASS,'localhost', $dumpSettings);
// $dump->start('db/backup/mysqldump_'.date('Y-m-d-H').'.sql');

foreach (glob("db/backup/*") as $file) {
	if (filemtime($file) < time() - 1209600) {
		unlink($file);
	}
}
*/

?><div id="header">
			<div id="innerheader">
				<img src="img/loading2.gif" style="display:none" />
				<img src="img/ppma24.png" id="ppma" />
				<div id="topbar">
					Logged in as <?php
						// echo "<strong>".htmlentities($_SESSION['username'])."</strong> @ <strong>".($usertype == "a" ? "<a href=\"settings.php?s=admin\">".$clientname."</a>" : $clientname)."</strong> | <a href=\"defs/logout.php\">Log out</a>"
						echo "<strong>".htmlentities($_SESSION['username'])."</strong> @ ";
						if ($usertype == "a") {
						?>
						<select id="changeclientID"><?php 
						echo "				<option value=\"0\"";
						if ($clientid == '0')
							echo " selected=\"selected\"";
						echo ">[None]</option>\n";
						foreach($db->query("SELECT * FROM clients") as $row) {
							echo "				<option value=\"".$row['id']."\"";
							if ($clientid == $row['id'])
								echo " selected=\"selected\"";					
							echo ">".$row['name']."</option>\n";
						}
						?>
						</select>						
						<?php
						} else {
							echo "<strong>".$clientname."</strong>";
						}
						echo "| <a href=\"defs/logout.php\">Log out</a>";
					?> 
				</div>
				<div id="tabdiv"><?php echo "\n";

					$tabs = array(
						'bank' => 'Bank',
						'properties' => 'Properties',
						'landlords' => 'Landlords',						
						'tenants' => 'Tenants',
						'reports' => 'Reports',
						'settings' => 'Settings'
					);

					foreach ($tabs as $tablink => $tabname) {
						if ($pagetitle == $tabname) {
							echo "					<span class=\"tab_active\">".$tabname."</span>\n";
							$thispage = $tablink;
						} else {
							echo "					<a href=\"".$tablink.".php\"><span class=\"tab\">".$tabname."</span></a>\n";
						}
					}?>
				</div>
			</div>
		</div>
		<input type="hidden" id="thispage" value="<?php echo $thispage ?>" />
		<link rel="shortcut icon" href="favicon.ico?v=2" />
		<div id="debug"></div>
