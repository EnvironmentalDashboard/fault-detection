<?php
require '../bos/class.bos.php';
foreach ($db->query('SELECT api_id, url FROM orgs') as $org) {
	$stmt = $db->prepare('SELECT client_id, client_secret, username, password FROM api WHERE id = ?');
	$stmt->execute([$org['api_id']]);
	$api = $stmt->fetch();
  $bos = new BuildingOS($db, [$api['client_id'], $api['client_secret'], $api['username'], $api['password']]);
  $bos->syncBuildings($org['url'], true);
}
?>