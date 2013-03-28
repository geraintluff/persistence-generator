<?php

if (!isset($_GET["passive"])) {
	include "gen.php";
	$apiDef = new ApiGenerator("gen", "gen_latest");
	$apiDef->addFallback("../gen_old", "_inner_old");

	$generator = $apiDef->createClass("ExampleClass", "ExampleTable", "example_id_column");
	$generator->addInteger("exampleId", "example_id_column");
	$generator->addString("exampleString", "example_string_column", 255);
	$generator->addInteger("otherInteger", "some_other_integer");
	$generator->addNumber("otherNumber", "some_other_number");
	$generator->addBoolean("desiresBacon", "desires_bacon");
	$generator->setDefault(array(
		"exampleString" => "Test string"
	));
	
	$innerGenerator = $apiDef->createClass("ExampleInnerClass", "ExampleInnerTable", "id");
	$innerGenerator->addString("string1", "string1");
	$innerGenerator->addString("string2", "string2");
	$generator->addObject("inner", "inner_id", $innerGenerator);

	$apiDef->exportPhpCode();
}

echo '<pre>'.htmlentities(file_get_contents("gen/ExampleClass.php")).'</pre>';
?>
<hr>
<?php

include "gen_latest/common.php";
include "include/config.php";
DatabaseBackedClass::connectToDatabase(MYSQL_SERVER, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DATABASE);

include "ExampleClass.php";
include "ExampleInnerClass.php";

$prevExample = ExampleClass::open(1);
if ($prevExample) {
	echo($prevExample->json());
	echo '<hr>';
}

$prevExample = ExampleClass::open(4);
if ($prevExample) {
	echo($prevExample->json());
	echo '<hr>';
}

var_dump(ExampleClass::setupTables());
var_dump(ExampleInnerClass::setupTables());

$example = ExampleClass::create();

$example->exampleString = "String".rand();
$example->desiresBacon = TRUE;
echo "<pre>{$example->json()}</pre>";

echo "Fetching: ";
$fetched = ExampleClass::open(2);
if ($fetched) {
	$fetched->otherInteger=5;
	$fetched->otherNumber = rand()/32768;
	echo "<pre>{$fetched->json()}</pre>";

	$inner = array("string1" => "test");
	$fetched->inner = $inner;

	$fetched->inner = array("string2" => "other test");
} else {
	echo 'NOT FOUND!';
}

ExampleClass::open(3);
var_dump(ExampleClass::tableStatus());
echo "<br>Done!";

?>