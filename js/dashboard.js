// Activate Bootstrap tabs from hash
document.addEventListener('DOMContentLoaded', () => {
    const hash = window.location.hash;
    if (hash) {
        const tabTrigger = document.querySelector(`button[data-bs-target='${hash}']`);
        if (tabTrigger) {
            new bootstrap.Tab(tabTrigger).show();
        }
    }
}); 