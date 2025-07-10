<?php
$pageTitle = 'Mobile Header Test';
$page_css = 'businesses.css';
include($_SERVER['DOCUMENT_ROOT'].'/includes/header_main.php');
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h1>Mobile Header Test Page</h1>
            <p>This page is designed to test the mobile header functionality.</p>
            
            <div class="alert alert-info">
                <h4>Test Instructions:</h4>
                <ol>
                    <li>Resize your browser window to mobile size (below 1024px width)</li>
                    <li>Check that the mobile header appears with logo and menu button</li>
                    <li>Click the hamburger menu button to open the navigation</li>
                    <li>Verify the menu slides in from the right</li>
                    <li>Test closing the menu by clicking the X or outside the menu</li>
                    <li>Check that the bottom navigation appears at the bottom</li>
                </ol>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5>Mobile Header Status</h5>
                </div>
                <div class="card-body">
                    <div id="mobileStatus">
                        <p><strong>Screen Width:</strong> <span id="screenWidth"></span></p>
                        <p><strong>Header Type:</strong> <span id="headerType"></span></p>
                        <p><strong>Mobile Menu:</strong> <span id="menuStatus">Not tested</span></p>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <h3>Test Content</h3>
                <p>This is some test content to ensure the page scrolls properly and the mobile header stays fixed at the top.</p>
                
                <?php for($i = 1; $i <= 20; $i++): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5>Test Section <?= $i ?></h5>
                            <p>This is test content section <?= $i ?>. It helps verify that the mobile header remains fixed while scrolling.</p>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update status information
    function updateStatus() {
        const width = window.innerWidth;
        const screenWidthEl = document.getElementById('screenWidth');
        const headerTypeEl = document.getElementById('headerType');
        
        screenWidthEl.textContent = width + 'px';
        
        if (width <= 1023) {
            headerTypeEl.textContent = 'Mobile Header (should be visible)';
            headerTypeEl.style.color = 'green';
        } else {
            headerTypeEl.textContent = 'Desktop Header (mobile hidden)';
            headerTypeEl.style.color = 'blue';
        }
    }
    
    // Test mobile menu functionality
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const mobileNavMenu = document.getElementById('mobileNavMenu');
    const menuStatusEl = document.getElementById('menuStatus');
    
    if (mobileMenuToggle && mobileNavMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            menuStatusEl.textContent = 'Menu opened';
            menuStatusEl.style.color = 'green';
        });
        
        // Listen for menu close
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    if (!mobileNavMenu.classList.contains('active')) {
                        menuStatusEl.textContent = 'Menu closed';
                        menuStatusEl.style.color = 'orange';
                    }
                }
            });
        });
        
        observer.observe(mobileNavMenu, {
            attributes: true,
            attributeFilter: ['class']
        });
    }
    
    // Initial status update
    updateStatus();
    
    // Update on resize
    window.addEventListener('resize', updateStatus);
    
    console.log('Mobile test page loaded successfully');
});
</script>

<?php include($_SERVER['DOCUMENT_ROOT'].'/includes/footer_main.php'); ?> 