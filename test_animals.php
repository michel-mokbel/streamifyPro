<?php
// Test animals request specifically
$testMessage = 'Show me videos about animals';
$payload = json_encode(['message' => $testMessage]);

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
$responseData = json_decode($response, true);

if ($responseData) {
    echo "Response structure: " . json_encode(array_keys($responseData), JSON_PRETTY_PRINT) . "\n";
    if (isset($responseData['items'])) {
        echo "Items count: " . count($responseData['items']) . "\n";
        if (count($responseData['items']) > 0) {
            echo "First item: " . json_encode($responseData['items'][0], JSON_PRETTY_PRINT) . "\n";
        }
    }
    echo "Summary: " . ($responseData['summary'] ?? 'none') . "\n";
} else {
    echo "Invalid JSON response\n";
    echo "Raw: " . substr($response, 0, 300) . "\n";
}
