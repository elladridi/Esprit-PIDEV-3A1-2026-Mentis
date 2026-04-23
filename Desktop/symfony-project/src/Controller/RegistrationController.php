<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Smalot\PdfParser\Parser;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // ANALYSE DU CV POUR LES PSYCHOLOGUES
            $cvFile = $form->get('cvFile')->getData();
            
            if ($cvFile && $user->getType() === 'Psychologist') {
                try {
                    // Créer un dossier temporaire
                    $tempDir = $this->getParameter('kernel.project_dir') . '/var/temp_cvs/';
                    if (!is_dir($tempDir)) {
                        mkdir($tempDir, 0777, true);
                    }
                    
                    // Sauvegarder le CV temporairement
                    $tempFileName = uniqid() . '.pdf';
                    $cvFile->move($tempDir, $tempFileName);
                    $tempFilePath = $tempDir . $tempFileName;
                    
                    // Extraire le texte du PDF
                    $cvText = $this->extractTextFromPDF($tempFilePath);
                    
                    // Analyser le CV
                    $analysis = $this->analyzeCVWithZai($cvText);
                    
                    // Remplir automatiquement les champs avec les données extraites du CV
                    if (!empty($analysis['firstname']) && empty($user->getFirstname())) {
                        $user->setFirstname($analysis['firstname']);
                    }
                    if (!empty($analysis['lastname']) && empty($user->getLastname())) {
                        $user->setLastname($analysis['lastname']);
                    }
                    if (!empty($analysis['email']) && empty($user->getEmail())) {
                        $user->setEmail($analysis['email']);
                    }
                    if (!empty($analysis['phone']) && empty($user->getPhone())) {
                        $user->setPhone($analysis['phone']);
                    }
                    if (!empty($analysis['dateofbirth']) && empty($user->getDateofbirth())) {
                        $user->setDateofbirth($analysis['dateofbirth']);
                    }
                    if (!empty($analysis['gender']) && empty($user->getGender())) {
                        $user->setGender($analysis['gender']);
                    }
                    
                    // Stocker l'analyse complète en session
                    $session = $request->getSession();
                    $session->set('cv_analysis_' . $user->getEmail(), [
                        'firstname' => $analysis['firstname'],
                        'lastname' => $analysis['lastname'],
                        'email' => $analysis['email'],
                        'phone' => $analysis['phone'],
                        'dateofbirth' => $analysis['dateofbirth'],
                        'gender' => $analysis['gender'],
                        'score' => $analysis['score'] ?? 0,
                        'is_valid' => $analysis['is_valid'] ?? false,
                        'degree_found' => $analysis['degree_found'] ?? false,
                        'experience_years' => $analysis['experience_years'] ?? 0,
                        'specializations' => $analysis['specializations'] ?? [],
                        'missing_requirements' => $analysis['missing_requirements'] ?? [],
                        'analysis_date' => date('Y-m-d H:i:s')
                    ]);
                    
                    // Supprimer le fichier temporaire
                    unlink($tempFilePath);
                    
                    // Afficher un résumé des informations extraites
                    $this->addFlash('info', '📄 CV Analysis: ' . 
                        'Name: ' . ($analysis['firstname'] ?: 'Not found') . ' ' . ($analysis['lastname'] ?: '') . ', ' .
                        'Email: ' . ($analysis['email'] ?: 'Not found') . ', ' .
                        'Phone: ' . ($analysis['phone'] ?: 'Not found') . ', ' .
                        'Experience: ' . $analysis['experience_years'] . ' years, ' .
                        'Score: ' . $analysis['score'] . '%'
                    );
                    
                    // Message flash de validation
                    if ($analysis['is_valid'] ?? false) {
                        $this->addFlash('success', '✓ CV validated! Welcome to Mentis.');
                    } else {
                        $this->addFlash('warning', '⚠️ Your CV is under review. Missing: ' . implode(', ', $analysis['missing_requirements']));
                    }
                    
                } catch (\Exception $e) {
                    $this->addFlash('error', 'CV analysis failed: ' . $e->getMessage());
                }
            }
            
            // Hachage du mot de passe
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Registration successful! Please login.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/analyze-cv', name: 'app_analyze_cv', methods: ['POST'])]
    public function analyzeCv(Request $request): JsonResponse
    {
        $cvFile = $request->files->get('cv');
        
        if (!$cvFile) {
            return $this->json(['error' => 'No file uploaded'], 400);
        }
        
        try {
            // Créer un dossier temporaire
            $tempDir = $this->getParameter('kernel.project_dir') . '/var/temp_cvs/';
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            
            // Sauvegarder le fichier temporairement
            $tempFileName = uniqid() . '.pdf';
            $cvFile->move($tempDir, $tempFileName);
            $tempFilePath = $tempDir . $tempFileName;
            
            // Extraire et analyser
            $cvText = $this->extractTextFromPDF($tempFilePath);
            $analysis = $this->analyzeCVWithZai($cvText);
            
            // Supprimer le fichier temporaire
            unlink($tempFilePath);
            
            return $this->json([
                'success' => true,
                'firstname' => $analysis['firstname'],
                'lastname' => $analysis['lastname'],
                'email' => $analysis['email'],
                'phone' => $analysis['phone'],
                'dateofbirth' => $analysis['dateofbirth'],
                'gender' => $analysis['gender'],
                'score' => $analysis['score'],
                'is_valid' => $analysis['is_valid'],
                'experience_years' => $analysis['experience_years'],
                'specializations' => $analysis['specializations'],
                'missing_requirements' => $analysis['missing_requirements']
            ]);
            
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

   private function extractTextFromPDF(string $pdfPath): string
{
    // Méthode 1: Utiliser smalot/pdfparser (recommandé)
    if (class_exists('Smalot\PdfParser\Parser')) {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($pdfPath);
            $text = $pdf->getText();
            
            // Nettoyer le texte
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);
            
            if ($text && strlen($text) > 50) {
                return substr($text, 0, 5000);
            }
        } catch (\Exception $e) {
            // Fallback à la méthode suivante
        }
    }
    
    // Méthode 2: Utiliser pdftotext (si installé sur le serveur)
    if (function_exists('shell_exec')) {
        // Essayer différentes commandes pdftotext
        $commands = [
            'pdftotext ' . escapeshellarg($pdfPath) . ' - 2>/dev/null',
            'C:\Program Files\poppler\bin\pdftotext.exe ' . escapeshellarg($pdfPath) . ' - 2>nul',
        ];
        
        foreach ($commands as $command) {
            $output = shell_exec($command);
            if ($output && strlen($output) > 100 && !str_contains($output, 'ERROR')) {
                return substr($output, 0, 5000);
            }
        }
    }
    
    // Méthode 3: Tenter de décoder le contenu du PDF
    $content = file_get_contents($pdfPath);
    if ($content) {
        // Chercher les chaînes de texte dans le PDF
        // Les textes dans un PDF sont souvent entre parenthèses
        preg_match_all('/\(([^)]+)\)/', $content, $matches);
        if (!empty($matches[1])) {
            $text = implode(' ', $matches[1]);
            $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);
            
            if (strlen($text) > 100) {
                return substr($text, 0, 5000);
            }
        }
        
        // Chercher les chaînes encodées en hexadécimal
        preg_match_all('/<([0-9A-Fa-f]{2,})>/', $content, $hexMatches);
        if (!empty($hexMatches[1])) {
            $hexText = '';
            foreach ($hexMatches[1] as $hex) {
                $hexText .= hex2bin($hex);
            }
            $hexText = preg_replace('/[^\x20-\x7E]/', ' ', $hexText);
            $hexText = preg_replace('/\s+/', ' ', $hexText);
            $hexText = trim($hexText);
            
            if (strlen($hexText) > 100) {
                return substr($hexText, 0, 5000);
            }
        }
    }
    
    return '';
}

    private function analyzeCVWithZai(string $cvText): array
    {
        // Analyse locale sans API externe
        $cvLower = strtolower($cvText);
        
        // ========== 1. EXTRACTION DES INFORMATIONS PERSONNELLES ==========
        
        // 1.1 Email
        $email = '';
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $cvText, $emailMatches)) {
            $email = $emailMatches[0];
        }
        
        // 1.2 Téléphone
        $phone = '';
        if (preg_match('/\+?[0-9]{1,3}[-.\s]?[0-9]{2,4}[-.\s]?[0-9]{3,4}[-.\s]?[0-9]{3,4}/', $cvText, $phoneMatches)) {
            $phone = $phoneMatches[0];
        } elseif (preg_match('/[0-9]{2,4}[-.\s]?[0-9]{2,4}[-.\s]?[0-9]{2,4}[-.\s]?[0-9]{2,4}/', $cvText, $phoneMatches)) {
            $phone = $phoneMatches[0];
        }
        
        // 1.3 Prénom et Nom
        $firstName = '';
        $lastName = '';
        
        if (preg_match('/name[:\\s]+([A-Z][a-z]+)\\s+([A-Z][a-z]+)/', $cvText, $nameMatches)) {
            $firstName = $nameMatches[1];
            $lastName = $nameMatches[2];
        } elseif (preg_match('/first\\s*name[:\\s]+([A-Z][a-z]+)/i', $cvText, $nameMatches)) {
            $firstName = $nameMatches[1];
        } elseif (preg_match('/last\\s*name[:\\s]+([A-Z][a-z]+)/i', $cvText, $nameMatches)) {
            $lastName = $nameMatches[1];
        } elseif (preg_match('/prénom[:\\s]+([A-Z][a-z]+)/i', $cvText, $nameMatches)) {
            $firstName = $nameMatches[1];
        } elseif (preg_match('/nom[:\\s]+([A-Z][a-z]+)/i', $cvText, $nameMatches)) {
            $lastName = $nameMatches[1];
        }
        
        if (empty($firstName) && empty($lastName)) {
            $words = preg_split('/\s+/', trim($cvText), 5);
            if (count($words) >= 2) {
                $firstName = ucfirst(strtolower($words[0]));
                $lastName = ucfirst(strtolower($words[1]));
            }
        }
        
        // 1.4 Date de naissance
        $dateOfBirth = '';
        $dobPatterns = [
            '/birth[:\\s]+([0-9]{2}[-\\/][0-9]{2}[-\\/][0-9]{4})/i',
            '/born[:\\s]+([0-9]{2}[-\\/][0-9]{2}[-\\/][0-9]{4})/i',
            '/date\\s*of\\s*birth[:\\s]+([0-9]{2}[-\\/][0-9]{2}[-\\/][0-9]{4})/i',
            '/naissance[:\\s]+([0-9]{2}[-\\/][0-9]{2}[-\\/][0-9]{4})/i',
            '/([0-9]{4}-[0-9]{2}-[0-9]{2})/',
            '/([0-9]{2}\\/[0-9]{2}\\/[0-9]{4})/'
        ];
        
        foreach ($dobPatterns as $pattern) {
            if (preg_match($pattern, $cvText, $dobMatches)) {
                $dateOfBirth = $dobMatches[1];
                break;
            }
        }
        
        // 1.5 Genre
        $gender = '';
        $genderKeywords = [
            'male' => 'male',
            'masculin' => 'male',
            'homme' => 'male',
            'female' => 'female',
            'feminin' => 'female',
            'femme' => 'female'
        ];
        
        foreach ($genderKeywords as $keyword => $value) {
            if (strpos($cvLower, $keyword) !== false) {
                $gender = $value;
                break;
            }
        }
        
        // ========== 2. ANALYSE DES QUALIFICATIONS ==========
        
        // 2.1 Vérification du diplôme
        $degreeFound = false;
        $degreeType = 'None';
        $degreeKeywords = ['master', 'phd', 'doctorate', 'doctorat', 'master\'s', 'masters', 'maîtrise', 'doctor', 'm.sc', 'm.a', 'msc', 'ma'];
        
        foreach ($degreeKeywords as $keyword) {
            if (strpos($cvLower, $keyword) !== false) {
                $degreeFound = true;
                $degreeType = 'Master/PhD';
                break;
            }
        }
        
        // 2.2 Extraction des années d'expérience
        $experienceYears = 0;
        $patterns = [
            '/([0-9]+)\s*(?:years?|ans)/i',
            '/experience[\s:]+([0-9]+)/i',
            '/([0-9]+)\s*(?:years?|ans)\s*(?:of|d\')?/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $cvLower, $matches)) {
                $experienceYears = intval($matches[1]);
                break;
            }
        }
        
        // 2.3 Détection des spécialisations
        $specializations = [];
        $specKeywords = [
            'cbt' => 'CBT',
            'cognitive behavioral' => 'Cognitive Behavioral Therapy',
            'clinical' => 'Clinical Psychology',
            'counseling' => 'Counseling',
            'therapy' => 'Therapy',
            'psychotherapy' => 'Psychotherapy',
            'child' => 'Child Psychology',
            'adolescent' => 'Adolescent Psychology',
            'family' => 'Family Therapy',
            'couple' => 'Couples Therapy',
            'trauma' => 'Trauma Therapy',
            'anxiety' => 'Anxiety Treatment',
            'depression' => 'Depression Treatment'
        ];
        
        foreach ($specKeywords as $keyword => $label) {
            if (strpos($cvLower, $keyword) !== false) {
                $specializations[] = $label;
            }
        }
        $specializations = array_unique($specializations);
        
        // 2.4 Validation
        $minExperienceRequired = 2;
        $isValid = $degreeFound && ($experienceYears >= $minExperienceRequired);
        
        // 2.5 Calcul du score (0-100)
        $score = 0;
        if ($degreeFound) $score += 50;
        $score += min($experienceYears * 10, 40);
        $score += min(count($specializations) * 5, 10);
        $score = min($score, 100);
        
        // 2.6 Pré-requis manquants
        $missingRequirements = [];
        if (!$degreeFound) {
            $missingRequirements[] = 'Master or PhD degree in Psychology';
        }
        if ($experienceYears < $minExperienceRequired) {
            $missingRequirements[] = "Minimum {$minExperienceRequired} years of experience (found: {$experienceYears})";
        }
        
        return [
            'firstname' => $firstName,
            'lastname' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'dateofbirth' => $dateOfBirth,
            'gender' => $gender,
            'degree_found' => $degreeFound,
            'degree_type' => $degreeType,
            'experience_years' => $experienceYears,
            'specializations' => $specializations,
            'is_valid' => $isValid,
            'score' => $score,
            'missing_requirements' => $missingRequirements,
            'analysis_method' => 'local',
            'analysis_date' => date('Y-m-d H:i:s')
        ];
    }
}