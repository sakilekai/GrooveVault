<?php
require_once __DIR__ . '/inc/functions.inc.php';

$pageTitle  = 'GrooveVault — Community Guidelines';
$navVariant = current_user() ? 'user' : 'guest';

require_once('inc/header.inc.php');
?>

<style>
  .legal-wrap{max-width:820px;margin:0 auto;}
  .legal-meta{background:var(--card-bg);border:1.5px solid var(--card-border);border-radius:16px;padding:1.4rem 1.6rem;margin-bottom:2rem;display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;}
  .legal-meta .lbl{font-size:.7rem;letter-spacing:1px;text-transform:uppercase;color:var(--text-muted);font-family:'Space Mono',monospace;margin-bottom:.2rem;}
  .legal-meta .val{font-size:.9rem;color:var(--text-main);word-break:break-word;}
  .legal-doc h2{font-family:'Bebas Neue',sans-serif;font-size:1.5rem;letter-spacing:1px;color:var(--text-main);margin:2.4rem 0 .8rem;}
  .legal-doc h3{font-size:1.02rem;font-weight:600;color:var(--electric-blue);margin:1.5rem 0 .5rem;}
  .legal-doc p{color:var(--text-muted);font-size:.92rem;line-height:1.8;margin-bottom:1rem;}
  .legal-doc ul{color:var(--text-muted);font-size:.92rem;line-height:1.75;margin:0 0 1.1rem 1.15rem;}
  .legal-doc li{margin-bottom:.45rem;}
  .legal-doc strong{color:var(--text-main);font-weight:600;}
  .legal-doc a{color:var(--electric-blue);text-decoration:none;}
  .legal-doc a:hover{text-decoration:underline;}
  .legal-table-wrap{overflow-x:auto;margin:.4rem 0 1.2rem;}
  .legal-table{width:100%;border-collapse:collapse;font-size:.88rem;min-width:480px;}
  .legal-table th,.legal-table td{text-align:left;padding:.7rem .9rem;border:1px solid var(--card-border);vertical-align:top;}
  .legal-table th{background:rgba(255,255,255,0.04);color:var(--text-main);font-weight:600;}
  .legal-table td{color:var(--text-muted);}
  .legal-table td:first-child{color:var(--text-main);font-weight:600;white-space:nowrap;}
</style>

<div class="container" style="padding-top:6rem;padding-bottom:3rem;">
  <div class="legal-wrap">
    <div class="text-center mb-4">
      <div class="hero-badge mb-2"><i class="bi bi-people me-1"></i>COMMUNITY</div>
      <h1 class="section-title" style="font-size:2.6rem;">COMMUNITY <span class="text-blue">GUIDELINES</span></h1>
      <p style="color:var(--text-muted);max-width:560px;margin:.4rem auto 0;">How we keep GrooveVault a great place for music lovers.</p>
    </div>

    <div class="legal-meta">
      <div><div class="lbl">Company</div><div class="val">MSSRA, LLC</div></div>
      <div><div class="lbl">Effective Date</div><div class="val">June 26, 2026</div></div>
      <div><div class="lbl">Email</div><div class="val"><a href="mailto:legal@groove-vault.com" class="text-blue text-decoration-none">legal@groove-vault.com</a></div></div>
      <div><div class="lbl">Website</div><div class="val"><a href="https://groove-vault.com" target="_blank" rel="noopener" class="text-blue text-decoration-none">groove-vault.com</a></div></div>
    </div>

    <div class="legal-doc">
      <h2>Welcome to the Groove-Vault Community</h2>
      <p>Groove-Vault is a platform built for music lovers — people who want to curate, share, and celebrate sound. Whether you're creating a high-energy workout channel, a late-night chill playlist, or a genre-specific collection, Groove-Vault is your stage.</p>
      <p>These Community Guidelines exist to ensure that Groove-Vault remains a safe, respectful, and enjoyable place for everyone. By using the Service, you agree to follow these Guidelines in addition to our <a href="terms.php">Terms of Service</a>. Violations may result in content removal, account suspension, or permanent termination.</p>

      <h2>1. Be Respectful</h2>
      <p>Groove-Vault is a music platform, not a debate stage. We expect all users to treat each other — and the platform itself — with respect.</p>
      <ul>
        <li>Do not use channel names, track titles, or any shared content to harass, bully, intimidate, or demean any individual or group</li>
        <li>Do not target users with hateful language based on race, ethnicity, religion, gender, sexual orientation, gender identity, disability, age, or national origin</li>
        <li>Do not use Groove-Vault channels or shared links to threaten or incite violence against any person or group</li>
        <li>Do not create channels designed to mock, impersonate, or defame real people</li>
      </ul>
      <p>Music brings people together. Keep it that way.</p>

      <h2>2. Respect Copyright &amp; Intellectual Property</h2>
      <p>This is one of our most important guidelines — and it protects you too.</p>
      <h3>2.1 Only Upload Content You Have the Right to Share</h3>
      <ul>
        <li>Only upload or link to MP4 files that you own, have licensed, or are otherwise legally permitted to share</li>
        <li>Purchasing a song on iTunes, Spotify, or elsewhere does not grant you the right to upload or redistribute that song</li>
        <li>Do not upload commercial music recordings without a proper synchronization or distribution license</li>
        <li>Do not upload content that samples copyrighted recordings without clearance</li>
      </ul>
      <h3>2.2 What's Generally Acceptable</h3>
      <ul>
        <li>Original music you created yourself</li>
        <li>Music you have an explicit license to distribute</li>
        <li>Royalty-free or Creative Commons licensed music (verify the specific license terms)</li>
        <li>Public domain recordings</li>
      </ul>
      <h3>2.3 DMCA Takedowns</h3>
      <p>Groove-Vault will respond promptly to valid DMCA takedown notices. Repeat infringers will have their accounts terminated. If you believe your content was wrongly removed, you may submit a counter-notice to <a href="mailto:legal@groove-vault.com">legal@groove-vault.com</a>.</p>

      <h2>3. Keep Content Appropriate</h2>
      <p>Groove-Vault is a music channel platform open to users aged 13 and older. Content must be appropriate for a general audience.</p>
      <ul>
        <li>Do not upload, link to, or include in channel names any sexually explicit or pornographic content</li>
        <li>Do not upload content that depicts graphic violence, gore, or torture</li>
        <li>Do not use channel names or track titles that contain excessive profanity or obscene language</li>
        <li>Do not create content that promotes, glorifies, or instructs in illegal activities</li>
        <li>Do not upload content depicting or encouraging the use of illegal drugs, self-harm, or eating disorders</li>
      </ul>

      <h2>4. Protect User Privacy</h2>
      <ul>
        <li>Do not include personally identifiable information about others (addresses, phone numbers, financial information, etc.) in channel names or track titles</li>
        <li>Do not upload content that reveals private information about another person without their consent</li>
        <li>Do not use Groove-Vault channels to stalk, monitor, or collect information about others</li>
        <li>Do not share another user's login credentials or account information</li>
      </ul>

      <h2>5. No Spam or Deceptive Practices</h2>
      <ul>
        <li>Do not create multiple accounts for the purpose of circumventing plan limits, suspensions, or bans</li>
        <li>Do not use channel names or track titles to promote spam, phishing schemes, or fraudulent offers</li>
        <li>Do not use misleading channel names to impersonate artists, brands, or other Groove-Vault users</li>
        <li>Do not link to external URLs in track titles unless they are direct MP4 file links</li>
        <li>Do not use the Service to promote pyramid schemes, multi-level marketing, or other deceptive commercial practices</li>
      </ul>

      <h2>6. Use Sharing Features Responsibly</h2>
      <p>Groove-Vault's shareable channel links allow anyone with the link to listen to your channel without an account. This is a powerful feature — use it responsibly.</p>
      <ul>
        <li>Do not share channel links in contexts designed to circumvent copyright enforcement</li>
        <li>Do not use shared channels to distribute unauthorized commercial recordings at scale</li>
        <li>Do not share links to channels containing any content that violates these Guidelines</li>
        <li>Understand that once you share a link publicly, anyone may access your channel</li>
      </ul>

      <h2>7. Technical Use Restrictions</h2>
      <p>To keep the platform running smoothly and fairly for everyone:</p>
      <ul>
        <li>Do not attempt to bypass or exploit the 10-minute track duration limit</li>
        <li>Do not attempt to exceed the 12-track-per-channel limit through technical means</li>
        <li>Do not use automated scripts, bots, or tools to create channels, upload tracks, or interact with the Service</li>
        <li>Do not attempt to reverse engineer, scrape, or copy the Groove-Vault platform</li>
        <li>Do not upload tracks that contain malicious code, viruses, or disruptive audio designed to harm playback systems</li>
      </ul>

      <h2>8. Enforcement</h2>
      <h3>8.1 Reporting Violations</h3>
      <p>If you encounter content or behavior that violates these Guidelines, please report it to us at <a href="mailto:support@groove-vault.com">support@groove-vault.com</a> and include:</p>
      <ul>
        <li>A description of the violation</li>
        <li>The channel name or link in question</li>
        <li>Any relevant context</li>
      </ul>
      <h3>8.2 Actions We May Take</h3>
      <p>Depending on the severity and frequency of violations, Groove-Vault may:</p>
      <div class="legal-table-wrap">
        <table class="legal-table">
          <thead><tr><th>Action</th><th>When Applied</th></tr></thead>
          <tbody>
            <tr><td>Content Removal</td><td>Specific channel or track removed for violating Guidelines</td></tr>
            <tr><td>Warning</td><td>First-time or minor violations — notice sent by email</td></tr>
            <tr><td>Temporary Suspension</td><td>Repeated or moderate violations — account access restricted</td></tr>
            <tr><td>Permanent Termination</td><td>Severe violations, repeated offenses, or legal violations</td></tr>
            <tr><td>DMCA Takedown</td><td>Copyright infringement — content removed, user notified</td></tr>
            <tr><td>Law Enforcement Referral</td><td>Illegal content (CSAM, credible threats, etc.)</td></tr>
          </tbody>
        </table>
      </div>
      <h3>8.3 Appeals</h3>
      <p>If you believe your content was removed or your account was suspended in error, you may appeal by emailing <a href="mailto:legal@groove-vault.com">legal@groove-vault.com</a> with the subject line "Content Appeal" or "Account Appeal." We will review appeals within 10 business days.</p>

      <h2>9. Updates to These Guidelines</h2>
      <p>Groove-Vault may update these Community Guidelines from time to time to address new situations or evolving standards. We will notify users of material changes via email. Your continued use of the Service after such updates constitutes acceptance of the revised Guidelines.</p>

      <h2>10. Contact</h2>
      <p>Questions about these Community Guidelines? Contact us:</p>
      <ul>
        <li><strong>Email:</strong> <a href="mailto:support@groove-vault.com">support@groove-vault.com</a></li>
        <li><strong>Legal:</strong> <a href="mailto:legal@groove-vault.com">legal@groove-vault.com</a></li>
        <li><strong>Website:</strong> <a href="https://groove-vault.com" target="_blank" rel="noopener">groove-vault.com</a></li>
      </ul>
      <p style="color:var(--text-muted);font-size:.82rem;margin-top:1.5rem;">— End of Community Guidelines —</p>
    </div>
  </div>
</div>

<?php require_once('inc/footer.inc.php'); ?>
