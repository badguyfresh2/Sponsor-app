/**
 * Discover Screen Interactions
 */

document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('discoverSearchInput');
  const beneficiaryContainer = document.getElementById('beneficiaryListContainer');
  const filterChips = document.querySelectorAll('.filter-chip');
  let searchTimeout = null;
  let activeCategory = 'all';

  function fetchBeneficiaries() {
    const query = searchInput ? searchInput.value.trim() : '';
    const category = activeCategory !== 'all' ? activeCategory : '';

    if (beneficiaryContainer) {
      beneficiaryContainer.classList.add('opacity-50');
    }

    const params = new URLSearchParams();
    if (query) params.append('q', query);
    if (category) params.append('category', category);

    fetch(`${window.BASE_URL || ''}/api/search.php?${params.toString()}`)
      .then(res => res.json())
      .then(data => {
        if (beneficiaryContainer) {
          beneficiaryContainer.classList.remove('opacity-50');
          if (data.html) {
            beneficiaryContainer.innerHTML = data.html;
          } else {
            beneficiaryContainer.innerHTML = `
              <div class="app-card text-center py-8">
                <i class="fa-solid fa-user-slash text-4xl text-slate-300 mb-3"></i>
                <p class="font-bold text-slate-700">No beneficiaries found</p>
                <p class="text-xs text-slate-500 mt-1">Try adjusting your keywords or category filters.</p>
              </div>`;
          }
        }
      })
      .catch(err => {
        console.error(err);
        if (beneficiaryContainer) beneficiaryContainer.classList.remove('opacity-50');
      });
  }

  if (searchInput) {
    searchInput.addEventListener('input', () => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(fetchBeneficiaries, 350);
    });
  }

  filterChips.forEach(chip => {
    chip.addEventListener('click', (e) => {
      e.preventDefault();
      filterChips.forEach(c => c.classList.remove('active'));
      chip.classList.add('active');
      activeCategory = chip.getAttribute('data-category') || 'all';
      fetchBeneficiaries();
    });
  });
});
