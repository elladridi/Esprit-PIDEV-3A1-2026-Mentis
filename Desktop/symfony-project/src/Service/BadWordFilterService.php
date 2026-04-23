<?php

namespace App\Service;

class BadWordFilterService
{
    private array $badWords = [
        // English bad words
        'fuck', 'shit', 'asshole', 'bitch', 'damn', 'hell', 'crap',
        'stupid', 'idiot', 'dumb', 'moron', 'loser', 'hate', 'kill',
        
        // French bad words (common)
        'merde', 'putain', 'connard', 'con', 'salope', 'enculer',
        'bordel', 'chiant', 'crotte', 'zut', 'mince',
        
        // Add more as needed
    ];

    public function containsBadWords(string $text): bool
    {
        $textLower = strtolower($text);
        
        foreach ($this->badWords as $badWord) {
            if (str_contains($textLower, strtolower($badWord))) {
                return true;
            }
        }
        
        return false;
    }

    public function getBadWordsFound(string $text): array
    {
        $found = [];
        $textLower = strtolower($text);
        
        foreach ($this->badWords as $badWord) {
            if (str_contains($textLower, strtolower($badWord))) {
                $found[] = $badWord;
            }
        }
        
        return array_unique($found);
    }

    public function filterBadWords(string $text): string
    {
        foreach ($this->badWords as $badWord) {
            $replacement = str_repeat('*', strlen($badWord));
            $text = str_ireplace($badWord, $replacement, $text);
        }
        
        return $text;
    }
}