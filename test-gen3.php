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
	
	$generatorArray = $apiDef->createArrayClass("ExampleArray", "ExampleArrayTable", "array_id", "index");
	//$generatorArray->setInteger("integer_column");
	$generatorArray->setObject("object_id", $generator);
	$generator->addArray("array", "array_id", $generatorArray);

	$apiDef->exportPhpCode();
}

echo '<pre>'.htmlentities(file_get_contents("gen/ExampleArray.php")).'</pre>';
?>
<hr>
<?php

include "ExampleClass.php";
include "ExampleInnerClass.php";
include "ExampleArray.php";

$lastObj = ExampleClass::open(1);
echo '<B><pre>' . htmlentities($lastObj->json()) . '</pre></B>';

$arr = ExampleArray::open(1);
echo "<pre>{$arr->json()}</pre>";

ExampleArray::setupTables();
ExampleClass::setupTables();
ExampleInnerClass::setupTables();

$obj = ExampleClass::create();
$obj->array = array();
$obj->array[] = array("exampleString" => "Testing, testing...", "desiresBacon" => TRUE);
$obj->array[] = $obj->array[0];
var_Dump($obj->array[0]);
var_Dump($obj->array[1]);
$obj->desiresBacon = TRUE;

?>