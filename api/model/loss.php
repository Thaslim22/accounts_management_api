<?php
error_reporting(1);
error_reporting(E_ALL);

require_once "include/apiResponseGenerator.php";
require_once "include/dbConnection.php";
class LOSSMODEL extends APIRESPONSE
{
    private function processMethod($data, $loginData)
    {

        switch (REQUESTMETHOD) {
            case 'GET':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                 if ($urlParam[1] == "get") {
                    $result = $this->getLoss($data, $loginData);
                }
                else {
                    throw new Exception("Unable to proceed your request!");
                }
                return $result;
                break;
            case 'POST':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] === 'create') {
                    $result = $this->createLoss($data, $loginData);
                    return $result;                    
                }
                elseif ($urlParam[1] === 'list') {
                    $result = $this->getLossDetails($data, $loginData);
                    return $result;                    
                }
                else {
                    throw new Exception("Unable to proceed your request!");
                }
                break;
            case 'PUT':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] == "update") {
                    $result = $this->updateLoss($data, $loginData);
                }
                else {
                    throw new Exception("Unable to proceed your request!");
                }
                return $result;
                break;
            case 'DELETE':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                 if ($urlParam[1] == "delete") {
                    $result = $this->deleteLoss($data);
                }
                else {
                    throw new Exception("Unable to proceed your request!");
                }
                return $result;
                break;
            default:
                $result = $this->handle_error();
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
     * Function is to get the for particular record
     *
     * @param array $data
     * @return multitype:
     */
    public function getLossDetails($data, $loginData)
    {
        try {
            $responseArray = "";
            $res=array();
            $db = $this->dbConnect();            
            $recordCount=$this->getTotalCount($loginData);
            $start_index=$data['pageIndex']*$data['dataLength'];
            $end_index=$data['dataLength'];
            $sql = "SELECT id,invoice_number,loss_date, vendor_name, city, product_type, product_quantity, unit, amount FROM tbl_loss   WHERE status = 1 and created_by = ".$loginData['user_id']." ORDER BY id DESC LIMIT ".$start_index.",".$end_index."";

            $result = $db->query($sql);
            $row_cnt = mysqli_num_rows($result);
            if ($row_cnt > 0) {
                while($data = mysqli_fetch_array($result, MYSQLI_ASSOC)){
                    array_push($res, $data);
                }
                $responseArray = array(
                    "pageIndex"=>$start_index,
                    "dataLength"=>$end_index,
                    'LossData' => $res,
                );
            }
            if ($responseArray) {
                $resultArray = array(
                    "apiStatus" => array(
                        "code" => "200",
                        "message" => "Loss details fetched successfully"),
                    "result" => $responseArray,
                );
                return $resultArray;
            } else {
                return array(
                    "apiStatus" => array(
                        "code" => "404",
                        "message" => "No data found..."),
                );
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Function is to get the for particular record
     *
     * @param array $data
     * @return multitype:
     */
    public function getLoss($data, $loginData)
    {
        try {
            $id=$data[2];
            $db = $this->dbConnect();
            if(empty($data[2])){
                throw new Exception("Bad request");
            }

            $responseArray = "";
            $db = $this->dbConnect();
            $sql = "SELECT id,invoice_number,loss_date, vendor_name, city, product_type, product_quantity, unit, amount FROM tbl_loss	WHERE status = 1 and created_by = ".$loginData['user_id']." and id =$id";
            $result = $db->query($sql);
            $row_cnt = mysqli_num_rows($result);
            if ($row_cnt > 0) {
                $data = mysqli_fetch_array($result, MYSQLI_ASSOC);
                $responseArray = array(
                    'lossData' => $data,
                );
            }
            if ($responseArray) {
                $resultArray = array(
                    "apiStatus" => array(
                        "code" => "200",
                        "message" => "Loss details fetched successfully"),
                    "result" => $responseArray,
                );
                return $resultArray;
            } else {
                return array(
                    "apiStatus" => array(
                        "code" => "404",
                        "message" => "No data found..."),
                );
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    /**
     * Post/Add sale
     *
     * @param array $data
     * @return multitype:string
     */
    private function createLoss($data, $loginData)
    {

        try {
            $db = $this->dbConnect();
            $validationData = array("loss date"=>$data['lossDate'],"Amount"=>$data['amount']);
            $this->validateInputDetails($validationData);
            $dateNow = date("Y-m-d H:i:s");
            $insertQuery = "INSERT INTO tbl_loss (invoice_number,loss_date, vendor_name, city, product_type, product_quantity, unit, amount, created_by, created_date) VALUES ('" . $data['invoiceNumber'] . "','" . $data['lossDate'] . "','" . $data['vendorName'] . "','" . $data['city'] . "','" . $data['productType'] . "','" . $data['productQuantity'] . "','". $data['unit'] ."','" . $data['amount'] . "', '" . $loginData['user_id'] . "','$dateNow')";
            if ($db->query($insertQuery) === true) {
                $db->close();
                $statusCode="200";
                $statusMessage="Loss details created successfully";
                
            }else{
                $statusCode="500";
                $statusMessage="Unable to create loss details, please try again later";
            }
            $resultArray = array(
                "apiStatus" => array(
                    "code" => $statusCode,
                    "message" => $statusMessage),
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


    /**
     * Put/Update a Sale
     *
     * @param array $data
     * @return multitype:string
     */
    private function updateLoss($data, $loginData)
    {
        try {
            $db = $this->dbConnect();
            $validationData = array("Id"=>$data['id'],"Loss date"=>$data['lossDate'],"Amount"=>$data['amount']);
            $this->validateInputDetails($validationData);
            $dateNow = date("Y-m-d H:i:s");
            $updateQuery = "UPDATE tbl_loss SET invoice_number = '" . $data['invoiceNumber'] . "', loss_date = '" . $data['lossDate'] . "',vendor_name = '" . $data['vendorName'] . "', city = '" . $data['city'] . "', product_type = '" . $data['productType'] . "', product_quantity = '" . $data['productQuantity'] . "', 	unit = '" . $data['unit'] . "', amount = '" . $data['amount'] . "',updated_by = '" . $loginData['user_id'] . "',updated_date = '$dateNow' WHERE id = ".$data['id']."";
            if ($db->query($updateQuery) === true) {
                $db->close();
                $statusCode="200";
                $statusMessage="Loss details updated successfully";
                
            }else{
                $statusCode="500";
                $statusMessage="Unable to update loss details, please try again later";
            }
            $resultArray = array(
                "apiStatus" => array(
                    "code" => $statusCode,
                    "message" => $statusMessage),
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

    

    
    private function deleteLoss($data)
    {
        try {
           $id=$data[2];
            $db = $this->dbConnect();
            if(empty($data[2])){
                throw new Exception("Bad request");
            }
            $deleteQuery = "UPDATE tbl_loss set status=0 WHERE id = ".$id."";
            if ($db->query($deleteQuery) === true) {
                $db->close();
                $statusCode="200";
                $statusMessage="Loss details deleted successfully";
                
            }else{
                $statusCode="500";
                $statusMessage="Unable to delete loss details, please try again later";
            }
            $resultArray = array(
            "apiStatus" => array(
                "code" => $statusCode,
                "message" => $statusMessage),
            );
            return $resultArray;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());

        }
    }
    /**
     * Validate function for sale create
     *
     * @param array $data
     * @throws Exception
     * @return multitype:string NULL
     */
    public function validateInputDetails($validationData) {
        foreach ($validationData as $key => $value) {            
            if (empty($value) || trim($value) == "") {
                throw new Exception($key. " should not be empty!");
            }
        }    
    }
   

    private function getTotalCount($loginData)
    {
        try {
            $db = $this->dbConnect();
            $sql = "SELECT * FROM tbl_sales WHERE status = 1 and created_by = ".$loginData['user_id']."";
            $result = $db->query($sql);
            $row_cnt = mysqli_num_rows($result);
            return $row_cnt;
        } catch (Exception $e) {
            return array(
                "result" => "401",
                "message" => $e->getMessage(),
            );
        }
    }
    private function getTotalPages($dataCount)
    {
        try {
            $pages = null;
            if (MAX_LIMIT) {
                $pages = ceil((int) $dataCount / (int) MAX_LIMIT);
            } else {
                $pages = count($dataCount);
            }
            return $pages;
        } catch (Exception $e) {
            return array(
                "result" => "401",
                "message" => $e->getMessage(),
            );
        }
    }
    // Unautherized api request
    private function handle_error()
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
            return $responseData;
        } catch (Exception $e) {
            return array(
                "apiStatus" => array(
                    "code" => "401",
                    "message" => $e->getMessage()),
            );
        }
    }
}
