<?php
require_once __DIR__ . '/inc/functions.inc.php';
require_once __DIR__ . '/inc/media_scan.inc.php';
require_login();
$user = current_user();
require_active_plan();

$errors    = [];
$maxTracks = (int)setting('max_tracks_per_channel', 12);
$maxMin    = (int)setting('max_track_duration_min', 10);

/** Parse "3:24" or "204" into seconds. Returns null on bad input. */
function parse_duration(string $raw): ?int {
    $raw = trim($raw);
    if ($raw === '') return null;
    if (ctype_digit($raw)) return (int)$raw;
    if (preg_match('/^(\d+):([0-5]?\d)$/', $raw, $m)) {
        return (int)$m[1] * 60 + (int)$m[2];
    }
    return null;
}

// Edit mode? Load the track and confirm the user owns its channel.
$editId    = (int)($_GET['edit'] ?? $_POST['edit_track'] ?? 0);
$editTrack = null;
if ($editId) {
    $stmt = db()->prepare('SELECT * FROM tracks WHERE id = ?');
    $stmt->execute([$editId]);
    $editTrack = $stmt->fetch();
    $channel   = $editTrack ? owned_channel((int)$editTrack['channel_id'], (int)$user['id']) : null;
    if (!$editTrack || !$channel) { flash_set('error', 'Track not found.'); redirect('dashboard.php'); }
} else {
    $channelId = isset($_GET['channel']) ? (int)$_GET['channel'] : (int)($_POST['channel'] ?? 0);
    $channel   = owned_channel($channelId, (int)$user['id']);
    if (!$channel) { flash_set('error', 'Channel not found.'); redirect('dashboard.php'); }
}

// Form values: existing track (edit) > defaults.
$old = $editTrack
    ? [
        'title'       => $editTrack['title'],
        'source_url'  => $editTrack['source_url'] ?? '',
        'duration'    => fmt_duration((int)$editTrack['duration_seconds']),
        'source_type' => $editTrack['source_type'],
      ]
    : ['title' => '', 'source_url' => '', 'duration' => '', 'source_type' => 'link'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $type     = ($_POST['source_type'] ?? 'link') === 'upload' ? 'upload' : 'link';
    $title    = trim($_POST['title'] ?? '');
    $url      = trim($_POST['source_url'] ?? '');
    $duration = parse_duration($_POST['duration'] ?? '');
    $old = ['title' => $title, 'source_url' => $url, 'duration' => $_POST['duration'] ?? '', 'source_type' => $type];

    if ($title === '') $errors[] = 'Track title is required.';

    // The track-count cap only applies when ADDING a new track.
    if (!$editId && channel_track_count((int)$channel['id']) >= $maxTracks) {
        $errors[] = "This channel already has the maximum of $maxTracks tracks.";
    }
    if ($duration === null) {
        $errors[] = 'Enter a valid duration (e.g. 3:24 or 204 seconds).';
    } elseif ($duration > $maxMin * 60) {
        $errors[] = "Tracks must be $maxMin minutes or shorter.";
    } elseif ($duration < 1) {
        $errors[] = 'Duration must be at least 1 second.';
    }

    $oldFile  = $editTrack['file_path'] ?? null;   // existing upload, if any
    $filePath = null;
    if ($type === 'link') {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $errors[] = 'Enter a valid MP4 URL.';
        } else {
            // Rewrite known share/song page links (e.g. Suno) to a direct media URL.
            $url = normalize_media_url($url);
            $old['source_url'] = $url;
            $scan = media_scan_url($url);
            if (!$scan['safe']) {
                $errors[] = $scan['message'];
            }
        }
    } else { // upload
        $hasNewFile = !empty($_FILES['file']['name']) && $_FILES['file']['error'] === UPLOAD_ERR_OK;
        if ($hasNewFile) {
            $allowed = ['audio/mpeg' => 'mp3', 'audio/mp4' => 'm4a', 'video/mp4' => 'mp4', 'audio/wav' => 'wav', 'audio/x-wav' => 'wav', 'audio/ogg' => 'ogg'];
            $mime = mime_content_type($_FILES['file']['tmp_name']);
            if (!isset($allowed[$mime])) {
                $errors[] = 'File must be MP3, MP4, M4A, WAV or OGG.';
            } elseif ($_FILES['file']['size'] > 30 * 1024 * 1024) {
                $errors[] = 'File must be under 30 MB.';
            } else {
                $scan = media_scan_file($_FILES['file']['tmp_name'], $_FILES['file']['name']);
                if (!$scan['safe']) {
                    $errors[] = $scan['message'];
                }
            }
            if (!$errors) {
                $dir = __DIR__ . '/uploads/tracks';
                if (!is_dir($dir)) mkdir($dir, 0775, true);
                $fname = 'tr_' . $channel['id'] . '_' . random_token(6) . '.' . $allowed[$mime];
                if (move_uploaded_file($_FILES['file']['tmp_name'], $dir . '/' . $fname)) {
                    $filePath = 'uploads/tracks/' . $fname;
                } else {
                    $errors[] = 'Upload failed. Please try again.';
                }
            }
        } elseif ($editId && $editTrack['source_type'] === 'upload' && $oldFile) {
            // Editing and keeping the existing uploaded file.
            $filePath = $oldFile;
        } else {
            $errors[] = 'Choose an audio/video file to upload.';
        }
    }

    if (!$errors) {
        $newUrl  = $type === 'link'   ? $url      : null;
        $newFile = $type === 'upload' ? $filePath : null;

        if ($editId) {
            db()->prepare(
                'UPDATE tracks SET title = ?, source_type = ?, source_url = ?, file_path = ?, duration_seconds = ?
                 WHERE id = ? AND channel_id = ?'
            )->execute([$title, $type, $newUrl, $newFile, $duration, $editId, $channel['id']]);

            // Remove the old upload if it was replaced or the track switched to a link.
            if ($oldFile && $oldFile !== $newFile) {
                $abs = __DIR__ . '/' . $oldFile;
                if (is_file($abs)) @unlink($abs);
            }
            flash_set('success', 'Track updated.');
        } else {
            $pos = db()->prepare('SELECT COALESCE(MAX(position),0)+1 FROM tracks WHERE channel_id = ?');
            $pos->execute([$channel['id']]);
            $nextPos = (int)$pos->fetchColumn();
            db()->prepare(
                'INSERT INTO tracks (channel_id, title, source_type, source_url, file_path, duration_seconds, position)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            )->execute([$channel['id'], $title, $type, $newUrl, $newFile, $duration, $nextPos]);
            flash_set('success', 'Track added to ' . $channel['name'] . '.');
        }
        redirect('channel_detail.php?id=' . $channel['id']);
    }
}

$formAction = $editId ? ('add_track.php?edit=' . $editId) : ('add_track.php?channel=' . (int)$channel['id']);
$pageTitle  = 'GrooveVault — ' . ($editId ? 'Edit' : 'Add') . ' Track';
$navVariant = 'user';
require_once('inc/header.inc.php');
?>

<div class="view" style="min-height:100vh;">
  <div class="modal-stage">
    <form method="post" enctype="multipart/form-data" action="<?= e($formAction) ?>" class="gv-modal-card lg p-4" id="addTrackForm">
      <h5 class="modal-title-gv mb-3"><?= $editId ? 'EDIT' : 'ADD' ?> TRACK <small style="text-transform:none;color:var(--text-muted);font-size:.8rem;"><?= $editId ? 'in' : 'to' ?> <?= e($channel['name']) ?></small></h5>
      <?php foreach ($errors as $err): ?>
        <div class="gv-alert gv-alert-danger mb-2" style="font-size:.82rem;"><?= e($err) ?></div>
      <?php endforeach; ?>
      <?= csrf_field() ?>
      <?php if ($editId): ?><input type="hidden" name="edit_track" value="<?= (int)$editId ?>"><?php endif; ?>
      <input type="hidden" name="source_type" id="sourceType" value="<?= e($old['source_type']) ?>">

      <ul class="nav nav-tabs mb-3" style="border-color:var(--card-border);">
        <li class="nav-item"><span class="nav-link tab-link" data-tab="link"   style="cursor:pointer;">MP4 Link</span></li>
        <li class="nav-item"><span class="nav-link tab-link" data-tab="upload" style="cursor:pointer;">Upload File</span></li>
      </ul>

      <div class="mb-3"><label class="form-label">Track Title</label><input name="title" class="form-control" placeholder="Song name" value="<?= e($old['title']) ?>" required></div>

      <div id="tab-link">
        <div class="mb-3"><label class="form-label">Track URL</label><input name="source_url" id="trackUrl" class="form-control" placeholder="MP4/MP3 link · YouTube · Spotify · SoundCloud · Suno" value="<?= e($old['source_url']) ?>"><div style="color:var(--text-muted);font-size:.78rem;margin-top:.3rem;">Direct video/audio file (MP4/MP3 — video links play video, audio links play audio), or a YouTube, Spotify, SoundCloud or Suno link — duration is detected automatically. <span style="color:var(--text-muted);">Spotify needs a logged-in Spotify account for full playback (otherwise a 30-second preview).</span></div></div>
      </div>
      <div id="tab-upload" style="display:none;">
        <div class="mb-3">
          <label class="form-label">Audio / Video File</label>
          <?php if ($editId && $editTrack['source_type'] === 'upload' && $editTrack['file_path']): ?>
            <p style="font-size:.78rem;color:var(--text-muted);margin:0 0 .4rem;">Current file: <span class="text-blue"><?= e(basename($editTrack['file_path'])) ?></span> — leave empty to keep it, or choose a new one to replace.</p>
          <?php endif; ?>
          <label class="upload-zone d-block" style="cursor:pointer;">
            <i class="bi bi-cloud-arrow-up" style="font-size:2rem;color:var(--electric-blue);"></i>
            <p style="color:var(--text-muted);margin:.6rem 0 0;font-size:.85rem;">Click to <span class="text-blue">browse</span> (MP3 / MP4 / WAV · max 30 MB)</p>
            <p style="font-size:.74rem;color:var(--text-muted);margin:0;" id="fileName">No file selected</p>
            <input type="file" name="file" id="trackFile" accept="audio/*,video/mp4" hidden>
          </label>
        </div>
      </div>

      <div class="mb-3"><label class="form-label">Duration <small style="text-transform:none;color:var(--text-muted);">(auto-detected · editable · max <?= $maxMin ?> min)</small></label><input name="duration" id="trackDuration" class="form-control" placeholder="3:24" value="<?= e($old['duration']) ?>" required><div id="durStatus" style="font-size:.74rem;color:var(--text-muted);margin-top:.3rem;"></div></div>

      <div class="d-flex justify-content-end gap-2 mt-3">
        <a href="channel_detail.php?id=<?= (int)$channel['id'] ?>" class="btn btn-secondary btn-sm">Cancel</a>
        <button type="submit" class="btn btn-gv-primary btn-sm"><i class="bi bi-check2 me-1"></i><?= $editId ? 'Save Changes' : 'Add Track' ?></button>
      </div>
      <p style="color:var(--text-muted);font-size:.78rem;margin-top:.8rem;margin-bottom:0;">Max <?= $maxMin ?> min · over-length is rejected · <?= $maxTracks ?>-track limit.</p>
    </form>
  </div>
</div>

<div id="malwareModal" class="gv-threat-overlay" hidden aria-hidden="true">
  <div class="gv-threat-card" role="alertdialog" aria-labelledby="malwareModalTitle" aria-modal="true">
    <div class="gv-threat-icon"><i class="bi bi-shield-exclamation"></i></div>
    <h5 id="malwareModalTitle" class="gv-threat-title">VIRUS / MALWARE DETECTED</h5>
    <p id="malwareModalMsg" class="gv-threat-msg">This file could not be uploaded because it may contain malware or a security threat.</p>
    <button type="button" class="btn btn-gv-primary btn-sm" id="malwareModalClose">OK, I Understand</button>
  </div>
</div>

<style>
  .gv-threat-overlay{position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:1.2rem;background:rgba(5,2,10,0.82);backdrop-filter:blur(3px);}
  .gv-threat-card{background:var(--card-bg);border:1.5px solid rgba(255,45,120,0.45);border-radius:18px;max-width:420px;width:100%;padding:1.6rem;text-align:center;box-shadow:0 12px 40px rgba(255,45,120,0.15);}
  .gv-threat-icon{width:52px;height:52px;margin:0 auto .9rem;border-radius:50%;display:flex;align-items:center;justify-content:center;background:rgba(255,45,120,0.12);color:#ff5a9a;font-size:1.5rem;}
  .gv-threat-title{font-family:'Bebas Neue',sans-serif;letter-spacing:.5px;color:#ff5a9a;margin:0 0 .6rem;font-size:1.35rem;}
  .gv-threat-msg{color:var(--text-muted);font-size:.85rem;margin:0 0 1.2rem;line-height:1.45;}
</style>

<script src="gv-player.js"></script>
<script>
  (function () {
    var type = document.getElementById('sourceType');
    function activate(tab) {
      type.value = tab;
      document.getElementById('tab-link').style.display   = tab === 'link'   ? '' : 'none';
      document.getElementById('tab-upload').style.display = tab === 'upload' ? '' : 'none';
      document.querySelectorAll('.tab-link').forEach(function (el) {
        var on = el.dataset.tab === tab;
        el.classList.toggle('active', on);
        el.style.background = on ? 'var(--card-bg)' : '';
        el.style.color = on ? 'var(--neon-green)' : 'var(--text-muted)';
        el.style.borderColor = on ? 'var(--card-border) var(--card-border) transparent' : 'transparent';
      });
    }
    document.querySelectorAll('.tab-link').forEach(function (el) {
      el.addEventListener('click', function () { activate(el.dataset.tab); });
    });
    activate(type.value || 'link');
  })();

  // Auto-detect track duration from the chosen file or pasted link.
  // GVPlayer.detectDuration handles direct files, Spotify, YouTube and SoundCloud.
  (function () {
    var durField = document.getElementById('trackDuration');
    var status   = document.getElementById('durStatus');
    var fileIn   = document.getElementById('trackFile');
    var urlIn    = document.getElementById('trackUrl');
    var fileName = document.getElementById('fileName');

    function fmt(s) { s = Math.round(s); return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0'); }
    function say(msg, ok) { if (status) { status.textContent = msg || ''; status.style.color = ok ? 'var(--neon-green)' : 'var(--text-muted)'; } }

    var token = 0;
    function detect(input) {
      var mine = ++token;
      say('Reading duration…', false);
      GVPlayer.detectDuration(input, function (secs) {
        if (mine !== token) return;          // a newer request superseded this one
        if (secs > 0) { durField.value = fmt(secs); say('Duration detected automatically.', true); }
        else { say('Couldn’t read duration — please enter it manually.', false); }
      });
    }

    if (fileIn) fileIn.addEventListener('change', function () {
      if (fileName) fileName.textContent = this.files[0] ? this.files[0].name : 'No file selected';
      if (this.files[0]) detect(this.files[0]);
    });
    if (urlIn) urlIn.addEventListener('change', function () {
      var v = this.value.trim();
      if (v) detect(v);
    });
  })();

  // Malware / virus scan before upload or link submit.
  (function () {
    var form      = document.getElementById('addTrackForm');
    var modal     = document.getElementById('malwareModal');
    var modalMsg  = document.getElementById('malwareModalMsg');
    var modalClose= document.getElementById('malwareModalClose');
    var sourceType= document.getElementById('sourceType');
    var fileIn    = document.getElementById('trackFile');
    var urlIn     = document.getElementById('trackUrl');
    var scanning  = false;
    var bypassScan= false;

    function showThreat(msg) {
      if (modalMsg) modalMsg.textContent = msg || 'This file could not be uploaded because it may contain malware or a security threat.';
      if (modal) { modal.hidden = false; modal.setAttribute('aria-hidden', 'false'); }
    }
    function hideThreat() {
      if (modal) { modal.hidden = true; modal.setAttribute('aria-hidden', 'true'); }
    }
    if (modalClose) modalClose.addEventListener('click', hideThreat);

    function needsFileScan() {
      if (sourceType.value !== 'upload') return false;
      if (!fileIn || !fileIn.files || !fileIn.files[0]) {
        // Editing: empty file input means keep the existing upload.
        return !form.querySelector('input[name="edit_track"]');
      }
      return true;
    }

    function scanPayload() {
      var fd = new FormData();
      var csrf = form.querySelector('input[name="csrf"]');
      if (csrf) fd.append('csrf', csrf.value);
      fd.append('source_type', sourceType.value || 'link');
      if (sourceType.value === 'upload') {
        if (!fileIn || !fileIn.files || !fileIn.files[0]) {
          return Promise.resolve({ safe: true, message: 'OK' });
        }
        fd.append('file', fileIn.files[0]);
      } else {
        fd.append('source_url', urlIn ? urlIn.value.trim() : '');
      }
      return fetch('api/scan_media.php', { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .catch(function () { return { safe: false, message: 'Security scan failed. Please try again.' }; });
    }

    if (form) form.addEventListener('submit', function (e) {
      if (bypassScan || scanning) return;
      if (sourceType.value === 'upload' && !needsFileScan()) {
        bypassScan = true;
        form.submit();
        return;
      }
      e.preventDefault();
      scanning = true;
      var btn = form.querySelector('button[type="submit"]');
      if (btn) { btn.disabled = true; btn.dataset.prev = btn.innerHTML; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Scanning…'; }
      scanPayload().then(function (res) {
        scanning = false;
        if (btn) { btn.disabled = false; btn.innerHTML = btn.dataset.prev || btn.innerHTML; }
        if (res && res.safe) {
          bypassScan = true;
          form.submit();
        } else {
          showThreat((res && res.message) ? res.message : 'Upload blocked: possible virus or malware detected.');
          if (sourceType.value === 'upload' && fileIn) fileIn.value = '';
          var fileName = document.getElementById('fileName');
          if (fileName) fileName.textContent = 'No file selected';
        }
      });
    });
  })();
</script>

<?php
require_once('inc/footer.inc.php');
?>
