<?php


function createRequestSignature($requestBody, $hexKey) {
  $requestValues = array_values($requestBody); // Get only the values of the request body
  sort($requestValues, SORT_STRING); // Sort the values in ascending order
  $hmacData = implode('|', $requestValues);
  $hmacKey = hex2bin($hexKey);
  $hmacSignature = hash_hmac('sha256', $hmacData, $hmacKey, false); // Set the raw_output parameter to false
  return $hmacSignature;
}


function getCardToken($number, $expirationMonth, $expirationYear, $securityCode) {
  $requestPayload = array(
    'project' => 'f3dfc697e008463fa760d6d0f71093ba',
    'number' => $number,
    'expiration_month' => $expirationMonth,
    'expiration_year' => $expirationYear,
    'security_code' => $securityCode
  );

  $httpOptions = array(
    'http' => array(
      'method' => 'POST',
      'header' => 'Content-Type: application/json',
      'content' => json_encode($requestPayload)
    ),
    'ssl' => array(
      'verify_peer' => false,
      'verify_peer_name' => false
    )
  );

  $streamContext = stream_context_create($httpOptions);
  $apiResponse = @file_get_contents('https://api.pinpaygate.com/dev/card/getToken', false, $streamContext);



  if ($apiResponse === false) {
    throw new Exception('Failed to connect to the API endpoint');
  }

  // Log the API response
  $logFile = 'api_log.txt'; // Path to the log file
  $logHandle = fopen($logFile, 'a'); // Open the log file in append mode

  if ($logHandle) {
    // Log the API response
    fwrite($logHandle, "API Response: " . $apiResponse . "\n");
  } else {
    error_log("Failed to open log file: " . $logFile);
  }

  // Close the log file
  if ($logHandle) {
    fclose($logHandle);
  }



  $responsePayload = json_decode($apiResponse, true);

  if (isset($responsePayload['id'])) {
    return $responsePayload['id'];
  } else {
    $errorCode = isset($responsePayload['code']) ? $responsePayload['code'] : null;
    $errorMessage = isset($responsePayload['message']) ? $responsePayload['message'] : 'Token acquisition failed';
    throw new Exception($errorMessage, $errorCode);
  }
}

function initiatePayment($paymentRequestData) {
  $requestPayload = array(
    'project' => 'f3dfc697e008463fa760d6d0f71093ba',
    'card_token' => $paymentRequestData['card_token'],
    'order_id' => $paymentRequestData['order_id'],
    'price' => strval($paymentRequestData['price']),
    'currency' => $paymentRequestData['currency'],
    'description' => 'Your hardcoded description goes here',
    'user_name' => isset($paymentRequestData['user_name']) ? $paymentRequestData['user_name'] : '',
    'user_phone' => isset($paymentRequestData['user_phone']) ? $paymentRequestData['user_phone'] : '123',
    'user_contact_email' => isset($paymentRequestData['user_contact_email']) ? $paymentRequestData['user_contact_email'] : '',
    'ip' => isset($paymentRequestData['ip']) ? $paymentRequestData['ip'] : '',
    'real_url' => isset($paymentRequestData['real_url']) ? $paymentRequestData['real_url'] : 'https://dystryx.online/',
    'result_url' => isset($paymentRequestData['result_url']) ? $paymentRequestData['result_url'] : '',
    'success_url' => isset($paymentRequestData['success_url']) ? $paymentRequestData['success_url'] : '',
    'failure_url' => isset($paymentRequestData['failure_url']) ? $paymentRequestData['failure_url'] : '',
    'user_country' => isset($paymentRequestData['user_country']) ? $paymentRequestData['user_country'] : '',
    'user_state' => isset($paymentRequestData['user_state']) ? $paymentRequestData['user_state'] : '',
    'user_city' => isset($paymentRequestData['user_city']) ? $paymentRequestData['user_city'] : '',
    'user_address' => isset($paymentRequestData['user_address']) ? $paymentRequestData['user_address'] : '',
    'user_postal_code' => isset($paymentRequestData['user_postal_code']) ? $paymentRequestData['user_postal_code'] : '',
    'user_nationality' => isset($paymentRequestData['user_nationality']) ? $paymentRequestData['user_nationality'] : '',
    'signature' => ''
  );

  $requestPayload['signature'] = createRequestSignature($requestPayload, '7b8e642a01e3499f9be2865e0519e3ad70a934f9a392edac8596a3264400fa63');

  // Initialising curl
  $curl = curl_init();
  
  // Set curl options
  curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://api.pinpaygate.com/dev/card/process',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($requestPayload),
    CURLOPT_HTTPHEADER => array(
      'Content-Type: application/json',
    ),
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_SSL_VERIFYPEER => false
  ));

  // Execute and get the response
  $apiResponse = curl_exec($curl);

  // Check if any error occurred
  if(curl_errno($curl)) {
    throw new Exception(curl_error($curl));
  }

  // Close curl
  curl_close($curl);
  
  // Log the API response
  $logFile = 'api_log.txt'; // Path to the log file
  $logHandle = fopen($logFile, 'a'); // Open the log file in append mode

  if ($logHandle) {
    // Log the API response
    fwrite($logHandle, "API Response: " . $apiResponse . "\n");
  } else {
    error_log("Failed to open log file: " . $logFile);
  }

  // Close the log file
  if ($logHandle) {
    fclose($logHandle);
  }

  $responsePayload = json_decode($apiResponse, true);

  if (isset($responsePayload['success']) && $responsePayload['success'] === true) {
    return $responsePayload;
  } else {
    throw new Exception('Payment transaction failed');
  }
}

function generateUniqueOrderId() {
  return strval(rand(0, 999999));
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $cardNumber = $_POST['cardNumber'];
    $expiryMonth = $_POST['expiryMonth'];
    $expiryYear = $_POST['expiryYear'];
    $cvv = $_POST['cvv'];
    $transactionAmount = $_POST['price'];
    $transactionCurrency = $_POST['currency'];
    $cardHolderName = $_POST['name'];
    $cardHolderEmail = $_POST['email'];
    $userIP = $_SERVER['REMOTE_ADDR']; // Retrieve user IP address
    $userCountry = $_POST['user_country'];
    $userState = $_POST['user_state'];
    $userCity = $_POST['user_city'];
    $userAddress = $_POST['user_address'];
    $userPostalCode = $_POST['user_postal_code'];
    $userNationality = $_POST['user_nationality'];

    // request data for /getToken
    $paymentRequestData = array(
      'number' => $cardNumber,
      'expiration_month' => $expiryMonth,
      'expiration_year' => $expiryYear,
      'security_code' => $cvv,
      'price' => $transactionAmount,
      'currency' => $transactionCurrency,
      'user_name' => $cardHolderName,
      'user_contact_email' => $cardHolderEmail,
      'ip' => $userIP, // Pass user IP address
      'user_country' => $userCountry,
      'user_state' => $userState,
      'user_city' => $userCity,
      'user_address' => $userAddress,
      'user_postal_code' => $userPostalCode,
      'user_nationality' => $userNationality

    );
    
    $tokenResponse = getCardToken(
      $paymentRequestData['number'],
      $paymentRequestData['expiration_month'],
      $paymentRequestData['expiration_year'],
      $paymentRequestData['security_code']
    );

    $token = $tokenResponse; // Extract the token from the response



// a new array for payment request /processs 
$paymentRequestData = array(
  'card_token' => $token,
  'order_id' => generateUniqueOrderId(),
  'price' => $transactionAmount,
  'currency' => $transactionCurrency,
  'description' => 'Your hardcoded description goes here',
  'user_name' => $cardHolderName,
  'user_contact_email' => $cardHolderEmail,
  'ip' => $userIP, // Pass user IP address
  'real_url' => 'https://dystryx.online/',
  'result_url' => 'https://dystryx.online/',
  'success_url' => 'https://dystryx.online/luck',
  'failure_url' => 'https://dystryx.online/failure.php',
  'user_country' => $userCountry,
  'user_state' => $userState,
  'user_city' => $userCity,
  'user_address' => $userAddress,
  'user_postal_code' => $userPostalCode,
  'user_nationality' => $userNationality,
  'signature' => ''
);

// // Generate the signature for the new payment request data
// $signature = createRequestSignature($paymentRequestData, '7b8e642a01e3499f9be2865e0519e3ad70a934f9a392edac8596a3264400fa63');

// // Add the signature to the payment request data
// $paymentRequestData['signature'] = $signature;

// Call initiatePayment with the updated payment request data
$paymentResponse = initiatePayment($paymentRequestData);

if (isset($paymentResponse['acs']['url'])) {
  // Redirect to the ACS URL for 3DSecure authorization
  header('Location: ' . $paymentResponse['acs']['url']);
  exit();
} elseif (isset($paymentResponse['acs'])) {
  // Perform POST redirection for 3DSecure authorization
  $acsUrl = $paymentResponse['acs']['url'];
  $acsParams = $paymentResponse['acs']['params'];
  echo '<html><body onload="document.forms[0].submit()">';
  echo '<form method="post" action="' . $acsUrl . '">';
  foreach ($acsParams as $name => $value) {
    echo '<input type="hidden" name="' . $name . '" value="' . $value . '">';
  }
  echo '</form></body></html>';
  exit();
} elseif ($paymentResponse['success']) {
  // Payment transaction successful
  header('Location: https://dystryx.online/luck');
  exit();
} else {
  // Payment transaction failed
  header('Location: https://dystryx.online/failure.php');
  exit();
}
} catch (Exception $e) {
echo '<script>';
echo 'console.error("Caught exception: ' . $e->getMessage() . '");';
echo '</script>';
header('Location: https://dystryx.online/failure.php');
exit();
}

}



?>