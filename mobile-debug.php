<?php
$pageTitle = 'Mobile Menu Debug';
$page_css = 'businesses.css';
include 'includes/header_main.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h1>🔧 Mobile Menu Debug</h1>
            <p>Testing mobile menu functionality to identify the issue.</p>
            
            <div class="card">
                <div class="card-header">
                    <h5>Debug Information</h5>
                </div>
                <div class="card-body">
                    <div id="debugInfo">
                        <p><strong>Screen Width:</strong> <span id="screenWidth"></span></p>
                        <p><strong>Mobile Menu Button Found:</strong> <span id="buttonFound">Checking...</span></p>
                        <p><strong>Mobile Nav Menu Found:</strong> <span id="menuFound">Checking...</span></p>
                        <p><strong>Click Events Attached:</strong> <span id="eventsAttached">Checking...</span></p>
                        <p><strong>Last Click Time:</strong> <span id="lastClick">None</span></p>
                        <p><strong>Menu State:</strong> <span id="menuState">Closed</span></p>
                        <p><strong>Console Logs:</strong> <span id="consoleLogs">Check browser console</span></p>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <h3>Test Instructions:</h3>
                <ol>
                    <li>Resize browser to mobile size (below 1024px)</li>
                    <li>Check the debug information above</li>
                    <li>Click the hamburger menu button</li>
                    <li>Watch for changes in the debug info</li>
                    <li>Check browser console for any errors</li>
                </ol>
            </div>
            
            <div class="mt-4">
                <h3>Manual Test Button</h3>
                <button id="manualTestBtn" class="btn btn-primary">Test Mobile Menu Programmatically</button>
                <p class="mt-2"><small>This will try to open the mobile menu directly via JavaScript.</small></p>
            </div>
            
            <div class="mt-4">
                <h3>Element Inspector</h3>
                <div id="elementInfo">
                    <p><strong>Mobile Menu Button HTML:</strong></p>
                    <pre id="buttonHTML" style="background: #f8f9fa; padding: 10px; border-radius: 5px; font-size: 12px;"></pre>
                    
                    <p class="mt-3"><strong>Mobile Nav Menu HTML:</strong></p>
                    <pre id="menuHTML" style="background: #f8f9fa; padding: 10px; border-radius: 5px; font-size: 12px;"></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Mobile debug page loaded');
    
    // Update screen width
    function updateScreenWidth() {
        document.getElementById('screenWidth').textContent = window.innerWidth + 'px';
    }
    
    // Check if elements exist
    function checkElements() {
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const mobileNavMenu = document.getElementById('mobileNavMenu');
        
        // Update status
        document.getElementById('buttonFound').textContent = mobileMenuToggle ? '✅ Found' : '❌ Not Found';
        document.getElementById('menuFound').textContent = mobileNavMenu ? '✅ Found' : '❌ Not Found';
        
        // Show HTML
        if (mobileMenuToggle) {
            document.getElementById('buttonHTML').textContent = mobileMenuToggle.outerHTML;
        }
        if (mobileNavMenu) {
            document.getElementById('menuHTML').textContent = mobileNavMenu.outerHTML;
        }
        
        return { mobileMenuToggle, mobileNavMenu };
    }
    
    // Test click functionality
    function testClickFunctionality() {
        const { mobileMenuToggle, mobileNavMenu } = checkElements();
        
        if (mobileMenuToggle && mobileNavMenu) {
            // Add our own click listener for testing
            mobileMenuToggle.addEventListener('click', function(e) {
                console.log('🔧 Manual click detected on mobile menu button');
                document.getElementById('lastClick').textContent = new Date().toLocaleTimeString();
                
                // Toggle menu manually
                if (mobileNavMenu.classList.contains('active')) {
                    mobileNavMenu.classList.remove('active');
                    document.getElementById('menuState').textContent = 'Closed';
                } else {
                    mobileNavMenu.classList.add('active');
                    document.getElementById('menuState').textContent = 'Open';
                }
                
                document.getElementById('eventsAttached').textContent = '✅ Click detected';
            });
            
            document.getElementById('eventsAttached').textContent = '✅ Events attached';
        } else {
            document.getElementById('eventsAttached').textContent = '❌ Cannot attach events - elements missing';
        }
    }
    
    // Manual test button
    document.getElementById('manualTestBtn').addEventListener('click', function() {
        const mobileNavMenu = document.getElementById('mobileNavMenu');
        if (mobileNavMenu) {
            mobileNavMenu.classList.add('active');
            document.getElementById('menuState').textContent = 'Open (Manual)';
            console.log('🔧 Manual menu open triggered');
        }
    });
    
    // Monitor menu state changes
    const mobileNavMenu = document.getElementById('mobileNavMenu');
    if (mobileNavMenu) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const isActive = mobileNavMenu.classList.contains('active');
                    document.getElementById('menuState').textContent = isActive ? 'Open' : 'Closed';
                    console.log('🔧 Menu state changed:', isActive ? 'Open' : 'Closed');
                }
            });
        });
        
        observer.observe(mobileNavMenu, {
            attributes: true,
            attributeFilter: ['class']
        });
    }
    
    // Initialize
    updateScreenWidth();
    checkElements();
    testClickFunctionality();
    
    // Update on resize
    window.addEventListener('resize', updateScreenWidth);
    
    // Log any errors
    window.addEventListener('error', function(e) {
        console.error('🔧 Error detected:', e.error);
        document.getElementById('consoleLogs').textContent = '❌ Error: ' + e.error.message;
    });
    
    console.log('🔧 Mobile debug setup complete');
});
</script>

<?php include 'includes/footer_main.php'; ?> 