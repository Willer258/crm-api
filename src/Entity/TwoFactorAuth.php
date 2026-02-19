<?php

namespace App\Entity;

use App\Repository\TwoFactorAuthRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Configuration 2FA TOTP pour un utilisateur
 *
 * Stocke le secret TOTP et les codes de récupération (hashés).
 * Compatible avec les applications authenticator standard (Google Authenticator, Authy, etc.)
 */
#[ORM\Entity(repositoryClass: TwoFactorAuthRepository::class)]
#[ORM\Table(name: 'two_factor_auth')]
class TwoFactorAuth
{
    private const TOTP_ISSUER = 'CRM Application';
    private const TOTP_DIGITS = 6;
    private const TOTP_PERIOD = 30;
    private const RECOVERY_CODES_COUNT = 10;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'twoFactorAuth')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    /**
     * Secret TOTP encodé en base32 (non encrypté pour la simplicité)
     * En production, envisager le chiffrement avec une clé serveur
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $totpSecret = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isEnabled = false;

    /**
     * Codes de récupération hashés avec bcrypt
     * JSON array de hashes
     */
    #[ORM\Column(type: 'json')]
    private array $recoveryCodes = [];

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $enabledAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastUsedAt = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * Nombre de codes de récupération restants (non persisté)
     */
    private ?int $recoveryCodesRemaining = null;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTotpSecret(): ?string
    {
        return $this->totpSecret;
    }

    public function setTotpSecret(?string $totpSecret): self
    {
        $this->totpSecret = $totpSecret;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function setIsEnabled(bool $isEnabled): self
    {
        $this->isEnabled = $isEnabled;
        if ($isEnabled && !$this->enabledAt) {
            $this->enabledAt = new \DateTime();
        }
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getEnabledAt(): ?\DateTimeInterface
    {
        return $this->enabledAt;
    }

    public function getLastUsedAt(): ?\DateTimeInterface
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(?\DateTimeInterface $lastUsedAt): self
    {
        $this->lastUsedAt = $lastUsedAt;
        return $this;
    }

    /**
     * Génère un nouveau secret TOTP (base32, 160 bits)
     */
    public function generateSecret(): string
    {
        $secret = random_bytes(20); // 160 bits
        $this->totpSecret = $this->base32Encode($secret);
        $this->updatedAt = new \DateTime();
        return $this->totpSecret;
    }

    /**
     * Génère des codes de récupération
     *
     * @return string[] Les codes en clair (à afficher une seule fois à l'utilisateur)
     */
    public function generateRecoveryCodes(int $count = self::RECOVERY_CODES_COUNT): array
    {
        $plainCodes = [];
        $hashedCodes = [];

        for ($i = 0; $i < $count; $i++) {
            // Format: XXXX-XXXX (8 caractères alphanumériques)
            $code = strtoupper(bin2hex(random_bytes(4)));
            $code = substr($code, 0, 4) . '-' . substr($code, 4, 4);
            $plainCodes[] = $code;
            // Hash avec bcrypt pour le stockage sécurisé
            $hashedCodes[] = password_hash($code, PASSWORD_BCRYPT);
        }

        $this->recoveryCodes = $hashedCodes;
        $this->recoveryCodesRemaining = $count;
        $this->updatedAt = new \DateTime();

        return $plainCodes;
    }

    /**
     * Vérifie et utilise un code de récupération
     *
     * @return bool True si le code est valide et a été utilisé
     */
    public function useRecoveryCode(string $code): bool
    {
        // Normalise le code (supprime les tirets, met en majuscules)
        $code = strtoupper(str_replace('-', '', $code));
        if (\strlen($code) === 8) {
            $code = substr($code, 0, 4) . '-' . substr($code, 4, 4);
        }

        foreach ($this->recoveryCodes as $index => $hashedCode) {
            if (password_verify($code, $hashedCode)) {
                // Supprime le code utilisé
                unset($this->recoveryCodes[$index]);
                $this->recoveryCodes = array_values($this->recoveryCodes);
                $this->updatedAt = new \DateTime();
                return true;
            }
        }

        return false;
    }

    /**
     * Retourne le nombre de codes de récupération restants
     */
    public function getRecoveryCodesCount(): int
    {
        return \count($this->recoveryCodes);
    }

    /**
     * Vérifie un code TOTP
     *
     * @param string $code Le code à 6 chiffres
     * @param int $window Le nombre de périodes à accepter avant/après (défaut: 1)
     */
    public function verifyTotpCode(string $code, int $window = 1): bool
    {
        if (!$this->totpSecret) {
            return false;
        }

        $timestamp = time();
        $period = self::TOTP_PERIOD;

        // Vérifie le code pour la période actuelle et les périodes adjacentes
        for ($i = -$window; $i <= $window; $i++) {
            $expectedCode = $this->generateTotpCode($timestamp + ($i * $period));
            if (hash_equals($expectedCode, $code)) {
                $this->lastUsedAt = new \DateTime();
                return true;
            }
        }

        return false;
    }

    /**
     * Génère le code TOTP pour un timestamp donné
     */
    private function generateTotpCode(int $timestamp): string
    {
        $secret = $this->base32Decode($this->totpSecret);
        $counter = (int) floor($timestamp / self::TOTP_PERIOD);

        // Convertit le compteur en bytes (big-endian 64-bit)
        $counterBytes = pack('J', $counter);

        // Calcule le HMAC-SHA1
        $hash = hash_hmac('sha1', $counterBytes, $secret, true);

        // Extrait le code dynamique (RFC 6238)
        $offset = \ord($hash[\strlen($hash) - 1]) & 0x0F;
        $binary =
            ((\ord($hash[$offset]) & 0x7F) << 24) |
            ((\ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((\ord($hash[$offset + 2]) & 0xFF) << 8) |
            (\ord($hash[$offset + 3]) & 0xFF);

        // Modulo pour obtenir le code à N chiffres
        $otp = $binary % (10 ** self::TOTP_DIGITS);

        return str_pad((string) $otp, self::TOTP_DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Génère l'URL pour le QR code (format otpauth://)
     */
    public function getQrCodeUrl(string $userEmail): string
    {
        if (!$this->totpSecret) {
            throw new \RuntimeException('TOTP secret not generated');
        }

        $params = [
            'secret' => $this->totpSecret,
            'issuer' => self::TOTP_ISSUER,
            'algorithm' => 'SHA1',
            'digits' => self::TOTP_DIGITS,
            'period' => self::TOTP_PERIOD,
        ];

        return sprintf(
            'otpauth://totp/%s:%s?%s',
            rawurlencode(self::TOTP_ISSUER),
            rawurlencode($userEmail),
            http_build_query($params)
        );
    }

    /**
     * Désactive le 2FA et nettoie les données
     */
    public function disable(): self
    {
        $this->isEnabled = false;
        $this->totpSecret = null;
        $this->recoveryCodes = [];
        $this->enabledAt = null;
        $this->updatedAt = new \DateTime();

        return $this;
    }

    /**
     * Encode en base32 (RFC 4648)
     */
    private function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $encoded = '';
        $buffer = 0;
        $bitsRemaining = 0;

        for ($i = 0; $i < \strlen($data); $i++) {
            $buffer = ($buffer << 8) | \ord($data[$i]);
            $bitsRemaining += 8;

            while ($bitsRemaining >= 5) {
                $bitsRemaining -= 5;
                $encoded .= $alphabet[($buffer >> $bitsRemaining) & 0x1F];
            }
        }

        if ($bitsRemaining > 0) {
            $encoded .= $alphabet[($buffer << (5 - $bitsRemaining)) & 0x1F];
        }

        return $encoded;
    }

    /**
     * Décode depuis base32 (RFC 4648)
     */
    private function base32Decode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $data = strtoupper($data);
        $decoded = '';
        $buffer = 0;
        $bitsRemaining = 0;

        for ($i = 0; $i < \strlen($data); $i++) {
            $char = $data[$i];
            if ($char === '=' || $char === ' ') {
                continue;
            }

            $value = strpos($alphabet, $char);
            if ($value === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $value;
            $bitsRemaining += 5;

            if ($bitsRemaining >= 8) {
                $bitsRemaining -= 8;
                $decoded .= \chr(($buffer >> $bitsRemaining) & 0xFF);
            }
        }

        return $decoded;
    }
}
