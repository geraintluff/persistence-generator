<?php
include "include/common.php";
include "include/json-utils.php";

json_exit(array(
	"title" => "Test"
), SCHEMA_BASE."home.json");

?>