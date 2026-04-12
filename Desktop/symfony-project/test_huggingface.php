<?php
// Simple test script for Hugging Face AI integration

require_once 'vendor/autoload.php';

use Symfony\Component\HttpClient\HttpClient;

$httpClient = HttpClient::create();

// Test configuration
$hfApiKey = $_ENV['HF_API_KEY'] ?? getenv('HF_API_KEY') ?? '';
$hfModel = $_ENV['HF_API_MODEL'] ?? getenv('HF_API_MODEL') ?? 'google/flan-t5-large';

echo "═══════════════════════════════════════════════════════\n";
echo "Testing Hugging Face Integration\n";
echo "═══════════════════════════════════════════════════════\n\n";

// Check if API key is configured
if (empty($hfApiKey)) {
    echo "❌ HF_API_KEY is not set in .env\n";
    echo "   Please add your Hugging Face API key to .env:\n";
    echo "   HF_API_KEY=your_key_here\n\n";
    echo "   You can get a token from: https://huggingface.co/settings/tokens\n";
    exit(1);
}

echo "✓ API Key configured\n";
echo "✓ Model: {$hfModel}\n\n";

// Test 1: Simple chat request
echo "Test 1: Simple Chat Message\n";
echo "─────────────────────────────────────────────────────\n";

$prompt = "You are a supportive assistant.\n\nUser: Hello, how are you?";

try {
    $response = $httpClient->request('POST', "https://api-inference.huggingface.co/models/{$hfModel}", [
        'headers' => [
            'Authorization' => 'Bearer ' . $hfApiKey,
            'Content-Type' => 'application/json',
        ],
        'json' => [
            'inputs' => $prompt,
            'parameters' => [
                'max_new_tokens' => 256,
                'temperature' => 0.7,
                'top_p' => 0.9,
                'return_full_text' => false,
            ],
            'options' => [
                'wait_for_model' => true,
            ],
        ],
        'timeout' => 60,
    ]);

    $statusCode = $response->getStatusCode();
    $data = $response->toArray();

    echo "Status: {$statusCode}\n";
    echo "Response: " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

    if ($statusCode === 200) {
        if (isset($data[0]['generated_text'])) {
            echo "✓ Chat test PASSED\n";
            echo "Generated: " . substr($data[0]['generated_text'], 0, 100) . "...\n";
        } else {
            echo "⚠ Response received but format unexpected\n";
        }
    } else {
        echo "❌ Request failed with status {$statusCode}\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Content generation request
echo "Test 2: Content Generation\n";
echo "─────────────────────────────────────────────────────\n";

$genPrompt = "Generate a professional article about stress management. Output only valid JSON with the following structure: {\"title\": \"...\", \"sections\": [{\"heading\": \"...\", \"paragraphs\": [\"...\"]}]}";

try {
    $response = $httpClient->request('POST', "https://api-inference.huggingface.co/models/{$hfModel}", [
        'headers' => [
            'Authorization' => 'Bearer ' . $hfApiKey,
            'Content-Type' => 'application/json',
        ],
        'json' => [
            'inputs' => $genPrompt,
            'parameters' => [
                'max_new_tokens' => 512,
                'temperature' => 0.7,
                'top_p' => 0.9,
                'return_full_text' => false,
            ],
            'options' => [
                'wait_for_model' => true,
            ],
        ],
        'timeout' => 60,
    ]);

    $statusCode = $response->getStatusCode();
    $data = $response->toArray();

    echo "Status: {$statusCode}\n";
    echo "Response preview: " . substr(json_encode($data), 0, 150) . "...\n\n";

    if ($statusCode === 200) {
        echo "✓ Generation test PASSED\n";
    } else {
        echo "❌ Request failed with status {$statusCode}\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n═══════════════════════════════════════════════════════\n";
echo "Test complete. If both tests passed, Hugging Face is ready!\n";
echo "═══════════════════════════════════════════════════════\n";
