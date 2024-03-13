<?php
// error_reporting(E_ALL);

require_once "include/apiResponseGenerator.php";
require_once "include/dbConnection.php";
class PURCHASEMODEL extends APIRESPONSE
{
    private function processMethod($data, $loginData)
    {

        switch (REQUESTMETHOD) {
            case 'GET':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] == "get") {
                    $result = $this->getPurchase($data, $loginData);
                } else {
                    throw new Exception("Unable to proceed your request!");
                }
                return $result;
                break;
            case 'POST':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] === 'create') {
                    $result = $this->createPurchase($data, $loginData);
                    return $result;
                } elseif ($urlParam[1] === 'list') {
                    $result = $this->getPurchaseDetails($data, $loginData);
                    return $result;
                } else {
                    throw new Exception("Unable to proceed your request!");
                }
                break;
            case 'PUT':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] === "update") {
                    $result = $this->updatePurchase($data, $loginData);
                } else {
                    throw new Exception("Unable to proceed your request!");
                }
                return $result;
                break;
            case 'DELETE':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] === "delete") {
                    $result = $this->deletePurchase($data);
                } else {
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
    public function getPurchaseDetails($data, $loginData)
    {
        try {
            $responseArray = "";
            $res = array();
            $db = $this->dbConnect();
            $recordCount = $this->getTotalCount($loginData);
            $start_index = $data['pageIndex'] * $data['dataLength'];
            $end_index = $data['dataLength'];

            $validationData = array();

            $this->validateInputDetails($validationData);
            // if ($data['InvoiceNumber'] == "" && $data['paymentStatus'] == "" && $data['VendorName'] == "") {
            //     throw new Exception("All the field  should not be empty!");
            // }

            if (isset($data["paymentStatus"]) && $data['paymentStatus'] != "") {
                $paymetWise = " and payment_status ='" . $data['paymentStatus'] . "' ";
            } else {
                $paymetWise = "";
            }
            if (isset($data["invoiceNumber"]) && $data['invoiceNumber'] != "") {
                $InvoiceWise = " and invoice_number ='" . $data['invoiceNumber'] . "' ";
            } else {
                $InvoiceWise = "";
            }

            if (isset($data['vendorName']) && $data['vendorName'] != "") {
                $vendorNameWise = " and vendor_name  ='" . $data['vendorName'] . "'";
            } else {
                $vendorNameWise = "";
            }
            $sql = "SELECT id, invoice_number, purchase_date, vendor_name, city, product_type, product_quantity, unit, wages, transport, amount, payment_status 
        FROM tbl_purchase   
        WHERE status = 1 
        AND created_by = " . $loginData['user_id'] . " 
        $InvoiceWise
        $paymetWise
        $vendorNameWise
        ORDER BY id DESC 
        LIMIT " . $start_index . "," . $end_index;
            // Print_r($sql);
            // exit;
            $result = $db->query($sql);
            $row_cnt = mysqli_num_rows($result);
            if ($row_cnt > 0) {
                while ($data = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                    array_push($res, $data);
                }
                $responseArray = array(
                    "pageIndex" => $start_index,
                    "dataLength" => $end_index,
                    'purchaseData' => $res,
                );
            }
            if ($responseArray) {
                $resultArray = array(
                    "apiStatus" => array(
                        "code" => "200",
                        "message" => "Purchase details fetched successfully"
                    ),
                    "result" => $responseArray,
                );
                return $resultArray;
            } else {
                return array(
                    "apiStatus" => array(
                        "code" => "404",
                        "message" => "No data found..."
                    ),
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
    public function getPurchase($data, $loginData)
    {
        try {
            $id = $data[2];
            $db = $this->dbConnect();
            if (empty($data[2])) {
                throw new Exception("Bad request");
            }

            $responseArray = "";
            $db = $this->dbConnect();
            $sql = "SELECT id,invoice_number,purchase_date, vendor_name, city, product_type, product_quantity, unit, wages, transport, amount,payment_status FROM tbl_purchase	WHERE status = 1 and created_by = " . $loginData['user_id'] . " and id =$id";
            $result = $db->query($sql);
            $row_cnt = mysqli_num_rows($result);
            if ($row_cnt > 0) {
                $data = mysqli_fetch_array($result, MYSQLI_ASSOC);
                $responseArray = array(
                    'purchaseData' => $data,
                );
            }
            if ($responseArray) {
                $resultArray = array(
                    "apiStatus" => array(
                        "code" => "200",
                        "message" => "Purchase details fetched successfully"
                    ),
                    "result" => $responseArray,
                );
                return $resultArray;
            } else {
                return array(
                    "apiStatus" => array(
                        "code" => "404",
                        "message" => "No data found..."
                    ),
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
    private function createPurchase($data, $loginData)
    {

        try {
            $db = $this->dbConnect();
            $validationData = array("Invoice Number" => $data['invoiceNumber'], "Purchase date" => $data['purchaseDate'], "Vendor name" => $data['vendorName'], "Product type" => $data['productType'], "Amount" => $data['amount']);
            // print_r($validationData);exit;
            $this->validateInputDetails($validationData);
            $sql = "SELECT id FROM tbl_purchase WHERE invoice_number = '" . $data['invoiceNumber'] . "' AND status = 1";
            $result = mysqli_query($db, $sql);
            $row_cnt = mysqli_num_rows($result);
            if ($row_cnt > 0) {
                throw new Exception("Invoice number is already exist");
            }
            $dateNow = date("Y-m-d H:i:s");
            $insertQuery = "INSERT INTO tbl_purchase (invoice_number,purchase_date, vendor_name, city, product_type, product_quantity, unit, wages, transport, amount,payment_status, created_by, created_date) VALUES ('" . $data['invoiceNumber'] . "','" . $data['purchaseDate'] . "','" . $data['vendorName'] . "','" . $data['city'] . "','" . $data['productType'] . "','" . $data['productQuantity'] . "','" . $data['unit'] . "','" . $data['wages'] . "','" . $data['transport'] . "','" . $data['amount'] . "', '" . $data['paymentStatus'] . "','" . $loginData['user_id'] . "','$dateNow')";
            if ($db->query($insertQuery) === true) {
                $db->close();
                $statusCode = "200";
                $statusMessage = "Purchase details created successfully";
            } else {
                $statusCode = "500";
                $statusMessage = "Unable to create Purchase details, please try again later";
            }
            $resultArray = array(
                "apiStatus" => array(
                    "code" => $statusCode,
                    "message" => $statusMessage
                ),
            );
            return $resultArray;
        } catch (Exception $e) {
            return array(
                "apiStatus" => array(
                    "code" => "401",
                    "message" => $e->getMessage()
                ),
            );
        }
    }


    /**
     * Put/Update a Sale
     *
     * @param array $data
     * @return multitype:string
     */
    private function updatePurchase($data, $loginData)
    {
        try {
            $db = $this->dbConnect();
            $validationData = array("Id" => $data['id'], "Invoice Number" => $data['invoiceNumber'], "Purchase date" => $data['purchaseDate'], "Vendor name" => $data['vendorName'], "Product type" => $data['productType'], "Amount" => $data['amount']);
            $this->validateInputDetails($validationData);
            $dateNow = date("Y-m-d H:i:s");
            $updateQuery = "UPDATE tbl_purchase SET invoice_number = '" . $data['invoiceNumber'] . "', purchase_date = '" . $data['purchaseDate'] . "',vendor_name = '" . $data['vendorName'] . "', city = '" . $data['city'] . "', product_type = '" . $data['productType'] . "', product_quantity = '" . $data['productQuantity'] . "', 	unit = '" . $data['unit'] . "', wages = '" . $data['wages'] . "', transport = '" . $data['transport'] . "', amount = '" . $data['amount'] . "',payment_status='" . $data['paymentStatus'] . "',updated_by = '" . $loginData['user_id'] . "',updated_date = '$dateNow' WHERE id = " . $data['id'] . "";
            if ($db->query($updateQuery) === true) {
                $db->close();
                $statusCode = "200";
                $statusMessage = "Purchase details updated successfully";
            } else {
                $statusCode = "500";
                $statusMessage = "Unable to update purchase details, please try again later";
            }
            $resultArray = array(
                "apiStatus" => array(
                    "code" => $statusCode,
                    "message" => $statusMessage
                ),
            );
            return $resultArray;
        } catch (Exception $e) {
            return array(
                "apiStatus" => array(
                    "code" => "401",
                    "message" => $e->getMessage()
                ),
            );
        }
    }




    private function deletePurchase($data)
    {
        try {
            $id = $data[2];
            $db = $this->dbConnect();
            if (empty($data[2])) {
                throw new Exception("Bad request");
            }
            $deleteQuery = "UPDATE tbl_purchase set status=0 WHERE id = " . $id . "";
            if ($db->query($deleteQuery) === true) {
                $db->close();
                $statusCode = "200";
                $statusMessage = "Purchase details deleted successfully";
            } else {
                $statusCode = "500";
                $statusMessage = "Unable to delete purchase details, please try again later";
            }
            $resultArray = array(
                "apiStatus" => array(
                    "code" => $statusCode,
                    "message" => $statusMessage
                ),
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
    public function validateInputDetails($validationData)
    {
        foreach ($validationData as $key => $value) {
            if (empty($value) || trim($value) == "") {
                throw new Exception($key . " should not be empty!");
            }
        }
    }


    private function getTotalCount($loginData)
    {
        try {
            $db = $this->dbConnect();
            $sql = "SELECT * FROM tbl_purchase WHERE status = 1 and created_by = " . $loginData['user_id'] . "";
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
                    "message" => $e->getMessage()
                ),
            );
        }
    }
}
