<?php
// Default Config
define('APPLICATIONPATH', '/');
define('DEFAULTPAGE', 'index.php');
define('CONTROLLERPATH', '/api/controller/');
define('MODELPATH', '/api/model/');
define('RESPONSEMESSAGEFILE', 'include/messageProperty.json');

// Global Variables
define('ERRORREPORTING', E_ALL);
define('CONTROLEREXT', 'Ctrl');
define('APIRESPONSETYPE', 'application/json');
define('EXPIRETIME', '20');

// Request Setting
define('REQUESTMETHOD', $_SERVER['REQUEST_METHOD']);
// Request Setting
define('UPLOAD', '/uploads');

// Log Files
define('LOG_GENERAL', getcwd() . '/logs/general.log');
define('LOG_LOGIN', getcwd() . '/logs/login.log');

// Validation input
define('MIN_LENGTH', 2);
define('MAX_LENGTH', 60);
define('MOB_MIN_LENGTH', 10);
define('MOB_MAX_LENGTH', 15);
define('MAX_LIMIT', 10);
define('DEFAULT_DISPLAY_COUNT', 4);

// // Redirect URLs
// define('SUPERADMINURL', '/admin/dashboard');
// define('ADMINURL', '/admin/dashboard');
// define('STAFFURL', '/staff/dashboard');
// define('STUDENTURL', '/student/my-profile');

// $settings = array(
// 	'reportList' => array(
// 		'superadmin' => 'all',
// 		'countrymanger' => '>1',
// 		'marketingmanger' => '>1',
// 		'zonemanger' => '>3',
// 		'regionmanger' => '>4',
// 	),
// );
?>