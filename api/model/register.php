<?php
require_once "include/apiResponseGenerator.php";
require_once "include/dbConnection.php";
class REGISTERMODEL extends APIRESPONSE {
	private function processMethod($data) {
		
		switch (REQUESTMETHOD) {
		case 'POST':
			$type = $data['type'];
			if ($type == 'user') {
				$result = $this->userRegistration($data);
			} else{
				$result = array(
					"apiStatus" => array(
						"code" => "404",
						"message" => "Invalid request"),
				);
			}
			return $result;
			break;
		default:
			$result = $this->handle_error($data);
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
	 * Post/Register Member
	 *
	 * @param array $data
	 * @return multitype:string
	 */
	private function userRegistration($data) {
		try {
			$db = $this->dbConnect();
			$userData = $data['userData'];
			if ($userData['password'] != $userData['confirmPassword']) {
				throw new Exception("Password & Confirm Password are not correct!");
			}
			$password = $userData['password'];
			$sql = "SELECT id FROM tbl_users WHERE email_id = '".$userData['emailId'] . "' AND status = 1";
			$result = mysqli_query($db, $sql);
			$row_cnt = mysqli_num_rows($result);
			if ($row_cnt > 0) {
				throw new Exception("User already exist");
			}
			if (!empty($userData['name'])) {
				$name = $userData['name'];
			} else {
				$name = "";
			}
			if (!empty($userData['phone'])) {
				$phone = $userData['phone'];
			} else {
				$phone = "";
			}

			$hashed_password = hash('sha256', hash('sha256', $password));
			
			
			$insertQuery = "INSERT INTO tbl_users (`name`, email_id,`password`, phone) VALUES ('" .$name . "','" . $userData['emailId'] . "','". $hashed_password ."','" . $phone . "')";
			if ($db->query($insertQuery) === TRUE) {
				$lastInsertedId = mysqli_insert_id($db);
				$this->updateUserRole($lastInsertedId);
				$this->updateUserActivation($lastInsertedId);
				$db->close();
			}
			$resultArray = array(
				"apiStatus" => array(
					"code" => "200",
					"message" => "Your registration has submitted Successfully"),
				"result" => array("mailStatus" => ""),
			);
			return $resultArray;
		} catch (Exception $e) {
			return array(
				"apiStatus" => array(
					"code" => "401",
					"message" => $e->getMessage()),
			);
		}
	}
	private function updateUserRole($lastInsertedId) {
		try {
			$db = $this->dbConnect();

			if ($lastInsertedId) {
				//$deleteQuery = "DELETE FROM tbl_users WHERE id = '" . $data['id'] . "'";
				$insertQuery = "INSERT INTO tbl_user_role_map (`user_id`, `role_id`, `created_by`) VALUES ('$lastInsertedId', '1', '$lastInsertedId') ";
				if ($db->query($insertQuery) === TRUE) {
					$db->close();
					return true;
				}
				return false;
			} else {
				throw new Exception("Not able to update role");
			}

		} catch (Exception $e) {
			return array(
				"apiStatus" => array(
					"code" => "401",
					"message" => $e->getMessage()),
			);
		}
	}

	private function updateUserActivation($lastInsertedId) {
		try {
			$db = $this->dbConnect();

			if ($lastInsertedId) {
				//$deleteQuery = "DELETE FROM tbl_users WHERE id = '" . $data['id'] . "'";
				$insertQuery = "INSERT INTO tbl_user_activation (user_id,email_status,activation_status) VALUES ('$lastInsertedId','1','1') ";
				if ($db->query($insertQuery) === TRUE) {
					$db->close();
					return true;
				}
				return false;
			} else {
				throw new Exception("Not able to update activation");
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