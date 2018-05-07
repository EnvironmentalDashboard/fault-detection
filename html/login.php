<?php
error_reporting(-1);
ini_set('display_errors', 'On');
require '../db.php';
if (isset($_POST['email']) && isset($_POST['password'])) {
	$stmt = $db->prepare('SELECT id, password FROM users WHERE username = ?');
	$stmt->execute([$_POST['email']]);
	if ($stmt->rowCount() == 0) {
		echo "NO EMAIL";
	}
	$user = $stmt->fetch();
	if (password_verify($_POST['password'], $user['password'])) {
		echo $user['id'];
	} else {
		echo "BAD PASS";
	}
}
