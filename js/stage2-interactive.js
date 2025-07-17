/**
 * Stage 2: Interactive Polish Pass
 * Animated Social Proof Numbers
 */

document.addEventListener("DOMContentLoaded", function() {
    const statsSection = document.getElementById('trust-section');

    if (!statsSection) return;

    const animateValue = (element, start, end, duration) => {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const currentValue = Math.floor(progress * (end - start) + start);
            element.innerText = currentValue.toLocaleString() + '+';
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const statNumbers = entry.target.querySelectorAll('.stat-number');
                statNumbers.forEach(numberEl => {
                    const target = parseInt(numberEl.dataset.target, 10);
                    animateValue(numberEl, 0, target, 2000); // Animate over 2 seconds
                });
                observer.unobserve(entry.target); // Animate only once
            }
        });
    }, { threshold: 0.5 }); // Trigger when 50% of the section is visible

    observer.observe(statsSection);
}); 