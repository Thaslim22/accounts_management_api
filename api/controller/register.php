<?php
// Include Deals Model
require_once "model/register.php";
require_once "model/login.php";
class REGISTER extends REGISTERMODEL {
	public function registerCtrl($request, $tokenParms) {
		try {
			$response = $this->processList($request, $tokenParms);
			echo $this->json($response);
			exit();
		} catch (Exception $e) {
			echo $this->json(array(
				"code" => "400",
				"message" => $e->getMessage(),
			));
			exit();
		}
	}
}

// Initiate controller & Response method
$classActivate = new REGISTER();
// Reponse for the request
$classActivate->registerCtrl($data, $token);

?>