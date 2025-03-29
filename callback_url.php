<?php
header("Content-Type: application/json");

$response = '{
    "ResultCode": 0, 
    "ResultDesc": "Confirmation Received Successfully"
}';

// Get the raw JSON from M-Pesa
$mpesaResponse = file_get_contents('php://input');

// Decode the new transaction
$newTransaction = json_decode($mpesaResponse, true);

// Log file
$logFile = "M_PESAConfirmationResponse.json";

// Load existing transactions (or create an empty array if the file doesn't exist)
if (file_exists($logFile)) {
    $existingTransactions = json_decode(file_get_contents($logFile), true);
    if (!isset($existingTransactions["Body"]["stkCallback"])) {
        $existingTransactions["Body"]["stkCallback"] = [];
    }
} else {
    $existingTransactions = ["Body" => ["stkCallback" => []]];
}

// Append the new transaction to the JSON array
$existingTransactions["Body"]["stkCallback"][] = $newTransaction["Body"]["stkCallback"];

// Save the updated transactions back to the file
file_put_contents($logFile, json_encode($existingTransactions, JSON_PRETTY_PRINT));

// Extract data for database insertion
$Resultcode = $newTransaction["Body"]["stkCallback"]["ResultCode"];
$CheckoutRequestID = $newTransaction["Body"]["stkCallback"]["CheckoutRequestID"];
$Amount = $newTransaction["Body"]["stkCallback"]["CallbackMetadata"]["Item"][0]["Value"];
$MpesaReceiptNumber = $newTransaction["Body"]["stkCallback"]["CallbackMetadata"]["Item"][1]["Value"];
$Mpesadate = $newTransaction["Body"]["stkCallback"]["CallbackMetadata"]["Item"][3]["Value"];
$PhoneNumber = $newTransaction["Body"]["stkCallback"]["CallbackMetadata"]["Item"][4]["Value"];
$formatedPhone = str_replace("254", "0", $PhoneNumber);

// Insert into database only if payment was successful
if ($Resultcode == 0) {
    // Database connection
    $conn = mysqli_connect("localhost", "root", "123456789", "money_db");

    if (!$conn) {
        die(json_encode(["error" => "Database connection failed: " . mysqli_connect_error()]));
    }

    // Insert transaction using prepared statement
    $stmt = $conn->prepare("INSERT INTO transactions (Resultcode, Amount, MpesaReceiptNumber, PhoneNumber, TransactionDate) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $Resultcode, $Amount, $MpesaReceiptNumber, $formatedPhone, $Mpesadate);

    if ($stmt->execute()) {
        echo json_encode(["success" => "Transaction recorded successfully!"]);
    } else {
        echo json_encode(["error" => $stmt->error]);
    }

    $stmt->close();
    mysqli_close($conn);
}

// Respond to Safaricom
echo $response;
?>
