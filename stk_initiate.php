<?php
header("Content-Type: application/json");

// Ensure only POST requests are allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Method Not Allowed. Use POST."]);
    exit;
}

date_default_timezone_set('Africa/Nairobi');

// M-PESA API Credentials
$consumerKey = 'rDfxbPHYjhMHqmX72AZbvigxhnIJKy3gTQFBUiDSOU6HeBUS';
$consumerSecret = 'TbJBs8IvOxoGwUkwPdfBcrempsAvRPgYPA0LWsrpZQiKkJlHqombSMdnyFZexdDd';
$BusinessShortCode = '174379'; //Paybill/Till
$Passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';
$CallBackURL = 'https://largely-discrete-flea.ngrok-free.app/mbesa/callback_url.php';

// Get input data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['phone']) || !isset($data['amount'])) {
    echo json_encode(["error" => "Missing phone or amount"]);
    exit;
}

// Validate and Format Phone Number
$PartyA = preg_replace('/\D/', '', $data['phone']); // Remove non-digits

if (preg_match('/^(07|01)\d{8}$/', $PartyA)) {
    $PartyA = '254' . substr($PartyA, 1);
}

if (!preg_match('/^254(7|1)\d{8}$/', $PartyA)) {
    echo json_encode(["error" => "Invalid phone number format. Use 07XXXXXXXX or 01XXXXXXXX."]);
    exit;
}

// Validate Amount
$Amount = filter_var($data['amount'], FILTER_VALIDATE_FLOAT);
if (!$Amount || $Amount <= 0) {
    echo json_encode(["error" => "Invalid amount."]);
    exit;
}

$AccountReference = '997005 QTS'; //paybill
$TransactionDesc = 'This is a payment'; //any desc
$Timestamp = date('YmdHis');
$Password = base64_encode($BusinessShortCode . $Passkey . $Timestamp);

// Get Access Token
$access_token_url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
$curl = curl_init($access_token_url);
curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_USERPWD, "$consumerKey:$consumerSecret");
$result = curl_exec($curl);
curl_close($curl);

$result = json_decode($result, true);
if (!isset($result['access_token'])) {
    echo json_encode(["error" => "Failed to obtain access token"]);
    exit;
}

$access_token = $result['access_token'];

// STK Push Request
$stkheader = [
    'Content-Type:application/json',
    'Authorization:Bearer ' . $access_token
];

$curl_post_data = [
    'BusinessShortCode' => $BusinessShortCode,
    'Password' => $Password,
    'Timestamp' => $Timestamp,
    'TransactionType' => 'CustomerPayBillOnline',
    'Amount' => $Amount,
    'PartyA' => $PartyA,
    'PartyB' => $BusinessShortCode,
    'PhoneNumber' => $PartyA,
    'CallBackURL' => $CallBackURL,
    'AccountReference' => $AccountReference,
    'TransactionDesc' => $TransactionDesc
];

$curl = curl_init('https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest');
curl_setopt($curl, CURLOPT_HTTPHEADER, $stkheader);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($curl_post_data));

$curl_response = curl_exec($curl);
curl_close($curl);

// Handle API Response
$response = json_decode($curl_response, true);

if (isset($response['ResponseCode']) && $response['ResponseCode'] == "0") {
    echo json_encode(["success" => "Payment initiated successfully"]);
} else {
    echo json_encode(["error" => $response['errorMessage'] ?? "Failed to process payment"]);
}
?>
