<?php

namespace App\Service;

use App\Entity\EmailVerificationToken;
use App\Entity\PasswordResetToken;
use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Psr\Log\LoggerInterface;

class EmailService
{
    private const FROM_EMAIL = 'noreply@crm-app.com';
    private const FROM_NAME = 'CRM Application';

    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger,
        private string $frontendUrl = 'http://localhost:3000' // Should be configured via env
    ) {}

    /**
     * Send email verification email
     */
    public function sendVerificationEmail(User $user, EmailVerificationToken $token): void
    {
        try {
            // Generate verification URL
            $verificationUrl = $this->frontendUrl . '/auth/verify-email?token=' . $token->getToken();

            $email = (new Email())
                ->from(self::FROM_EMAIL)
                ->to($user->getEmail())
                ->subject('Verify your email address')
                ->html($this->getVerificationEmailTemplate($user, $verificationUrl, $token->getExpiresAt()));

            $this->mailer->send($email);

            $this->logger->info('Verification email sent', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to send verification email', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'error' => $e->getMessage()
            ]);

            throw new \RuntimeException('Failed to send verification email', 0, $e);
        }
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail(User $user, PasswordResetToken $token): void
    {
        try {
            // Generate reset URL
            $resetUrl = $this->frontendUrl . '/auth/reset-password?token=' . $token->getToken();

            $email = (new Email())
                ->from(self::FROM_EMAIL)
                ->to($user->getEmail())
                ->subject('Reset your password')
                ->html($this->getPasswordResetEmailTemplate($user, $resetUrl, $token->getExpiresAt()));

            $this->mailer->send($email);

            $this->logger->info('Password reset email sent', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to send password reset email', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'error' => $e->getMessage()
            ]);

            throw new \RuntimeException('Failed to send password reset email', 0, $e);
        }
    }

    /**
     * Send welcome email after successful registration
     */
    public function sendWelcomeEmail(User $user): void
    {
        try {
            $email = (new Email())
                ->from(self::FROM_EMAIL)
                ->to($user->getEmail())
                ->subject('Welcome to CRM Application')
                ->html($this->getWelcomeEmailTemplate($user));

            $this->mailer->send($email);

            $this->logger->info('Welcome email sent', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to send welcome email', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'error' => $e->getMessage()
            ]);

            // Don't throw - welcome email failure shouldn't break the flow
        }
    }

    /**
     * Send password changed notification
     */
    public function sendPasswordChangedNotification(User $user): void
    {
        try {
            $email = (new Email())
                ->from(self::FROM_EMAIL)
                ->to($user->getEmail())
                ->subject('Your password has been changed')
                ->html($this->getPasswordChangedEmailTemplate($user));

            $this->mailer->send($email);

            $this->logger->info('Password changed notification sent', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to send password changed notification', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'error' => $e->getMessage()
            ]);

            // Don't throw - notification failure shouldn't break the flow
        }
    }

    /**
     * Send suspicious activity alert
     */
    public function sendSuspiciousActivityAlert(User $user, string $ipAddress, string $location = 'Unknown'): void
    {
        try {
            $email = (new Email())
                ->from(self::FROM_EMAIL)
                ->to($user->getEmail())
                ->subject('Suspicious activity detected on your account')
                ->html($this->getSuspiciousActivityEmailTemplate($user, $ipAddress, $location));

            $this->mailer->send($email);

            $this->logger->info('Suspicious activity alert sent', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'ip_address' => $ipAddress
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to send suspicious activity alert', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Email verification template
     */
    private function getVerificationEmailTemplate(User $user, string $verificationUrl, \DateTimeInterface $expiresAt): string
    {
        $expiresIn = $expiresAt->diff(new \DateTimeImmutable())->format('%h hours');

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .button {
                    display: inline-block;
                    padding: 12px 24px;
                    background-color: #4CAF50;
                    color: white;
                    text-decoration: none;
                    border-radius: 4px;
                    margin: 20px 0;
                }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Verify Your Email</h1>
                </div>
                <div class="content">
                    <p>Hello {$user->getFirstname()},</p>
                    <p>Thank you for registering with CRM Application. Please verify your email address by clicking the button below:</p>
                    <p style="text-align: center;">
                        <a href="{$verificationUrl}" class="button">Verify Email Address</a>
                    </p>
                    <p>Or copy and paste this link into your browser:</p>
                    <p style="word-break: break-all; color: #666;">{$verificationUrl}</p>
                    <p>This link will expire in {$expiresIn}.</p>
                    <p>If you didn't create an account, you can safely ignore this email.</p>
                </div>
                <div class="footer">
                    <p>&copy; 2025 CRM Application. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    /**
     * Password reset email template
     */
    private function getPasswordResetEmailTemplate(User $user, string $resetUrl, \DateTimeInterface $expiresAt): string
    {
        $expiresIn = $expiresAt->diff(new \DateTimeImmutable())->format('%h hours %i minutes');

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #FF5722; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .button {
                    display: inline-block;
                    padding: 12px 24px;
                    background-color: #FF5722;
                    color: white;
                    text-decoration: none;
                    border-radius: 4px;
                    margin: 20px 0;
                }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .warning { background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Reset Your Password</h1>
                </div>
                <div class="content">
                    <p>Hello {$user->getFirstname()},</p>
                    <p>We received a request to reset your password. Click the button below to create a new password:</p>
                    <p style="text-align: center;">
                        <a href="{$resetUrl}" class="button">Reset Password</a>
                    </p>
                    <p>Or copy and paste this link into your browser:</p>
                    <p style="word-break: break-all; color: #666;">{$resetUrl}</p>
                    <p>This link will expire in {$expiresIn}.</p>
                    <div class="warning">
                        <strong>Security Notice:</strong> If you didn't request a password reset, please ignore this email or contact support if you're concerned about your account security.
                    </div>
                </div>
                <div class="footer">
                    <p>&copy; 2025 CRM Application. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    /**
     * Welcome email template
     */
    private function getWelcomeEmailTemplate(User $user): string
    {
        $dashboardUrl = $this->frontendUrl . '/dashboard';

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #2196F3; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .button {
                    display: inline-block;
                    padding: 12px 24px;
                    background-color: #2196F3;
                    color: white;
                    text-decoration: none;
                    border-radius: 4px;
                    margin: 20px 0;
                }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .features { list-style: none; padding: 0; }
                .features li { padding: 8px 0; padding-left: 24px; position: relative; }
                .features li:before { content: "✓"; position: absolute; left: 0; color: #4CAF50; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Welcome to CRM Application!</h1>
                </div>
                <div class="content">
                    <p>Hello {$user->getFirstname()},</p>
                    <p>Your email has been verified and your account is now active. Welcome to CRM Application!</p>
                    <p>Here's what you can do with your account:</p>
                    <ul class="features">
                        <li>Manage contacts and companies</li>
                        <li>Track deals and opportunities</li>
                        <li>Schedule activities and meetings</li>
                        <li>Generate reports and insights</li>
                        <li>Collaborate with your team</li>
                    </ul>
                    <p style="text-align: center;">
                        <a href="{$dashboardUrl}" class="button">Go to Dashboard</a>
                    </p>
                </div>
                <div class="footer">
                    <p>&copy; 2025 CRM Application. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    /**
     * Password changed notification template
     */
    private function getPasswordChangedEmailTemplate(User $user): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .info { background-color: #e3f2fd; border-left: 4px solid #2196F3; padding: 12px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Password Changed</h1>
                </div>
                <div class="content">
                    <p>Hello {$user->getFirstname()},</p>
                    <p>This is to confirm that your password has been successfully changed.</p>
                    <div class="info">
                        <strong>When:</strong> {$this->formatDateTime(new \DateTimeImmutable())}
                    </div>
                    <p>If you didn't make this change, please contact our support team immediately.</p>
                </div>
                <div class="footer">
                    <p>&copy; 2025 CRM Application. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    /**
     * Suspicious activity alert template
     */
    private function getSuspiciousActivityEmailTemplate(User $user, string $ipAddress, string $location): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #f44336; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .alert { background-color: #ffebee; border-left: 4px solid #f44336; padding: 12px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>⚠️ Suspicious Activity Detected</h1>
                </div>
                <div class="content">
                    <p>Hello {$user->getFirstname()},</p>
                    <p>We detected suspicious activity on your account:</p>
                    <div class="alert">
                        <p><strong>Multiple failed login attempts</strong></p>
                        <p><strong>IP Address:</strong> {$ipAddress}</p>
                        <p><strong>Location:</strong> {$location}</p>
                        <p><strong>Time:</strong> {$this->formatDateTime(new \DateTimeImmutable())}</p>
                    </div>
                    <p>If this was you, you can ignore this message. If not, we recommend:</p>
                    <ul>
                        <li>Change your password immediately</li>
                        <li>Review your recent account activity</li>
                        <li>Enable two-factor authentication</li>
                    </ul>
                </div>
                <div class="footer">
                    <p>&copy; 2025 CRM Application. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    /**
     * Format datetime for email display
     */
    private function formatDateTime(\DateTimeInterface $dateTime): string
    {
        return $dateTime->format('F j, Y \a\t g:i A');
    }
}
