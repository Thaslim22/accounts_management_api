<?php
require_once "include/apiResponseGenerator.php";
require_once "include/dbConnection.php";
class COMMONMODEL extends APIRESPONSE {
	private function processMethod($data, $token) {
		$request = explode("/", substr(@$_SERVER['PATH_INFO'], 1));
		switch (REQUESTMETHOD) {
		case 'GET':
			$urlPath = $_GET['url'];
            $urlParam = explode('/', $urlPath);
			if ($urlParam[1] == 'unit') {
				$result = $this->getUnitList();
			}
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
	 * Get Contact List
	 *
	 * @return multitype:
	 */
	private function getUnitList() {
		try {
			$db = $this->dbConnect();
			$list = array();
			$query = "SELECT id, unit_name FROM tbl_units WHERE unit_status=1";
			$result = $db->query($query);
			$row_cnt = mysqli_num_rows($result);
			if ($row_cnt > 0) {
				$list = array();
				while ($row = $result->fetch_assoc()) {
					$list[] = $row;
				}
				$result = array('unitList' => $list);
				$resultArray = array(
					"apiStatus" => array(
						"code" => "200",
						"message" => "Units list fetched successfully"),
					"result" => $result,
				);
				return $resultArray;
			} else {
				throw new Exception("No data available.");
			}
		} catch (Exception $e) {
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
	public function processList($request, $token) {
		try {
			$responseData = $this->processMethod($request, $token);
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