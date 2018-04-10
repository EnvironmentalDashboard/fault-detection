<?php
require_once 'class.bos.php';
ignore_user_abort(1);
set_time_limit(0);
$bos = new BuildingOS($db);
$res = 'quarterhour';
$chunk = 604800; // download data in week chunks
$start = 1451624400; // 2016-01-01T00:00:00-05:00
$end = 1483246800;  // 2017-01-01T00:00:00-05:00
// 578 = cox, 545 = burton, (565, 566, 567, 568, 569, 570) = union houses, 547 = farichild, 588 = firelands, 553 = north, 555 = south, 362 = east
foreach ($db->query('SELECT id, url FROM meters WHERE building_id IN (578, 545, 565, 566, 567, 568, 569, 570, 547, 588, 553, 555, 362) AND resource = "Water" AND scope = "Whole building"') as $meter) {
	echo "About to update meter {$meter['id']}\n";
	$bos->updateMeter($meter['id'], $meter['url'] . '/data', $res, $chunk, $start, $end);
	echo "Updated meter {$meter['id']}\n";
	sleep(3); // don't bombard bos api
}
?>