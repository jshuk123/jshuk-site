<?php
$pageTitle = 'Mobile Menu Test';
$page_css = 'businesses.css';
include 'includes/header_main.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h1>🧪 Mobile Menu Test</h1>
            <p>This page is designed to test the mobile navigation menu functionality.</p>
            
            <div class="card">
                <div class="card-header">
                    <h5>Test Instructions</h5>
                </div>
                <div class="card-body">
                    <ol>
                        <li>Resize your browser window to mobile size (below 1024px width)</li>
                        <li>You should see the mobile header with a hamburger menu button</li>
                        <li>Click the hamburger menu button (☰) to open the mobile navigation</li>
                        <li>The menu should slide in from the right</li>
                        <li><strong>All menu links should be clearly visible in white text</strong></li>
                        <li>Test clicking on different menu items</li>
                        <li>Test the close button (×) to close the menu</li>
                    </ol>
                    
                    <div class="alert alert-info mt-3">
                        <strong>Expected Behavior:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Menu links should be white text on dark background</li>
                            <li>Icons should be white</li>
                            <li>Links should be clearly readable and tappable</li>
                            <li>Hover/focus states should highlight in gold (#ffd700)</li>
                            <li>Active page should be highlighted with gold background</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5>Debug Information</h5>
                </div>
                <div class="card-body">
                    <div id="debugInfo">
                        <p><strong>Screen Width:</strong> <span id="screenWidth"></span></p>
                        <p><strong>Mobile Menu Button:</strong> <span id="buttonStatus">Checking...</span></p>
                        <p><strong>Mobile Nav Menu:</strong> <span id="menuStatus">Checking...</span></p>
                        <p><strong>Menu State:</strong> <span id="menuState">Closed</span></p>
                        <p><strong>CSS Loaded:</strong> <span id="cssStatus">Checking...</span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Mobile menu test page loaded');
    
    // Update debug information
    function updateDebugInfo() {
        const screenWidth = window.innerWidth;
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const mobileNavMenu = document.getElementById('mobileNavMenu');
        
        document.getElementById('screenWidth').textContent = screenWidth + 'px';
        document.getElementById('buttonStatus').textContent = mobileMenuToggle ? 'Found' : 'Not Found';
        document.getElementById('menuStatus').textContent = mobileNavMenu ? 'Found' : 'Not Found';
        document.getElementById('cssStatus').textContent = 'Loaded';
        
        if (mobileNavMenu) {
            const isActive = mobileNavMenu.classList.contains('active');
            document.getElementById('menuState').textContent = isActive ? 'Open' : 'Closed';
        }
    }
    
    // Update on load and resize
    updateDebugInfo();
    window.addEventListener('resize', updateDebugInfo);
    
    // Monitor menu state changes
    const mobileNavMenu = document.getElementById('mobileNavMenu');
    if (mobileNavMenu) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    updateDebugInfo();
                }
            });
        });
        
        observer.observe(mobileNavMenu, {
            attributes: true,
            attributeFilter: ['class']
        });
    }
    
    // Test menu functionality
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            console.log('Mobile menu toggle clicked');
            setTimeout(updateDebugInfo, 100);
        });
    }
});
</script>

<?php include 'includes/footer_main.php'; ?> 