<?php
require_once __DIR__ . '/inc/functions.inc.php';

$pageTitle  = 'GrooveVault — Privacy Policy';
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
</style>

<div class="container" style="padding-top:6rem;padding-bottom:3rem;">
  <div class="legal-wrap">
    <div class="text-center mb-4">
      <div class="hero-badge mb-2"><i class="bi bi-shield-lock me-1"></i>LEGAL</div>
      <h1 class="section-title" style="font-size:2.6rem;">PRIVACY <span class="text-blue">POLICY</span></h1>
      <p style="color:var(--text-muted);max-width:560px;margin:.4rem auto 0;">Your privacy matters to us — here's how we collect, use and protect your data.</p>
    </div>

    <div class="legal-meta">
      <div><div class="lbl">Company</div><div class="val">MSSRA, LLC</div></div>
      <div><div class="lbl">Effective Date</div><div class="val">June 26, 2026</div></div>
      <div><div class="lbl">Email</div><div class="val"><a href="mailto:legal@groove-vault.com" class="text-blue text-decoration-none">legal@groove-vault.com</a></div></div>
      <div><div class="lbl">Website</div><div class="val"><a href="https://groove-vault.com" target="_blank" rel="noopener" class="text-blue text-decoration-none">groove-vault.com</a></div></div>
    </div>

    <div class="legal-doc">
      <h2>1. Introduction</h2>
      <p>Groove-Vault ("we," "us," or "our") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our website and mobile applications (the "Service"). Please read this policy carefully. By using the Service, you consent to the practices described herein.</p>
      <p>If you do not agree with the terms of this Privacy Policy, please discontinue use of the Service.</p>

      <h2>2. Information We Collect</h2>
      <h3>2.1 Information You Provide Directly</h3>
      <p>When you register for and use the Service, we may collect the following information:</p>
      <ul>
        <li><strong>Account Information:</strong> display name, email address, and password (stored in hashed form)</li>
        <li><strong>Subscription Information:</strong> your selected subscription plan and payment confirmation data</li>
        <li><strong>User Content:</strong> channel names, track titles, and MP4 file URLs or uploaded file data you provide</li>
        <li><strong>Communications:</strong> messages you send us via email or support channels</li>
      </ul>
      <h3>2.2 Information Collected Automatically</h3>
      <p>When you access the Service, certain information may be collected automatically:</p>
      <ul>
        <li><strong>Device Information:</strong> device type, operating system version, and browser type</li>
        <li><strong>Usage Data:</strong> pages visited, features used, time spent on the Service, and click patterns</li>
        <li><strong>Log Data:</strong> IP address, access times, and referring URLs</li>
        <li><strong>Local Storage Data:</strong> the Service uses your browser's localStorage to store account session data, channel information, and user preferences on your device</li>
      </ul>
      <h3>2.3 Information from Third Parties</h3>
      <p>We may receive limited information from third-party services we integrate with:</p>
      <ul>
        <li><strong>PayPal:</strong> transaction confirmation data (we do not store your full payment card number or bank account details — these are handled exclusively by PayPal)</li>
      </ul>

      <h2>3. How We Use Your Information</h2>
      <p>We use the information we collect for the following purposes:</p>
      <ul>
        <li><strong>Account Management:</strong> to create and maintain your account, verify your email address, and authenticate your identity</li>
        <li><strong>Service Delivery:</strong> to provide, operate, and improve the Groove-Vault platform and its features</li>
        <li><strong>Subscription Processing:</strong> to process payments, manage billing, and send subscription-related communications</li>
        <li><strong>Customer Support:</strong> to respond to your inquiries, troubleshoot issues, and provide technical assistance</li>
        <li><strong>Service Improvements:</strong> to analyze usage patterns and develop new features</li>
        <li><strong>Legal Compliance:</strong> to comply with applicable laws and regulations and to enforce our Terms of Service</li>
        <li><strong>Communications:</strong> to send you service announcements, updates, and, where permitted, marketing communications</li>
      </ul>

      <h2>4. How We Share Your Information</h2>
      <p>Groove-Vault does not sell, rent, or trade your personal information to third parties. We may share your information in the following limited circumstances:</p>
      <h3>4.1 Service Providers</h3>
      <p>We may share information with third-party vendors who provide services on our behalf, including payment processing (PayPal), cloud hosting, and analytics. These providers are contractually obligated to use your information only to provide services to us and in accordance with this Privacy Policy.</p>
      <h3>4.2 Public Channel Sharing</h3>
      <p>When you share a channel via a shareable link, the channel name, track list, and track titles become publicly accessible to anyone with the link. Do not include sensitive personal information in channel or track names.</p>
      <h3>4.3 Legal Requirements</h3>
      <p>We may disclose your information if required to do so by law, court order, or governmental authority, or if we believe in good faith that such disclosure is necessary to protect the rights, property, or safety of Groove-Vault, our users, or the public.</p>
      <h3>4.4 Business Transfers</h3>
      <p>In the event of a merger, acquisition, bankruptcy, or sale of all or a portion of our assets, your information may be transferred as part of that transaction. We will notify you via email and/or a prominent notice on our website before your information is transferred and becomes subject to a different privacy policy.</p>

      <h2>5. Data Storage &amp; Security</h2>
      <h3>5.1 Where Data Is Stored</h3>
      <p>In the current version of the Service, user account data, channel data, and track information are primarily stored in your browser's localStorage on your own device. This means your data stays on your device and is not transmitted to Groove-Vault's servers in the web-based version of the platform.</p>
      <h3>5.2 Security Measures</h3>
      <p>We implement reasonable administrative, technical, and physical security measures to protect your information. These include:</p>
      <ul>
        <li>HTTPS encryption for all data transmitted between your browser and our servers</li>
        <li>Password hashing — we never store passwords in plaintext</li>
        <li>Access controls limiting employee access to personal information</li>
      </ul>
      <h3>5.3 Security Limitations</h3>
      <p>No method of transmission over the Internet or electronic storage is 100% secure. While we strive to protect your information, we cannot guarantee absolute security. You are responsible for maintaining the security of your account credentials.</p>
      <h3>5.4 Data Retention</h3>
      <p>We retain your account information for as long as your account is active or as needed to provide the Service. You may request deletion of your account and associated data by contacting us at <a href="mailto:legal@groove-vault.com">legal@groove-vault.com</a>. We will process deletion requests within 30 days, subject to any legal obligations to retain certain data.</p>

      <h2>6. Cookies &amp; Local Storage</h2>
      <p>The Service may use browser cookies and localStorage to maintain your session, remember your preferences, and improve performance. You may control cookie settings through your browser settings; however, disabling cookies may affect your ability to use certain features of the Service. The Service's use of localStorage is integral to its operation and cannot be disabled without affecting core functionality.</p>

      <h2>7. Children's Privacy</h2>
      <p>The Service is not directed to children under the age of 13. We do not knowingly collect personal information from children under 13. If you are a parent or guardian and believe that your child has provided us with personal information, please contact us at <a href="mailto:legal@groove-vault.com">legal@groove-vault.com</a> and we will take steps to delete such information promptly. Users between ages 13 and 18 must have parental consent to use the Service.</p>

      <h2>8. Your Privacy Rights</h2>
      <h3>8.1 General Rights</h3>
      <p>Depending on your jurisdiction, you may have the following rights with respect to your personal information:</p>
      <ul>
        <li><strong>Right to Access:</strong> request a copy of the personal information we hold about you</li>
        <li><strong>Right to Correction:</strong> request correction of inaccurate or incomplete information</li>
        <li><strong>Right to Deletion:</strong> request deletion of your personal information, subject to certain exceptions</li>
        <li><strong>Right to Portability:</strong> request a machine-readable copy of your data</li>
        <li><strong>Right to Opt Out:</strong> opt out of marketing communications at any time</li>
      </ul>
      <h3>8.2 Florida Residents</h3>
      <p>Florida residents may have additional rights under the Florida Digital Bill of Rights and other applicable Florida statutes. To exercise any of your rights, contact us at <a href="mailto:legal@groove-vault.com">legal@groove-vault.com</a> with the subject line "Privacy Request."</p>
      <h3>8.3 How to Exercise Your Rights</h3>
      <p>To exercise any of the rights listed above, please email <a href="mailto:legal@groove-vault.com">legal@groove-vault.com</a> with your request. We will respond within 30 days. We may need to verify your identity before processing your request.</p>

      <h2>9. Third-Party Links &amp; Services</h2>
      <p>The Service may contain links to third-party websites and services (such as social sharing buttons for X/Twitter, WhatsApp, and PayPal). This Privacy Policy does not apply to those third-party services. We encourage you to review the privacy policies of any third-party services you interact with.</p>

      <h2>10. Changes to This Privacy Policy</h2>
      <p>We may update this Privacy Policy from time to time. When we make material changes, we will notify you by email and by updating the effective date at the top of this policy. Your continued use of the Service after such changes constitutes your acceptance of the revised Privacy Policy.</p>

      <h2>11. Contact Us</h2>
      <p>If you have questions, concerns, or requests regarding this Privacy Policy, please contact us:</p>
      <ul>
        <li><strong>Company:</strong> MSSRA, LLC</li>
        <li><strong>Privacy Email:</strong> <a href="mailto:legal@groove-vault.com">legal@groove-vault.com</a></li>
        <li><strong>Support Email:</strong> <a href="mailto:support@groove-vault.com">support@groove-vault.com</a></li>
        <li><strong>Website:</strong> <a href="https://groove-vault.com" target="_blank" rel="noopener">groove-vault.com</a></li>
      </ul>
      <p style="color:var(--text-muted);font-size:.82rem;margin-top:1.5rem;">— End of Privacy Policy —</p>
    </div>
  </div>
</div>

<?php require_once('inc/footer.inc.php'); ?>
