<?php
require_once __DIR__ . '/inc/functions.inc.php';

$pageTitle  = 'GrooveVault — Terms of Service';
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
      <div class="hero-badge mb-2"><i class="bi bi-file-earmark-text me-1"></i>LEGAL</div>
      <h1 class="section-title" style="font-size:2.6rem;">TERMS OF <span class="text-blue">SERVICE</span></h1>
      <p style="color:var(--text-muted);max-width:560px;margin:.4rem auto 0;">Please read these terms carefully before using GrooveVault.</p>
    </div>

    <div class="legal-meta">
      <div><div class="lbl">Company</div><div class="val">MSSRA, LLC</div></div>
      <div><div class="lbl">Effective Date</div><div class="val">June 26, 2026</div></div>
      <div><div class="lbl">Email</div><div class="val"><a href="mailto:legal@groove-vault.com" class="text-blue text-decoration-none">legal@groove-vault.com</a></div></div>
      <div><div class="lbl">Website</div><div class="val"><a href="https://groove-vault.com" target="_blank" rel="noopener" class="text-blue text-decoration-none">groove-vault.com</a></div></div>
    </div>

    <div class="legal-doc">
      <h2>1. Agreement to Terms</h2>
      <p>Welcome to Groove-Vault. These Terms of Service ("Terms") constitute a legally binding agreement between you ("User," "you," or "your") and Groove-Vault ("Company," "we," "us," or "our"). By accessing or using the Groove-Vault platform — including our website, mobile application, and all related services (collectively, the "Service") — you agree to be bound by these Terms.</p>
      <p>If you do not agree to these Terms in their entirety, you must immediately discontinue use of the Service. Your continued use of the Service following any modification to these Terms constitutes your acceptance of those modifications.</p>

      <h2>2. Description of Service</h2>
      <p>Groove-Vault is a subscription-based music channel platform that enables users to:</p>
      <ul>
        <li>Create named music channels containing up to 12 MP4 audio or video tracks per channel</li>
        <li>Upload MP4 files or provide MP4 links with a maximum individual track duration of 10 minutes</li>
        <li>Organize, reorder, shuffle, and play music within their channels</li>
        <li>Share music channels via unique links accessible to the public</li>
        <li>Manage multiple channels under a single account</li>
      </ul>
      <p>The Service is available via web browser and, where applicable, mobile applications for iOS and Android devices. Access to the full feature set requires an active paid subscription.</p>

      <h2>3. Eligibility</h2>
      <p>You must meet the following requirements to use the Service:</p>
      <ul>
        <li>You must be at least 13 years of age. If you are under 18, you must have parental or guardian consent.</li>
        <li>You must be a human individual. Accounts registered by automated means are prohibited.</li>
        <li>You must provide accurate, current, and complete registration information.</li>
        <li>You must not be prohibited from using the Service under applicable law.</li>
        <li>You must not have had a prior account terminated by Groove-Vault for violation of these Terms.</li>
      </ul>

      <h2>4. Account Registration &amp; Security</h2>
      <h3>4.1 Account Creation</h3>
      <p>To access the full features of the Service, you must register for an account by providing a display name, valid email address, and password. You agree to provide truthful and accurate information and to update it as necessary to keep it current.</p>
      <h3>4.2 Email Verification</h3>
      <p>Upon registration, you will be required to verify your email address. Access to certain features may be restricted until verification is complete.</p>
      <h3>4.3 Account Security</h3>
      <p>You are solely responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account. You agree to:</p>
      <ul>
        <li>Use a strong, unique password not shared with other services</li>
        <li>Notify Groove-Vault immediately at <a href="mailto:support@groove-vault.com">support@groove-vault.com</a> if you suspect unauthorized access</li>
        <li>Not share your account credentials with any third party</li>
        <li>Log out of your account after each session on shared devices</li>
      </ul>
      <p>Groove-Vault will not be liable for any loss or damage arising from your failure to comply with these security obligations.</p>

      <h2>5. Subscriptions &amp; Payments</h2>
      <h3>5.1 Subscription Plans</h3>
      <p>Access to Groove-Vault's channel creation and management features requires a paid subscription. We currently offer the following plans:</p>
      <div class="legal-table-wrap">
        <table class="legal-table">
          <thead><tr><th>Plan</th><th>Price</th><th>Features</th></tr></thead>
          <tbody>
            <tr><td>Groove Starter</td><td>$4.99/month</td><td>Up to 5 channels, 12 tracks per channel, shareable links, shuffle</td></tr>
            <tr><td>Groove Pro</td><td>$9.99/month</td><td>Up to 12 channels, 12 tracks per channel, shareable links, shuffle, priority support</td></tr>
            <tr><td>Groove Annual</td><td>$79.00/year</td><td>Everything in Groove Pro plus unlimited channels, 33% savings, early access to new features</td></tr>
          </tbody>
        </table>
      </div>
      <h3>5.2 Billing &amp; Payment</h3>
      <p>Subscription fees are billed in advance on a recurring basis (monthly or annually, depending on your plan). Payment is processed through our third-party payment processor, PayPal. By subscribing, you authorize Groove-Vault and/or PayPal to charge your designated payment method on each renewal date.</p>
      <h3>5.3 Renewal</h3>
      <p>Subscriptions are purchased for a fixed term — one month for monthly plans (Starter, Pro) or one year for the Annual plan. Subscriptions do not renew automatically and you are never charged automatically. When your term ends, access to channel creation, management, and playback pauses until you purchase a new subscription term. Your existing channels are retained while your subscription is lapsed.</p>
      <h3>5.4 Cancellation</h3>
      <p>You may cancel your subscription at any time through your account settings or by contacting us at <a href="mailto:support@groove-vault.com">support@groove-vault.com</a>. Cancellation takes effect at the end of the current billing period. No refunds are provided for partial subscription periods, except as required by applicable law.</p>
      <h3>5.5 Price Changes</h3>
      <p>Groove-Vault reserves the right to modify subscription pricing at any time. We will provide at least 30 days' advance notice of any price changes via email to your registered address. Your continued use of the Service after the effective date of a price change constitutes your acceptance of the new price.</p>
      <h3>5.6 Free Trial</h3>
      <p>Groove-Vault may, at its discretion, offer free trial periods. At the end of any free trial, your account will automatically convert to a paid subscription unless you cancel before the trial ends.</p>

      <h2>6. User Content</h2>
      <h3>6.1 Ownership</h3>
      <p>You retain full ownership of all content you upload, link, or otherwise submit to the Service ("User Content"), including MP4 audio and video files and channel names. By submitting User Content, you represent and warrant that you own all rights to that content or have obtained all necessary licenses, permissions, and authorizations to use and share it.</p>
      <h3>6.2 License to Groove-Vault</h3>
      <p>By submitting User Content, you grant Groove-Vault a non-exclusive, royalty-free, worldwide license to host, store, and display your User Content solely for the purpose of operating and providing the Service, including enabling the sharing features of the platform.</p>
      <h3>6.3 Content Restrictions</h3>
      <p>You agree not to upload, link to, or share any User Content that:</p>
      <ul>
        <li>Infringes any copyright, trademark, patent, trade secret, or other intellectual property right of any party</li>
        <li>Contains sexually explicit, pornographic, or obscene material</li>
        <li>Depicts or promotes violence, self-harm, or harm to others</li>
        <li>Constitutes hate speech targeting individuals or groups based on race, ethnicity, religion, gender, sexual orientation, disability, or national origin</li>
        <li>Contains malware, viruses, or any harmful or disruptive code</li>
        <li>Violates any applicable federal, state, or local law or regulation</li>
        <li>You do not have the legal right to upload or share</li>
      </ul>
      <h3>6.4 Copyright &amp; DMCA</h3>
      <p>Groove-Vault respects intellectual property rights and expects users to do the same. If you believe that your copyrighted work has been used on the Service in a way that constitutes infringement, please submit a DMCA takedown notice to <a href="mailto:legal@groove-vault.com">legal@groove-vault.com</a> including:</p>
      <ul>
        <li>A description of the copyrighted work claimed to be infringed</li>
        <li>A description of where the infringing material is located on the Service</li>
        <li>Your contact information (name, address, phone, email)</li>
        <li>A statement that you have a good faith belief the use is not authorized</li>
        <li>A statement under penalty of perjury that the information in the notice is accurate</li>
        <li>Your physical or electronic signature</li>
      </ul>
      <h3>6.5 Content Removal</h3>
      <p>Groove-Vault reserves the right to remove any User Content at any time, with or without notice, that we believe violates these Terms or is otherwise objectionable, without liability to you.</p>

      <h2>7. Track &amp; Channel Restrictions</h2>
      <p>The following technical restrictions apply to all user accounts:</p>
      <ul>
        <li>Maximum of 12 tracks per channel</li>
        <li>Maximum track duration of 10 minutes (600 seconds) per MP4 file or link</li>
        <li>Supported file format: MP4 (.mp4) only</li>
        <li>Groove Starter plan: maximum of 5 channels</li>
        <li>Groove Pro plan: maximum of 12 channels</li>
        <li>Groove Annual plan: unlimited channels</li>
      </ul>
      <p>Attempts to circumvent these restrictions may result in account suspension or termination.</p>

      <h2>8. Prohibited Conduct</h2>
      <p>You agree not to engage in any of the following:</p>
      <ul>
        <li>Using the Service for any unlawful purpose or in violation of any applicable law</li>
        <li>Attempting to gain unauthorized access to any portion of the Service or any other user's account</li>
        <li>Scraping, data mining, or harvesting data from the Service without our written permission</li>
        <li>Transmitting spam, unsolicited messages, or promotional materials</li>
        <li>Impersonating any person or entity, or falsely stating your affiliation with any person or entity</li>
        <li>Interfering with or disrupting the integrity or performance of the Service or servers</li>
        <li>Using the Service to distribute malware or other harmful software</li>
        <li>Uploading content that violates the rights of any third party</li>
        <li>Creating multiple accounts to circumvent plan limits or suspensions</li>
        <li>Reselling or sublicensing your account or subscription to any third party</li>
      </ul>

      <h2>9. Intellectual Property</h2>
      <p>The Groove-Vault name, logo, platform design, software, and all related content (excluding User Content) are the exclusive property of Groove-Vault and are protected by United States and international copyright, trademark, and other intellectual property laws. You may not copy, modify, distribute, sell, or lease any part of the Service, nor may you reverse engineer or attempt to extract the source code of our software.</p>

      <h2>10. Privacy</h2>
      <p>Your use of the Service is also governed by our <a href="privacy.php">Privacy Policy</a>, which is incorporated into these Terms by reference. By using the Service, you consent to the collection and use of information as described in our Privacy Policy. Our Privacy Policy is available at <a href="privacy.php">groove-vault.com/privacy</a> or by contacting us at <a href="mailto:legal@groove-vault.com">legal@groove-vault.com</a>.</p>

      <h2>11. Third-Party Services</h2>
      <p>The Service may integrate with or link to third-party services, including PayPal for payment processing. Your use of such third-party services is governed by those services' own terms of service and privacy policies. Groove-Vault is not responsible for the practices or content of any third-party services.</p>

      <h2>12. Disclaimers</h2>
      <p>THE SERVICE IS PROVIDED ON AN "AS IS" AND "AS AVAILABLE" BASIS WITHOUT WARRANTIES OF ANY KIND, EITHER EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, AND NON-INFRINGEMENT. GROOVE-VAULT DOES NOT WARRANT THAT THE SERVICE WILL BE UNINTERRUPTED, ERROR-FREE, OR FREE OF VIRUSES OR OTHER HARMFUL COMPONENTS.</p>

      <h2>13. Limitation of Liability</h2>
      <p>TO THE MAXIMUM EXTENT PERMITTED BY APPLICABLE LAW, GROOVE-VAULT AND ITS OFFICERS, DIRECTORS, EMPLOYEES, AGENTS, AND LICENSORS SHALL NOT BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES, INCLUDING BUT NOT LIMITED TO LOSS OF PROFITS, DATA, GOODWILL, OR OTHER INTANGIBLE LOSSES, RESULTING FROM: (A) YOUR ACCESS TO OR USE OF, OR INABILITY TO ACCESS OR USE, THE SERVICE; (B) ANY CONDUCT OR CONTENT OF ANY THIRD PARTY ON THE SERVICE; (C) ANY USER CONTENT; OR (D) UNAUTHORIZED ACCESS, USE, OR ALTERATION OF YOUR TRANSMISSIONS OR CONTENT.</p>

      <h2>14. Indemnification</h2>
      <p>You agree to defend, indemnify, and hold harmless Groove-Vault and its officers, directors, employees, and agents from and against any claims, liabilities, damages, judgments, awards, losses, costs, expenses, or fees (including reasonable attorneys' fees) arising out of or relating to your violation of these Terms or your use of the Service, including your User Content.</p>

      <h2>15. Termination</h2>
      <h3>15.1 Termination by You</h3>
      <p>You may terminate your account at any time by contacting us at <a href="mailto:support@groove-vault.com">support@groove-vault.com</a> or by cancelling your subscription through your account settings. Termination does not entitle you to a refund of any prepaid fees.</p>
      <h3>15.2 Termination by Groove-Vault</h3>
      <p>Groove-Vault reserves the right to suspend or terminate your account, with or without notice, for any violation of these Terms, for conduct that we determine in our sole discretion is harmful to other users or to the Service, or for any other reason at our sole discretion. Upon termination, your right to use the Service ceases immediately.</p>

      <h2>16. Governing Law &amp; Dispute Resolution</h2>
      <p>These Terms are governed by and construed in accordance with the laws of the State of Florida, without regard to its conflict of law provisions. Any dispute arising from these Terms or your use of the Service shall be resolved exclusively in the state or federal courts located in Sumter County, Florida, and you consent to personal jurisdiction in those courts.</p>
      <p>Before initiating any legal action, you agree to first attempt to resolve any dispute informally by contacting Groove-Vault at <a href="mailto:legal@groove-vault.com">legal@groove-vault.com</a>. We will attempt to resolve the dispute within 30 days.</p>

      <h2>17. Changes to These Terms</h2>
      <p>Groove-Vault reserves the right to modify these Terms at any time. We will notify you of material changes by email to your registered address and by posting the updated Terms on our website with a new effective date. Your continued use of the Service after the effective date of any changes constitutes your acceptance of the revised Terms.</p>

      <h2>18. Miscellaneous</h2>
      <ul>
        <li><strong>Entire Agreement:</strong> These Terms, together with our Privacy Policy and Community Guidelines, constitute the entire agreement between you and Groove-Vault regarding the Service.</li>
        <li><strong>Severability:</strong> If any provision of these Terms is found to be unenforceable, that provision will be limited or eliminated to the minimum extent necessary, and the remaining provisions will remain in full force and effect.</li>
        <li><strong>No Waiver:</strong> Our failure to enforce any right or provision of these Terms will not be considered a waiver of those rights.</li>
        <li><strong>Assignment:</strong> You may not assign or transfer your rights under these Terms without our prior written consent. Groove-Vault may assign its rights without restriction.</li>
        <li><strong>Contact:</strong> For any questions about these Terms, contact us at <a href="mailto:legal@groove-vault.com">legal@groove-vault.com</a>.</li>
      </ul>

      <h2>19. Contact Information</h2>
      <ul>
        <li><strong>Company Name:</strong> MSSRA, LLC</li>
        <li><strong>Legal Email:</strong> <a href="mailto:legal@groove-vault.com">legal@groove-vault.com</a></li>
        <li><strong>Support Email:</strong> <a href="mailto:support@groove-vault.com">support@groove-vault.com</a></li>
        <li><strong>Website:</strong> <a href="https://groove-vault.com" target="_blank" rel="noopener">groove-vault.com</a></li>
      </ul>
      <p style="color:var(--text-muted);font-size:.82rem;margin-top:1.5rem;">— End of Terms of Service —</p>
    </div>
  </div>
</div>

<?php require_once('inc/footer.inc.php'); ?>
