<?php

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
    Symfony\Bundle\MakerBundle\MakerBundle::class => ['dev' => true],
    Flasher\Symfony\FlasherSymfonyBundle::class => ['all' => true],
    Flasher\SweetAlert\Symfony\FlasherSweetAlertSymfonyBundle::class => ['all' => true],
    CalendarBundle\CalendarBundle::class => ['all' => true],
    Bazinga\GeocoderBundle\BazingaGeocoderBundle::class => ['all' => true],
];
