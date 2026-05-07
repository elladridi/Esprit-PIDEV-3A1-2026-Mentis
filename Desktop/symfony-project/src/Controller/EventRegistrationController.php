<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventRegistration;
use App\Entity\User;
use App\Form\EventRegistrationType;
use App\Repository\EventRegistrationRepository;
use App\Service\EmailNotificationService;
use App\Service\PDFTicketService;
use App\Service\QRCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/events')]
class EventRegistrationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventRegistrationRepository $registrationRepository,
        private EmailNotificationService $emailService,
        private PDFTicketService $pdfTicketService,
        private QRCodeService $qrCodeService,
        private KernelInterface $kernel
    ) {
    }

    #[Route('/{id}/register', name: 'app_registration_new', methods: ['GET', 'POST'])]
    public function register(Event $event, Request $request): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('error', 'Please login to register for events.');
            return $this->redirectToRoute('app_login');
        }

        if (!$event->isAvailable()) {
            $this->addFlash('error', 'This event is sold out.');
            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        /** @var User $user */
        $user = $this->getUser();

        $existingRegistration = $this->registrationRepository->findOneBy([
            'event' => $event,
            'email' => $user->getEmail(),
        ]);

        if ($existingRegistration && $existingRegistration->getStatus() !== 'CANCELLED') {
            $this->addFlash('info', 'You are already registered for this event.');
            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        $registration = new EventRegistration();
        $registration->setEvent($event);
        $registration->setUser($user);
        $registration->setEmail($user->getEmail());
        $registration->setPhone($user->getPhone() ?? '');
        $registration->setTicketType('STANDARD');
        $registration->setNumberOfTickets(1);
        $registration->setStatus('CONFIRMED');
        $registration->setRegistrationDate(new \DateTime());
        $registration->setPaymentMethod($event->isFree() ? 'FREE' : 'CREDIT_CARD');
        $registration->setTotalPrice('0');

        if (method_exists($user, 'getFirstname') && method_exists($user, 'getLastname')) {
            $registration->setUserName(trim($user->getFirstname() . ' ' . $user->getLastname()));
        } else {
            $registration->setUserName($user->getEmail());
        }

        $form = $this->createForm(EventRegistrationType::class, $registration);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $registration->setStatus('CONFIRMED');
                $registration->setRegistrationDate(new \DateTime());

                if ($event->isFree()) {
                    $registration->setPaymentMethod('FREE');
                }

                $totalPrice = $this->calculateTotalPrice($registration, $event);
                $registration->setTotalPrice((string) $totalPrice);

                $event->setCurrentParticipants(
                    $event->getCurrentParticipants() + $registration->getNumberOfTickets()
                );

                $this->entityManager->persist($registration);
                $this->entityManager->flush();

                $confirmationNumber = 'REG-' . str_pad((string) $registration->getId(), 6, '0', STR_PAD_LEFT);
                $registration->setConfirmationNumber($confirmationNumber);

                $qrCodePath = $this->qrCodeService->generateAndSave($registration, $event);

                if ($qrCodePath) {
                    $registration->setQrCodePath($qrCodePath);
                }

                $this->entityManager->flush();

                if (method_exists($this->emailService, 'sendConfirmationEmail')) {
                    $this->emailService->sendConfirmationEmail($registration, $event);
                }

                $this->addFlash('success', 'Registration confirmed! Your confirmation number is ' . $confirmationNumber);

                return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
            } catch (\Throwable $exception) {
                $this->addFlash('error', 'Registration failed: ' . $exception->getMessage());
            }
        }

        return $this->render('event/register.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
            'isPatient' => true,
            'canManage' => $this->canManage(),
        ]);
    }

    #[Route('/{id}/registration/new', name: 'registration_new', methods: ['GET', 'POST'])]
    public function newRegistration(Event $event, Request $request): Response
    {
        if (!$this->canManage()) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        if (!$event->isAvailable()) {
            $this->addFlash('error', 'This event is sold out.');
            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        $registration = new EventRegistration();
        $registration->setEvent($event);
        $registration->setStatus('CONFIRMED');
        $registration->setRegistrationDate(new \DateTime());
        $registration->setPaymentMethod($event->isFree() ? 'FREE' : 'CREDIT_CARD');
        $registration->setTotalPrice('0');

        $form = $this->createForm(EventRegistrationType::class, $registration);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $registration->setStatus('CONFIRMED');
                $registration->setRegistrationDate(new \DateTime());

                if ($event->isFree()) {
                    $registration->setPaymentMethod('FREE');
                }

                $totalPrice = $this->calculateTotalPrice($registration, $event);
                $registration->setTotalPrice((string) $totalPrice);

                $event->setCurrentParticipants(
                    $event->getCurrentParticipants() + $registration->getNumberOfTickets()
                );

                $this->entityManager->persist($registration);
                $this->entityManager->flush();

                $confirmationNumber = 'REG-' . str_pad((string) $registration->getId(), 6, '0', STR_PAD_LEFT);
                $registration->setConfirmationNumber($confirmationNumber);

                $qrCodePath = $this->qrCodeService->generateAndSave($registration, $event);

                if ($qrCodePath) {
                    $registration->setQrCodePath($qrCodePath);
                }

                $this->entityManager->flush();

                if (method_exists($this->emailService, 'sendConfirmationEmail')) {
                    $this->emailService->sendConfirmationEmail($registration, $event);
                }

                $this->addFlash('success', 'Registration created successfully!');

                return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
            } catch (\Throwable $exception) {
                $this->addFlash('error', 'Registration failed: ' . $exception->getMessage());
            }
        }

        return $this->render('event/register.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
            'isPatient' => false,
            'canManage' => true,
        ]);
    }

    #[Route('/registration/{id}/edit', name: 'app_registration_edit', methods: ['GET', 'POST'])]
    public function editRegistration(EventRegistration $registration, Request $request): Response
    {
        if (!$this->canManage()) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        $event = $registration->getEvent();
        $oldTicketCount = $registration->getNumberOfTickets();

        $form = $this->createForm(EventRegistrationType::class, $registration);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if (!$registration->getStatus()) {
                    $registration->setStatus('CONFIRMED');
                }

                $totalPrice = $this->calculateTotalPrice($registration, $event);
                $registration->setTotalPrice((string) $totalPrice);

                $difference = $registration->getNumberOfTickets() - $oldTicketCount;

                $event->setCurrentParticipants(
                    max(0, $event->getCurrentParticipants() + $difference)
                );

                $qrCodePath = $this->qrCodeService->generateAndSave($registration, $event);

                if ($qrCodePath) {
                    $registration->setQrCodePath($qrCodePath);
                }

                $this->entityManager->flush();

                $this->addFlash('success', 'Registration updated successfully!');

                return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
            } catch (\Throwable $exception) {
                $this->addFlash('error', 'Update failed: ' . $exception->getMessage());
            }
        }

        return $this->render('event/register.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
            'registration' => $registration,
            'isEdit' => true,
            'isPatient' => false,
            'canManage' => true,
        ]);
    }

    #[Route('/registration/{id}/cancel', name: 'app_registration_cancel', methods: ['POST'])]
    public function cancelRegistration(EventRegistration $registration, Request $request): Response
    {
        $event = $registration->getEvent();
        $user = $this->getUser();

        $isOwner = $user instanceof User && $registration->getEmail() === $user->getEmail();

        if (!$isOwner && !$this->canManage()) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        if ($this->isCsrfTokenValid('cancel' . $registration->getId(), $request->request->get('_token'))) {
            if ($registration->getStatus() === 'CONFIRMED') {
                $event->setCurrentParticipants(
                    max(0, $event->getCurrentParticipants() - $registration->getNumberOfTickets())
                );
            }

            $registration->setStatus('CANCELLED');
            $this->entityManager->flush();

            $this->addFlash('warning', 'Registration cancelled.');
        }

        return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
    }

    #[Route('/registration/{id}/delete', name: 'app_registration_delete', methods: ['POST'])]
    public function deleteRegistration(EventRegistration $registration, Request $request): Response
    {
        if (!$this->canManage()) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        $event = $registration->getEvent();
        $eventId = $event->getId();

        if ($this->isCsrfTokenValid('delete' . $registration->getId(), $request->request->get('_token'))) {
            if ($registration->getStatus() === 'CONFIRMED') {
                $event->setCurrentParticipants(
                    max(0, $event->getCurrentParticipants() - $registration->getNumberOfTickets())
                );
            }

            $this->entityManager->remove($registration);
            $this->entityManager->flush();

            $this->addFlash('success', 'Registration deleted successfully!');
        }

        return $this->redirectToRoute('app_event_show', ['id' => $eventId]);
    }

    #[Route('/registration/{id}/download-ticket', name: 'app_registration_download_ticket', methods: ['GET'])]
    public function downloadTicket(EventRegistration $registration): Response
    {
        $this->denyUnlessOwnerOrManager($registration);

        $pdfContent = $this->pdfTicketService->generateTicketContent(
            $registration,
            $registration->getEvent()
        );

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="ticket-' . $registration->getConfirmationNumber() . '.pdf"',
        ]);
    }

    #[Route('/registration/{id}/download-qr', name: 'app_registration_download_qr', methods: ['GET'])]
    public function downloadQRCode(EventRegistration $registration): Response
    {
        $this->denyUnlessOwnerOrManager($registration);

        $qrCodePath = $registration->getQrCodePath();

        if (!$qrCodePath) {
            $qrCodePath = $this->qrCodeService->generateAndSave($registration, $registration->getEvent());

            if ($qrCodePath) {
                $registration->setQrCodePath($qrCodePath);
                $this->entityManager->flush();
            }
        }

        if (!$qrCodePath) {
            $this->addFlash('error', 'Could not generate QR code.');
            return $this->redirectToRoute('app_event_show', [
                'id' => $registration->getEvent()->getId(),
            ]);
        }

        $absolutePath = $this->kernel->getProjectDir() . '/public' . $qrCodePath;

        if (!file_exists($absolutePath)) {
            $this->addFlash('error', 'QR code file was not found.');
            return $this->redirectToRoute('app_event_show', [
                'id' => $registration->getEvent()->getId(),
            ]);
        }

        $response = new BinaryFileResponse($absolutePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'qr-' . $registration->getConfirmationNumber() . '.png'
        );

        return $response;
    }

    #[Route('/registration/{id}/resend-email', name: 'app_registration_resend_email', methods: ['POST'])]
    public function resendEmail(EventRegistration $registration, Request $request): Response
    {
        if (!$this->canManage()) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        if ($this->isCsrfTokenValid('resend' . $registration->getId(), $request->request->get('_token'))) {
            if (method_exists($this->emailService, 'sendConfirmationEmail')) {
                $this->emailService->sendConfirmationEmail($registration, $registration->getEvent());
                $this->addFlash('success', 'Email sent successfully.');
            } else {
                $this->addFlash('error', 'Email service method sendConfirmationEmail() was not found.');
            }
        }

        return $this->redirectToRoute('app_event_show', [
            'id' => $registration->getEvent()->getId(),
        ]);
    }

    private function denyUnlessOwnerOrManager(EventRegistration $registration): void
    {
        $user = $this->getUser();
        $isOwner = $user instanceof User && $registration->getEmail() === $user->getEmail();

        if (!$isOwner && !$this->canManage()) {
            throw $this->createAccessDeniedException('Access denied.');
        }
    }

    private function canManage(): bool
    {
        return $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_PSYCHOLOGIST');
    }

    private function calculateTotalPrice(EventRegistration $registration, Event $event): float
    {
        $basePrice = floatval($event->getPrice());
        $multiplier = $registration->getTicketType() === 'VIP' ? 1.5 : 1.0;
        return $basePrice * $registration->getNumberOfTickets() * $multiplier;
    }
}