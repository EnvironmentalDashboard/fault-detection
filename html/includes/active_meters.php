<?php
error_reporting(-1);
ini_set('display_errors', 'On');
require '../../db.php';
if (isset($_POST['meterid']) && isset($_POST['uid'])) {
	if (isset($_POST['add']) && $_POST['add'] === '1') {
		$stmt = $db->prepare("INSERT INTO active_meters (meter_id, user_id) VALUES (?, ?)");
		$stmt->execute([$_POST['meterid'], $_POST['uid']]);
		echo "added";
	} elseif (isset($_POST['remove']) && $_POST['remove'] === '1') {
		$stmt = $db->prepare("DELETE FROM active_meters WHERE meter_id = ? AND user_id = ?");
		$stmt->execute([$_POST['meterid'], $_POST['uid']]);
	}
}
?>