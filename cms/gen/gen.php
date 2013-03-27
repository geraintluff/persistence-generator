<?php

class ApiGenerator {
	public static function phpString($s) {
		$s = str_replace("\\", "\\\\", $s);
		$s = str_replace("'", "\\'", $s);
		return "'".$s."'";
	}

	protected $outputDir;
	protected $generators = array();
	protected $fallbackDirs = array();
	public $debug = FALSE;
	
	public function __construct($outputDir, $latestDir, $versionIdentifier="vX", $classSuffix="_inner") {
		if ($outputDir[strlen($outputDir) - 1] != "/") {
			$outputDir .= "/";
		}
		if ($latestDir[strlen($latestDir) - 1] != "/") {
			$latestDir .= "/";
		}
		$this->outputDir = $outputDir;
		$this->latestDir = $latestDir;
		$this->tablePrefix = "{$versionIdentifier}_";
		$this->classSuffix = $classSuffix;
		$this->versionSuffix = "_{$versionIdentifier}";
		$this->includePrefix = "";
	}
	
	public function setIncludePrefix($prefix) {
		$this->includePrefix = $prefix;
	}
	
	public function setVersion($versionIdentifier) {
		$this->tablePrefix = "{$versionIdentifier}_";
		$this->versionSuffix = "_{$versionIdentifier}";
	}
	
	public function addFallback($directory, $classSuffix) {
		if ($directory[strlen($directory) - 1] != "/") {
			$directory .= "/";
		}
		$this->fallbacks[] = array(
			"directory" => $directory,
			"suffix" => $classSuffix
		);
	}
	
	public function createClass($className, $tableName, $tableKeyColumn) {
		$result = new DatabaseBackedClassGenerator($className, $this->tablePrefix.$tableName, $tableKeyColumn);
		$result->includePrefix = $this->includePrefix;
		$this->generators[] = $result;
		return $result;
	}

	public function createArrayClass($className, $tableName, $tableKeyColumn, $indexColumn) {
		$result = new DatabaseBackedArrayClassGenerator($className, $this->tablePrefix.$tableName, $tableKeyColumn, $indexColumn);
		$result->includePrefix = $this->includePrefix;
		$this->generators[] = $result;
		return $result;
	}
	
	public function exportPhpCode() {
		if (!file_exists($this->outputDir)) {
			mkdir($this->outputDir, 0777, TRUE);
		}
		if (!file_exists($this->latestDir)) {
			mkdir($this->latestDir, 0777, TRUE);
		}
		if (!file_exists($this->latestDir."stubs/")) {
			mkdir($this->latestDir."stubs/", 0777, TRUE);
		}
		$includePathConstant = "PATH_INCLUDE{$this->versionSuffix}";
		$commonPath = "{$this->outputDir}common.php";
		$commonLatestPath = "{$this->latestDir}common.php";
		$pathsPath = "{$this->outputDir}paths.php";
		$pathsPathLatest = "{$this->latestDir}paths.php";
		$pathsCode = "<?php\n";
		$pathsCodeLatest = "<?php\n";
		$pathsCode .= "define(".self::phpString($includePathConstant).", dirname(__FILE__).'/');\n";
		$pathsCodeLatest .= "define(".self::phpString($includePathConstant).", ".self::phpString($this->includePrefix.$this->outputDir).");\n";
		$fallbackLocations = array();
		foreach ($this->fallbacks as $index => $fallback) {
			$index++;
			$pathsCode .= "define(".self::phpString("PATH_FALLBACK{$this->versionSuffix}_{$index}").", {$includePathConstant}.".self::phpString($fallback['directory']).");\n";
			$pathsCodeLatest .= "define(".self::phpString("PATH_FALLBACK{$this->versionSuffix}_{$index}").", {$includePathConstant}.".self::phpString($fallback['directory']).");\n";
			$fallbackLocations["PATH_FALLBACK{$this->versionSuffix}_{$index}"] = $fallback['suffix'];
		}
		$pathsCode .= "?>";
		$pathsCodeLatest .= "?>";
		file_put_contents($pathsPath, $pathsCode);
		file_put_contents($pathsPathLatest, $pathsCodeLatest);

		$commonCode = "<?php\ninclude_once dirname(__FILE__).".self::phpString("/paths.php").";\n?".">";
		foreach ($this->generators as $generator) {
			$localName = "{$generator->className}.php";

			$outputFilename = "{$this->outputDir}$localName";
			file_put_contents($outputFilename, $generator->phpCode($this->classSuffix.$this->versionSuffix, "common.php", "DatabaseBackedClass{$this->versionSuffix}", "DatabaseBackedArrayClass{$this->versionSuffix}", "DatabasePendingClass{$this->versionSuffix}", $fallbackLocations));

			$latestOutputFilename = "{$this->latestDir}{$generator->className}.php";
			$latestCode = "<?php\n";
			$latestCode .= "include_once dirname(__FILE__).\"/paths.php\";\n";
			$latestCode .= "include {$includePathConstant}.".self::phpString($localName).";\n\n";
			$latestCode .= "class {$generator->className}{$this->classSuffix} extends {$generator->className}{$this->classSuffix}{$this->versionSuffix} {}\n";
			$latestCode .= "?>";
			file_put_contents($latestOutputFilename, $latestCode);

			$stubFilename = "{$this->latestDir}stubs/{$generator->className}.php";
			file_put_contents($stubFilename, $generator->phpStubCode($this->classSuffix, $latestOutputFilename));

		}
		$commonCode .= file_get_contents("gen.common.php");
		$commonCode = str_replace("\n?><?php", "", $commonCode);
		$commonCode = str_replace(" DatabaseBackedClass ", " DatabaseBackedClass{$this->versionSuffix} ", $commonCode);
		$commonCode = str_replace("\"DatabaseBackedClass\"", "\"DatabaseBackedClass{$this->versionSuffix}\"", $commonCode);
		$commonCode = str_replace("DatabaseBackedClass::", "DatabaseBackedClass{$this->versionSuffix}::", $commonCode);
		$commonCode = str_replace(" DatabaseBackedArrayClass ", " DatabaseBackedArrayClass{$this->versionSuffix} ", $commonCode);
		$commonCode = str_replace("\"DatabaseBackedArrayClass\"", "\"DatabaseBackedArrayClass{$this->versionSuffix}\"", $commonCode);
		$commonCode = str_replace("DatabaseBackedArrayClass::", "DatabaseBackedArrayClass{$this->versionSuffix}::", $commonCode);
		$commonCode = str_replace(" DatabasePendingClass ", " DatabasePendingClass{$this->versionSuffix} ", $commonCode);
		$commonCode = str_replace("\"DatabasePendingClass\"", "\"DatabasePendingClass{$this->versionSuffix}\"", $commonCode);
		$commonCode = str_replace("DatabasePendingClass::", "DatabasePendingClass{$this->versionSuffix}::", $commonCode);
		file_put_contents($commonPath, $commonCode);
		
		$commonLatestCode = "<?php\n";
		$commonLatestCode .= "include_once dirname(__FILE__).\"/paths.php\";\n";
		$commonLatestCode .= "include_once {$includePathConstant}.\"common.php\";\n\n";
		$commonLatestCode .= "abstract class DatabaseBackedClass extends DatabaseBackedClass{$this->versionSuffix} {}";
		$commonLatestCode .= "\n?".">";
		file_put_contents($commonLatestPath, $commonLatestCode);
	}
}

class DatabaseBackedClassGenerator {
	public static function phpString($s) {
		return ApiGenerator::phpString($s);
	}

	public $maps = array();
	public $includePrefix = "";
	public $defaultJson = '{}';

	public function __construct($className, $tableName, $tableKeyColumn) {
		$this->className = $className;
		$this->tableName = $tableName;
		$this->tableKeyColumn = $tableKeyColumn;
	}
	
	public function isArray() {
		return FALSE;
	}
	
	public function setDefault($default) {
		$this->defaultJson = json_encode((object)$default);
	}
	
	public function escape($name) {
		return "`".str_replace("`", "``", $name)."`";
	}
	
	public function addInteger($objKey, $columnName, $length=11) {
		$this->maps[$columnName] = array(
			"type" => "integer",
			"phpType" => "integer",
			"sqlType" => "INTEGER($length)",
			"primary" => ($columnName == $this->tableKeyColumn),
			"columnName" => $columnName,
			"objKey" => $objKey
		);
	}

	public function addNumber($objKey, $columnName) {
		$this->maps[$columnName] = array(
			"type" => "number",
			"phpType" => "float",
			"sqlType" => "FLOAT",
			"primary" => ($columnName == $this->tableKeyColumn),
			"columnName" => $columnName,
			"objKey" => $objKey
		);
	}

	public function addString($objKey, $columnName, $length=NULL) {
		$this->maps[$columnName] = array(
			"type" => "string",
			"phpType" => "string",
			"sqlType" => ($length == NULL) ? "TEXT" : "VARCHAR($length)",
			"primary" => ($columnName == $this->tableKeyColumn),
			"columnName" => $columnName,
			"objKey" => $objKey
		);
	}

	public function addBoolean($objKey, $columnName) {
		$this->maps[$columnName] = array(
			"type" => "boolean",
			"phpType" => "boolean",
			"sqlType" => 'TINYINT(1)',
			"primary" => FALSE,
			"columnName" => $columnName,
			"objKey" => $objKey
		);
	}

	public function addObject($objKey, $columnName, $objDef) {
		$remoteKey = $objDef->maps[$objDef->tableKeyColumn];
		$this->maps[$columnName] = array(
			"type" => "object",
			"phpType" => "object",
			"sqlType" => isset($remoteKey) ? $remoteKey['sqlType'] : 'INT(11)',
			"primary" => FALSE,
			"columnName" => $columnName,
			"objKey" => $objKey,
			"objDef" => $objDef
		);
	}

	public function addArray($objKey, $columnName, $arrayDef) {
		$this->maps[$columnName] = array(
			"type" => "array",
			"phpType" => "array",
			"sqlType" => 'INT(11)',
			"primary" => FALSE,
			"columnName" => $columnName,
			"objKey" => $objKey,
			"arrayDef" => $arrayDef
		);
	}
	
	public function phpStubCode($classNameSuffix, $baseClassPath) {
		return '<?php
include '.self::phpString($this->includePrefix.$baseClassPath).';

class '.$this->className.' extends '.$this->className.$classNameSuffix.' {
}
?>';
	}

	public function phpCode($classNameSuffix="", $commonPath=NULL, $commonClass="DatabaseBackedClass", $commonArrayClass="DatabaseBackedArrayClass", $pendingClass="DatabasePendingClass", $fallbackLocations=NULL) {
		$code = "<?php\n";
		if ($commonPath != NULL) {
			$code .= "include_once dirname(__FILE__).".self::phpString("/$commonPath").";\n";
		}
		$code .= '
class '.$this->className.'_pending'.$classNameSuffix.' extends '.$pendingClass.' {
	private $id;
	public function __construct($id) {';
		if ($debug) {
			$code .= 'echo "'.$this->className.'_pending'.$classNameSuffix.'#$id<br>";';
		}
		$code .= '
		$this->id = $id;
	}
	public function open() {
		return '.$this->className.$classNameSuffix.'::open($this->id);
	}
}

class '.$this->className.$classNameSuffix.' extends '.$commonClass.' implements IteratorAggregate {
	static private $cache = array();
	static private $pendingIds = array();
	
	static public function setupTables() {
		self::querySql("DROP TABLE '.$this->escape($this->tableName).'");
		$autoIncrement = "";';
		if ($fallbackLocations != NULL) {
			foreach ($fallbackLocations as $constantName => $fallbackClassSuffix) {
				$code .= '
		@include_once '.$constantName.'.'.self::phpString("{$this->className}.php").';
		if (class_exists('.self::phpString($this->className.$fallbackClassSuffix).')) {
			if (method_exists('.self::phpString($this->className.$fallbackClassSuffix).', "tableStatus")) {
				$tableStatus = '.$this->className.$fallbackClassSuffix.'::tableStatus();
				if (isset($tableStatus["Auto_increment"])) {
					$autoIncrement = " AUTO_INCREMENT=".((int)$tableStatus["Auto_increment"]);
				}
			}';
				if ($debug) {
					$code .= '
		} else {
			echo "Fallback - class not found: '.$this->className.$fallbackClassSuffix.'";';
				}
				$code .= '
		}';
			}
		}
		$code .= '
		return self::querySql("CREATE TABLE IF NOT EXISTS '.$this->escape($this->tableName).' (';
		$columns = array();
		if (!isset($this->maps[$this->tableKeyColumn])) {
			$columns[] = "
			{$this->escape($this->tableKeyColumn)} INT(11) PRIMARY KEY NOT NULL AUTO_INCREMENT";
		}
		foreach ($this->maps as $map) {
			$col = "
			{$this->escape($map['columnName'])} {$map['sqlType']}";
			if ($map['primary']) {
				$col .= " PRIMARY KEY NOT NULL";
				if ($map['type'] == "integer") {
					$col .= " AUTO_INCREMENT";
				}
			}
			$columns[] = $col;
		}
		$code .= implode(",", $columns);
		$code .= '
		){$autoIncrement}");
	}
	
	static public function tableStatus() {
		$result = self::querySql("SHOW TABLE STATUS LIKE \''.$this->tableName.'\'");
		if ($result) {';
		if ($debug) {
			$code .= '
			var_dump($result);';
		}
		$code .= '
			return $result[0];
		}
		return $result;
	}

	static public function create($json=NULL) {
		if (is_null($json)) {
			$json = '.self::phpString($this->defaultJson).';
		}
		if (is_array($json) || is_object($json)) {
			$json = json_encode($json);
		}
		$result = new '.$this->className.'(json_decode($json, TRUE));
		if (!$result->save(TRUE)) {
			echo "\n<B>Error creating new '.$this->className.'</B><br>\n";
		}
		return $result;
	}
	
	static public function openPending($id) {
		if (isset(self::$cache[$id])) {
			return self::$cache[$id];
		} else {
			self::$pendingIds[$id] = $id;
			return new '.$this->className.'_pending'.$classNameSuffix.'($id);
		}
	}

	static public function open($openId, $includePending=TRUE) {
		if (isset(self::$cache[$openId])) {
			return self::$cache[$openId];
		}
		if ($includePending) {
			self::$pendingIds[$openId] = $openId;
			$idList = array();
			foreach (self::$pendingIds as $id) {
				$idList[] = "\'".self::escapeString($id)."\'";
			}
			self::$pendingIds = array();
			$idList = implode(", ", $idList);
		} else {
			unset(self::$pendingIds[$openId]);
			$idList = "\'".self::escapeString($openId)."\'";
		}
		$rows = self::querySql("SELECT * FROM '.$this->escape($this->tableName).'
			WHERE '.$this->escape($this->tableKeyColumn).' IN ($idList)");
		foreach ($rows as $row) {
			$id = $row['.$this->phpString($this->tableKeyColumn).'];
			$hasPending = FALSE;
			$innerData = array();';
			foreach ($this->maps as $map) {
				$code .= '
			if (isset($row['.self::phpString($map['columnName']).'])) {';
				if ($map['type'] == "object") {
					$objDef = $map['objDef'];
					$code .= '
				$hasPending = TRUE;
				$innerData['.self::phpString($map['objKey']).'] = '.$objDef->className.'::openPending($row['.self::phpString($map['columnName']).']);';
				} elseif ($map['type'] == "array") {
					$arrayDef = $map['arrayDef'];
					$code .= '
				$hasPending = TRUE;
				$innerData['.self::phpString($map['objKey']).'] = '.$arrayDef->className.'::openPending($row['.self::phpString($map['columnName']).']);';
				} else {
					$code .= '
				$innerData['.self::phpString($map['objKey']).'] = ('.$map['phpType'].')$row['.self::phpString($map['columnName']).'];';
				}
				$code .= '
			}';
			}
			$code .= '
			$result = new '.$this->className.'($innerData);
			self::$cache[$id] = $result;
			$result->id = $id;
			if ($hasPending) {
				$result->hasPending = TRUE;
			}
		}
		if (!isset(self::$cache[$openId])) {';
		if ($fallbackLocations != NULL) {
			foreach ($fallbackLocations as $constantName => $fallbackClassSuffix) {
				$code .= '
			@include_once '.$constantName.'.'.self::phpString("{$this->className}.php").';
			if (class_exists('.self::phpString($this->className.$fallbackClassSuffix).')) {
				$result = '.$this->className.$fallbackClassSuffix.'::open($id, TRUE);
				if ($result) {
					return $result;
				}';
				if ($debug) {
					$code .= '
			} else {
				echo "Fallback - class not found: '.$this->className.$fallbackClassSuffix.'";';
				}
				$code .= '
			}';
			}
		}
		$code .= '
			return FALSE;
		}
		return self::$cache[$openId];
	}
	
	private $id;
	private $innerData;
	private $hasPending = FALSE;
	
	protected function __construct($initialValue=NULL) {
		$this->innerData = (array)$initialValue;
	}

	public function __isset($key) {
		return isset($this->innerData[$key]);
	}
	
	public function __get($key) {
		$this->executePending();
		return $this->innerData[$key];
	}
	
	public function __set($key, $value) {
		$this->executePending();';
		foreach ($this->maps as $map) {
			if ($map['type'] == "object") {
				$code .= '
		if ($key == '.self::phpString($map['objKey']).') {
			if (!is_object($this->innerData[$key])) {
				$this->innerData[$key] = '.($map['objDef']->className).'::create();
				$this->markToSave();
			}
			foreach ($value as $innerKey => $innerValue) {
				$this->innerData[$key]->$innerKey = $innerValue;
			}
			if (is_array($value)) {
				foreach ($this->innerData[$key] as $innerKey => $innerValue) {
					if (!isset($value[$innerKey])) {
						unset($this->innerData[$key]->$innerKey);
					}
				}
			} else {
				foreach ($this->innerData[$key] as $innerKey => $innerValue) {
					if (!isset($value->$innerKey)) {
						unset($this->innerData[$key]->$innerKey);
					}
				}
			}
			return;
		}';
			} elseif ($map['type'] == "array") {
				$code .= '
		if ($key == '.self::phpString($map['objKey']).') {
			if (!is_object($this->innerData[$key])) {
				$this->innerData[$key] = '.($map['arrayDef']->className).'::create();
				$this->markToSave();
			}
			foreach ($value as $innerIndex => $innerValue) {
				$this->innerData[$key][$innerIndex] = $innerValue;
			}
			foreach ($this->innerData[$key] as $innerIndex => $innerValue) {
				if (!isset($value[$innerIndex])) {
					unset($this->innerData[$key][$innerIndex]);
				}
			}
			return;
		}';
			}
		}
		$code.= '
		$this->innerData[$key] = $value;
		$this->markToSave();
	}

	public function __unset($key) {
		$this->executePending();';
		foreach ($this->maps as $map) {
			if ($map['type'] == "object") {
				$code .= '
		if ($key == '.self::phpString($map['objKey']).') {
			if (is_object($this->innerData[$key])) {
				$this->innerData[$key]->delete();
			}
		}';
			} elseif ($map['type'] == "array") {
				$code .= '
		if ($key == '.self::phpString($map['objKey']).') {
			if (is_object($this->innerData[$key])) {
				$this->innerData[$key]->delete();
			}
		}';
			}
		}
		$code.= '
		$this->markToSave();
		unset($this->innerData[$key]);
	}
	
	public function getIterator() {
		$this->executePending();
		$o = new ArrayObject($this->innerData);
		return $o->getIterator();
	}
	
	public function id() {
		return $this->id;
	}

	protected function executePending($key) {
		if ($this->hasPending) {
			$this->hasPending = FALSE;
			foreach ($this->innerData as $key => $value) {
				if ($value instanceof '.$pendingClass.') {
					$this->innerData[$key] = $value->open();
				}
			}
		}
	}

	protected function save($create=FALSE, $forceId=NULL) {';
		$sqlColumns = array();
		$phpSqlValue = array();
		foreach ($this->maps as $map) {
			if ($map['columnName'] == $this->tableKeyColumn) {
				continue;
			}
			$sqlColumns[] = $this->escape($map['columnName']);
			$innerKey = self::phpString($map['objKey']);
			$phpSqlValue = '$this->innerData['.$innerKey.']';
			if ($map['type'] == "integer") {
				$phpSqlValue = '(is_integer($this->innerData['.$innerKey.']) && !is_string($this->innerData['.$innerKey.'])) ? '.$phpSqlValue.' : "NULL"';
			} elseif ($map['type'] == "number") {
				$phpSqlValue = '(is_numeric($this->innerData['.$innerKey.']) && !is_string($this->innerData['.$innerKey.'])) ? '.$phpSqlValue.' : "NULL"';
			} elseif ($map['type'] == "string") {
				$phpSqlValue = '"\'".self::escapeString('.$phpSqlValue.')."\'"';
				$phpSqlValue = 'is_string($this->innerData['.$innerKey.']) ? '.$phpSqlValue.' : "NULL"';
			} elseif ($map['type'] == "boolean") {
				$phpSqlValue = '('.$phpSqlValue.' ? "TRUE" : "FALSE")';
				$phpSqlValue = 'is_bool($this->innerData['.$innerKey.']) ? '.$phpSqlValue.' : "NULL"';
			} elseif ($map['type'] == "object") {
				$phpSqlValue = "{$phpSqlValue}->id()";
				$phpSqlValue = '"\'".self::escapeString('.$phpSqlValue.')."\'"';
				$phpSqlValue = 'is_object($this->innerData['.$innerKey.']) ? '.$phpSqlValue.' : "NULL"';
			} elseif ($map['type'] == "array") {
				$phpSqlValue = "{$phpSqlValue}->id()";
				$phpSqlValue = '"\'".self::escapeString('.$phpSqlValue.')."\'"';
				$phpSqlValue = 'is_object($this->innerData['.$innerKey.']) ? '.$phpSqlValue.' : "NULL"';
			} else {
				$phpSqlValue = 'isset($this->innerData['.$innerKey.']) ? '.$phpSqlValue.' : "NULL"';
			}
			$phpSqlValues[] = "($phpSqlValue)";
		}
		$code .= '
		if ($create) {
			$forceIdColumn = $forceId ? '.self::phpString($this->escape($this->tableKeyColumn).", ").' : "";
			$forceIdValue = $forceId ? "\'".self::escapeString($forceId)."\'," : "";
			$result = self::querySql("INSERT INTO '.$this->escape($this->tableName).' ({$forceIdColumn}'.implode(", ", $sqlColumns).') VALUES
				(	{$forceIdValue}
					".'.implode('.",
					".', $phpSqlValues).'."
				)");
			if ($result) {';
		if (isset($this->maps[$this->tableKeyColumn])) {
			$innerKey = self::phpString($this->maps[$this->tableKeyColumn]['objKey']);
			$code .= '
				$this->innerData['.$innerKey.'] = $result["insert_id"];';
		}
		$code .= '
				$this->id = $result["insert_id"];
				self::$cache[$result["insert_id"]] = $this;
			}
			return $result;
		} else {
			$result = self::querySql("UPDATE '.$this->escape($this->tableName).' SET';
			$updateParts = array();
			foreach ($sqlColumns as $index => $columnName) {
				$updateParts[] = "
					$columnName=\".".$phpSqlValues[$index].".\"";
			}
			$code .= implode(",", $updateParts);
			$code .= '
				WHERE '.$this->escape($this->tableKeyColumn).'=\'".self::escapeString($this->id)."\'");
			if ($result["affected_rows"] != 1) {
				return FALSE;
			}
			return $result;
		}
	}

	public function delete() {
		parent::delete();
		self::$cache[$this->id] = FALSE;';
		foreach ($this->maps as $map) {
			if ($map['type'] == "object") {
				$code .= '
		if (isset($this->innerData['.self::phpString($map['objKey']).'])) {
			$this->innerData['.self::phpString($map['objKey']).']->delete();
		}';
			} elseif ($map['type'] == "array") {
				$code .= '
		if (isset($this->innerData['.self::phpString($map['objKey']).'])) {
			$this->innerData['.self::phpString($map['objKey']).']->delete();
		}';
			}
		}
		$code .= '
		return self::querySql("DELETE FROM '.$this->escape($this->tableName).' WHERE '.$this->escape($this->tableKeyColumn).'=\'".(self::escapeString($this->id))."\'");
	}

	public function json() {
		return json_encode($this->exportObject());
	}
	
	public function exportObject() {
		$this->executePending();
		$result = new StdClass;
		foreach ($this->innerData as $key => $value) {
			if ($value instanceof '.$commonClass.') {
				$result->$key = $value->exportObject();
			} elseif ($value instanceof '.$commonArrayClass.') {
				$result->$key = $value->exportArray();
			} else {
				$result->$key = $value;
			}
		}
		return $result;
	}
}
		';
		$code .= "\n?>";
		return $code;
	}
}

class DatabaseBackedArrayClassGenerator {
	public static function phpString($s) {
		return ApiGenerator::phpString($s);
	}
	public $maps = array();
	public $includePrefix = "";
	public $defaultJson = '[]';
	
	public function __construct($className, $tableName, $tableKeyColumn, $tableIndexColumn) {
		$this->className = $className;
		$this->tableName = $tableName;
		$this->tableKeyColumn = $tableKeyColumn;
		$this->tableIndexColumn = $tableIndexColumn;
	}

	public function isArray() {
		return TRUE;
	}
	
	public function escape($name) {
		return "`".str_replace("`", "``", $name)."`";
	}
	
	public function setDefault($default) {
		$this->defaultJson = json_encode((array)$default);
	}

	public function setInteger($columnName, $length=11) {
		$this->maps[$columnName] = array(
			"type" => "integer",
			"phpType" => "integer",
			"sqlType" => "INTEGER($length)",
			"columnName" => $columnName
		);
	}

	public function setNumber($columnName) {
		$this->maps[$columnName] = array(
			"type" => "number",
			"phpType" => "float",
			"sqlType" => "FLOAT",
			"columnName" => $columnName
		);
	}

	public function setString($columnName, $length=NULL) {
		$this->maps[$columnName] = array(
			"type" => "string",
			"phpType" => "string",
			"sqlType" => ($length == NULL) ? "TEXT" : "VARCHAR($length)",
			"columnName" => $columnName
		);
	}

	public function setBoolean($columnName) {
		$this->maps[$columnName] = array(
			"type" => "boolean",
			"phpType" => "boolean",
			"sqlType" => 'TINYINT(1)',
			"columnName" => $columnName
		);
	}

	public function setObject($columnName, $objDef) {
		$remoteKey = $objDef->maps[$objDef->tableKeyColumn];
		$this->maps[$columnName] = array(
			"type" => "object",
			"phpType" => "object",
			"sqlType" => isset($remoteKey) ? $remoteKey['sqlType'] : 'INT(11)',
			"columnName" => $columnName,
			"objDef" => $objDef
		);
	}

	public function setArray($columnName, $arrayDef) {
		$this->maps[$columnName] = array(
			"type" => "array",
			"phpType" => "array",
			"sqlType" => 'INT(11)',
			"columnName" => $columnName,
			"arrayDef" => $arrayDef
		);
	}

	public function phpStubCode($classNameSuffix, $baseClassPath) {
		return '<?php
include '.self::phpString($this->includePrefix.$baseClassPath).';

class '.$this->className.' extends '.$this->className.$classNameSuffix.' {
}
?>';
	}

	public function phpCode($classNameSuffix="", $commonPath=NULL, $commonObjectClass="DatabaseBackedClass", $commonClass="DatabaseBackedArrayClass", $pendingClass="DatabasePendingClass", $fallbackLocations=NULL) {
		$code = "<?php\n";
		if ($commonPath != NULL) {
			$code .= "include_once dirname(__FILE__).".self::phpString("/$commonPath").";\n";
		}
		$code .= '
class '.$this->className.'_pending'.$classNameSuffix.' extends '.$pendingClass.' {
	private $id;
	public function __construct($id) {';
		if ($debug) {
			$code .= '
		echo "'.$this->className.'_pending'.$classNameSuffix.'#$id<br>";';
		}
		$code .= '
		$this->id = $id;
	}
	public function open() {
		return '.$this->className.$classNameSuffix.'::open($this->id);
	}
}

class '.$this->className.$classNameSuffix.' extends '.$commonClass.' implements IteratorAggregate {
	static private $cache = array();
	static private $pendingIds = array();
	
	static public function setupTables() {
		self::querySql("DROP TABLE '.$this->escape($this->tableName).'");
		$autoIncrement = "";';
		if ($fallbackLocations != NULL) {
			foreach ($fallbackLocations as $constantName => $fallbackClassSuffix) {
				$code .= '
		@include_once '.$constantName.'.'.self::phpString("{$this->className}.php").';
		if (class_exists('.self::phpString($this->className.$fallbackClassSuffix).')) {
			if (method_exists('.self::phpString($this->className.$fallbackClassSuffix).', "tableStatus")) {
				$tableStatus = '.$this->className.$fallbackClassSuffix.'::tableStatus();
				if (isset($tableStatus["Auto_increment"])) {
					$autoIncrement = " AUTO_INCREMENT=".((int)$tableStatus["Auto_increment"]);
				}
			}';
				if ($debug) {
					$code .= '
		} else {
			echo "Fallback - class not found: '.$this->className.$fallbackClassSuffix.'";';
				}
				$code .= '
		}';
			}
		}
		$code .= '
		return self::querySql("CREATE TABLE IF NOT EXISTS '.$this->escape($this->tableName).' (';
		$columns = array();
		$columns[] = "
			{$this->escape($this->tableKeyColumn)} INT(11) NOT NULL AUTO_INCREMENT";
		$columns[] = "
			{$this->escape($this->tableIndexColumn)} INT(11) NOT NULL";
		foreach ($this->maps as $map) {
			$col = "
			{$this->escape($map['columnName'])} {$map['sqlType']}";
			$columns[] = $col;
		}
		$code .= implode(",", $columns);
		$code .= ',
			PRIMARY KEY ('.$this->escape($this->tableKeyColumn).', '.$this->escape($this->tableIndexColumn).')
		){$autoIncrement}");
	}
	
	static public function tableStatus() {
		$result = self::querySql("SHOW TABLE STATUS LIKE \''.$this->tableName.'\'");
		if ($result) {';
		if ($this->debug) {
			$code .= '
			var_dump($result);';
		}
		$code .= '
			return $result[0];
		}
		return $result;
	}

	static public function create($json=NULL) {
		if (is_null($json)) {
			$json = '.self::phpString($this->defaultJson).';
		}
		$result = new '.$this->className.'(json_decode($json, TRUE));
		if (!$result->save(TRUE)) {
			echo "\n<B>Error creating new '.$this->className.'</B><br>\n";
		}
		return $result;
	}

	static public function openPending($id) {
		if (isset(self::$cache[$id])) {
			return self::$cache[$id];
		} else {
			self::$pendingIds[$id] = $id;
			return new '.$this->className.'_pending'.$classNameSuffix.'($id);
		}
	}

	static public function open($openId, $includePending=TRUE) {
		if (isset(self::$cache[$openId])) {
			return self::$cache[$openId];
		}
		if ($includePending) {
			self::$pendingIds[$openId] = $openId;
			$idList = array();
			foreach (self::$pendingIds as $id) {
				$idList[] = "\'".self::escapeString($id)."\'";
			}
			self::$pendingIds = array();
			$idList = implode(", ", $idList);
		} else {
			unset(self::$pendingIds[$openId]);
			$idList = "\'".self::escapeString($openId)."\'";
		}
		$rows = self::querySql("SELECT * FROM '.$this->escape($this->tableName).'
			WHERE '.$this->escape($this->tableKeyColumn).' IN ($idList)
			ORDER BY '.$this->escape($this->tableKeyColumn).', '.$this->escape($this->tableIndexColumn).'");
		
		$innerData = array();
		$havePending = array();
		$lastId = NULL;
		foreach ($rows as $row) {
			$id = $row['.$this->phpString($this->tableKeyColumn).'];';
		$else = "
			";
		foreach ($this->maps as $map) {
			$code .= $else.'if (isset($row['.self::phpString($map['columnName']).'])) {';
			if ($map['type'] == "object") {
				$objDef = $map['objDef'];
				$code .= '
				$havePending[$id] = TRUE;
				$innerData[$id][] = '.$objDef->className.'::openPending($row['.self::phpString($map['columnName']).']);';
			} elseif ($map['type'] == "array") {
				$arrayDef = $map['arrayDef'];
				$code .= '
				$havePending[$id] = TRUE;
				$innerData[$id][] = '.$arrayDef->className.'::openPending($row['.self::phpString($map['columnName']).']);';
			} else {
				$code .= '
				$innerData[$id][] = ('.$map['phpType'].')$row['.self::phpString($map['columnName']).'];';
			}
			$code .= '
			}';
			$else = " else";
		}
		$code .= '
		}
		foreach ($innerData as $id => $innerInnerData) {
			$result = new '.$this->className.'($innerInnerData);
			self::$cache[$id] = $result;
			$result->id = $id;
			if (isset($havePending[$id])) {
				$result->hasPending = TRUE;
			}
		}
		
		if (count($rows) == 0) {';
		if ($fallbackLocations != NULL) {
			foreach ($fallbackLocations as $constantName => $fallbackClassSuffix) {
				$code .= '
			@include_once '.$constantName.'.'.self::phpString("{$this->className}.php").';
			if (class_exists('.self::phpString($this->className.$fallbackClassSuffix).')) {
				$result = '.$this->className.$fallbackClassSuffix.'::open($id, TRUE);
				if (count($result)) {
					return $result;
				}';
				if ($debug) {
					$code .= '
			} else {
				echo "Fallback - class not found: '.$this->className.$fallbackClassSuffix.'";';
				}
				$code .= '
			}';
			}
		}
		$code .= '
			return array();
		}
		return $result;
	}
	
	private $id;
	private $hasPending = FALSE;
	
	public function __construct($initialValue=NULL) {
		parent::__construct((array)$initialValue);
	}

	public function id() {
		return $this->id;
	}

	protected function executePending($key) {
		if ($this->hasPending) {
			// disable the flag first, because the iteration calls back to this function
			$this->hasPending = FALSE;
			foreach ($this as $index => $value) {
				if ($value instanceof '.$pendingClass.') {
					parent::offsetSet($index, $value->open());
				}
			}
		}
	}

	protected function save($create=FALSE, $forceId=NULL) {';
		$sqlColumns = array();
		$phpSqlValue = array();
		foreach ($this->maps as $map) {
			if ($map['columnName'] == $this->tableKeyColumn) {
				continue;
			}
			$sqlColumns[] = $this->escape($map['columnName']);
			$phpSqlValue = '$entry';
			if ($map['type'] == "integer") {
				$phpSqlValue = '(is_integer($entry) && !is_string($entry)) ? '.$phpSqlValue.' : "NULL"';
			} elseif ($map['type'] == "number") {
				$phpSqlValue = '(is_numeric($entry) && !is_string($entry)) ? '.$phpSqlValue.' : "NULL"';
			} elseif ($map['type'] == "string") {
				$phpSqlValue = '"\'".self::escapeString('.$phpSqlValue.')."\'"';
				$phpSqlValue = 'is_string($entry) ? '.$phpSqlValue.' : "NULL"';
			} elseif ($map['type'] == "boolean") {
				$phpSqlValue = '('.$phpSqlValue.' ? "TRUE" : "FALSE")';
				$phpSqlValue = 'is_bool($entry) ? '.$phpSqlValue.' : "NULL"';
			} elseif ($map['type'] == "object") {
				$phpSqlValue = "{$phpSqlValue}->id()";
				$phpSqlValue = '"\'".self::escapeString('.$phpSqlValue.')."\'"';
				$phpSqlValue = 'is_object($entry) ? '.$phpSqlValue.' : "NULL"';
			} elseif ($map['type'] == "array") {
				$phpSqlValue = "{$phpSqlValue}->id()";
				$phpSqlValue = '"\'".self::escapeString('.$phpSqlValue.')."\'"';
				$phpSqlValue = 'is_object($entry) ? '.$phpSqlValue.' : "NULL"';
			} else {
				$phpSqlValue = 'isset($entry) ? '.$phpSqlValue.' : "NULL"';
			}
			$phpSqlValues[] = "($phpSqlValue)";
		}
		$code .= '
		if ($create) {
			if ($forceId) {
				$this->id = $forceId;
			} else {
				$result = self::querySql("INSERT INTO '.$this->escape($this->tableName).' ('.$this->escape($this->tableIndexColumn).') VALUES (-1)");
				$this->id = $result["insert_id"];
				self::$cache[$result["insert_id"]] = $this;
			}
		}
		// Delete all the old entries
		$result = self::querySql("DELETE FROM '.$this->escape($this->tableName).' WHERE '.$this->escape($this->tableKeyColumn).'=\'".self::escapeString($this->id)."\'");
		if (!$result) {
			return $result;
		}
		// Insert new entries
		$insertValues = array();
		$idSql = ((int)$this->id);
		foreach ($this as $index => $entry){
			$insertValues[] = "($idSql, ".((int)$index).", ".'.implode('.",	".', $phpSqlValues).'.")";
		}
		if (count($insertValues) > 0) {
			$result = self::querySql("INSERT INTO '.$this->escape($this->tableName).' ('.$this->escape($this->tableKeyColumn).', '.$this->escape($this->tableIndexColumn).', '.implode(", ", $sqlColumns).') VALUES
				".implode(",
				", $insertValues));
		}
		return $result;
	}

	public function delete() {
		parent::delete();
		self::$cache[$this->id] = FALSE;';
		foreach ($this->maps as $map) {
			if ($map['type'] == "object") {
				$code .= '
		foreach ($this as $index => $entry) {
			$entry->delete();
		}';
			} elseif ($map['type'] == "array") {
				$code .= '
		foreach ($this as $index => $entry) {
			$entry->delete();
		}';
			}
		}
		$code .= '
		return self::querySql("DELETE FROM '.$this->escape($this->tableName).' WHERE '.$this->escape($this->tableKeyColumn).'=\'".self::escapeString($this->id)."\'");
	}
	
	public function offsetSet($index, $value) {
		$this->executePending();';
		foreach ($this->maps as $map) {
			if ($map['type'] == "object") {
				$code .= '
		if (!is_null($index) && isset($this[$index])) {
			$entry = $this[$index];
		} else {
			$entry = '.($map['objDef']->className).'::create();
			parent::offsetSet($index, $entry);
			$this->markToSave();
		}
		foreach ($value as $innerKey => $innerValue) {
			$entry->$innerKey = $innerValue;
		}
		if (is_array($value)) {
			foreach ($entry as $innerKey => $innerValue) {
				if (!isset($value[$innerKey])) {
					unset($entry->$innerKey);
				}
			}
		} else {
			foreach ($entry as $innerKey => $innerValue) {
				if (!isset($value->$innerKey)) {
					unset($entry->$innerKey);
				}
			}
		}';
			} elseif ($map['type'] == "array") {
				$code .= '
		if (!is_null($index) && isset($this[$index])) {
			$entry = $this[$index];
		} else {
			$entry = '.($map['arrayDef']->className).'::create();
			parent::offsetSet($index, $entry);
			$this->markToSave();
		}
		foreach ($value as $innerIndex => $innerValue) {
			$entry[$innerIndex] = $innerValue;
		}
		foreach ($entry as $innerIndex => $innerValue) {
			if (!isset($value[$innerIndex])) {
				unset($entry[$innerIndex]);
			}
		}';
			} else {
				$code .= '
		parent::offsetSet($index, $value);
		$this->markToSave();';
			}
		}
		$code.= '
	}
	
	public function offsetGet($index) {
		$this->executePending();
		return parent::offsetGet($index);
	}
	
	public function offsetUnset($index) {
		$this->executePending();
		$this->markToSave();';
		foreach ($this->maps as $map) {
			if ($map['type'] == "object") {
				$code .= '
		$this[$index]->delete();';
			} elseif ($map['type'] == "array") {
				$code .= '
		$this[$index]->delete();';
			}
		}
		$code .= '
		parent::offsetUnset($index);
	}
	
	public function getIterator() {
		$this->executePending();
		return parent::getIterator();
	}
	
	public function json() {
		return json_encode($this->exportArray());
	}
	
	public function exportArray() {
		$result = array();
		foreach ($this as $index => $value) {
			if ($value instanceof '.$commonObjectClass.') {
				$result[$index] = $value->exportObject();
			} elseif ($value instanceof '.$commonClass.') {
				$result[$index] = $value->exportArray();
			} else {
				$result[$index] = $value;
			}
		}
		return $result;
	}
}
		';
		$code .= "\n?>";
		return $code;
	}
}
