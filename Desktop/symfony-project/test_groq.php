<?php

require_once 'vendor/autoload.php';

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

$apiKey = $_ENV['GROQ_API_KEY'] ?? '';
$model = $_ENV['GROQ_MODEL'] ?? 'llama3-8b-8192';

if (!$apiKey) {
    die("GROQ_API_KEY not found in .env\n");
}

$client = HttpClient::create();

echo "Testing Groq API with model: $model\n";

try {
    echo "Sending request to Groq...\n";
    $response = $client->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ],
        'json' => [
            'model' => 'llama-3.1-8b-instant',
            'messages' => [
                ['role' => 'user', 'content' => 'Hello']
            ],
        ],
        'verify_peer' => false,
    ]);

    $statusCode = $response->getStatusCode();
    echo "Status Code: $statusCode\n";
    $content = $response->getContent(false);
    echo "Raw Content: $content\n";
    echo "✅ Success!\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
