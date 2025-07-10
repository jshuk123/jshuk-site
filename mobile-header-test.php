<?php
$pageTitle = 'Mobile Header Test - Fixed';
$page_css = 'businesses.css';
include 'includes/header_main.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h1>✅ Mobile Header Test - FIXED</h1>
            <p>This page tests the mobile header after removing JavaScript code from the PHP template.</p>
            
            <div class="alert alert-success">
                <h4>✅ Issue Resolved:</h4>
                <p>The JavaScript code that was being output as text in the mobile header has been removed. The mobile header should now render properly as HTML.</p>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5>Mobile Header Status</h5>
                </div>
                <div class="card-body">
                    <div id="mobileStatus">
                        <p><strong>Screen Width:</strong> <span id="screenWidth"></span></p>
                        <p><strong>Header Type:</strong> <span id="headerType"></span></p>
                        <p><strong>Mobile Menu:</strong> <span id="menuStatus">Ready to test</span></p>
                        <p><strong>JavaScript Output:</strong> <span id="jsOutput" style="color: green;">✅ Clean - No raw JS in HTML</span></p>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <h3>Test Instructions:</h3>
                <ol>
                    <li>Resize your browser to mobile size (below 1024px)</li>
                    <li>Check that the mobile header appears with proper HTML structure</li>
                    <li>Click the hamburger menu button</li>
                    <li>Verify the menu opens without showing raw JavaScript code</li>
                    <li>Test navigation links in the mobile menu</li>
                </ol>
            </div>
            
            <div class="mt-4">
                <h3>Expected Results:</h3>
                <ul>
                    <li>✅ Mobile header displays with logo and menu button</li>
                    <li>✅ Menu opens/closes smoothly</li>
                    <li>✅ No raw JavaScript code visible in the HTML</li>
                    <li>✅ All navigation links work properly</li>
                    <li>✅ Bottom navigation appears on mobile</li>
                </ul>
            </div>
            
            <div class="mt-4">
                <h3>Test Content</h3>
                <p>Scroll down to test that the mobile header stays fixed at the top.</p>
                
                <?php for($i = 1; $i <= 10; $i++): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5>Test Section <?= $i ?></h5>
                            <p>This is test content section <?= $i ?>. The mobile header should remain fixed at the top while scrolling.</p>
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
            menuStatusEl.textContent = 'Menu opened - Working!';
            menuStatusEl.style.color = 'green';
        });
        
        // Listen for menu close
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    if (!mobileNavMenu.classList.contains('active')) {
                        menuStatusEl.textContent = 'Menu closed - Working!';
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
    
    // Check for any JavaScript code in the HTML
    const bodyText = document.body.innerText;
    if (bodyText.includes('function') || bodyText.includes('document.addEventListener')) {
        document.getElementById('jsOutput').textContent = '❌ Raw JavaScript found in HTML';
        document.getElementById('jsOutput').style.color = 'red';
    } else {
        document.getElementById('jsOutput').textContent = '✅ Clean - No raw JS in HTML';
        document.getElementById('jsOutput').style.color = 'green';
    }
    
    // Initial status update
    updateStatus();
    
    // Update on resize
    window.addEventListener('resize', updateStatus);
    
    console.log('✅ Mobile header test page loaded successfully');
});
</script>

<?php include 'includes/footer_main.php'; ?> 