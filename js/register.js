document.addEventListener('DOMContentLoaded', function() {
    const planCards = document.querySelectorAll('.plan-card');
    const selectedPlanInput = document.getElementById('selected_plan');

    if (planCards.length > 0) {
        planCards.forEach(card => {
            card.addEventListener('click', function() {
                // Remove selected class from all cards
                planCards.forEach(c => c.classList.remove('selected'));
                
                // Add selected class to clicked card
                this.classList.add('selected');
                
                // Update hidden input
                if(selectedPlanInput) {
                    selectedPlanInput.value = this.dataset.plan;
                }
            });
        });

        // Select Basic plan by default if it exists
        const basicPlan = document.querySelector('.plan-card[data-plan="Basic"]');
        if (basicPlan) {
            basicPlan.click();
        }
    }

    // Form validation
    const form = document.getElementById('registerForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                // You might want to show a more user-friendly error message here
                alert('Passwords do not match!');
            }
        });
    }
}); 