document.addEventListener('DOMContentLoaded', function() {
    // Logic for toggling monthly/annual pricing
    const priceToggle = document.getElementById('priceToggle');
    if (priceToggle) {
        priceToggle.addEventListener('change', function() {
            const isAnnual = this.checked;
            document.querySelectorAll('.plan-card').forEach(card => {
                const monthlyPrice = card.querySelector('.price-monthly');
                const annualPrice = card.querySelector('.price-annual');
                if (isAnnual) {
                    monthlyPrice.style.display = 'none';
                    annualPrice.style.display = 'block';
                } else {
                    monthlyPrice.style.display = 'block';
                    annualPrice.style.display = 'none';
                }
            });
        });
    }

    // Tooltip initialization
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Handle plan selection form submission
    const planForms = document.querySelectorAll('.plan-select-form');
    planForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const planId = this.querySelector('input[name="plan_id"]').value;
            const action = this.querySelector('input[name="action"]').value;
            window.location.href = `/jshuk/payment/checkout.php?plan_id=${planId}&action=${action}`;
        });
    });

    // Handle ad slot booking form submission
    const adForms = document.querySelectorAll('.ad-select-form');
    adForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const slotId = this.querySelector('input[name="slot_id"]').value;
            window.location.href = `/jshuk/payment/advertising_checkout.php?slot=${slotId}`;
        });
    });
});
