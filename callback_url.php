<?php
header("Content-Type: application/json");

// Send response back to Safaricom immediately
$response = json_encode([
    "ResultCode" => 0, 
    "ResultDesc" => "Confirmation Received Successfully"
]);

// Receive M-PESA response
$mpesaResponse = file_get_contents('php://input');

// Log the response in a JSON file
$logFile = "M_PESAConfirmationResponse.json";
file_put_contents($logFile, $mpesaResponse . PHP_EOL, FILE_APPEND);

// Decode the JSON response
$callbackContent = json_decode($mpesaResponse, true);

// Check if the response contains the required fields
if (isset($callbackContent['Body']['stkCallback'])) {
    $stkCallback = $callbackContent['Body']['stkCallback'];
    
    $ResultCode = $stkCallback['ResultCode'];
    $CheckoutRequestID = $stkCallback['CheckoutRequestID'];
    $Amount = $stkCallback['CallbackMetadata']['Item'][0]['Value'];
    $MpesaReceiptNumber = $stkCallback['CallbackMetadata']['Item'][1]['Value'];
    $TransactionDate = $stkCallback['CallbackMetadata']['Item'][2]['Value'];
    $PhoneNumber = $stkCallback['CallbackMetadata']['Item'][3]['Value'];

    // Format the phone number (convert 2547XXXXXXXX to 07XXXXXXXX)
    $formattedPhone = str_replace("254", "0", $PhoneNumber);

    // Process only successful transactions (ResultCode == 0)
    if ($ResultCode == 0) {
        // Connect to the database
        $conn = new mysqli("localhost", "root", "123456789", "money_db");

        // Check for connection errors
        if ($conn->connect_error) {
            die(json_encode(["error" => "Database connection failed: " . $conn->connect_error]));
        }

        // Prepare the SQL statement to insert transaction data
        $stmt = $conn->prepare("INSERT INTO transactions (CheckoutRequestID, ResultCode, Amount, MpesaReceiptNumber, PhoneNumber, TransactionDate) 
                                VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sissss", $CheckoutRequestID, $ResultCode, $Amount, $MpesaReceiptNumber, $formattedPhone, $TransactionDate);

        // Execute the query and close the connection
        $stmt->execute();
        $stmt->close();
        $conn->close();
    }
}

// Send response to Safaricom
echo $response;
?>
