<?php
/**
 * Database Connection
 */
class DBCONNECTION {
	public $conn;
	public function connect() {
		try {
			// connect to MySql
			$config = parse_ini_file(__DIR__ .'/../core/db.ini');
			$this->conn = new mysqli($config['HOSTNAME'], $config['HOSTUSERNAME'], $config['HOSTPASSWORD'], $config['DB']);
			$this->conn->set_charset("utf8");
			if(!$this->conn){
				die("Failed to connect to Database"); 
			}
		} catch (Exception $e) {
			echo "Connection error: " . $e->getMessage();
		}
		return $this->conn;
	}
}
?>