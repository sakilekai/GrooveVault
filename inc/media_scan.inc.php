<?php
/* GrooveVault — malware / safety checks for track uploads and media links. */

/** Domains that only store a playback link (no file is pulled onto our server). */
function media_scan_trusted_link(string $url): bool {
    $url = trim($url);
    if ($url === '') return false;
    if (spotify_uri($url)) return true;
    if (youtube_id($url)) return true;
    if (soundcloud_url($url)) return true;
    if (preg_match('~^https?://(?:www\.)?suno\.(?:com|ai)/song/~i', $url)) return true;
    if (preg_match('~^https?://cdn\d*\.suno\.ai/~i', $url)) return true;
    return false;
}

/** Blocked executable / script extensions in URLs and filenames. */
function media_scan_blocked_extension(string $name): ?string {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $blocked = [
        'exe', 'msi', 'bat', 'cmd', 'com', 'scr', 'pif', 'dll', 'sys', 'drv',
        'vbs', 'vbe', 'js', 'jse', 'ws', 'wsf', 'wsh', 'ps1', 'psm1', 'psd1',
        'php', 'phtml', 'phar', 'asp', 'aspx', 'jsp', 'cgi', 'pl', 'py', 'rb',
        'hta', 'jar', 'apk', 'dmg', 'pkg', 'deb', 'rpm', 'sh', 'bash', 'zsh',
        'html', 'htm', 'svg', 'xml', 'zip', 'rar', '7z', 'gz', 'tar', 'iso',
    ];
    return in_array($ext, $blocked, true) ? $ext : null;
}

/** Return true when binary content looks like allowed audio/video, not an executable. */
function media_scan_valid_magic(string $head): bool {
    if ($head === '') return false;
    // MP3: ID3 tag or MPEG frame sync.
    if (strncmp($head, 'ID3', 3) === 0) return true;
    if (isset($head[0], $head[1]) && $head[0] === "\xFF" && in_array(ord($head[1]) & 0xE0, [0xE0, 0xF0], true)) return true;
    // MP4 / M4A: "ftyp" at offset 4.
    if (strlen($head) >= 12 && substr($head, 4, 4) === 'ftyp') return true;
    // WAV: RIFF....WAVE
    if (strncmp($head, 'RIFF', 4) === 0 && substr($head, 8, 4) === 'WAVE') return true;
    // OGG
    if (strncmp($head, 'OggS', 4) === 0) return true;
    return false;
}

/** Scan a byte sample for embedded malware / script signatures. */
function media_scan_binary_threat(string $sample): ?string {
    if ($sample === '') return null;

    // Windows PE, ELF, Mach-O, Java classfile.
    if (strncmp($sample, "MZ", 2) === 0) return 'Windows executable (PE) signature';
    if (strncmp($sample, "\x7FELF", 4) === 0) return 'Linux executable (ELF) signature';
    if (strncmp($sample, "\xFE\xED\xFA", 3) === 0 || strncmp($sample, "\xCE\xFA\xED\xFE", 4) === 0) {
        return 'macOS executable signature';
    }
    if (strncmp($sample, "\xCA\xFE\xBA\xBE", 4) === 0) return 'Java bytecode signature';

    $patterns = [
        '/<\?php/i'              => 'embedded PHP code',
        '/<\?=/i'                 => 'embedded PHP short tag',
        '/<script[\s>]/i'         => 'embedded script tag',
        '/\bpowershell\b/i'       => 'PowerShell command',
        '/\bcmd\.exe\b/i'         => 'Windows command shell reference',
        '/\bWScript\.Shell\b/i'   => 'Windows script host reference',
        '/\beval\s*\(/i'          => 'suspicious eval() call',
        '/\bbase64_decode\s*\(/i' => 'suspicious base64_decode() call',
        '/\bsystem\s*\(/i'        => 'suspicious system() call',
        '/\bshell_exec\s*\(/i'    => 'suspicious shell_exec() call',
        '/\bpassthru\s*\(/i'      => 'suspicious passthru() call',
    ];
    foreach ($patterns as $re => $label) {
        if (preg_match($re, $sample)) return $label;
    }
    return null;
}

/** Run Windows Defender on a file when available (XAMPP on Windows). */
function media_scan_defender(string $path): ?string {
    if (PHP_OS_FAMILY !== 'Windows') return null;
    $mp = 'C:\\Program Files\\Windows Defender\\MpCmdRun.exe';
    if (!is_file($mp)) return null;

    $cmd = '"' . $mp . '" -Scan -ScanType 3 -File ' . escapeshellarg($path);
    @exec($cmd, $out, $code);
    if ($code === 2) return 'Windows Defender flagged this file as malware';
    return null;
}

/** Run ClamAV when installed. */
function media_scan_clamav(string $path): ?string {
    static $bin = null;
    if ($bin === null) {
        $candidates = ['clamscan', 'C:\\Program Files\\ClamAV\\clamscan.exe'];
        $bin = false;
        foreach ($candidates as $c) {
            if ($c === 'clamscan') {
                $found = trim((string)@shell_exec('where clamscan 2>nul'));
                if ($found !== '') { $bin = 'clamscan'; break; }
            } elseif (is_file($c)) {
                $bin = '"' . $c . '"';
                break;
            }
        }
    }
    if ($bin === false) return null;

    $cmd = $bin . ' --no-summary ' . escapeshellarg($path);
    @exec($cmd, $out, $code);
    if ($code === 1) return 'ClamAV detected a virus in this file';
    return null;
}

/**
 * Scan a local file (upload temp path or saved path).
 * @return array{safe:bool,message:string}
 */
function media_scan_file(string $path, ?string $originalName = null): array {
    if (!is_file($path) || !is_readable($path)) {
        return ['safe' => false, 'message' => 'Unable to read the uploaded file.'];
    }

    if ($originalName !== null) {
        if ($bad = media_scan_blocked_extension($originalName)) {
            return ['safe' => false, 'message' => "Blocked file type (.$bad). Only audio/video media is allowed."];
        }
        // Reject double extensions like track.mp3.exe
        $base = strtolower(pathinfo($originalName, PATHINFO_FILENAME));
        if (preg_match('/\.(exe|php|js|bat|cmd|msi|scr|vbs|ps1|dll|html|htm)$/', $base)) {
            return ['safe' => false, 'message' => 'Suspicious filename — upload rejected for your safety.'];
        }
    }

    $size = filesize($path);
    if ($size === false || $size < 1) {
        return ['safe' => false, 'message' => 'The file appears to be empty or corrupt.'];
    }

    $head = (string)@file_get_contents($path, false, null, 0, min(65536, $size));
    $tail = $size > 65536 ? (string)@file_get_contents($path, false, null, max(0, $size - 65536)) : '';

    if (!media_scan_valid_magic($head)) {
        return ['safe' => false, 'message' => 'File is not a valid MP3, MP4, WAV or OGG audio/video file.'];
    }

    foreach ([$head, $tail] as $chunk) {
        if ($threat = media_scan_binary_threat($chunk)) {
            return ['safe' => false, 'message' => 'Security threat detected: ' . $threat . '.'];
        }
    }

    if ($msg = media_scan_defender($path)) {
        return ['safe' => false, 'message' => $msg . '.'];
    }
    if ($msg = media_scan_clamav($path)) {
        return ['safe' => false, 'message' => $msg . '.'];
    }

    return ['safe' => true, 'message' => 'OK'];
}

/**
 * Scan a remote media URL before it is saved.
 * Trusted embed providers (Spotify, Suno, etc.) skip deep inspection.
 * @return array{safe:bool,message:string}
 */
function media_scan_url(string $url): array {
    $url = trim($url);
    if ($url === '') {
        return ['safe' => false, 'message' => 'Enter a valid track URL.'];
    }
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return ['safe' => false, 'message' => 'Enter a valid track URL.'];
    }

    $parts = parse_url($url);
    $scheme = strtolower($parts['scheme'] ?? '');
    if (!in_array($scheme, ['http', 'https'], true)) {
        return ['safe' => false, 'message' => 'Only HTTP/HTTPS links are allowed.'];
    }

    if (media_scan_trusted_link($url)) {
        return ['safe' => true, 'message' => 'OK'];
    }

    $path = $parts['path'] ?? '';
    if ($bad = media_scan_blocked_extension($path)) {
        return ['safe' => false, 'message' => "This link points to a blocked file type (.$bad)."];
    }

    $normalized = normalize_media_url($url);
    if ($normalized !== $url && media_scan_trusted_link($normalized)) {
        return ['safe' => true, 'message' => 'OK'];
    }

    // Probe direct media URLs: fetch a small sample and run the same checks as uploads.
    $ctx = stream_context_create([
        'http' => [
            'method'          => 'GET',
            'timeout'         => 12,
            'follow_location' => 1,
            'max_redirects'   => 3,
            'header'          => "User-Agent: GrooveVault/1.0\r\nRange: bytes=0-131071\r\n",
        ],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);

    $body = @file_get_contents($normalized, false, $ctx, 0, 131072);
    if ($body === false || $body === '') {
        return ['safe' => false, 'message' => 'Could not verify this link — it may be unreachable or unsafe.'];
    }

    if (!media_scan_valid_magic($body)) {
        return ['safe' => false, 'message' => 'This link does not appear to be a valid audio or video file.'];
    }
    if ($threat = media_scan_binary_threat($body)) {
        return ['safe' => false, 'message' => 'Security threat detected in linked file: ' . $threat . '.'];
    }

    $tmp = tempnam(sys_get_temp_dir(), 'gvscan_');
    if ($tmp && @file_put_contents($tmp, $body) !== false) {
        if ($msg = media_scan_defender($tmp)) {
            @unlink($tmp);
            return ['safe' => false, 'message' => $msg . '.'];
        }
        if ($msg = media_scan_clamav($tmp)) {
            @unlink($tmp);
            return ['safe' => false, 'message' => $msg . '.'];
        }
        @unlink($tmp);
    }

    return ['safe' => true, 'message' => 'OK'];
}
