<?php

namespace App\Service;

use App\Entity\EmailOtp;
use App\Entity\EmailVerificationToken;
use App\Entity\MagicLink;
use App\Entity\PasswordResetToken;
use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Psr\Log\LoggerInterface;

class EmailService
{
    private const FROM_EMAIL = 'wilfriedhouinlindjonon91@gmail.com';
    private const FROM_NAME = 'CRM Application';

    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger,
        private string $frontendUrl = 'http://localhost:3000', // Should be configured via env
        private string $frontendLocale = 'fr' // Default locale for frontend URLs
    ) {}

    /**
     * Send email verification email
     */
    public function sendVerificationEmail(User $user, EmailVerificationToken $token): void
    {
        try {
            // Generate verification URL with locale (frontend uses email param, not token)
            $verificationUrl = $this->frontendUrl . '/' . $this->frontendLocale . '/verify-email?email=' . urlencode($user->getEmail());

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
            // Generate reset URL with locale
            $resetUrl = $this->frontendUrl . '/' . $this->frontendLocale . '/reset-password?token=' . $token->getToken();

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
        $dashboardUrl = $this->frontendUrl . '/' . $this->frontendLocale . '/dashboard';

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

    /**
     * Envoie un code OTP par email
     */
    public function sendOtpCode(EmailOtp $otp, ?User $user = null): bool
    {
        try {
            $email = (new Email())
                ->from(self::FROM_EMAIL)
                ->to($otp->getEmail())
                ->subject($this->getOtpSubject($otp->getPurpose()))
                ->html($this->getOtpEmailTemplate($otp, $user));

            $this->mailer->send($email);

            $this->logger->info('OTP email sent', [
                'email' => $otp->getEmail(),
                'purpose' => $otp->getPurpose()
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send OTP email', [
                'email' => $otp->getEmail(),
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Retourne le sujet de l'email selon le purpose
     */
    private function getOtpSubject(string $purpose): string
    {
        return match ($purpose) {
            'email_verification' => 'Vérifiez votre adresse email - Code OTP',
            'password_reset' => 'Réinitialisez votre mot de passe - Code OTP',
            'login' => 'Code de connexion',
            default => 'Votre code de vérification',
        };
    }

    /**
     * Génère le template HTML de l'email avec OTP
     */
    private function getOtpEmailTemplate(EmailOtp $otp, ?User $user): string
    {
        $userName = $user ? ($user->getFirstname() ?? 'Utilisateur') : 'Utilisateur';
        $code = $otp->getCode();
        $expiresIn = $this->getOtpExpirationMinutes($otp);

        $purpose = match ($otp->getPurpose()) {
            'email_verification' => 'vérifier votre adresse email',
            'password_reset' => 'réinitialiser votre mot de passe',
            'login' => 'vous connecter',
            default => 'compléter votre action',
        };

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .otp-code {
            background: white;
            border: 2px dashed #667eea;
            padding: 20px;
            text-align: center;
            font-size: 36px;
            font-weight: bold;
            letter-spacing: 10px;
            color: #667eea;
            margin: 20px 0;
            border-radius: 8px;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .footer {
            text-align: center;
            color: #999;
            font-size: 12px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔐 Code de Vérification</h1>
    </div>
    <div class="content">
        <p>Bonjour <strong>{$userName}</strong>,</p>

        <p>Vous avez demandé un code de vérification pour {$purpose}.</p>

        <p>Voici votre code OTP :</p>

        <div class="otp-code">
            {$code}
        </div>

        <div class="warning">
            ⚠️ <strong>Important :</strong> Ce code expire dans <strong>{$expiresIn} minutes</strong>.
            Ne partagez jamais ce code avec qui que ce soit.
        </div>

        <p>Si vous n'avez pas demandé ce code, veuillez ignorer cet email.</p>

        <p>Cordialement,<br>L'équipe CRM API</p>
    </div>
    <div class="footer">
        <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
        <p>&copy; 2025 CRM Application. All rights reserved.</p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Calcule le temps d'expiration en minutes pour OTP
     */
    private function getOtpExpirationMinutes(EmailOtp $otp): int
    {
        $now = new \DateTime();
        $interval = $now->diff($otp->getExpiresAt());
        return $interval->i + ($interval->h * 60);
    }

    /**
     * Send magic link email for passwordless authentication
     */
    public function sendMagicLinkEmail(User $user, MagicLink $magicLink): bool
    {
        try {
            $magicLinkUrl = $magicLink->generateUrl($this->frontendUrl, $this->frontendLocale);

            $email = (new Email())
                ->from(self::FROM_EMAIL)
                ->to($user->getEmail())
                ->subject('Connexion à CRM Application')
                ->html($this->getMagicLinkEmailTemplate($user, $magicLinkUrl, $magicLink->getExpiresAt()));

            $this->mailer->send($email);

            $this->logger->info('Magic link email sent', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail()
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send magic link email', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Magic link email template
     */
    private function getMagicLinkEmailTemplate(User $user, string $magicLinkUrl, \DateTimeInterface $expiresAt): string
    {
        $expiresIn = $expiresAt->diff(new \DateTimeImmutable())->format('%i minutes');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .button {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0;
        }
        .button:hover {
            opacity: 0.9;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .security-info {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 12px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .footer {
            text-align: center;
            color: #999;
            font-size: 12px;
            margin-top: 20px;
        }
        .link-text {
            word-break: break-all;
            color: #666;
            font-size: 12px;
            background: #eee;
            padding: 10px;
            border-radius: 4px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔗 Connexion sans mot de passe</h1>
    </div>
    <div class="content">
        <p>Bonjour <strong>{$user->getFirstname()}</strong>,</p>

        <p>Vous avez demandé à vous connecter à CRM Application sans mot de passe.</p>

        <p>Cliquez sur le bouton ci-dessous pour vous connecter instantanément :</p>

        <p style="text-align: center;">
            <a href="{$magicLinkUrl}" class="button">Se connecter</a>
        </p>

        <div class="warning">
            ⏱️ <strong>Attention :</strong> Ce lien expire dans <strong>{$expiresIn}</strong> et ne peut être utilisé qu'une seule fois.
        </div>

        <div class="security-info">
            🔒 <strong>Sécurité :</strong> Ce lien est personnel et sécurisé. Ne le partagez avec personne.
        </div>

        <p>Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :</p>
        <div class="link-text">{$magicLinkUrl}</div>

        <p>Si vous n'avez pas demandé ce lien de connexion, vous pouvez ignorer cet email en toute sécurité.</p>

        <p>Cordialement,<br>L'équipe CRM Application</p>
    </div>
    <div class="footer">
        <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
        <p>&copy; 2025 CRM Application. All rights reserved.</p>
    </div>
</body>
</html>
HTML;
    }
}
