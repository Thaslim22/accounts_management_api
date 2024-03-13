<?php
// Include Deals Model
require_once "model/common.php";
class COMMON extends COMMONMODEL {
	public function commonCtrl($request, $token) {
		try {
			$response = $this->processList($request, $token);
			echo $this->json($response);
			exit();
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}
}
// Initiate controller & Response method
$classActivate = new COMMON();

// Reponse for the request
$classActivate->commonCtrl($data, $token);

?>