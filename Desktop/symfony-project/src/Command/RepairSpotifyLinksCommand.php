<?php

namespace App\Command;

use App\Repository\MoodRepository;
use App\Service\SpotifyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:spotify:repair-mood-recommendations',
    description: 'Repairs old or broken Spotify URLs in Mood logs.',
)]
class RepairSpotifyLinksCommand extends Command
{
    public function __construct(
        private MoodRepository $moodRepository,
        private EntityManagerInterface $entityManager,
        private SpotifyService $spotifyService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $moods = $this->moodRepository->findAll();
        $fixedCount = 0;

        foreach ($moods as $mood) {
            $url = $mood->getRecommendedTrackUrl();
            if ($url && !str_contains($url, 'open.spotify.com/track/')) {
                // If it's a search URL or broken, replace with fallback
                $fallback = $this->spotifyService->getRecommendationForMood($mood->getFeeling());
                $mood->setRecommendedTrackName($fallback['track_name']);
                $mood->setRecommendedArtistName($fallback['artist_name']);
                $mood->setRecommendedTrackUrl($fallback['track_url']);
                $mood->setRecommendedImageUrl($fallback['image_url']);
                $mood->setRecommendedPreviewUrl($fallback['preview_url']);
                $fixedCount++;
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('Repaired %d Spotify links.', $fixedCount));

        return Command::SUCCESS;
    }
}
