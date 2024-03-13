<?php
// Include Deals Model
require_once "model/login.php";
class LOGIN extends LOGINMODEL {
	public function loginCtrl($data, $token) {
		try {
			$response = $this->processList($data, $token);
			echo $this->json($response);
			exit();
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}
}
// Initiate controller & Response method
$classActivate = new LOGIN();

// Reponse for the request
$classActivate->loginCtrl($data, $token);
?>