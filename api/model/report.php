<?php
// error_reporting(E_ALL);

require_once "include/apiResponseGenerator.php";
require_once "include/dbConnection.php";
class REPORTMODEL extends APIRESPONSE
{
  private function processMethod($data, $loginData)
  {

    switch (REQUESTMETHOD) {
      case 'GET':
        $urlPath = $_GET['url'];
        $urlParam = explode('/', $urlPath);
        if ($urlParam[1] == "download") {
          $result = $this->generateReport($data, $loginData, $urlParam[1]);
          return $result;
        } else {
          throw new Exception("Unable to proceed your request!");
        }

        break;
      case 'POST':
        $urlPath = $_GET['url'];
        $urlParam = explode('/', $urlPath);
        if ($urlParam[1] === 'generate' || $urlParam[1] === 'download') {
          $result = $this->generateReport($data, $loginData, $urlParam[1]);
          return $result;
        } else {
          throw new Exception("Unable to proceed your requestsssss!");
        }
        break;
      case 'PUT':
        throw new Exception("Unable to proceed your request!");
        break;
      case 'DELETE':
        throw new Exception("Unable to proceed your request!");
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
  public function generateReport($data, $loginData, $type)
  {
    // echo ($type);
    // exit;
    try {
      $db = $this->dbConnect();
      $validationData = array();

      $this->validateInputDetails($validationData);
      if ($data["fromDate"] == "" && $data['toDate'] == "" && $data['paymentStatus'] == "" && $data['customerName'] == "") {
        throw new Exception("All the field  should not be empty!");
      }
      if ($data["fromDate"] != "") {
        if ($data['toDate'] == "") {
          throw new Exception("ToDate should not be empty! ");
        }
      }
      if ($data["toDate"] != "") {
        if ($data['fromDate'] == "") {
          throw new Exception("FromDate should not be empty! ");
        }
      }
      if ($data["fromDate"] != "" && $data['toDate'] != "") {
        $saleDateWiseFilter = " and  sale_date >='" . $data['fromDate'] . "' and sale_date <= '" . $data['toDate'] . "' ";
        $lossDateWiseFilter = " and  loss_date >='" . $data['fromDate'] . "' and loss_date <= '" . $data['toDate'] . "' ";
        $purchaseDateWiseFilter = " and  purchase_date >='" . $data['fromDate'] . "' and purchase_date <= '" . $data['toDate'] . "' ";
      } else {
        $saleDateWiseFilter = "";
        $lossDateWiseFilter = "";
        $purchaseDateWiseFilter = "";
      }

      if (isset($data["paymentStatus"]) && $data['paymentStatus'] != "") {
        $paymetWise = " and payment_status ='" . $data['paymentStatus'] . "' ";
      } else {
        $paymetWise = "";
      }
      if (isset($data['customerName']) && $data['customerName'] != "") {
        $customerNameWise = " and customer_name  ='" . $data['customerName'] . "'";
        $vendorNameWise = " and vendor_name  ='" . $data['customerName'] . "'";
      } else {
        $customerNameWise = "";
        $vendorNameWise = "";
      }
      $saleQuery = "SELECT id, invoice_number,sale_date, customer_name, city, product_type, product_quantity, unit, wages, transport, amount,payment_status FROM tbl_sales WHERE status = 1 and created_by = " . $loginData['user_id'] . $saleDateWiseFilter . $paymetWise . $customerNameWise;
      // echo $saleQuery;exit;
      $result = $db->query($saleQuery);
      $row_cnt = mysqli_num_rows($result);
      if ($row_cnt > 0) {
        while ($sal_data = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
          $sale_data[] = $sal_data;
        }
      }
      $saleAmountQuery = "SELECT ROUND(SUM(wages+transport+amount), 2) as amount FROM tbl_sales WHERE status = 1 and created_by = " . $loginData['user_id'] . $saleDateWiseFilter . $paymetWise . $customerNameWise;
      $saleAmount = $this->totalAmountCalculation($saleAmountQuery);
      $sale_amount = $saleAmount['amount'];
      $saleQuantityQuery = "SELECT SUM(product_quantity) as product_quantity  FROM tbl_sales WHERE status = 1 and created_by = " . $loginData['user_id'] . $saleDateWiseFilter . $paymetWise . $customerNameWise;
      $sale_Quantity = $this->totalAmountCalculation($saleQuantityQuery);

      $purchaseQuery = "SELECT id,invoice_number,purchase_date, vendor_name, city, product_type, product_quantity, unit, wages, transport, amount,payment_status FROM tbl_purchase WHERE status = 1 and created_by = " . $loginData['user_id'] . $purchaseDateWiseFilter . $paymetWise . $vendorNameWise;
      $result = $db->query($purchaseQuery);
      $row_cnt = mysqli_num_rows($result);
      if ($row_cnt > 0) {
        while ($pur_data = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
          $purchase_data[] = $pur_data;
        }
      }
      $purchaseQuantityQuery = "SELECT SUM(product_quantity) as product_quantity  FROM tbl_purchase WHERE status = 1 and created_by = " . $loginData['user_id'] . $purchaseDateWiseFilter . $paymetWise . $vendorNameWise;
      $purchase_Quantity = $this->totalAmountCalculation($purchaseQuantityQuery);

      $purchaseAmountQuery = "SELECT ROUND(SUM(wages+transport+amount), 2) as amount FROM tbl_purchase WHERE status = 1 and created_by = " . $loginData['user_id'] . $purchaseDateWiseFilter . $paymetWise . $vendorNameWise;
      $purchaseAmount = $this->totalAmountCalculation($purchaseAmountQuery);
      $purchase_amount = $purchaseAmount['amount'];
      if ($paymetWise == '') {
        $lossQuery = "SELECT id,invoice_number,loss_date, vendor_name, city, product_type, product_quantity, unit, amount FROM tbl_loss WHERE status = 1 and created_by = " . $loginData['user_id'] . $lossDateWiseFilter . $vendorNameWise;
        $result = $db->query($lossQuery);
        $row_cnt = mysqli_num_rows($result);
        if ($row_cnt > 0) {
          while ($los_data = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $loss_data[] = $los_data;
          }
        }
        $lossAmountQuery = "SELECT ROUND(SUM(amount), 2) as amount FROM tbl_loss WHERE status = 1 and created_by = " . $loginData['user_id'] . $lossDateWiseFilter . $vendorNameWise;
        $lossAmount = $this->totalAmountCalculation($lossAmountQuery);
        $loss_amount = $lossAmount['amount'];
      } else {
        $loss_data = null;
        $loss_amount = 0;
      }
      $totalIncome = $sale_amount;
      $expenses = $purchase_amount;
      $profit = $sale_amount - ($purchase_amount + $loss_amount);
      $loss = $loss_amount;
      $total_sale_product_quantity = $sale_Quantity['product_quantity'];
      $total_purchase_product_quantity = $purchase_Quantity['product_quantity'];
      if ($type == "generate") {
        $responseArray = array(
          "totalIncome" => $totalIncome,
          "expenses" => $expenses,
          "profit" => $profit,
          "loss" => $loss,
          "total_sale_product_quantity" => $total_sale_product_quantity,
          "total_purchase_product_quantity" => $total_purchase_product_quantity,
          "saleData" => $sale_data,
          "purchaseData" => $purchase_data,
          "lossData" => $loss_data


        );

        if ($responseArray) {
          $resultArray = array(
            "apiStatus" => array(
              "code" => "200",
              "message" => "Report details generated successfully"
            ),
            "result" => $responseArray,
          );
          return $resultArray;
        }
      } elseif ($type == "download") {
        $this->generateExcelReport($sale_data, $purchase_data, $loss_amount, $totalIncome, $profit, $expenses, $total_sale_product_quantity, $total_puechase_product_quantity, $paymetWise, $loss_data);
      }
    } catch (Exception $e) {
      throw new Exception($e->getMessage());
    }
  }

  public function generateExcelReport($sale_data, $purchase_data, $loss_amount, $totalIncome, $profit, $expenses, $total_sale_product_quantity, $total_puechase_product_quantity, $paymetWise, $loss_data)
  {
    $dateNow = date("Y-m-d");
    $file = "Report_" . $dateNow . ".xls";
    $html = '<!DOCTYPE html>
<html>
<head>
<style>
body{
  font-family: arial, sans-serif;
}
table {
  font-family: arial, sans-serif;
  border-collapse: collapse;
  width: 100%;
}

td, th {
  border: 0px;
  text-align: left;
  padding: 8px;
}
table tbody{
  color: #000000;
}
table td, table th{
  border: 1px solid #fff;
}
table.bordered {
  border: 1px solid #dddddd;
}
table .table-title{
  font-family: "arial";
  font-size: 14px;
  font-weight: bold;
  text-transform: uppercase;
}
table.bg-blue thead{
  background-color: #2676d5;
  color: #ffffff;
}
table.bg-blue tbody tr:nth-child(odd){
  background-color: #84bcff;
}
table.bg-blue tbody tr:nth-child(even){
  background-color: #bbdaff;
}
table.bg-green thead{
  background-color: #38a902;
  color: #ffffff;
}
table.bg-green tbody tr:nth-child(odd){
  background-color: #8ddc78;
}
table.bg-green tbody tr:nth-child(even){
  background-color: #bff8b0;
}

</style>
</head>
<body>

<h2>Report: </h2>

<table width="100%">
  <tr>
    <td colspan="3">
      <table width="100%" class="bordered bg-blue">
        <thead>
          <tr>
            <th width="25%">
              <div class="table-title">Total Income</div>
              <div>' . $totalIncome . '</div>
            </th>
            <th width="25%">
              <div class="table-title">Purchase</div>
              <div>' . $expenses . '</div>
            </th>
            <th width="25%">
              <div class="table-title">Profit</div>
              <div>' . $profit . '</div>
            </th>
            <th width="25%">
              <div class="table-title">Expence</div>
              <div>' . $loss_amount . '</div>
            </th>
            <th width="25%">
              <div class="table-title">Sale Total Quantity</div>
              <div>' . $total_sale_product_quantity . '</div>
            </th>
            <th width="25%">
              <div class="table-title">Purchase Total Quantity</div>
              <div>' . $total_puechase_product_quantity . '</div>
            </th>
          </tr>
        </thead>
      </table>
    </td>
  </tr>
  <tr>';
    if ($sale_data != null) {
      $html .= '
    <td width="48%" >
    <h4>Sale</h4>
    
      <table class="bordered bg-blue">
        <thead>
          <tr>
            <th>Invoice Number</th>
            <th>Date</th>
            <th>Customer Name</th>
            <th>City</th>
            <th>Product</th>
            <th>Quantity</th>
            <th>Amount</th>
            <th>Payment Status</th>
          </tr>
        </thead>
        <tbody>';
      foreach ($sale_data as $sale) {
        $html .= '<tr>
            <td>' . $sale['invoice_number'] . '</td>
            <td>' . $sale['sale_date'] . '</td>
            <td>' . $sale['customer_name'] . '</td>
            <td>' . $sale['city'] . '</td>
            <td>' . $sale['product_type'] . '</td>
            <td>' . $sale['product_quantity'] . $sale['unit'] . '</td>
            <td>' . $sale['amount'] . '</td>
            <td>' . $sale['payment_status'] . '</td>
          </tr>';
      }
      $html .= ' </tbody>
      </table>
    </td>
    <td width="4%"> </td>';
    }
    if ($purchase_data != null) {
      $html .= '
    <td width="48%">
    <h4>Purchase</h4>
      <table class="bordered bg-green">
        <thead>
          <tr>
            <th>Invoice Number</th>
            <th>Date</th>
            <th>Vendor Name</th>
            <th>City</th>
            <th>Product</th>
            <th>Quantity</th>
            <th>Amount</th>
            <th>Payment Status</th>
          </tr>
        </thead>
        <tbody>';
      foreach ($purchase_data as $purchase) {
        $html .= '<tr>
            <td>' . $purchase['invoice_number'] . '</td>
            <td>' . $purchase['purchase_date'] . '</td>
            <td>' . $purchase['vendor_name'] . '</td>
            <td>' . $purchase['city'] . '</td>
            <td>' . $purchase['product_type'] . '</td>
            <td>' . $purchase['product_quantity'] . $purchase['unit'] . '</td>
            <td>' . $purchase['amount'] . '</td>
            <td>' . $purchase['payment_status'] . '</td>
          </tr>';
      }
      $html .= '</tbody>
      </table>
    </td>
    <td width="4%"> </td>
    ';
    }
    if ($paymetWise == "" && $loss_data != null) {
      $html .= '
    <td width="48%">
    <h4>Loss</h4>
      <table class="bordered bg-green">
        <thead>
          <tr>
            <th>Invoice Number</th>
            <th>Date</th>
            <th>Vendor Name</th>
            <th>City</th>
            <th>Product</th>
            <th>Quantity</th>
            <th>Amount</th>
          </tr>
        </thead>
      
        <tbody>';

      foreach ($loss_data as $loss) {
        $html .= '<tr>
            <td>' . $loss['invoice_number'] . '</td>
            <td>' . $loss['loss_date'] . '</td>
            <td>' . $loss['vendor_name'] . '</td>
            <td>' . $loss['city'] . '</td>
            <td>' . $loss['product_type'] . '</td>
            <td>' . $loss['product_quantity'] . $loss['unit'] . '</td>
            <td>' . $loss['amount'] . '</td>
          </tr>';
      }

      $html .= '</tbody>
      </table>
    </td>
    ';
    }
    $html .= '
  </tr>
</table>
</body>
</html>';
    header("Content-type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$file\"");
    echo $html;
    exit();
  }





  public function totalAmountCalculation($sql)
  {
    try {
      $db = $this->dbConnect();
      $result = $db->query($sql);
      $row_cnt = mysqli_num_rows($result);
      if ($row_cnt > 0) {
        $data = mysqli_fetch_array($result, MYSQLI_ASSOC);
        return $data;
      }
    } catch (Exception $e) {
      throw new Exception($e->getMessage());
    }
  }
  public function validateInputDetails($validationData)
  {
    foreach ($validationData as $key => $value) {
      if (empty($value) || trim($value) == "") {
        throw new Exception($key . " should not be empty!");
      }
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
