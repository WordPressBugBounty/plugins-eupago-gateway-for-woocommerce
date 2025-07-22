<?php


// Retrieve the request parameters
$grantType = $_POST['grant_type'];
$clientId = $_POST['client_id'];
$clientSecret = $_POST['client_secret'];
$callbackApiKey = $_POST['callback_api_key'];

if (substr($callbackApiKey, 0, 4) === 'demo') {
    $url = 'https://sandbox.eupago.pt/api/auth/token';
    $newUrl = 'https://sandbox.eupago.pt/api/management/v1.02/channels/configuration/info';
} else {
    $url = 'https://clientes.eupago.pt/api/auth/token';
    $newUrl = 'https://clientes.eupago.pt/api/management/v1.02/channels/configuration/info';
}

// Create the request payload
$data = [
  'grant_type' => $grantType,
  'client_id' => $clientId,
  'client_secret' => $clientSecret,
];

// Convert the payload to JSON
$jsonData = json_encode($data);

// Set the request options
$headers = [
    'Content-Type: application/json',
];

// Initialize cURL session for the first request
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute the first cURL request
$response = curl_exec($ch);

// Check if the request was successful
if ($response === false) {
    // Handle the error
    $error = curl_error($ch);
    echo json_encode(['error' => $error]);
} else {
    // Close the cURL session
    curl_close($ch);

    // Parse the response as JSON
    $responseData = json_decode($response, true);

    // Check if the transactionStatus is "Success"
    if ($responseData['transactionStatus'] === 'Success') {
        $accessToken = $responseData['access_token'];

        // Create the new request payload
        $newData = [
          'channelApiKey' => $callbackApiKey
        ];

        // Convert the new payload to JSON
        $newJsonData = json_encode($newData);

        // Set the new request options
        // Set the new request headers
        $newHeaders = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
        ];

        // Initialize cURL session for the new request
        $newCh = curl_init($newUrl);
        curl_setopt($newCh, CURLOPT_POST, 1);
        curl_setopt($newCh, CURLOPT_POSTFIELDS, $newJsonData);
        curl_setopt($newCh, CURLOPT_HTTPHEADER, $newHeaders);
        curl_setopt($newCh, CURLOPT_RETURNTRANSFER, true);

        // Execute the new cURL request
        $newResponse = curl_exec($newCh);
        $jsonResponse = json_decode($newResponse, true);
        
        // Check if the new request was successful
        if ($jsonResponse === false) {
            // Handle the error
            $error = error_get_last();
            $errorMessage = isset($error['message']) ? $error['message'] : 'An error occurred.';
            echo json_encode(['error' => $errorMessage]);
        } else {
            // Parse the new response as JSON
            $newResponseData = json_decode($newResponse, true);
            echo json_encode($newResponseData);
        }
    } else {
        echo json_encode(['error' => 'Transaction status is not "Success".']);
    }
}
