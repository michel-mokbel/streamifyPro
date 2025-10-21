<?php
// Debug the API response
error_reporting(E_ALL);
ini_set('display_errors', 1);

$testMessage = 'Show me videos about animals';
$payload = json_encode(['message' => $testMessage]);

echo "Testing API with message: $testMessage\n";

$ch = curl_init('http://localhost/streamifyPro/api/llm_agent.php');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Raw Response Length: " . strlen($response) . "\n";
echo "Raw Response: " . $response . "\n";

$responseData = json_decode($response, true);
if ($responseData) {
    echo "Parsed JSON successfully\n";
    echo "Keys: " . implode(', ', array_keys($responseData)) . "\n";
    if (isset($responseData['items'])) {
        echo "Items array length: " . count($responseData['items']) . "\n";
    }
} else {
    echo "Failed to parse JSON\n";
}
