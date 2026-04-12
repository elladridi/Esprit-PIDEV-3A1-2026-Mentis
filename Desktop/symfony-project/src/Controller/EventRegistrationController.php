<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventRegistration;
use App\Form\EventRegistrationType;
use App\Repository\EventRegistrationRepository;
use App\Service\EmailNotificationService;
use App\Service\PDFTicketService;
use App\Service\QRCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/registration')]
class EventRegistrationController extends AbstractController
{
    #[Route('/event/{id}/register', name: 'app_registration_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        Event $event,
        EntityManagerInterface $entityManager,
        QRCodeService $qrCodeService,
        PDFTicketService $pdfService,
        EmailNotificationService $emailService,
        EventRegistrationRepository $registrationRepository
    ): Response {
        // Allow Patients, Admins, and Psychologists to create registrations
        if (!$this->isGranted('ROLE_PATIENT') && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_PSYCHOLOGIST')) {
            throw $this->createAccessDeniedException('You must be logged in to register for events.');
        }

        // Check if event is available
        if (!$event->isAvailable()) {
            $this->addFlash('error', 'This event is sold out!');
            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        // For logged-in patients, pre-fill data
        $registration = new EventRegistration();
        $registration->setEvent($event);

        if ($this->getUser() && $this->isGranted('ROLE_PATIENT')) {
            $user = $this->getUser();
            $registration->setUser($user);
            $registration->setUserName($user->getFirstname() . ' ' . $user->getLastname());
            $registration->setEmail($user->getEmail());
            $registration->setPhone($user->getPhone());

            // Check if already registered
            $existing = $registrationRepository->findOneBy([
                'event' => $event,
                'email' => $user->getEmail()
            ]);
            if ($existing) {
                $this->addFlash('warning', 'You are already registered for this event!');
                return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
            }
        }

        $form = $this->createForm(EventRegistrationType::class, $registration);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Calculate total price
            $basePrice = floatval($event->getPrice());
            $multiplier = $registration->getTicketType() === 'VIP' ? 1.5 : 1.0;
            $totalPrice = $basePrice * $registration->getNumberOfTickets() * $multiplier;
            $registration->setTotalPrice((string)$totalPrice);

            // Set payment method default for free events
            if ($event->isFree()) {
                $registration->setPaymentMethod('FREE');
            }

            // Generate confirmation number
            $registration->setConfirmationNumber('REG-' . uniqid());

            // Save registration
            $entityManager->persist($registration);
            
            // Update event participant count
            $event->setCurrentParticipants($event->getCurrentParticipants() + $registration->getNumberOfTickets());
            
            $entityManager->flush();

            // Generate QR code
            $qrCodePath = $qrCodeService->generateAndSave($registration, $event);
            if ($qrCodePath) {
                $registration->setQrCodePath($qrCodePath);
                $entityManager->flush();
            }

            // Generate PDF ticket
            $pdfService->generateTicket($registration, $event);

            // Send confirmation email
            $emailService->sendConfirmationEmail($registration, $event);

            $this->addFlash('success', 'Registration completed successfully! A confirmation email has been sent.');

            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        return $this->render('event/register.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_registration_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        EventRegistration $registration,
        EntityManagerInterface $entityManager
    ): Response {
        // Allow Admin and Psychologist to edit registrations
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_PSYCHOLOGIST')) {
            throw $this->createAccessDeniedException('Only administrators and psychologists can edit registrations.');
        }

        $event = $registration->getEvent();
        $form = $this->createForm(EventRegistrationType::class, $registration);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Recalculate price
            $basePrice = floatval($event->getPrice());
            $multiplier = $registration->getTicketType() === 'VIP' ? 1.5 : 1.0;
            $totalPrice = $basePrice * $registration->getNumberOfTickets() * $multiplier;
            $registration->setTotalPrice((string)$totalPrice);

            $entityManager->flush();
            $this->addFlash('success', 'Registration updated successfully!');

            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        return $this->render('event/register_edit.html.twig', [
            'form' => $form->createView(),
            'registration' => $registration,
            'event' => $event,
        ]);
    }

    #[Route('/{id}/cancel', name: 'app_registration_cancel', methods: ['POST'])]
    public function cancel(
        Request $request,
        EventRegistration $registration,
        EntityManagerInterface $entityManager
    ): Response {
        $event = $registration->getEvent();

        // Check permissions - Owner OR Admin OR Psychologist can cancel
        $isOwner = $this->getUser() && $registration->getEmail() === $this->getUser()->getEmail();
        if (!$isOwner && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_PSYCHOLOGIST')) {
            throw $this->createAccessDeniedException('You do not have permission to cancel this registration.');
        }

        if ($this->isCsrfTokenValid('cancel' . $registration->getId(), $request->request->get('_token'))) {
            // Update participant count
            $event->setCurrentParticipants(max(0, $event->getCurrentParticipants() - $registration->getNumberOfTickets()));
            $registration->setStatus('CANCELLED');
            
            $entityManager->flush();
            $this->addFlash('success', 'Registration cancelled successfully!');
        }

        return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
    }

    #[Route('/{id}/delete', name: 'app_registration_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        EventRegistration $registration,
        EntityManagerInterface $entityManager
    ): Response {
        // Allow Admin and Psychologist to delete registrations
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_PSYCHOLOGIST')) {
            throw $this->createAccessDeniedException('Only administrators and psychologists can delete registrations.');
        }

        $event = $registration->getEvent();

        if ($this->isCsrfTokenValid('delete' . $registration->getId(), $request->request->get('_token'))) {
            // Update participant count if registration was confirmed
            if ($registration->isConfirmed()) {
                $event->setCurrentParticipants(max(0, $event->getCurrentParticipants() - $registration->getNumberOfTickets()));
            }
            $entityManager->remove($registration);
            $entityManager->flush();
            $this->addFlash('success', 'Registration deleted successfully!');
        }

        return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
    }

    #[Route('/{id}/download-ticket', name: 'app_registration_download_ticket', methods: ['GET'])]
    public function downloadTicket(
        EventRegistration $registration,
        PDFTicketService $pdfService
    ): Response {
        $event = $registration->getEvent();

        // Check permissions - Owner OR Admin OR Psychologist can download
        $isOwner = $this->getUser() && $registration->getEmail() === $this->getUser()->getEmail();
        if (!$isOwner && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_PSYCHOLOGIST')) {
            throw $this->createAccessDeniedException('You do not have permission to download this ticket.');
        }

        $pdfContent = $pdfService->generateTicketContent($registration, $event);

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'filename' => 'ticket_' . $registration->getConfirmationNumber() . '.pdf',
        ]);
    }

    #[Route('/{id}/resend-email', name: 'app_registration_resend_email', methods: ['POST'])]
    public function resendEmail(
        Request $request,
        EventRegistration $registration,
        EmailNotificationService $emailService,
        Event $event = null
    ): Response {
        // Only Admin and Psychologist can resend emails
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_PSYCHOLOGIST')) {
            throw $this->createAccessDeniedException('Only administrators and psychologists can resend confirmation emails.');
        }

        $event = $registration->getEvent();

        if ($this->isCsrfTokenValid('resend' . $registration->getId(), $request->request->get('_token'))) {
            $success = $emailService->sendConfirmationEmail($registration, $event);
            if ($success) {
                $this->addFlash('success', 'Confirmation email resent successfully!');
            } else {
                $this->addFlash('error', 'Failed to send email. Please check email configuration.');
            }
        }

        return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
    }
}