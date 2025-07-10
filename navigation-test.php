<?php
$pageTitle = "Navigation Test - JShuk";
$metaDescription = "Test page for the dual-mode navigation system";
$current_page = 'navigation-test';
require_once 'includes/header_main.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">🧭 Navigation System Test</h1>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">📱 Mobile Navigation (Below 1024px)</h5>
                </div>
                <div class="card-body">
                    <p>On mobile devices and screens below 1024px width:</p>
                    <ul>
                        <li>✅ Hamburger menu in top right</li>
                        <li>✅ Side drawer slides in from right</li>
                        <li>✅ Swipe-to-close functionality</li>
                        <li>✅ Bottom navigation bar</li>
                        <li>✅ Touch-friendly spacing</li>
                        <li>✅ Keyboard navigation support</li>
                    </ul>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">🖥️ Desktop Navigation (1024px and up)</h5>
                </div>
                <div class="card-body">
                    <p>On desktop screens 1024px and wider:</p>
                    <ul>
                        <li>✅ Full horizontal navigation bar</li>
                        <li>✅ All links visible without clicking</li>
                        <li>✅ Hover effects and active states</li>
                        <li>✅ User dropdown menu</li>
                        <li>✅ Responsive spacing for wide screens</li>
                        <li>✅ SEO-friendly visible links</li>
                    </ul>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">♿ Accessibility Features</h5>
                </div>
                <div class="card-body">
                    <ul>
                        <li>✅ ARIA labels and roles</li>
                        <li>✅ Keyboard navigation</li>
                        <li>✅ Focus management</li>
                        <li>✅ Screen reader support</li>
                        <li>✅ High contrast colors</li>
                        <li>✅ Semantic HTML structure</li>
                    </ul>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">🎯 Breakpoint Testing</h5>
                </div>
                <div class="card-body">
                    <p>Current screen width: <strong id="screenWidth">Calculating...</strong></p>
                    <p>Navigation mode: <strong id="navMode">Detecting...</strong></p>
                    
                    <div class="alert alert-info">
                        <strong>Test Instructions:</strong>
                        <ol class="mb-0 mt-2">
                            <li>Resize your browser window to test the breakpoint</li>
                            <li>At 1024px and above: Desktop navigation should appear</li>
                            <li>Below 1024px: Mobile navigation should appear</li>
                            <li>Test the hamburger menu on mobile</li>
                            <li>Try keyboard navigation (Tab, Arrow keys, Escape)</li>
                        </ol>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">🔗 Navigation Links Test</h5>
                </div>
                <div class="card-body">
                    <p>All navigation links should work correctly:</p>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Main Navigation:</h6>
                            <ul>
                                <li><a href="/index.php">Home</a></li>
                                <li><a href="/businesses.php">Browse Businesses</a></li>
                                <li><a href="/london.php">London</a></li>
                                <li><a href="/recruitment.php">Jobs</a></li>
                                <li><a href="/classifieds.php">Classifieds</a></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>User Navigation:</h6>
                            <ul>
                                <li><a href="/auth/login.php">Login</a></li>
                                <li><a href="/auth/register.php">Sign Up</a></li>
                                <li><a href="/users/dashboard.php">Dashboard</a></li>
                                <li><a href="/users/profile.php">Edit Profile</a></li>
                                <li><a href="/users/my_businesses.php">My Businesses</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Screen width detection
function updateScreenInfo() {
    const width = window.innerWidth;
    const mode = width >= 1024 ? 'Desktop' : 'Mobile';
    
    document.getElementById('screenWidth').textContent = width + 'px';
    document.getElementById('navMode').textContent = mode;
}

// Update on load and resize
window.addEventListener('load', updateScreenInfo);
window.addEventListener('resize', updateScreenInfo);

// Log navigation events for debugging
document.addEventListener('DOMContentLoaded', function() {
    console.log('🧭 Navigation Test Page Loaded');
    console.log('Screen width:', window.innerWidth);
    console.log('Navigation mode:', window.innerWidth >= 1024 ? 'Desktop' : 'Mobile');
});
</script>

<?php require_once 'includes/footer.php'; ?> 