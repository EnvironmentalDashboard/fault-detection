<?php
require '../bos/class.bos.php';
$org_id = false;
foreach ($db->query('SELECT id, org_id, url FROM meters WHERE id IN (SELECT meter_id FROM active_meters) ORDER BY org_id') as $meter) {
	if ($meter['org_id'] !== $org_id) {
		$org_id = $meter['org_id'];
		$stmt = $db->prepare('SELECT client_id, client_secret, username, password FROM api WHERE user_id IN (SELECT user_id FROM users_orgs_map WHERE org_id = ?)');
		$stmt->execute([$org_id]);
		$api = $stmt->fetch();
		$bos = new BuildingOS($db, [$api['client_id'], $api['client_secret'], $api['username'], $api['password']]);
	}
	$bos->updateMeter($meter['id'], $meter['url'] . '/data', 'quarterhour', PHP_INT_MAX, strtotime('-3 months'), time()); // PHP_INT_MAX so it doesnt grab data in chunks
}