<?php
require_once "include/apiResponseGenerator.php";
require_once "include/dbConnection.php";
class LOGOUTMODEL extends APIRESPONSE {
	private function processMethod($token) {
		
		$request = explode("/", substr(@$_SERVER['PATH_INFO'], 1));
		
		switch (REQUESTMETHOD) {
		case 'POST':
            break;
		case 'PUT':
			break;
		case 'GET':
			$result = $this->logout($token);
			return $result;
			break;
		default:
			$result = $this->handle_error($request);
			return $result;
			break;
		}
	}
	// Initiate db connection
	private function dbConnect() {
		$conn = new DBCONNECTION();
		$db = $conn->connect();
		return $db;
	}

	
	/**
	 * Function is to check the Login Authendication By token
	 *
	 * @param array $request
	 * @throws Exception
	 * @return multitype:
	 */
	
	/**
	 * Logout
	 *
	 * @param token $request
	 * @throws Exception
	 * @return multitype:string
	 */
	public function logout($token) {
		try {
			if (empty($token)) {
				throw new Exception("Please give the Token");
			}
			$db = $this->dbConnect();
			$query = "SELECT id, user_id FROM tbl_user_login_log WHERE token = '$token' and status='1'";
			$result = $db->query($query);
			$row_cnt = mysqli_num_rows($result);
			$data = mysqli_fetch_array($result, MYSQLI_ASSOC);
			if ($row_cnt > 0) {
				$sqlUpdate = "UPDATE tbl_user_login_log SET status = 0 WHERE id = '" . $data['id'] . "'";
				//print_r($sqlInsert);exit();
				if ($db->query($sqlUpdate) === TRUE) {
					$db->close();
				}
				$this->loginLogCreate("logout from the application USER ID: ", $data['user_id'], getcwd());
				$resultArray = array(
					"apiStatus" => array(
						"code" => "200",
						"message" => "Logout Successfully")
				);
			}
			else{
				$this->loginLogCreate("There is no active user found with this USER ID: ", $data['user_id'], getcwd());
				$resultArray = array(
				"apiStatus" => array(
					"code" => "400",
					"message" => "There is no active user found with this details")
				);
			}
			
			return $resultArray;
		} catch (Exception $e) {
			$this->loginLogCreate($e->getMessage(), "", getcwd());
			return array(
				"apiStatus" => array(
					"code" => "401",
					"message" => $e->getMessage()),
			);
		}
	}
	/**
	 * Log create For Login
	 *
	 * @param string $message
	 * @param string $userName
	 * @throws Exception
	 */
	public function loginLogCreate($message, $userName, $dir) {
		try {
			$fp = fopen(LOG_LOGIN, "a");
			$file = $dir;
			fwrite($fp, "" . "\t" . Date("r") . "\t$file\t$userName\t$message\r\n");
		} catch (Exception $e) {
			$this->loginLogCreate($e->getMessage(), "", getcwd());
			return array(
				"apiStatus" => array(
					"code" => "401",
					"message" => $e->getMessage()),
			);
		}
	}

	

	// Unautherized api request
	private function handle_error($request) {
	}
	/**
	 * Function is to process the crud request
	 *
	 * @param array $request
	 * @return array
	 */
	public function processRequest($token) {
		try {
			$responseData = $this->processMethod($token);
			$result = $this->response($responseData);
			return $result;
		} catch (Exception $e) {
			return array(
				"apiStatus" => array(
					"code" => "401",
					"message" => $e->getMessage()),
			);
		}
	}
}
?>