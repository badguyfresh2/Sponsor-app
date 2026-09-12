/**
 * Sponsor Dashboard interactive script
 */

document.addEventListener('DOMContentLoaded', () => {
  // Quick Donate Modal amount selector
  const amountPills = document.querySelectorAll('.donate-amount-pill');
  const customAmountInput = document.getElementById('customDonateAmount');

  amountPills.forEach(pill => {
    pill.addEventListener('click', () => {
      amountPills.forEach(p => p.classList.remove('active', 'border-blue-600', 'bg-blue-50', 'text-blue-600'));
      pill.classList.add('active', 'border-blue-600', 'bg-blue-50', 'text-blue-600');
      if (customAmountInput) {
        customAmountInput.value = pill.getAttribute('data-amount');
      }
    });
  });

  if (customAmountInput) {
    customAmountInput.addEventListener('input', () => {
      amountPills.forEach(p => p.classList.remove('active', 'border-blue-600', 'bg-blue-50', 'text-blue-600'));
    });
  }
});
