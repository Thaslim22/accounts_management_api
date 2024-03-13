<?php
// Include Deals Model
require_once "model/logout.php";
class LOGOUT extends LOGOUTMODEL {
	public function logoutCtrl($token) {
		try {
			$response = $this->processRequest($token);
			echo $this->json($response);
			exit();
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}
}
// Initiate controller & Response method
$classActivate = new LOGOUT();

// Reponse for the request
$classActivate->logoutCtrl($token);
?>