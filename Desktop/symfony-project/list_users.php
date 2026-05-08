<?php
require_once __DIR__.'/vendor/autoload.php';
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->load(__DIR__.'/.env');
$kernel = new Kernel($_ENV['APP_ENV'], (bool) $_ENV['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();
$users = $em->getRepository(\App\Entity\User::class)->findAll();

foreach ($users as $user) {
    echo $user->getEmail() . "\n";
}
