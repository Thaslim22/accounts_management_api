<?php
require_once "include/apiResponseGenerator.php";
require_once "include/dbConnection.php";
class LOGINMODEL extends APIRESPONSE
{
    private function processMethod($data, $token)
    {

        $request = explode("/", substr(@$_SERVER['PATH_INFO'], 1));

        switch (REQUESTMETHOD) {
            case 'POST':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] === "forgotPassword") {
                    $result = $this->forgotPassword($data);
                    return $result;
                }  
                else {
                    $result = $this->loginCheck($data);
                    return $result;
                }
                break;
            case 'PUT':
                // $result = $this->    ($data, $token);
                // return $result;
                break;
            case 'DELETE':
                echo REQUESTMETHOD;
                exit;
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
    private function dbConnect()
    {
        $conn = new DBCONNECTION();
        $db = $conn->connect();
        return $db;
    }

    /**
     * Get Login Authendication
     *
     * @return multitype:
     */
    private function loginCheck($request)
    {
        try {
            if (empty($request['loginType'])) {
                throw new Exception("Please select login Type");
            } else if (empty($request['userName'])) {
                throw new Exception("Please give the User Name");
            } else if (empty($request['password'])) {
                throw new Exception("Please give the Password");
            }
            $db = $this->dbConnect();
			if($request['loginType'] === "user"){
                $query = "SELECT name, password, id FROM tbl_users";
                $query .= " WHERE email_id = '" . $request['userName'] . "' AND status = 1";
            } 
            else {
				throw new Exception("Your Login has not actiated.");
			}
            $result = $db->query($query);
            if ($result) {
                $row_cnt = mysqli_num_rows($result);
                if ($row_cnt > 0) {
                    $data = mysqli_fetch_array($result, MYSQLI_ASSOC);
                    $hash = hash('sha256', hash('sha256', $request['password']));
                    if ($hash != $data['password']) {
                        throw new Exception("Invalid password");
                    }
                } else {
                    throw new Exception("Invalid Username Or password");
                }
            } else {
                throw new Exception("Invalid Username Or password");
            }

            // Create Token collection for authendication
            $token = md5(uniqid(mt_rand(), true));
            $roles = $this->getUserRoles($data['id']);
           
            $userDetails = array(
                'loginid' => $data['id'],
                'name' => $data['name'],
                // 'redirectUrl' => $redirectUrl,
                'roles' => $roles,
            );
            $result = array(
                "token" => $token,
                "userDetail" => $userDetails,
            );
            if (empty($newuser)) {
                $new = array(
                    "firstTime" => "true",
                );
                $result = array_merge($result, $new);
            }
            $userId = $data['id'];
            $timeNow = date("Y-m-d H:i:s");
            $sqlInsert = "INSERT INTO tbl_user_login_log (user_id, token, login_time, last_active_time) VALUES ('$userId', '$token', '$timeNow', '$timeNow')";

            //print_r($sqlInsert);exit();
            if ($db->query($sqlInsert) === true) {
                $db->close();
                $logger = $this->loginLogCreate("logged into the application", $request['userName'], getcwd());
            }
            $resultArray = array(
                "apiStatus" => array(
                    "code" => "200",
                    "message" => "Login Successfully"),
                "result" => $result,
            );
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
    public function loginLogCreate($message, $userName, $dir)
    {
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

    public function getUserRoles($userId)
    {
        try {
            $db = $this->dbConnect();
            $querySdfd = "SELECT rl.role_name, rl.id FROM tbl_user_role_map as urm JOIN tbl_roles as rl ON urm.role_id=rl.id WHERE urm.user_id = '$userId'";
            $result = $db->query($querySdfd);
            $row_cnt = mysqli_num_rows($result);

            $data = mysqli_fetch_array($result, MYSQLI_ASSOC);
            $role = array('roleName' => $data['role_name'], 'id' => $data['id']);
            return $role;
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
     * Function is to forgot the password
     *
     * @param array $request
     * @param array $user
     * @throws Exception
     * @return multitype:string NULL
     */

    public function forgotPassword($request)
    {
        try {
            if (empty($request['email'])) {
                throw new Exception("Please provide your registered email");
            }
            $db = $this->dbConnect();
            $query = "SELECT a.id,a.name FROM tbl_users a";
            $query .= " WHERE a.email_id = '" . $request['email'] . "'";
            $result = $db->query($query);
            if ($result) {
                $row_cnt = mysqli_num_rows($result);
                if ($row_cnt > 0) {
                    $data = mysqli_fetch_array($result, MYSQLI_ASSOC);
                } else {
                    throw new Exception("Invalid Username");
                }
            } else {
                throw new Exception("Invalid Username");
            }

            // Create random password
            $password = $this->randomPassword();

            $userData = array(
                "password" => $password,
                "email" => $request['email'],
                "name" => $data['name'],
            );

            $sendMailStatus = $this->sendForgorPasswordMail($userData);
            if ($sendMailStatus) {
                $hash = hash('sha256', hash('sha256', $password));
                // $sqlUpdateQuery = "UPDATE tbl_users SET password = '$hash' WHERE id = '" . $data['id'] . "'";;
                $sqlInsert = "INSERT INTO tbl_forgot_password (userId, password, hash) VALUES ('" . $data['id'] . "', '$password', '$hash')";
                //print_r($sqlInsert);exit();
                if ($db->query($sqlInsert) === true) {
                    // $db->query($sqlInsert);
                    $db->close();
                }
                $resultArray = array(
                    "apiStatus" => array(
                        "code" => "200",
                        "message" => "Forgot password mail sent successfully"),
                );
                return $resultArray;
            } else {
                return array(
                    "apiStatus" => array(
                        "code" => "401",
                        "message" => "Unable to process the request. Not able to send mail. Please contact support team!."),
                );
            }
        } catch (Exception $e) {
            return array(
                "result" => "401",
                "message" => $e->getMessage(),
            );
        }
    }

    private function randomPassword()
    {
        $length = 8;
        $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $str = '';
        $max = mb_strlen($keyspace, '8bit') - 1;
        if ($max < 1) {
            throw new Exception('$keyspace must be at least two characters long');
        }
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[random_int(0, $max)];
        }
        return $str;
    }

    private function sendForgorPasswordMail($userData)
    {
        try {
            $username = $userData['email'];
            $contactname = $userData['name'];
            $password = $userData['password'];
            $headers = 'From: noreply@vcloudpoint.com' . "\r\n" . 'Reply-To: noreply@vcloudpoint.com' . "\r\n" . 'X-Mailer: PHP/' . phpversion();
            $headers .= 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-Type: text/html; charset=iso-8859-1' . '\r\n';
            $message = "Hi $contactname, \nYour request for forgot password has been activated with vCloud Point. Click the forgot login link and change your password.\n

			Forgot Login URL : <a href='http://yebiy.com/forgot-login'>Click HERE</a>
			<table border='0' cellspacing='0'>
			<tr>
			<td>Username: </td>
			<td>Temporary Password: </td>
			</tr>
			<tr>
			<td>$username </td>
			<td>$password</td>
			</tr>
			</table>";
            if (!mail($userData['email'], "From vCloudPoint", $message, $headers)) {
                $mailStatus = "Cannot send the mail to User";
                return false;
            } else {
                $mailStatus = "Mail send to the User";
                return true;
            }

        } catch (Exception $e) {
            return array(
                "result" => "401",
                "message" => $e->getMessage(),
            );
        }
    }
    /**
     * Function is to check the Login Authendication By token
     *
     * @param array $request
     * @throws Exception
     * @return multitype:
     */
    public function tokenCheck($token = "")
    {
        try {
            if (empty($token)) {
                throw new Exception("Please give the Token");
            }
            $db = $this->dbConnect();
            // $token = explode(" ", $request);
            $query = "SELECT a.id, u.email_id, a.user_id,c.role_id,b.role_name FROM tbl_user_login_log a
            LEFT JOIN tbl_user_role_map c ON a.user_id = c.user_id
            LEFT JOIN tbl_roles b ON c.role_id = b.id
            LEFT JOIN tbl_users u ON a.user_id = u.id
            WHERE a.token = '$token'";
            $result = $db->query($query);
            $row_cnt = mysqli_num_rows($result);
            $data = mysqli_fetch_array($result, MYSQLI_ASSOC);
            if ($row_cnt < 1) {
                throw new Exception("Unauthorized Login");
            }
            return $data;
        } catch (Exception $e) {
            $this->loginLogCreate($e->getMessage(), "", getcwd());
            throw new Exception($e->getMessage());
        }
    }

    // Unautherized api request
    private function handle_error($request)
    {
    }
    /**
     * Function is to process the crud request
     *
     * @param array $request
     * @return array
     */
    public function processList($request, $token)
    {
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
