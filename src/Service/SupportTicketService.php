<?php

namespace App\Service;

use App\Entity\SupportTicket;
use App\Entity\SupportTicketMessage;
use App\Repository\SupportTicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Support Ticket Service
 * Manages customer support tickets and communication
 */
class SupportTicketService
{
    public function __construct(
        private EntityManagerInterface $em,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private string $supportEmail = 'support@example.com'
    ) {}

    /**
     * Create a new support ticket
     */
    public function createTicket(array $data): SupportTicket
    {
        $this->logger->info('Creating support ticket', [
            'tenant_id' => $data['tenant_id'],
            'subject' => $data['subject']
        ]);

        $ticket = new SupportTicket();
        $ticket->setTenantId($data['tenant_id']);
        $ticket->setSubject($data['subject']);
        $ticket->setDescription($data['description']);
        $ticket->setCategory($data['category'] ?? SupportTicket::CATEGORY_QUESTION);
        $ticket->setPriority($data['priority'] ?? SupportTicket::PRIORITY_MEDIUM);
        $ticket->setRequesterEmail($data['requester_email']);
        $ticket->setRequesterName($data['requester_name'] ?? null);

        if (!empty($data['tags'])) {
            $ticket->setTags($data['tags']);
        }

        // Add initial message
        $message = new SupportTicketMessage();
        $message->setMessage($data['description']);
        $message->setIsFromAgent(false);
        $message->setAuthorEmail($data['requester_email']);
        $message->setAuthorName($data['requester_name'] ?? null);

        $ticket->addMessage($message);

        $this->em->persist($ticket);
        $this->em->flush();

        // Send confirmation email to requester
        $this->sendTicketCreatedEmail($ticket);

        // Notify support team
        $this->notifySupportTeam($ticket);

        $this->logger->info('Support ticket created', [
            'ticket_id' => $ticket->getId(),
            'ticket_number' => $ticket->getTicketNumber()
        ]);

        return $ticket;
    }

    /**
     * Add message to ticket
     */
    public function addMessage(
        SupportTicket $ticket,
        string $messageText,
        bool $isFromAgent = false,
        ?string $authorName = null,
        ?string $authorEmail = null,
        bool $isInternal = false
    ): SupportTicketMessage {
        $message = new SupportTicketMessage();
        $message->setMessage($messageText);
        $message->setIsFromAgent($isFromAgent);
        $message->setAuthorName($authorName);
        $message->setAuthorEmail($authorEmail);
        $message->setIsInternal($isInternal);

        $ticket->addMessage($message);

        $this->em->flush();

        // Send notification email
        if ($isFromAgent && !$isInternal) {
            $this->sendAgentResponseEmail($ticket, $message);
        } elseif (!$isFromAgent) {
            $this->notifySupportTeam($ticket);
        }

        $this->logger->info('Message added to ticket', [
            'ticket_id' => $ticket->getId(),
            'from_agent' => $isFromAgent,
            'internal' => $isInternal
        ]);

        return $message;
    }

    /**
     * Assign ticket to agent
     */
    public function assignTicket(
        SupportTicket $ticket,
        int $agentUserId,
        string $agentName
    ): SupportTicket {
        $ticket->setAssignedToUserId($agentUserId);
        $ticket->setAssignedToName($agentName);
        $ticket->setStatus(SupportTicket::STATUS_IN_PROGRESS);

        $this->em->flush();

        $this->logger->info('Ticket assigned', [
            'ticket_id' => $ticket->getId(),
            'assigned_to' => $agentName
        ]);

        return $ticket;
    }

    /**
     * Update ticket status
     */
    public function updateStatus(SupportTicket $ticket, string $newStatus): SupportTicket
    {
        $oldStatus = $ticket->getStatus();
        $ticket->setStatus($newStatus);

        $this->em->flush();

        $this->logger->info('Ticket status updated', [
            'ticket_id' => $ticket->getId(),
            'old_status' => $oldStatus,
            'new_status' => $newStatus
        ]);

        // Send notification on resolution
        if ($newStatus === SupportTicket::STATUS_RESOLVED) {
            $this->sendTicketResolvedEmail($ticket);
        }

        return $ticket;
    }

    /**
     * Update ticket priority
     */
    public function updatePriority(SupportTicket $ticket, string $newPriority): SupportTicket
    {
        $ticket->setPriority($newPriority);
        $this->em->flush();

        $this->logger->info('Ticket priority updated', [
            'ticket_id' => $ticket->getId(),
            'priority' => $newPriority
        ]);

        return $ticket;
    }

    /**
     * Get ticket statistics
     */
    public function getStatistics(?string $tenantId = null): array
    {
        $repository = $this->em->getRepository(SupportTicket::class);

        $qb = $this->em->createQueryBuilder();
        $qb->select('t')
           ->from(SupportTicket::class, 't');

        if ($tenantId) {
            $qb->where('t.tenantId = :tenantId')
               ->setParameter('tenantId', $tenantId);
        }

        $tickets = $qb->getQuery()->getResult();

        $stats = [
            'total' => count($tickets),
            'by_status' => [],
            'by_priority' => [],
            'by_category' => [],
            'avg_response_time_minutes' => 0,
            'avg_resolution_time_hours' => 0,
        ];

        $responseTimes = [];
        $resolutionTimes = [];

        foreach ($tickets as $ticket) {
            // Count by status
            $status = $ticket->getStatus();
            $stats['by_status'][$status] = ($stats['by_status'][$status] ?? 0) + 1;

            // Count by priority
            $priority = $ticket->getPriority();
            $stats['by_priority'][$priority] = ($stats['by_priority'][$priority] ?? 0) + 1;

            // Count by category
            $category = $ticket->getCategory();
            $stats['by_category'][$category] = ($stats['by_category'][$category] ?? 0) + 1;

            // Response times
            if ($time = $ticket->getResponseTimeMinutes()) {
                $responseTimes[] = $time;
            }

            // Resolution times
            if ($time = $ticket->getResolutionTimeHours()) {
                $resolutionTimes[] = $time;
            }
        }

        if (!empty($responseTimes)) {
            $stats['avg_response_time_minutes'] = round(array_sum($responseTimes) / count($responseTimes), 2);
        }

        if (!empty($resolutionTimes)) {
            $stats['avg_resolution_time_hours'] = round(array_sum($resolutionTimes) / count($resolutionTimes), 2);
        }

        return $stats;
    }

    /**
     * Send ticket created email
     */
    private function sendTicketCreatedEmail(SupportTicket $ticket): void
    {
        try {
            $email = (new Email())
                ->from($this->supportEmail)
                ->to($ticket->getRequesterEmail())
                ->subject("Ticket Created: {$ticket->getTicketNumber()}")
                ->html("
                    <h2>Support Ticket Created</h2>
                    <p>Your support ticket has been created successfully.</p>
                    <p><strong>Ticket Number:</strong> {$ticket->getTicketNumber()}</p>
                    <p><strong>Subject:</strong> {$ticket->getSubject()}</p>
                    <p>We'll get back to you as soon as possible.</p>
                ");

            $this->mailer->send($email);

        } catch (\Exception $e) {
            $this->logger->error('Failed to send ticket created email', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send agent response email
     */
    private function sendAgentResponseEmail(SupportTicket $ticket, SupportTicketMessage $message): void
    {
        try {
            $email = (new Email())
                ->from($this->supportEmail)
                ->to($ticket->getRequesterEmail())
                ->subject("Re: {$ticket->getTicketNumber()} - {$ticket->getSubject()}")
                ->html("
                    <h2>New Response from Support Team</h2>
                    <p><strong>Ticket:</strong> {$ticket->getTicketNumber()}</p>
                    <p><strong>From:</strong> {$message->getAuthorName()}</p>
                    <hr>
                    <p>{$message->getMessage()}</p>
                ");

            $this->mailer->send($email);

        } catch (\Exception $e) {
            $this->logger->error('Failed to send agent response email', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send ticket resolved email
     */
    private function sendTicketResolvedEmail(SupportTicket $ticket): void
    {
        try {
            $email = (new Email())
                ->from($this->supportEmail)
                ->to($ticket->getRequesterEmail())
                ->subject("Resolved: {$ticket->getTicketNumber()}")
                ->html("
                    <h2>Ticket Resolved</h2>
                    <p>Your support ticket has been resolved.</p>
                    <p><strong>Ticket Number:</strong> {$ticket->getTicketNumber()}</p>
                    <p>If you need further assistance, please reply to this ticket.</p>
                ");

            $this->mailer->send($email);

        } catch (\Exception $e) {
            $this->logger->error('Failed to send ticket resolved email', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notify support team
     */
    private function notifySupportTeam(SupportTicket $ticket): void
    {
        // TODO: Send notification to support team (Slack, email, etc.)

        $this->logger->info('Support team notified', [
            'ticket_id' => $ticket->getId(),
            'ticket_number' => $ticket->getTicketNumber()
        ]);
    }
}
