<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

load_env(__DIR__ . '/../.env');

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

function load_mailer_dependencies(): void
{
    static $loaded = false;

    if ($loaded) {
        return;
    }

    $composerAutoloadPath = __DIR__ . '/../vendor/autoload.php';
    if (!is_file($composerAutoloadPath)) {
        throw new RuntimeException('Composer dependencies are missing. Run "composer require phpmailer/phpmailer".');
    }

    require_once $composerAutoloadPath;
    $loaded = true;
}

function mailer_env(string $key, string $default = ''): string
{
    $value = $_ENV[$key] ?? getenv($key);

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return (string) $value;
}

function otp_mail_palette(): array
{
    $theme = strtolower(mailer_env('MAIL_THEME', 'light'));

    if ($theme === 'dark') {
        return [
            'page_top' => '#13170f',
            'page_bottom' => '#090b06',
            'banner' => '#42493a',
            'surface' => '#151b12',
            'card_bg' => '#20291a',
            'text_main' => '#f3f9e7',
            'text_muted' => '#d2dec0',
            'accent' => '#87f414',
            'otp' => '#87f414',
            'border' => 'rgba(135, 244, 20, 0.35)',
        ];
    }

    return [
        'page_top' => '#eaf4ef',
        'page_bottom' => '#d3e7dd',
        'banner' => '#4bddd0',
        'surface' => '#f3f8f6',
        'card_bg' => '#ffffff',
        'text_main' => '#0f1815',
        'text_muted' => '#394842',
        'accent' => '#428368',
        'otp' => '#428368',
        'border' => 'rgba(66, 131, 104, 0.24)',
    ];
}

function otp_mail_html(string $recipientName, string $otpCode, string $brandName, int $otpTtlSeconds): string
{
    $palette = otp_mail_palette();

    $safeName = htmlspecialchars(trim($recipientName) !== '' ? trim($recipientName) : 'there', ENT_QUOTES, 'UTF-8');
    $safeBrand = htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8');
    $safeDate = htmlspecialchars(date('d M, Y'), ENT_QUOTES, 'UTF-8');
    $safeOtp = htmlspecialchars(implode(' ', str_split($otpCode)), ENT_QUOTES, 'UTF-8');

    $minutes = max(1, (int) ceil($otpTtlSeconds / 60));
    $expiresText = htmlspecialchars($minutes . ' minute' . ($minutes > 1 ? 's' : ''), ENT_QUOTES, 'UTF-8');

    return <<<HTML
<!doctype html>
<html lang="en">
  <body style="margin:0;padding:0;background:{$palette['surface']};font-family:'Manrope',Segoe UI,Arial,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;background:{$palette['surface']};">
      <tr>
        <td align="center" style="padding:22px 14px;">
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;border-collapse:separate;border-spacing:0;border-radius:24px;overflow:hidden;background:linear-gradient(160deg, {$palette['page_top']} 0%, {$palette['page_bottom']} 100%);">
            <tr>
              <td style="padding:24px 30px 84px;background:radial-gradient(circle at 82% 10%, rgba(255,255,255,0.28), transparent 44%), linear-gradient(150deg, {$palette['banner']} 0%, {$palette['accent']} 100%);">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                  <tr>
                    <td style="font-size:24px;font-weight:800;letter-spacing:0.05em;color:#ffffff;">{$safeBrand}</td>
                    <td align="right" style="font-size:14px;font-weight:600;color:rgba(255,255,255,0.92);">{$safeDate}</td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr>
              <td style="padding:0 22px 24px;background:{$palette['surface']};">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:-58px;border-collapse:separate;border-spacing:0;border-radius:26px;border:1px solid {$palette['border']};background:{$palette['card_bg']};box-shadow:0 22px 42px rgba(0,0,0,0.16);">
                  <tr>
                    <td align="center" style="padding:36px 28px 34px;">
                      <p style="margin:0;font-family:'Sora',Segoe UI,Arial,sans-serif;font-size:33px;font-weight:800;color:{$palette['text_main']};">Your OTP</p>
                      <p style="margin:20px 0 0;font-size:22px;font-weight:700;color:{$palette['text_main']};">Hey {$safeName},</p>
                      <p style="margin:14px auto 0;max-width:460px;font-size:17px;line-height:1.65;color:{$palette['text_muted']};">
                        Use this one-time password to continue your login. This OTP is valid for <strong style="color:{$palette['accent']};">{$expiresText}</strong>. Please do not share this code with anyone.
                      </p>
                      <p style="margin:30px 0 0;font-family:'Sora',Segoe UI,Arial,sans-serif;font-size:40px;font-weight:800;letter-spacing:0.32em;color:{$palette['otp']};">{$safeOtp}</p>
                      <p style="margin:16px 0 0;font-size:14px;line-height:1.5;color:{$palette['text_muted']};">If you did not try to sign in, you can safely ignore this email.</p>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
HTML;
}

function send_otp_via_brevo(
    string $apiKey,
    string $fromEmail,
    string $fromName,
    string $recipientEmail,
    string $recipientName,
    string $subject,
    string $htmlMessage,
    string $textMessage
): void {
    $payload = json_encode([
        'sender'      => ['email' => $fromEmail, 'name' => $fromName],
        'to'          => [['email' => $recipientEmail, 'name' => $recipientName !== '' ? $recipientName : $recipientEmail]],
        'subject'     => $subject,
        'htmlContent' => $htmlMessage,
        'textContent' => $textMessage,
    ]);

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'api-key: ' . $apiKey,
        ],
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 10,
    ]);

    $responseBody = curl_exec($ch);
    $httpCode     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError    = curl_error($ch);
    curl_close($ch);

    if ($curlError !== '') {
        throw new RuntimeException('Brevo request failed: ' . $curlError);
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        $decoded = is_string($responseBody) ? json_decode($responseBody, true) : [];
        $detail  = is_array($decoded) && isset($decoded['message']) ? $decoded['message'] : $responseBody;
        throw new RuntimeException('Brevo API error (' . $httpCode . '): ' . $detail);
    }
}

function send_otp_email(string $recipientEmail, string $recipientName, string $otpCode): void
{
    $fromEmail    = mailer_env('MAIL_FROM_ADDRESS');
    $fromName     = mailer_env('MAIL_FROM_NAME', 'Portfolio Security');
    $otpTtlSeconds = 60;
    $minutes      = max(1, (int) ceil($otpTtlSeconds / 60));
    $subject      = 'Your Portfolio OTP Code';
    $textMessage  = "Your one-time password is: {$otpCode}\n\nThis code expires in {$minutes} minute(s).\nIf this was not you, ignore this email.";
    $htmlMessage  = otp_mail_html($recipientName, $otpCode, $fromName, $otpTtlSeconds);

    $brevoApiKey = mailer_env('BREVO_API_KEY');

    if ($brevoApiKey !== '') {
        if ($fromEmail === '') {
            throw new RuntimeException('MAIL_FROM_ADDRESS is missing in .env.');
        }

        send_otp_via_brevo(
            $brevoApiKey,
            $fromEmail,
            $fromName,
            $recipientEmail,
            $recipientName,
            $subject,
            $htmlMessage,
            $textMessage
        );

        return;
    }

    load_mailer_dependencies();

    $smtpHost      = mailer_env('MAIL_HOST', 'smtp.gmail.com');
    $smtpPort      = (int) mailer_env('MAIL_PORT', '587');
    $smtpEncryption = strtolower(mailer_env('MAIL_ENCRYPTION', 'tls'));
    $smtpUser      = mailer_env('MAIL_USERNAME');
    $smtpPass      = mailer_env('MAIL_PASSWORD');

    if ($fromEmail === '') {
        $fromEmail = $smtpUser;
    }

    if ($smtpUser === '' || $smtpPass === '' || $fromEmail === '') {
        throw new RuntimeException('Mailer credentials are missing in .env.');
    }

    $rawMailHost = (string) ($_ENV['MAIL_HOST'] ?? getenv('MAIL_HOST') ?? '');
    $useSMTP     = $rawMailHost !== '';

    $mailer = new PHPMailer(true);

    try {
        $mailer->CharSet = 'UTF-8';

        if ($useSMTP) {
            $mailer->isSMTP();
            $mailer->Host              = $smtpHost;
            $mailer->Port              = $smtpPort;
            $mailer->SMTPAuth          = true;
            $mailer->Username          = $smtpUser;
            $mailer->Password          = $smtpPass;
            $mailer->Timeout = 5;

            if ($smtpEncryption === 'ssl') {
                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
        } else {
            $mailer->isMail();
        }

        $mailer->setFrom($fromEmail, $fromName);
        $mailer->addAddress($recipientEmail, $recipientName !== '' ? $recipientName : $recipientEmail);

        $mailer->isHTML(true);
        $mailer->Subject = $subject;
        $mailer->Body    = $htmlMessage;
        $mailer->AltBody = $textMessage;

        $mailer->send();
    } catch (Exception $exception) {
        throw new RuntimeException('Unable to send OTP email. ' . $exception->getMessage());
    }
}
