<?php
if (is_dir('db/upload/'.$clientid)) {
	foreach (preg_grep('/^([^.])/', scandir('db/upload/'.$clientid)) as $file) {
		$pathinfo = pathinfo($file);
		$ext = '.'.$pathinfo['extension'];
		$q = $db->prepare("INSERT INTO `docs` (`ext`, `clientid`) VALUES (:ext, :clientid)");
		$q->bindValue(':ext', $ext, PDO::PARAM_STR);
		$q->bindValue(':clientid', $clientid, PDO::PARAM_INT);
		$q->execute();
		$s = $db->query("SELECT `id` FROM `docs` ORDER BY `id` DESC LIMIT 1");
		$r = $s->fetch();
		rename ('db/upload/'.$clientid.'/'.$file, 'db/docs/Rx'.$r['id'].$ext);
	}
}
?>