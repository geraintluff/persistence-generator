<?php

include "include/common.php";
include "include/json-utils.php";
include "utils.php";
include "classes/User.php";

/*
User::setupTables();
$user1 = User::create();
$user1->name = "Test User";
$user1->favouriteNumber = 68;
*/

$method = $_SERVER['REQUEST_METHOD'];
if ($params = matchUriTemplate("/{userId}/")) {
	$userId = $params['userId'];
	$user = User::open($userId);
	json_exit_raw($user->json(), SCHEMA_BASE."user.json");
} else if (matchesUriTemplate("/")) {
	$posted = json_decode(file_get_contents("php://input"));
	if ($posted != null) {
		$user = User::create($posted);
		json_exit_raw($user->json());
	}
	json_exit($posted);
}
json_exit($method);
?>