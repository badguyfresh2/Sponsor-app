<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$user = require_auth();

$page_title = 'Help & Support';
$show_back = true;
$back_url = BASE_URL . '/sponsor/profile.php';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="space-y-4">
  <!-- Contact Support Card -->
  <div class="app-card bg-gradient-to-r from-blue-600 to-teal-600 text-white p-4">
    <h3 class="font-bold text-sm mb-1">Need Assistance?</h3>
    <p class="text-xs text-blue-100 mb-3">Our dedicated sponsorship coordinators are available daily to answer any questions.</p>
    <div class="flex items-center gap-2">
      <a href="mailto:support@sponsorapp.org" class="btn btn-sm bg-white text-blue-700 font-bold border-none hover:bg-blue-50 text-xs">
        <i class="fa-regular fa-envelope"></i> Email Support
      </a>
      <a href="tel:+256700000000" class="btn btn-sm bg-white/20 text-white font-bold border-none hover:bg-white/30 text-xs">
        <i class="fa-solid fa-phone"></i> Call Hotline
      </a>
    </div>
  </div>

  <!-- FAQs Accordion -->
  <div class="app-card p-4">
    <h3 class="card-title text-sm mb-3">Frequently Asked Questions</h3>

    <div class="space-y-3 divide-y divide-slate-100 text-xs">
      <details class="pt-2 group cursor-pointer" open>
        <summary class="font-bold text-slate-900 flex items-center justify-between list-none py-1">
          <span>How does child sponsorship work?</span>
          <i class="fa-solid fa-chevron-down text-slate-400 text-[10px] group-open:rotate-180 transition-transform"></i>
        </summary>
        <p class="text-slate-600 leading-relaxed pt-1.5">
          Your monthly contribution covers tuition fees, scholastic materials, nutritious meals, and primary healthcare through verified local partner organizations. You receive regular progress letters and academic report updates directly in the app.
        </p>
      </details>

      <details class="pt-2 group cursor-pointer">
        <summary class="font-bold text-slate-900 flex items-center justify-between list-none py-1">
          <span>What payment methods are supported?</span>
          <i class="fa-solid fa-chevron-down text-slate-400 text-[10px] group-open:rotate-180 transition-transform"></i>
        </summary>
        <p class="text-slate-600 leading-relaxed pt-1.5">
          We support MTN Mobile Money (MoMo), Airtel Money, and Visa/Mastercard debit and credit cards. Automatic recurring contributions can be paused or resumed anytime from your dashboard.
        </p>
      </details>

      <details class="pt-2 group cursor-pointer">
        <summary class="font-bold text-slate-900 flex items-center justify-between list-none py-1">
          <span>How are community organizations verified?</span>
          <i class="fa-solid fa-chevron-down text-slate-400 text-[10px] group-open:rotate-180 transition-transform"></i>
        </summary>
        <p class="text-slate-600 leading-relaxed pt-1.5">
          Every organization undergoes in-person field audits, legal registration verification in Uganda, and regular financial transparency checks before listing any beneficiaries.
        </p>
      </details>

      <details class="pt-2 group cursor-pointer">
        <summary class="font-bold text-slate-900 flex items-center justify-between list-none py-1">
          <span>Can I write to my sponsored child?</span>
          <i class="fa-solid fa-chevron-down text-slate-400 text-[10px] group-open:rotate-180 transition-transform"></i>
        </summary>
        <p class="text-slate-600 leading-relaxed pt-1.5">
          Yes! You can exchange messages with the supervising field coordinator via the in-app Messages tab, and they will deliver printed letters and drawings to the child.
        </p>
      </details>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
