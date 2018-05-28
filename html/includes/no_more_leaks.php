<?php
error_reporting(-1);
ini_set('display_errors', 'On');
require '../../db.php';
if (isset($_POST['meterid'])) {
	$stmt = $db->prepare("UPDATE meters SET last_leak = 0 WHERE id = ?");
	$stmt->execute([$_POST['meterid']]);
}
?>