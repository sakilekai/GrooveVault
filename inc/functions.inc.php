<?php
/* GrooveVault — application bootstrap & shared helpers.
   require_once this at the very TOP of every page (before any output):
       require_once __DIR__ . '/inc/functions.inc.php';      // from project root
       require_once __DIR__ . '/../inc/functions.inc.php';   // from /admin
   It starts the session and exposes the DB ($pdo) plus helper functions. */

require_once __DIR__ . '/db.inc.php';   // defines $pdo (PDO, throws on error)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* -------------------------------------------------------------------------
   Core helpers
   ------------------------------------------------------------------------- */
function db(): PDO {
    global $pdo;
    return $pdo;
}

/** HTML-escape shorthand. */
function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** Redirect and stop. */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/* -------------------------------------------------------------------------
   CSRF protection
   ------------------------------------------------------------------------- */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Verify the CSRF token on a POST; dies on mismatch. */
function verify_csrf(): void {
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Session expired or invalid request token. Go back and try again.');
    }
}

/* -------------------------------------------------------------------------
   Flash messages (one-shot notices across a redirect)
   ------------------------------------------------------------------------- */
function flash_set(string $type, string $msg): void {
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

/** Returns and clears all queued flash messages. */
function flash_get(): array {
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/** Renders queued flashes as GrooveVault alert markup. */
function flash_render(): string {
    $out = '';
    foreach (flash_get() as $f) {
        $cls = $f['type'] === 'error'   ? 'gv-alert-danger'
             : ($f['type'] === 'success' ? 'gv-alert-success'
             : 'gv-alert-info');
        $out .= '<div class="gv-alert ' . $cls . ' mb-3">' . e($f['msg']) . '</div>';
    }
    return $out;
}

/* -------------------------------------------------------------------------
   User authentication
   ------------------------------------------------------------------------- */
/** Returns the logged-in user row (with plan joined), or null. Cached per request. */
function current_user(): ?array {
    static $cache = null;
    if ($cache !== null) {
        return $cache ?: null;
    }
    if (empty($_SESSION['user_id'])) {
        $cache = false;
        return null;
    }
    $stmt = db()->prepare(
        'SELECT u.*, p.code AS plan_code, p.name AS plan_name, p.price AS plan_price,
                p.billing_period AS plan_billing, p.channel_limit AS plan_channel_limit
         FROM users u
         LEFT JOIN plans p ON p.id = u.plan_id
         WHERE u.id = ?'
    );
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user || $user['status'] === 'suspended') {
        // Account vanished or got suspended mid-session: force logout.
        session_unset();
        $cache = false;
        return null;
    }
    $cache = $user;
    return $user;
}

function require_login(): void {
    if (!current_user()) {
        flash_set('error', 'Please log in to continue.');
        redirect('login.php');
    }
}

/* -------------------------------------------------------------------------
   Admin authentication
   ------------------------------------------------------------------------- */
function current_admin(): ?array {
    static $cache = null;
    if ($cache !== null) {
        return $cache ?: null;
    }
    if (empty($_SESSION['admin_id'])) {
        $cache = false;
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM admins WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();
    $cache = $admin ?: false;
    return $admin ?: null;
}

/** Guard for pages inside /admin (call before any output). */
function require_admin(): void {
    if (!current_admin()) {
        redirect('login.php');   // admin/login.php (relative to /admin pages)
    }
}

/* -------------------------------------------------------------------------
   Settings (platform controls, key/value)
   ------------------------------------------------------------------------- */
function setting(string $key, $default = null) {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->query('SELECT setting_key, setting_value FROM settings') as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $cache[$key] ?? $default;
}

function set_setting(string $key, string $value): void {
    $stmt = db()->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $stmt->execute([$key, $value]);
}

/* -------------------------------------------------------------------------
   Activity log (admin audit trail)
   ------------------------------------------------------------------------- */
function gv_log(string $action, string $detail = '', ?int $adminId = null): void {
    if ($adminId === null) {
        $a = current_admin();
        $adminId = $a ? (int)$a['id'] : null;
    }
    $stmt = db()->prepare('INSERT INTO activity_log (admin_id, action, detail) VALUES (?, ?, ?)');
    $stmt->execute([$adminId, $action, $detail]);
}

/* -------------------------------------------------------------------------
   Small view helpers
   ------------------------------------------------------------------------- */
/** Two-letter initials for an avatar bubble. */
function initials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $a = mb_substr($parts[0] ?? '', 0, 1);
    $b = mb_substr($parts[1] ?? ($parts[0] ?? ''), strlen($parts[1] ?? '') ? 0 : 1, 1);
    return mb_strtoupper($a . ($b ?: ''));
}

/** "2h ago" style relative time from a datetime string. */
function time_ago(?string $datetime): string {
    if (!$datetime) return '—';
    $ts = strtotime($datetime);
    $diff = time() - $ts;
    if ($diff < 60)        return 'just now';
    if ($diff < 3600)      return floor($diff / 60) . 'm ago';
    if ($diff < 86400)     return floor($diff / 3600) . 'h ago';
    if ($diff < 604800)    return floor($diff / 86400) . 'd ago';
    if ($diff < 2592000)   return floor($diff / 604800) . 'w ago';
    return date('M j, Y', $ts);
}

/** Seconds -> "3:24". */
function fmt_duration(?int $seconds): string {
    $seconds = (int)$seconds;
    return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
}

/** A short URL-safe token for share links / verification / resets. */
function random_token(int $bytes = 6): string {
    return substr(bin2hex(random_bytes($bytes + 2)), 0, $bytes * 2);
}

/**
 * Turn a known share/song *page* URL into a direct, playable media URL.
 *
 * Some services (e.g. Suno) hand out a link to an HTML page rather than to the
 * audio file itself. A <video>/<audio> element can't play an HTML page, so the
 * track stays silent. Where the direct media URL can be derived from the page
 * URL, rewrite it; otherwise return the URL unchanged.
 */
function normalize_media_url(string $url): string {
    $url = trim($url);
    if ($url === '') return $url;

    // Suno: https://suno.com/song/<uuid>[?...]  ->  https://cdn1.suno.ai/<uuid>.mp3
    if (preg_match('~^https?://(?:www\.)?suno\.(?:com|ai)/song/([0-9a-f-]{36})~i', $url, $m)) {
        return 'https://cdn1.suno.ai/' . strtolower($m[1]) . '.mp3';
    }

    return $url;
}

/**
 * If $url is a Spotify link (track/album/playlist/episode/show), return its
 * canonical Spotify URI (e.g. "spotify:track:<id>"); otherwise null.
 *
 * Spotify can't be played from a raw media URL — it has to go through Spotify's
 * embed/IFrame player, which is driven by this URI. Accepts both the web URL
 * (open.spotify.com/...) and the native "spotify:..." URI form.
 */
function spotify_uri(string $url): ?string {
    $url = trim($url);
    if ($url === '') return null;

    if (preg_match('~^spotify:(track|album|playlist|episode|show):([A-Za-z0-9]+)~', $url, $m)) {
        return 'spotify:' . $m[1] . ':' . $m[2];
    }
    // https://open.spotify.com/[intl-xx/]track/<id>[?...]
    if (preg_match('~^https?://open\.spotify\.com/(?:intl-[a-z]{2}/)?(track|album|playlist|episode|show)/([A-Za-z0-9]+)~i', $url, $m)) {
        return 'spotify:' . strtolower($m[1]) . ':' . $m[2];
    }
    return null;
}

/**
 * If $url is a YouTube link, return its 11-char video id; otherwise null.
 * Handles watch?v=, youtu.be/, shorts/, embed/, live/ and the music/mobile hosts.
 */
function youtube_id(string $url): ?string {
    $url = trim($url);
    if ($url === '') return null;
    if (preg_match('~^https?://youtu\.be/([A-Za-z0-9_-]{11})~i', $url, $m)) return $m[1];
    if (preg_match('~^https?://(?:www\.|m\.|music\.)?youtube\.com/watch\?(?:[^#]*&)?v=([A-Za-z0-9_-]{11})~i', $url, $m)) return $m[1];
    if (preg_match('~^https?://(?:www\.|m\.)?youtube\.com/(?:shorts|embed|v|live)/([A-Za-z0-9_-]{11})~i', $url, $m)) return $m[1];
    return null;
}

/**
 * If $url is a SoundCloud track/set link, return the URL (the widget loads it
 * directly); otherwise null. Includes on.soundcloud.com short links.
 */
function soundcloud_url(string $url): ?string {
    $url = trim($url);
    if ($url === '') return null;
    if (preg_match('~^https?://(?:www\.|m\.)?soundcloud\.com/[A-Za-z0-9_\-]+/.+~i', $url)) return $url;
    if (preg_match('~^https?://on\.soundcloud\.com/[A-Za-z0-9]+~i', $url)) return $url;
    return null;
}

/**
 * Resolve a track row into how the front-end should play it.
 * Returns ['kind' => 'file'|'spotify'|'youtube'|'soundcloud', 'ref' => provider
 * reference (spotify uri / youtube id / soundcloud url), 'src' => direct media URL].
 * Admin-removed tracks resolve to an empty, non-playable 'file' entry.
 */
function track_media(array $t): array {
    if ((int)($t['removed_by_admin'] ?? 0) === 1) return ['kind' => 'file', 'ref' => '', 'src' => ''];
    if (($t['source_type'] ?? '') === 'upload')   return ['kind' => 'file', 'ref' => '', 'src' => (string)($t['file_path'] ?? '')];

    $url = (string)($t['source_url'] ?? '');
    if ($u = spotify_uri($url))    return ['kind' => 'spotify',    'ref' => $u, 'src' => ''];
    if ($v = youtube_id($url))     return ['kind' => 'youtube',    'ref' => $v, 'src' => ''];
    if ($s = soundcloud_url($url)) return ['kind' => 'soundcloud', 'ref' => $s, 'src' => ''];
    return ['kind' => 'file', 'ref' => '', 'src' => normalize_media_url($url)];
}

/** Preset channel gradients (value stored in channels.bg_color). */
function gv_gradients(): array {
    return [
        'linear-gradient(135deg,#FF2D78,#FF6B00)',
        'linear-gradient(135deg,#00D4FF,#0077FF)',
        'linear-gradient(135deg,#39FF14,#00A800)',
        'linear-gradient(135deg,#FFE600,#FF6B00)',
        'linear-gradient(135deg,#BF00FF,#FF2D78)',
        'linear-gradient(135deg,#00FFD1,#0077FF)',
        'linear-gradient(135deg,#7B2FF7,#00D4FF)',
    ];
}

/** Channels owned by a user. */
function user_channel_count(int $userId): int {
    $stmt = db()->prepare('SELECT COUNT(*) FROM channels WHERE user_id = ?');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

/** Max channels a user may create given their plan (PHP_INT_MAX = unlimited). */
function user_channel_limit(array $user): int {
    if (empty($user['plan_id'])) {
        // No plan yet — fall back to the Starter cap from settings.
        return (int)setting('starter_channel_limit', 5);
    }
    if ($user['plan_channel_limit'] === null) {
        return PHP_INT_MAX;   // unlimited (null channel_limit)
    }
    return (int)$user['plan_channel_limit'];
}

/** Max channels allowed by a given plan row (PHP_INT_MAX = unlimited). */
function plan_channel_limit(?array $plan): int {
    if (!$plan) {
        return (int)setting('starter_channel_limit', 5);
    }
    if (!array_key_exists('channel_limit', $plan) || $plan['channel_limit'] === null) {
        return PHP_INT_MAX;
    }
    return (int)$plan['channel_limit'];
}

/**
 * Block switching to a plan whose channel limit is below the user's current
 * channel count. Returns an error string to show the user, or null if allowed.
 */
function plan_downgrade_block(int $userId, ?array $plan): ?string {
    $limit = plan_channel_limit($plan);
    if ($limit === PHP_INT_MAX) {
        return null;   // unlimited target — always fine
    }
    $have = user_channel_count($userId);
    if ($have <= $limit) {
        return null;
    }
    $excess = $have - $limit;
    $planName = $plan['name'] ?? 'this plan';
    return 'Your account has ' . $have . ' channels, but the ' . $planName
         . ' plan allows only ' . $limit . '. Please delete ' . $excess . ' channel'
         . ($excess === 1 ? '' : 's') . ' before switching to this plan.';
}

/** The user's current active subscription row, or null. */
function active_subscription(int $userId): ?array {
    $stmt = db()->prepare(
        "SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active'
         ORDER BY started_at DESC, id DESC LIMIT 1"
    );
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

/**
 * If the user's paid subscription has lapsed, mark it 'expired' and clear their
 * plan so they're sent back to checkout. Returns the lapsed billing period
 * ('monthly' | 'annual') for the notice, or null if still valid / never paid.
 */
function enforce_subscription(array $user): ?string {
    if (empty($user['plan_id'])) return null;
    $sub = active_subscription((int)$user['id']);
    if (!$sub) return null;                        // plan set without a tracked sub (e.g. admin-granted) — leave alone
    if (empty($sub['expires_at'])) return null;    // legacy row with no expiry — leave alone
    if (strtotime($sub['expires_at']) > time()) return null;   // still valid

    db()->prepare("UPDATE subscriptions SET status = 'expired' WHERE id = ?")->execute([(int)$sub['id']]);
    db()->prepare('UPDATE users SET plan_id = NULL WHERE id = ?')->execute([(int)$user['id']]);
    return $sub['billing_period'] === 'annual' ? 'annual' : 'monthly';
}

/**
 * Gate channel features behind a valid subscription. Call right after
 * require_login(). Redirects to the plan picker (with an "expired" notice when
 * applicable) if the subscription has lapsed or the user has no plan yet.
 */
function require_active_plan(): void {
    $user = current_user();
    if (!$user) redirect('login.php');
    $expired = enforce_subscription($user);
    if ($expired !== null) {
        $_SESSION['plan_expired'] = $expired;
        redirect('pick_plan.php');
    }
    if (empty($user['plan_id'])) {
        redirect('pick_plan.php');
    }
}

/** Number of tracks in a channel. */
function channel_track_count(int $channelId): int {
    $stmt = db()->prepare('SELECT COUNT(*) FROM tracks WHERE channel_id = ?');
    $stmt->execute([$channelId]);
    return (int)$stmt->fetchColumn();
}

/** Load a channel owned by the given user, or null. */
function owned_channel(int $channelId, int $userId): ?array {
    $stmt = db()->prepare('SELECT * FROM channels WHERE id = ? AND user_id = ?');
    $stmt->execute([$channelId, $userId]);
    return $stmt->fetch() ?: null;
}

/** Total duration (seconds) of all tracks in a channel. */
function channel_total_seconds(int $channelId): int {
    $stmt = db()->prepare('SELECT COALESCE(SUM(duration_seconds),0) FROM tracks WHERE channel_id = ?');
    $stmt->execute([$channelId]);
    return (int)$stmt->fetchColumn();
}

/* -------------------------------------------------------------------------
   Admin-side flash rendering (admin panel uses its own styling)
   ------------------------------------------------------------------------- */
function gv_admin_flash(): string {
    $out = '';
    foreach (flash_get() as $f) {
        $color = $f['type'] === 'error'   ? 'var(--accent-pink)'
               : ($f['type'] === 'success' ? 'var(--accent-green)'
               : 'var(--accent-blue)');
        $bg = $f['type'] === 'error'   ? 'rgba(245,69,107,0.1)'
            : ($f['type'] === 'success' ? 'rgba(34,215,138,0.1)'
            : 'rgba(56,189,248,0.1)');
        $out .= '<div style="background:' . $bg . ';border:1px solid ' . $color
              . ';color:' . $color . ';border-radius:10px;padding:.7rem 1rem;'
              . 'font-size:.85rem;margin-bottom:1rem;">' . e($f['msg']) . '</div>';
    }
    return $out;
}
