<?php

abstract class DatabasePendingClass {
	public abstract function open();
}

abstract class DatabaseBackedClass {
	static public $mysqlConnection = NULL;
	static public function connectToDatabase($hostname, $username, $password, $database) {
		self::$mysqlConnection = new mysqli($hostname, $username, $password, $database);
		$mysqlConnection = self::$mysqlConnection;
		if ($mysqlConnection->connect_errno) {
			throw new Exception("Failed to connext to MySQL: {$mysqlConnection->connect_error}");
		}
	}

	static public $errorMessage = FALSE;
	static public function error() {
		return self::$errorMessage;
	}
	
	static public $saveList = array();
	static public function saveAll() {
		echo '<hr>Saving <code>'.htmlentities("DatabaseBackedClass").'</code> ('.count(self::$saveList).'):<br>';
		foreach (self::$saveList as $obj) {
			echo '<pre>'.htmlentities($obj->json()).'</pre>';
			if (!$obj->save()) {
				throw new Exception("Failed to save: ".$obj->json());
			}
		}
	}
	private $marked = FALSE;
	protected function markToSave() {
		if (!$this->marked) {
			$this->marked = TRUE;
			self::$saveList[] = $this;
		}
	}
	protected abstract function save($create=FALSE, $forceId=NULL);
	
	public function delete() {
		$this->marked = TRUE;
		$index = array_search($this, self::$saveList);
		if ($index !== FALSE) {
			unset(self::$saveList[$index]);
		}
	}
	
	static public function querySql($sql) {
		$mysqlConnection = self::$mysqlConnection;
		echo "<pre class='sql-statement'>$sql</pre>";
		$result = $mysqlConnection->query($sql);
		if (!$result) {
			self::$errorMessage = $mysqlConnection->error;
			return FALSE;
		} else {
			self::$errorMessage = FALSE;
		}
		if ($result === TRUE) {
			return array(
				"insert_id" => $mysqlConnection->insert_id,
				"affected_rows" => $mysqlConnection->affected_rows,
				"info" => $mysqlConnection->info
			);
		}
		$resultArray = array();
		while ($row = $result->fetch_assoc()) {
			$resultArray[] = $row;
		}
		return $resultArray;
	}

	static public function escapeString($string) {
		return self::$mysqlConnection->escape_string($string);
	}
	
	abstract public function json();

	abstract public function exportObject();
}

register_shutdown_function(array("DatabaseBackedClass", "saveAll"));

abstract class DatabaseBackedArrayClass extends ArrayObject {
	static public function connectToDatabase($hostname, $username, $password, $database) {
		return DatabaseBackedClass::connectToDatabase($hostname, $username, $password, $database);
	}

	static public $errorMessage = FALSE;
	static public function error() {
		return self::$errorMessage;
	}
	
	static public $saveList = array();
	static public function saveAll() {
		echo '<hr>Saving <code>'.htmlentities("DatabaseBackedArrayClass").'</code> ('.count(self::$saveList).'):<br>';
		foreach (self::$saveList as $obj) {
			echo '<pre>'.htmlentities($obj->json()).'</pre>';
			if (!$obj->save()) {
				throw new Exception("Failed to save: ".$obj->json());
			}
		}
	}
	private $marked = FALSE;
	protected function markToSave() {
		if (!$this->marked) {
			$this->marked = TRUE;
			self::$saveList[] = $this;
		}
	}
	protected abstract function save($create=FALSE, $forceId=NULL);
	
	public function delete() {
		$this->marked = TRUE;
		$index = array_search($this, self::$saveList);
		if ($index !== FALSE) {
			unset(self::$saveList[$index]);
		}
	}
	
	static public function querySql($sql) {
		return DatabaseBackedClass::querySql($sql);
	}

	static public function escapeString($string) {
		return DatabaseBackedClass::escapeString($string);
	}
	
	abstract public function json();

	abstract public function exportArray();
}

register_shutdown_function(array("DatabaseBackedArrayClass", "saveAll"));

?>