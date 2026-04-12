<?php

namespace App\Service;

class MeditationService
{
    private array $sessions = [
        'anxiety' => [
            'high' => [
                [
                    'title'       => '10-Minute Panic Relief Breathing',
                    'duration'    => '10 min',
                    'type'        => 'Breathing Exercise',
                    'description' => 'Box breathing technique to stop panic attacks immediately.',
                    'steps'       => [
                        'Find a comfortable seated position',
                        'Breathe IN slowly for 4 counts',
                        'HOLD your breath for 4 counts',
                        'Breathe OUT slowly for 4 counts',
                        'HOLD empty for 4 counts',
                        'Repeat 8-10 times',
                    ],
                    'youtubeId'  => 'tybOi4hjZFQ',
                    'color'      => '#e74c3c',
                    'icon'       => 'fa-wind',
                    'difficulty' => 'Beginner',
                ],
                [
                    'title'       => '5-4-3-2-1 Grounding Technique',
                    'duration'    => '5 min',
                    'type'        => 'Grounding',
                    'description' => 'Use your senses to anchor yourself to the present moment.',
                    'steps'       => [
                        'Name 5 things you can SEE',
                        'Name 4 things you can TOUCH',
                        'Name 3 things you can HEAR',
                        'Name 2 things you can SMELL',
                        'Name 1 thing you can TASTE',
                        'Take 3 deep breaths',
                    ],
                    'youtubeId'  => 'SqvMkj0GWMI',
                    'color'      => '#9b59b6',
                    'icon'       => 'fa-hand-sparkles',
                    'difficulty' => 'Beginner',
                ],
            ],
            'moderate' => [
                [
                    'title'       => '15-Minute Anxiety Release Meditation',
                    'duration'    => '15 min',
                    'type'        => 'Guided Meditation',
                    'description' => 'Body scan meditation to release tension and anxiety.',
                    'steps'       => [
                        'Lie down or sit comfortably',
                        'Close your eyes and breathe naturally',
                        'Scan your body from head to toe',
                        'Notice any tension without judgment',
                        'Breathe into areas of tension',
                        'Visualize tension melting away',
                    ],
                    'youtubeId'  => 'O-6f5wQXSu8',
                    'color'      => '#3498db',
                    'icon'       => 'fa-spa',
                    'difficulty' => 'Intermediate',
                ],
            ],
            'low' => [
                [
                    'title'       => 'Morning Mindfulness for Calm',
                    'duration'    => '10 min',
                    'type'        => 'Mindfulness',
                    'description' => 'Start your day grounded and centered.',
                    'steps'       => [
                        'Sit comfortably after waking',
                        'Set a positive intention for the day',
                        'Take 5 slow deep breaths',
                        'Observe thoughts without attachment',
                        'Return focus to breath when distracted',
                        'End with gratitude for 3 things',
                    ],
                    'youtubeId'  => 'inpok4MKVLM',
                    'color'      => '#50C878',
                    'icon'       => 'fa-sun',
                    'difficulty' => 'Beginner',
                ],
            ],
        ],

        'depression' => [
            'high' => [
                [
                    'title'       => 'Loving-Kindness Meditation',
                    'duration'    => '12 min',
                    'type'        => 'Compassion Practice',
                    'description' => 'Cultivate self-compassion and reduce self-criticism.',
                    'steps'       => [
                        'Sit comfortably and close your eyes',
                        'Place a hand on your heart',
                        'Repeat: "May I be happy"',
                        'Repeat: "May I be at peace"',
                        'Repeat: "May I be free from suffering"',
                        'Extend these wishes to others',
                    ],
                    'youtubeId'  => 'sz7cpV7ERsM',
                    'color'      => '#e91e63',
                    'icon'       => 'fa-heart',
                    'difficulty' => 'Beginner',
                ],
                [
                    'title'       => 'Behavioral Activation Walk',
                    'duration'    => '20 min',
                    'type'        => 'Movement Therapy',
                    'description' => 'A mindful walking exercise to activate your mood.',
                    'steps'       => [
                        'Put on comfortable shoes',
                        'Step outside or walk indoors',
                        'Notice each footstep as you walk',
                        'Observe 5 things in your environment',
                        'Breathe in for 4 steps, out for 4',
                        'End with a self-compassion statement',
                    ],
                    'youtubeId'  => 'MIr3RsUWrdo',
                    'color'      => '#ff9800',
                    'icon'       => 'fa-person-walking',
                    'difficulty' => 'Beginner',
                ],
            ],
            'moderate' => [
                [
                    'title'       => 'Gratitude Visualization',
                    'duration'    => '10 min',
                    'type'        => 'Visualization',
                    'description' => 'Shift focus toward positive experiences.',
                    'steps'       => [
                        'Sit quietly and close your eyes',
                        'Think of one person you appreciate',
                        'Visualize their face clearly',
                        'Feel gratitude in your chest',
                        'Think of one good memory',
                        'Breathe in the positive feeling',
                    ],
                    'youtubeId'  => 'U9YKY7fdwyg',
                    'color'      => '#50C878',
                    'icon'       => 'fa-star',
                    'difficulty' => 'Beginner',
                ],
            ],
            'low' => [
                [
                    'title'       => 'Positive Affirmation Practice',
                    'duration'    => '5 min',
                    'type'        => 'Affirmations',
                    'description' => 'Build a positive mindset with daily affirmations.',
                    'steps'       => [
                        'Stand or sit in front of a mirror',
                        'Take 3 deep breaths',
                        'Say: "I am worthy of happiness"',
                        'Say: "I am doing my best"',
                        'Say: "I choose to grow today"',
                        'Repeat each 3 times with conviction',
                    ],
                    'youtubeId'  => 'z6X5oH_hSBY',
                    'color'      => '#2196f3',
                    'icon'       => 'fa-microphone',
                    'difficulty' => 'Beginner',
                ],
            ],
        ],

        'stress' => [
            'high' => [
                [
                    'title'       => 'Progressive Muscle Relaxation',
                    'duration'    => '20 min',
                    'type'        => 'Body Relaxation',
                    'description' => 'Systematically tense and release muscle groups.',
                    'steps'       => [
                        'Lie down comfortably',
                        'Tense your feet for 5 seconds',
                        'Release and feel the relaxation',
                        'Move up to calves, thighs, stomach',
                        'Continue to hands, arms, shoulders',
                        'End with face muscles',
                    ],
                    'youtubeId'  => 'ClqPtWzAnPo',
                    'color'      => '#e67e22',
                    'icon'       => 'fa-person-rays',
                    'difficulty' => 'Beginner',
                ],
            ],
            'moderate' => [
                [
                    'title'       => '4-7-8 Breathing for Stress',
                    'duration'    => '8 min',
                    'type'        => 'Breathing Exercise',
                    'description' => 'A powerful technique to activate the relaxation response.',
                    'steps'       => [
                        'Exhale completely through your mouth',
                        'Close mouth and inhale for 4 counts',
                        'Hold breath for 7 counts',
                        'Exhale through mouth for 8 counts',
                        'This is one breath cycle',
                        'Repeat 4 times',
                    ],
                    'youtubeId'  => 'gz4G31LGyog',
                    'color'      => '#3498db',
                    'icon'       => 'fa-wind',
                    'difficulty' => 'Beginner',
                ],
            ],
            'low' => [
                [
                    'title'       => 'Nature Soundscape Relaxation',
                    'duration'    => '15 min',
                    'type'        => 'Sound Therapy',
                    'description' => 'Let natural sounds calm your nervous system.',
                    'steps'       => [
                        'Find a quiet comfortable space',
                        'Put on headphones if available',
                        'Close your eyes',
                        'Focus only on the sounds',
                        'Let thoughts pass like clouds',
                        'Breathe naturally throughout',
                    ],
                    'youtubeId'  => 'eKFTSSKCfni',
                    'color'      => '#50C878',
                    'icon'       => 'fa-leaf',
                    'difficulty' => 'Beginner',
                ],
            ],
        ],

        'sleep' => [
            'high' => [
                [
                    'title'       => 'Sleep Hygiene Reset',
                    'duration'    => '30 min',
                    'type'        => 'Sleep Protocol',
                    'description' => 'A complete wind-down routine for better sleep.',
                    'steps'       => [
                        'Dim all lights 30 minutes before bed',
                        'Put all screens away',
                        'Make a warm herbal tea',
                        'Write tomorrow\'s top 3 tasks',
                        'Do 5 minutes of gentle stretching',
                        'Practice 4-7-8 breathing in bed',
                    ],
                    'youtubeId'  => 'ZToicYcHIOU',
                    'color'      => '#673ab7',
                    'icon'       => 'fa-moon',
                    'difficulty' => 'Beginner',
                ],
            ],
            'moderate' => [
                [
                    'title'       => 'Body Scan for Sleep',
                    'duration'    => '20 min',
                    'type'        => 'Guided Meditation',
                    'description' => 'Progressively relax your body into sleep.',
                    'steps'       => [
                        'Lie in your sleep position',
                        'Close your eyes and breathe slowly',
                        'Focus on your toes — relax them',
                        'Move attention slowly up your body',
                        'At each area, breathe and release',
                        'By the time you reach your head, sleep',
                    ],
                    'youtubeId'  => 'T0nuKl9A7R4',
                    'color'      => '#3f51b5',
                    'icon'       => 'fa-bed',
                    'difficulty' => 'Beginner',
                ],
            ],
            'low' => [
                [
                    'title'       => 'Bedtime Gratitude Practice',
                    'duration'    => '5 min',
                    'type'        => 'Mindfulness',
                    'description' => 'End each day with a positive mindset.',
                    'steps'       => [
                        'Lie comfortably in bed',
                        'Think of 3 good things from today',
                        'They can be tiny — a good meal, a smile',
                        'Feel the warmth of each memory',
                        'Take 3 slow deep breaths',
                        'Let yourself drift off peacefully',
                    ],
                    'youtubeId'  => 'WPPPFqsECz0',
                    'color'      => '#009688',
                    'icon'       => 'fa-moon',
                    'difficulty' => 'Beginner',
                ],
            ],
        ],

        'general' => [
            'high' => [
                [
                    'title'       => 'Emergency Calm: 3-Minute Reset',
                    'duration'    => '3 min',
                    'type'        => 'Crisis Support',
                    'description' => 'Immediate relief when feeling overwhelmed.',
                    'steps'       => [
                        'Stop what you are doing',
                        'Place both feet flat on the floor',
                        'Take 5 very slow deep breaths',
                        'Name 3 things you can see',
                        'Say to yourself: "This will pass"',
                        'Repeat until feeling calmer',
                    ],
                    'youtubeId'  => 'tybOi4hjZFQ',
                    'color'      => '#f44336',
                    'icon'       => 'fa-circle-exclamation',
                    'difficulty' => 'Beginner',
                ],
            ],
            'low' => [
                [
                    'title'       => 'Daily Mindfulness Check-In',
                    'duration'    => '5 min',
                    'type'        => 'Mindfulness',
                    'description' => 'A simple daily practice to stay grounded.',
                    'steps'       => [
                        'Pause whatever you are doing',
                        'Take 3 conscious breaths',
                        'Ask: How am I feeling right now?',
                        'Accept whatever answer comes',
                        'Ask: What do I need right now?',
                        'Take one small action toward that need',
                    ],
                    'youtubeId'  => 'inpok4MKVLM',
                    'color'      => '#50C878',
                    'icon'       => 'fa-heart-pulse',
                    'difficulty' => 'Beginner',
                ],
            ],
        ],
    ];

    public function getSessions(string $assessmentType, string $riskLevel): array
    {
        $type = strtolower($assessmentType);
        $risk = strtolower($riskLevel);

        $riskKey = match(true) {
            in_array($risk, ['high', 'severe'])   => 'high',
            in_array($risk, ['moderate', 'mild']) => 'moderate',
            default                                => 'low',
        };

        $sessions = [];

        if (isset($this->sessions[$type][$riskKey])) {
            $sessions = array_merge($sessions, $this->sessions[$type][$riskKey]);
        }

        if (empty($sessions) && $riskKey === 'high' && isset($this->sessions[$type]['moderate'])) {
            $sessions = array_merge($sessions, $this->sessions[$type]['moderate']);
        }

        if (isset($this->sessions['general'][$riskKey])) {
            $sessions = array_merge($sessions, $this->sessions['general'][$riskKey]);
        }

        if (empty($sessions)) {
            foreach ($this->sessions['general'] as $levelSessions) {
                $sessions = array_merge($sessions, $levelSessions);
            }
        }

        return array_slice($sessions, 0, 4);
    }
}