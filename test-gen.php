<?php

if (!isset($_GET["passive"])) {
	include "gen.php";
	$apiDef = new ApiGenerator("gen", "gen_latest");
	$apiDef->addFallback("../gen_old", "_inner_old");

	$customGenerator = $apiDef->createCustomClass("CustomClass");

	$apiDef->exportPhpCode();
}

echo '<pre>'.htmlentities(file_get_contents("gen/CustomClass.php")).'</pre>';
echo '<hr>';
echo '<pre>'.htmlentities(file_get_contents("gen_latest/stubs/CustomClass.php")).'</pre>';
?>
<hr>
<?php

include "gen_latest/stubs/CustomClass.php";

?>Done