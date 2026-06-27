<?php
/* GrooveVault — transactional email via SMTP (cPanel mailbox).
   ---------------------------------------------------------------------------
   Emails are sent by authenticating against the client's own mailbox over SMTP.
   Configure the 6 SMTP_* / MAIL_FROM_* values below (or via environment vars).

     SMTP_HOST   → outgoing mail server. On cPanel this is normally
                   "mail.<your-domain>" (see cPanel → Email Accounts →
                   Connect Devices → "Outgoing Server"). Adjust if different.
     SMTP_PORT   → 465 with SMTP_SECURE 'ssl'  (recommended), or
                   587 with SMTP_SECURE 'tls'  (STARTTLS).
     SMTP_USER   → full email address used to log in.
     SMTP_PASS   → that mailbox's password.
     MAIL_FROM_* → the "From" shown to recipients. MAIL_FROM_EMAIL should match
                   SMTP_USER, otherwise the server may reject the message.
*/

if (!defined('SMTP_HOST'))   define('SMTP_HOST',   getenv('SMTP_HOST')   ?: 'mail.groove-vault.com');
if (!defined('SMTP_PORT'))   define('SMTP_PORT',   getenv('SMTP_PORT')   ?: '465');
if (!defined('SMTP_SECURE')) define('SMTP_SECURE', getenv('SMTP_SECURE') ?: 'ssl');   // 'ssl' | 'tls' | ''
if (!defined('SMTP_USER'))   define('SMTP_USER',   getenv('SMTP_USER')   ?: 'support@groove-vault.com');
if (!defined('SMTP_PASS'))   define('SMTP_PASS',   getenv('SMTP_PASS')   ?: 'DZfw37ZYM12Z4');

if (!defined('MAIL_FROM_EMAIL')) define('MAIL_FROM_EMAIL', getenv('MAIL_FROM_EMAIL') ?: 'support@groove-vault.com');
if (!defined('MAIL_FROM_NAME'))  define('MAIL_FROM_NAME',  getenv('MAIL_FROM_NAME')  ?: 'GrooveVault');

/** True once SMTP host, credentials and a valid sender are configured. */
function mail_configured(): bool {
    return SMTP_HOST !== '' && SMTP_USER !== '' && SMTP_PASS !== ''
        && MAIL_FROM_EMAIL !== '' && filter_var(MAIL_FROM_EMAIL, FILTER_VALIDATE_EMAIL);
}

/** Build an absolute URL for links inside emails. */
function mail_absolute_url(string $path): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    $path   = ltrim(str_replace('\\', '/', $path), '/');
    return $scheme . '://' . $host . ($dir !== '' ? $dir . '/' : '/') . $path;
}

/* -------------------------------------------------------------------------
   Minimal SMTP client (no external dependencies)
   ------------------------------------------------------------------------- */

/** Read one full SMTP reply (handles multi-line "250-..." continuations). */
function smtp__read($fp): string {
    $data = '';
    while (($line = fgets($fp, 1024)) !== false) {
        $data .= $line;
        // A reply line is "<code><space>..." on the LAST line, "<code>-..." before.
        if (strlen($line) < 4 || $line[3] === ' ') break;
        $info = stream_get_meta_data($fp);
        if (!empty($info['timed_out'])) break;
    }
    return $data;
}

/** Send a command and return the server's reply. */
function smtp__cmd($fp, string $cmd): string {
    fwrite($fp, $cmd . "\r\n");
    return smtp__read($fp);
}

/** First 3 digits of an SMTP reply as an int. */
function smtp__code(string $reply): int {
    return (int)substr($reply, 0, 3);
}

/** RFC 2047 encode a header value only when it contains non-ASCII bytes. */
function smtp__encode_header(string $value): string {
    return preg_match('/[\x80-\xFF]/', $value)
        ? '=?UTF-8?B?' . base64_encode($value) . '?='
        : $value;
}

/** Assemble a MIME multipart/alternative message (plain + HTML). */
function smtp__build_message(string $toEmail, ?string $toName, string $subject, string $html, ?string $text): string {
    $boundary = 'gvb_' . bin2hex(random_bytes(8));
    $domain   = substr(strrchr(MAIL_FROM_EMAIL, '@') ?: '@localhost', 1);

    if ($text === null || $text === '') {
        $text = trim(html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8'));
    }

    $fromHdr = smtp__encode_header(MAIL_FROM_NAME) . ' <' . MAIL_FROM_EMAIL . '>';
    $toHdr   = $toName ? smtp__encode_header($toName) . ' <' . $toEmail . '>' : $toEmail;

    $headers = [
        'Date: ' . date('r'),
        'From: ' . $fromHdr,
        'To: ' . $toHdr,
        'Subject: ' . smtp__encode_header($subject),
        'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $domain . '>',
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
    ];

    $body  = '--' . $boundary . "\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $text . "\r\n\r\n";
    $body .= '--' . $boundary . "\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $html . "\r\n\r\n";
    $body .= '--' . $boundary . "--\r\n";

    return implode("\r\n", $headers) . "\r\n\r\n" . $body;
}

/** Open an authenticated SMTP session. Returns the stream or an error array. */
function smtp__connect_auth() {
    if (!extension_loaded('openssl') && (SMTP_SECURE === 'ssl' || SMTP_SECURE === 'tls')) {
        return ['error' => 'PHP openssl extension is required for secure SMTP. Enable it in php.ini.'];
    }

    $remote = (SMTP_SECURE === 'ssl' ? 'ssl://' : 'tcp://') . SMTP_HOST . ':' . (int)SMTP_PORT;
    $ctx = stream_context_create(['ssl' => [
        'verify_peer'       => false,   // shared hosting often uses a generic cert
        'verify_peer_name'  => false,
        'allow_self_signed' => true,
    ]]);

    $fp = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) {
        return ['error' => 'Cannot reach SMTP server ' . SMTP_HOST . ':' . (int)SMTP_PORT . ' — ' . ($errstr ?: 'connection failed') . ' (' . $errno . ')'];
    }
    stream_set_timeout($fp, 20);

    $reply = smtp__read($fp);
    if (smtp__code($reply) !== 220) { fclose($fp); return ['error' => 'Unexpected SMTP greeting: ' . trim($reply)]; }

    $ehloHost = preg_replace('/[^A-Za-z0-9.\-]/', '', explode(':', $_SERVER['HTTP_HOST'] ?? 'localhost')[0]) ?: 'localhost';

    $reply = smtp__cmd($fp, 'EHLO ' . $ehloHost);
    if (smtp__code($reply) !== 250) { fclose($fp); return ['error' => 'EHLO failed: ' . trim($reply)]; }

    if (SMTP_SECURE === 'tls') {
        $reply = smtp__cmd($fp, 'STARTTLS');
        if (smtp__code($reply) !== 220) { fclose($fp); return ['error' => 'STARTTLS failed: ' . trim($reply)]; }
        $crypto = STREAM_CRYPTO_METHOD_TLS_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
            $crypto |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
        }
        if (!@stream_socket_enable_crypto($fp, true, $crypto)) { fclose($fp); return ['error' => 'Unable to start TLS encryption.']; }
        $reply = smtp__cmd($fp, 'EHLO ' . $ehloHost);   // re-introduce over the encrypted channel
        if (smtp__code($reply) !== 250) { fclose($fp); return ['error' => 'EHLO after STARTTLS failed: ' . trim($reply)]; }
    }

    if (smtp__code(smtp__cmd($fp, 'AUTH LOGIN')) !== 334) { fclose($fp); return ['error' => 'Server did not accept AUTH LOGIN.']; }
    if (smtp__code(smtp__cmd($fp, base64_encode(SMTP_USER))) !== 334) { fclose($fp); return ['error' => 'SMTP username was rejected.']; }
    if (smtp__code(smtp__cmd($fp, base64_encode(SMTP_PASS))) !== 235) { fclose($fp); return ['error' => 'SMTP login failed — check the mailbox username/password.']; }

    return $fp;
}

/**
 * Connect + authenticate only (no message sent). Useful for a config test.
 * @return array{ok: bool, error: ?string}
 */
function mail_smtp_check(): array {
    if (!mail_configured()) return ['ok' => false, 'error' => 'Email is not configured (SMTP host/user/pass).'];
    $fp = smtp__connect_auth();
    if (is_array($fp)) return ['ok' => false, 'error' => $fp['error']];
    smtp__cmd($fp, 'QUIT');
    fclose($fp);
    return ['ok' => true, 'error' => null];
}

/**
 * Send a transactional email through the configured SMTP mailbox.
 *
 * @return array{ok: bool, error: ?string}
 */
function send_email(string $toEmail, ?string $toName, string $subject, string $htmlContent, ?string $textContent = null): array {
    if (!mail_configured()) {
        return ['ok' => false, 'error' => 'Email is not configured. Set the SMTP_* values in inc/mail.inc.php.'];
    }
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Invalid recipient email address.'];
    }

    $fp = smtp__connect_auth();
    if (is_array($fp)) return ['ok' => false, 'error' => $fp['error']];

    $fail = function (string $msg) use ($fp): array { @fclose($fp); return ['ok' => false, 'error' => $msg]; };

    if (smtp__code(smtp__cmd($fp, 'MAIL FROM:<' . MAIL_FROM_EMAIL . '>')) !== 250) return $fail('MAIL FROM rejected by server.');
    $rcptCode = smtp__code(smtp__cmd($fp, 'RCPT TO:<' . $toEmail . '>'));
    if ($rcptCode !== 250 && $rcptCode !== 251) return $fail('Recipient address rejected by server.');
    if (smtp__code(smtp__cmd($fp, 'DATA')) !== 354) return $fail('Server refused the DATA command.');

    $message = smtp__build_message($toEmail, $toName, $subject, $htmlContent, $textContent);
    $message = preg_replace('/(?<!\r)\n/', "\r\n", $message);   // normalise line endings to CRLF
    $message = preg_replace('/^\./m', '..', $message);          // dot-stuff lines starting with '.'
    fwrite($fp, $message . "\r\n.\r\n");

    if (smtp__code(smtp__read($fp)) !== 250) return $fail('Server did not accept the message.');

    smtp__cmd($fp, 'QUIT');
    @fclose($fp);
    return ['ok' => true, 'error' => null];
}

/**
 * Send a password-reset email.
 *
 * @return array{ok: bool, error: ?string}
 */
function send_password_reset_email(string $toEmail, ?string $displayName, string $resetUrl): array {
    $name = $displayName ?: 'there';
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeUrl  = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
    $brand    = MAIL_FROM_NAME;

    $html = '
<!DOCTYPE html>
<html>
<body style="background:#0d0d12;margin:0;padding:30px;font-family:Arial,sans-serif;">
  <div style="max-width:480px;margin:auto;background:#18181f;border-radius:16px;padding:32px;text-align:center;">
    <div style="font-size:3rem;margin-bottom:16px;">🔐</div>
    <h2 style="color:#ffffff;margin:0 0 8px;">Reset Your Password</h2>
    <p style="color:#a0a0b0;font-size:0.95rem;margin:0 0 24px;">
      Hi <strong style="color:#ffffff;">' . $safeName . '</strong>,<br>
      We received a request to reset your GrooveVault password. Click the button below. This link expires in 1 hour.
    </p>
    <a href="' . $safeUrl . '"
       style="display:inline-block;background:linear-gradient(135deg,#FF2D78,#FF6B00);
              color:#fff;text-decoration:none;padding:14px 36px;border-radius:50px;
              font-weight:700;font-size:1rem;letter-spacing:.5px;">
      Reset My Password
    </a>
    <p style="color:#606070;font-size:0.78rem;margin-top:24px;">
      If the button does not work, copy this link into your browser:<br>
      <span style="color:#a0a0b0;word-break:break-all;">' . $safeUrl . '</span>
    </p>
    <p style="color:#606070;font-size:0.78rem;margin-top:16px;">
      If you did not request this, you can safely ignore this email.
    </p>
    <p style="color:#505060;font-size:0.72rem;margin-top:20px;margin-bottom:0;">— ' . htmlspecialchars($brand, ENT_QUOTES, 'UTF-8') . '</p>
  </div>
</body>
</html>';

    $text = "Hi $name,\n\nReset your GrooveVault password using this link (expires in 1 hour):\n$resetUrl\n\nIf you did not request this, ignore this email.\n\n— $brand";

    return send_email($toEmail, $displayName, 'Reset your GrooveVault password', $html, $text);
}
