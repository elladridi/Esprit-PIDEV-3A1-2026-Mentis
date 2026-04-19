<?php
// src/Command/CleanExpiredBansCommand.php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:clean-expired-bans', description: 'Clean expired bans')]
class CleanExpiredBansCommand extends Command
{
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(UserRepository $userRepository, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $bannedUsers = $this->userRepository->findBy(['isBanned' => true]);
        $count = 0;

        foreach ($bannedUsers as $user) {
            if ($user->getBannedUntil() && $user->getBannedUntil() < new \DateTime()) {
                $user->setIsBanned(false);
                $user->setBannedUntil(null);
                $count++;
            }
        }

        $this->entityManager->flush();
        
        $output->writeln("Cleaned {$count} expired bans.");
        
        return Command::SUCCESS;
    }
}