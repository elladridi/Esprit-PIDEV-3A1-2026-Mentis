<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class CVSummarizationService
{
    private const API_URL = 'https://api.openai.com/v1/chat/completions';
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private bool $useMockMode;
    private ?string $apiKey;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiKey = $_ENV['OPENAI_API_KEY'] ?? null;
        $this->useMockMode = empty($this->apiKey) || $this->apiKey === 'your-api-key-here';
        
        if ($this->useMockMode) {
            $this->logger->warning('⚠️ OPENAI_API_KEY not set. Using MOCK MODE for CV summarization.');
        }
    }

    /**
     * Extract information from CV that matches the user table schema
     */
    public function summarizeCV(string $cvText): CVSummary
    {
        if ($this->useMockMode) {
            return $this->getMockSummary($cvText);
        }

        try {
            $prompt = $this->buildPrompt($cvText);
            $aiResponse = $this->callAI($prompt);

            if ($aiResponse === null) {
                $this->logger->error('❌ AI response is null, falling back to mock data');
                return $this->getMockSummary($cvText);
            }

            return $this->parseAIResponse($aiResponse);
        } catch (\Exception $e) {
            $this->logger->error('❌ Error in AI summarization: ' . $e->getMessage());
            return $this->getMockSummary($cvText);
        }
    }

    private function buildPrompt(string $cvText): string
    {
        $truncatedText = strlen($cvText) > 3000 ? substr($cvText, 0, 3000) . '...' : $cvText;

        return sprintf("
Extract the person's information from this CV/resume.

NAME EXTRACTION - LOOK FOR THESE PATTERNS (in order of priority):
1. Large text at the VERY TOP of the document (header)
2. After \"Name:\" or \"Full Name:\" labels
3. After \"Personal Information\" or \"Personal Details\" sections
4. The first line that looks like a person's name (2-4 words, no numbers, not a section title)

COMMON NAME FORMATS TO RECOGNIZE:
- \"JOHN DOE\" (all caps)
- \"John Doe\" (title case)
- \"Doe, John\" (last name first)
- \"Dr. John Doe\" (with title)
- \"Prof. Jane Smith\" (with academic title)

COMMON SECTION TITLES TO IGNORE:
- \"PROFILE INFO\", \"SUMMARY\", \"EDUCATION\", \"EXPERIENCE\", \"SKILLS\"
- \"CURRICULUM VITAE\", \"RESUME\", \"CV\", \"BIOGRAPHY\"
- \"CONTACT\", \"PERSONAL INFORMATION\", \"ABOUT ME\"

Required fields:
- firstname: Person's first name (extract intelligently)
- lastname: Person's last name (extract intelligently)
- phone: Phone number (with country code if available)
- email: Email address
- dateofbirth: Date of birth in YYYY-MM-DD format (if available)

CV Text:
%s

Return ONLY valid JSON:
{
    \"firstname\": \"...\",
    \"lastname\": \"...\",
    \"phone\": \"...\",
    \"email\": \"...\", 
    \"dateofbirth\": \"...\"
}
", $truncatedText);
    }

    private function callAI(string $prompt): ?string
    {
        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 500,
                ],
                'timeout' => 30,
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->logger->error('❌ API call failed: ' . $response->getStatusCode());
                return null;
            }

            $data = $response->toArray();
            return json_encode($data);
            
        } catch (\Exception $e) {
            $this->logger->error('❌ Network error calling AI: ' . $e->getMessage());
            return null;
        }
    }

    private function parseAIResponse(string $aiResponse): CVSummary
    {
        try {
            $data = json_decode($aiResponse, true);
            
            if (!isset($data['choices'][0]['message']['content'])) {
                return $this->getMockSummary('');
            }
            
            $content = $data['choices'][0]['message']['content'];
            $content = preg_replace('/```json\\n|```/', '', $content);
            $content = trim($content);
            
            $result = json_decode($content, true);
            
            $summary = new CVSummary();
            $summary->setFirstname($result['firstname'] ?? '');
            $summary->setLastname($result['lastname'] ?? '');
            $summary->setPhone($result['phone'] ?? '');
            $summary->setEmail($result['email'] ?? '');
            $summary->setDateofbirth($result['dateofbirth'] ?? '');
            
            return $summary;
            
        } catch (\Exception $e) {
            $this->logger->error('❌ Error parsing AI response: ' . $e->getMessage());
            return $this->getMockSummary('');
        }
    }

    /**
     * Intelligent mock extraction that handles multiple CV formats
     */
    private function getMockSummary(string $cvText): CVSummary
    {
        $summary = new CVSummary();
        
        // Try multiple name extraction strategies
        $fullName = $this->extractNameMultiStrategy($cvText);
        
        if (!empty($fullName)) {
            // Handle different name formats
            $nameParts = $this->parseNameFormat($fullName);
            $summary->setFirstname($nameParts['firstName']);
            $summary->setLastname($nameParts['lastName']);
        }
        
        $summary->setEmail($this->extractEmailFromText($cvText));
        $summary->setPhone($this->extractPhoneFromText($cvText));
        $summary->setDateofbirth($this->extractDateOfBirthFromText($cvText));
        
        return $summary;
    }

    /**
     * Try multiple strategies to extract name from different CV formats
     */
    private function extractNameMultiStrategy(string $text): string
    {
        $lines = explode("\n", $text);
        
        // Strategy 1: Look for ALL CAPS at the top
        for ($i = 0; $i < min(10, count($lines)); $i++) {
            $line = trim($lines[$i]);
            if ($this->isValidNameLine($line) && preg_match('/^[A-Z\\s]+$/', $line)) {
                return $line;
            }
        }
        
        // Strategy 2: Look for "Name:" or "Full Name:" labels
        if (preg_match('/(?i)(?:name|full name|姓名|nombre)[:\\s]+([A-Za-z\\s]+)/', $text, $matches)) {
            return trim($matches[1]);
        }
        
        // Strategy 3: Look for name after "Personal Information" section
        if (preg_match('/(?i)(?:personal information|personal details|about me)[\\s\\n]+([A-Za-z\\s]+)/', $text, $matches)) {
            return trim($matches[1]);
        }
        
        // Strategy 4: Look for name in first few lines
        for ($i = 0; $i < min(15, count($lines)); $i++) {
            $line = trim($lines[$i]);
            if ($this->isValidNameLine($line)) {
                return $line;
            }
        }
        
        return '';
    }

    /**
     * Check if a line could be a valid name
     */
    private function isValidNameLine(string $line): bool
    {
        if (empty($line) || strlen($line) > 50) return false;
        
        $excludedTerms = [
            'profile', 'summary', 'education', 'experience', 'skills',
            'curriculum', 'vitae', 'resume', 'cv', 'contact', 'objective',
            'work', 'project', 'internship', 'training', 'certification',
            'language', 'reference', 'hobby', 'interest', 'achievement'
        ];
        
        $lowerLine = strtolower($line);
        foreach ($excludedTerms as $term) {
            if (strpos($lowerLine, $term) !== false) {
                return false;
            }
        }
        
        return preg_match('/.*[A-Za-z].*/', $line) && !preg_match('/.*\\d{4,}.*/', $line);
    }

    /**
     * Parse different name formats into first/last name
     */
    private function parseNameFormat(string $fullName): array
    {
        $firstName = '';
        $lastName = '';
        
        // Remove titles
        $name = preg_replace('/^(dr|prof|mr|mrs|ms|miss)\\s+/i', '', $fullName);
        
        // Handle "Last, First" format
        if (strpos($name, ',') !== false) {
            $split = explode(',', $name);
            $lastName = trim($split[0]);
            $firstName = isset($split[1]) ? trim($split[1]) : '';
        }
        // Handle multiple words
        else {
            $words = preg_split('/\\s+/', $name);
            
            if (count($words) == 1) {
                $firstName = $words[0];
                $lastName = '';
            } elseif (count($words) == 2) {
                $firstName = $words[0];
                $lastName = $words[1];
            } else {
                // For names with multiple words (e.g., "Jean Pierre Dubois")
                $firstName = $words[0] . ' ' . $words[1];
                $lastName = $words[count($words) - 1];
            }
        }
        
        return [
            'firstName' => $this->cleanNamePart($firstName),
            'lastName' => $this->cleanNamePart($lastName)
        ];
    }

    private function cleanNamePart(string $name): string
    {
        if (empty($name)) return '';
        
        // Remove punctuation
        $name = preg_replace('/[.,;:]/', '', $name);
        
        // Convert from ALL CAPS to Title Case
        if (preg_match('/^[A-Z\\s]+$/', $name)) {
            $name = ucwords(strtolower($name));
        }
        
        return $name;
    }

    private function extractEmailFromText(string $text): string
    {
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $text, $matches)) {
            return $matches[0];
        }
        return '';
    }

    private function extractPhoneFromText(string $text): string
    {
        if (preg_match('/\+?[0-9\-\s()]{10,20}/', $text, $matches)) {
            return trim($matches[0]);
        }
        return '';
    }

    private function extractDateOfBirthFromText(string $text): string
    {
        $patterns = [
            '/\b\d{4}[-\/]\d{2}[-\/]\d{2}\b/',
            '/\b\d{2}[-\/]\d{2}[-\/]\d{4}\b/',
            '/(?i)(?:dob|date of birth|born)[:\s]*(\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4})/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $date = isset($matches[1]) ? $matches[1] : $matches[0];
                return str_replace('/', '-', $date);
            }
        }
        return '';
    }
}