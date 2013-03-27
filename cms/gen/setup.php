<?php

include "gen.php";

$api = new ApiGenerator("v0.0", "latest", "v0_0");
$api->setIncludePrefix('gen/');

// User objects
$user = $api->createClass("User", "user", "id");
$user->addInteger("id", "id");
$user->addString("name", "name", 255);
$user->addNumber("favouriteNumber", "favourite_number");
$user->addBoolean("optimistic", "is_optimistic");

$api->exportPhpCode();

?>:)