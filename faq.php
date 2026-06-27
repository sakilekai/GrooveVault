<?php
require_once __DIR__ . '/inc/functions.inc.php';

$pageTitle  = 'GrooveVault — FAQ';
// Show the logged-in nav when signed in, otherwise the guest nav.
$navVariant = current_user() ? 'user' : 'guest';

/* All FAQ content, grouped by category. Edit here to add/update questions. */
$faqCategories = [
    'Getting Started' => [
        ['What is Groove-Vault?',
         'Groove-Vault is a subscription-based music channel platform. It lets you create named music channels, add up to 12 MP4 audio or video tracks per channel, organize and shuffle your music, and share your channels with anyone via a unique link — no login required for listeners.'],
        ['How do I create an account?',
         'Click the "Sign Up Free" button on the Groove-Vault homepage. Enter your display name, email address, and a password (minimum 8 characters). You\'ll receive a verification email — click the link inside to verify your address, then choose your subscription plan to get started.'],
        ['Do I need to verify my email address?',
         'Yes. Email verification is required to activate your account and access channel creation features. Check your inbox (and spam folder) after registration. If you didn\'t receive the email, contact us at support@groove-vault.com.'],
        ['Is there a free version of Groove-Vault?',
         'Groove-Vault requires a paid subscription to create and manage channels. We may offer free trial periods from time to time — check our website for current promotions. Anyone can listen to a shared channel without any account or subscription.'],
        ['What devices can I use Groove-Vault on?',
         'Groove-Vault works in any modern web browser (Chrome, Firefox, Safari, Edge) on desktop or mobile. We also offer a mobile app for Android (available on Google Play) and iOS (available on the Apple App Store), giving you the full Groove-Vault experience on your phone or tablet.'],
    ],
    'Subscription & Billing' => [
        ['What subscription plans are available?',
         'We offer three plans: Groove Starter ($4.99/month) up to 5 channels; Groove Pro ($9.99/month) up to 12 channels plus priority support; and Groove Annual ($79/year) — everything in Pro plus unlimited channels, at a 33% savings. All plans include 12 tracks per channel, shuffle, and shareable links.'],
        ['How do I pay for my subscription?',
         'Subscriptions are processed securely through PayPal. You can pay with your PayPal balance, a linked bank account, or any major credit or debit card via PayPal\'s checkout. Groove-Vault does not store your payment card details.'],
        ['Does my subscription renew automatically?',
         'No. GrooveVault subscriptions are one-time payments for a fixed term — 1 month for Starter and Pro, or 1 year for Annual. You are never charged automatically. When your subscription expires, you\'ll be asked to choose a plan and pay again to keep creating, managing, and playing your channels. Your channels are kept while you renew.'],
        ['How do I cancel my subscription?',
         'You can cancel your subscription at any time from your account settings page, or by emailing support@groove-vault.com. Cancellation takes effect at the end of your current billing period — you\'ll keep access to all features until then. We do not offer partial-period refunds.'],
        ['Can I upgrade or downgrade my plan?',
         'Yes. You can change your plan at any time from your account settings. Upgrades take effect immediately. Downgrades take effect at the start of your next billing period.'],
        ['Can I get a refund?',
         'We do not offer refunds for subscription fees already paid, except where required by applicable law. If you believe you were charged in error, contact us at support@groove-vault.com within 7 days of the charge and we\'ll review your case.'],
        ['What happens if my payment fails?',
         'If a payment fails, we\'ll notify you by email and give you a grace period to update your payment information. If payment is not resolved within the grace period, your account will be downgraded to a limited state, and channel creation may be paused until payment is restored.'],
    ],
    'Creating & Managing Channels' => [
        ['How many channels can I create?',
         'Groove Starter plan users can create up to 5 channels. Groove Pro plan users can create up to 12 channels. Groove Annual plan users can create unlimited channels.'],
        ['How many tracks can I add to a channel?',
         'Each channel can hold a maximum of 12 tracks. The "Add Track" button will be hidden once you reach 12 tracks. To add more tracks, you would need to remove an existing one first.'],
        ['How do I create a channel?',
         'From your Dashboard, click the "New Channel" button. Give your channel a name, choose an emoji icon, and select a background color gradient. Click Save — your channel is ready! Then open the channel and click "Add Track" to start building your playlist.'],
        ['How do I edit a channel\'s name, icon, or color?',
         'From your Dashboard, click the edit (pencil) icon on a channel card, or click the "Edit" button on the channel detail page. Make your changes and click Save.'],
        ['How do I delete a channel?',
         'Click the delete (trash) icon on a channel card from your Dashboard, or click "Delete" on the channel detail page. You\'ll be asked to confirm before the channel is permanently removed. Deleted channels cannot be recovered.'],
        ['Can I reorder the tracks in my channel?',
         'Yes! On the channel detail page, reorder tracks using the up/down controls on each track row to arrange them in any sequence you like.'],
    ],
    'Adding & Managing Tracks' => [
        ['What file type does Groove-Vault support?',
         'Groove-Vault supports MP4 (.mp4) files only. This includes both audio-only MP4 files and video MP4 files. The platform plays the audio content of any valid MP4 file.'],
        ['How do I add a track to a channel?',
         'Open your channel and click "Add Track." You have two options: (1) paste a direct MP4 link (URL) — enter the URL and track title, then click Add Track; or (2) upload a local MP4 file — drag and drop your file into the upload zone or click to browse your files.'],
        ['Is there a maximum track length?',
         'Yes. Individual tracks cannot exceed 10 minutes (600 seconds) in duration. If you upload or link a file that is longer than 10 minutes, you\'ll receive an error message, and the track will not be added. This limit helps ensure a great playback experience for all users.'],
        ['Why does my track URL show an error?',
         'This can happen if: (1) the URL does not point directly to an MP4 file; (2) the file server blocks cross-origin requests (CORS); or (3) the URL has expired or is no longer accessible. Make sure the URL ends in .mp4 and is a direct download link rather than a streaming page URL.'],
        ['Can I upload music from streaming services like Spotify or Apple Music?',
         'No. Tracks from Spotify, Apple Music, YouTube, or other streaming platforms cannot be uploaded or linked on Groove-Vault. These services\' content is licensed and cannot be redistributed. Only MP4 files that you own or have a license to share may be added.'],
        ['How do I delete a track from a channel?',
         'On the channel detail page, click the X (trash) button on the right side of any track row to remove it. Removed tracks cannot be recovered.'],
    ],
    'Playing Music' => [
        ['How do I play a channel?',
         'Click the "Play" button on any channel card from your Dashboard, or click "Play" on the channel detail page. The music player will appear at the bottom of the screen and begin playing the first track.'],
        ['How does Shuffle mode work?',
         'Click the Shuffle button (the crossed arrows icon) in the player bar or on the channel detail page to toggle shuffle on or off. When shuffle is on, tracks will play in a randomized order. The shuffle order is reset each time you toggle shuffle on.'],
        ['How does Repeat mode work?',
         'Click the Repeat button (the circular arrows icon) in the player bar to toggle repeat on or off. When repeat is on, the current track will loop continuously until you toggle repeat off or skip to another track.'],
        ['Can I skip forward or backward?',
         'Yes. Use the skip forward and skip backward buttons in the player bar. Clicking skip backward within the first 3 seconds of a track goes to the previous track. Clicking it after 3 seconds restarts the current track.'],
        ['Can I seek to a specific point in a track?',
         'Yes. Click anywhere on the progress bar in the player to jump to that position in the track.'],
        ['Why did my track get skipped automatically?',
         'If a track\'s duration is detected at playback time to exceed 10 minutes, it will be automatically skipped and you\'ll see a notification. This can happen if the duration check at upload time was not available (e.g., due to CORS restrictions on the URL).'],
    ],
    'Sharing Channels' => [
        ['How do I share a channel?',
         'Click the "Share" button on any channel card or channel detail page. A modal will appear with your unique channel link. Click "Copy Link" to copy it to your clipboard, or use the buttons to share directly via X (Twitter), WhatsApp, or email.'],
        ['Can people listen to my channel without a Groove-Vault account?',
         'Yes! Anyone with your channel link can listen to your channel — no account or subscription required. They can play the full channel and toggle shuffle from the shared view. This is a great way to share your music with friends and family.'],
        ['Can I make a channel private?',
         'Currently, all channels are accessible to anyone who has your shareable link. If you do not share the link, your channel is effectively private. A fully private/unlisted channel feature may be added in a future update.'],
        ['What do listeners see on my shared channel page?',
         'Listeners see your channel name, emoji, track count, and the full track list with track titles and durations. They can play the channel, shuffle it, and click individual tracks to play them directly.'],
    ],
    'Mobile App' => [
        ['Is there a Groove-Vault mobile app?',
         'Yes! Groove-Vault is available as a mobile app for Android (Google Play Store) and iOS (Apple App Store). The mobile app gives you the full Groove-Vault experience on your phone, including channel management, the music player, and channel sharing.'],
        ['Which Android devices are supported?',
         'The Groove-Vault Android app supports Android 10.0 and above (API Level 29+). This covers most Android phones and tablets released in 2019 and later, including TracFone Android devices.'],
        ['Which iOS devices are supported?',
         'The Groove-Vault iOS app supports iOS 16.0 and above. This includes iPhone 8 and later models.'],
        ['Can I use Groove-Vault on a TracFone?',
         'Yes, if your TracFone runs Android and has the Google Play Store, you can download the Groove-Vault app directly from the Play Store.'],
        ['Does the mobile app work offline?',
         'The mobile app stores your channel data locally on your device. You can view your channels offline. However, playing tracks requires an internet connection to stream the MP4 files (unless you have cached them on the device in a future Pro feature).'],
    ],
    'Account & Security' => [
        ['How do I reset my password?',
         'On the login screen, click "Forgot Password" and enter your registered email address. You\'ll receive a password reset link by email. If you don\'t receive it within a few minutes, check your spam folder or contact support@groove-vault.com.'],
        ['How do I delete my account?',
         'To delete your account and all associated data, email support@groove-vault.com with the subject line "Account Deletion Request" from your registered email address. We will process your request within 30 days. Note that account deletion is permanent and cannot be reversed.'],
        ['What should I do if I think my account was hacked?',
         'If you suspect unauthorized access, change your password immediately from your account settings and contact support@groove-vault.com right away. We recommend also changing your password on any other services where you used the same password.'],
        ['Can I have multiple accounts?',
         'You may create only one account per person. Creating multiple accounts to circumvent plan limits or suspensions violates our Terms of Service and may result in all associated accounts being terminated.'],
    ],
    'Contact & Support' => [
        ['How do I contact Groove-Vault support?',
         'Email us at support@groove-vault.com for general support questions. For legal and privacy matters, email legal@groove-vault.com. We aim to respond to all inquiries within 1–2 business days.'],
        ['How do I report a copyright violation or inappropriate content?',
         'Email legal@groove-vault.com with the subject line "DMCA Notice" or "Content Report." Include the channel link, a description of the issue, and your contact information. We review all reports and will respond within 5 business days.'],
        ['Where can I find Groove-Vault\'s Terms of Service, Privacy Policy, and Community Guidelines?',
         'All legal documents are available on our website at https://groove-vault.com. You can also request copies by emailing legal@groove-vault.com.'],
    ],
];

/* Escape, then turn email addresses and URLs into links. */
function faq_text(string $text): string {
    $s = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $s = preg_replace(
        '/([a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,})/',
        '<a href="mailto:$1" class="text-blue text-decoration-none">$1</a>',
        $s
    );
    $s = preg_replace_callback(
        '#\bhttps?://[^\s<]+#',
        fn($m) => '<a href="' . $m[0] . '" target="_blank" rel="noopener" class="text-blue text-decoration-none">' . $m[0] . '</a>',
        $s
    );
    return $s;
}

require_once('inc/header.inc.php');
?>

<style>
  .faq-wrap{max-width:820px;margin:0 auto;}
  .faq-search{position:relative;margin-bottom:1.8rem;}
  .faq-search .bi{position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--text-muted);}
  .faq-search input{width:100%;background:rgba(255,255,255,0.04);border:1.5px solid var(--card-border);color:var(--text-main);border-radius:12px;padding:.85rem 1rem .85rem 2.6rem;font-size:.95rem;}
  .faq-search input:focus{outline:none;border-color:var(--electric-blue);box-shadow:0 0 0 3px rgba(0,212,255,0.15);}
  .faq-search input::placeholder{color:var(--text-muted);}
  .faq-cat-title{font-family:'Bebas Neue',sans-serif;font-size:1.55rem;letter-spacing:1px;margin:2.2rem 0 1rem;color:var(--text-main);}
  .faq-item{background:var(--card-bg);border:1.5px solid var(--card-border);border-radius:14px;margin-bottom:.7rem;overflow:hidden;transition:border-color .2s;}
  .faq-item[open]{border-color:rgba(0,212,255,0.35);}
  .faq-item summary{list-style:none;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem 1.2rem;font-weight:600;font-size:.96rem;color:var(--text-main);}
  .faq-item summary::-webkit-details-marker{display:none;}
  .faq-item summary:hover{color:var(--electric-blue);}
  .faq-item[open] summary{color:var(--electric-blue);}
  .faq-item summary .chev{transition:transform .2s;color:var(--text-muted);flex-shrink:0;font-size:.9rem;}
  .faq-item[open] summary .chev{transform:rotate(180deg);}
  .faq-answer{padding:0 1.2rem 1.1rem;color:var(--text-muted);font-size:.9rem;line-height:1.75;}
  .faq-contact{background:var(--card-bg);border:1.5px solid var(--card-border);border-radius:16px;padding:1.6rem;text-align:center;margin-top:2.5rem;}
  #faqNoResults{display:none;text-align:center;color:var(--text-muted);padding:2rem;}
</style>

<div class="container" style="padding-top:6rem;padding-bottom:3rem;">
  <div class="faq-wrap">
    <div class="text-center mb-4">
      <div class="hero-badge mb-2"><i class="bi bi-patch-question me-1"></i>HELP CENTER</div>
      <h1 class="section-title" style="font-size:2.6rem;">FREQUENTLY ASKED <span class="text-blue">QUESTIONS</span></h1>
      <p style="color:var(--text-muted);max-width:540px;margin:.4rem auto 0;">Everything you need to know about GrooveVault — creating channels, billing, playback, sharing and more.</p>
    </div>

    <div class="faq-search">
      <i class="bi bi-search"></i>
      <input type="text" id="faqSearch" placeholder="Search the FAQs…" autocomplete="off" aria-label="Search FAQs">
    </div>

    <?php foreach ($faqCategories as $category => $items): ?>
      <div class="faq-cat">
        <h2 class="faq-cat-title"><?= e($category) ?></h2>
        <?php foreach ($items as [$q, $a]): ?>
          <details class="faq-item">
            <summary><span><?= e($q) ?></span><i class="bi bi-chevron-down chev"></i></summary>
            <div class="faq-answer"><?= faq_text($a) ?></div>
          </details>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>

    <div id="faqNoResults">
      <div style="font-size:2rem;">🔍</div>
      <p class="mt-2 mb-0">No FAQs match your search. Try different keywords, or email <a href="mailto:support@groove-vault.com" class="text-blue text-decoration-none">support@groove-vault.com</a>.</p>
    </div>

    <div class="faq-contact">
      <div style="font-size:1.8rem;">💬</div>
      <h5 style="font-family:'Bebas Neue',sans-serif;letter-spacing:1px;margin:.4rem 0 .3rem;">STILL HAVE QUESTIONS?</h5>
      <p style="color:var(--text-muted);font-size:.9rem;margin-bottom:1rem;">Can't find what you're looking for? Email us and we'll get back to you within 1–2 business days.</p>
      <a href="mailto:support@groove-vault.com" class="btn btn-gv-primary btn-sm"><i class="bi bi-envelope me-1"></i>support@groove-vault.com</a>
    </div>
  </div>
</div>

<script>
  (function () {
    var input = document.getElementById('faqSearch');
    var cats  = document.querySelectorAll('.faq-cat');
    var none  = document.getElementById('faqNoResults');
    if (!input) return;
    input.addEventListener('input', function () {
      var q = this.value.toLowerCase().trim();
      var total = 0;
      cats.forEach(function (cat) {
        var shown = 0;
        cat.querySelectorAll('.faq-item').forEach(function (item) {
          var match = !q || item.textContent.toLowerCase().indexOf(q) !== -1;
          item.style.display = match ? '' : 'none';
          if (match) shown++;
          if (q && match) item.setAttribute('open', ''); else if (q) item.removeAttribute('open');
        });
        cat.style.display = shown ? '' : 'none';
        total += shown;
      });
      none.style.display = total ? 'none' : 'block';
    });
  })();
</script>

<?php require_once('inc/footer.inc.php'); ?>
