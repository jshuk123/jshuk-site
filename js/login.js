// Show loading indicator when Google Sign-In is being processed
function showGoogleLoading() {
    const loadingDiv = document.getElementById('google-loading');
    if (loadingDiv) {
        loadingDiv.style.display = 'block';
    }
}

// Hide loading indicator
function hideGoogleLoading() {
    const loadingDiv = document.getElementById('google-loading');
    if (loadingDiv) {
        loadingDiv.style.display = 'none';
    }
}

function handleGoogleSignIn(response) {
    console.log('Google Sign-In response received:', response);
    
    // Show loading state
    showGoogleLoading();
    
    fetch('/auth/google-verify.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ credential: response.credential })
    })
    .then(res => {
        console.log('Response status:', res.status);
        return res.text();
    })
    .then(text => {
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Server response was not valid JSON:', text);
            const errorDiv = document.getElementById('login-error');
            if (errorDiv) {
                errorDiv.textContent = 'Unexpected server response. Please try again or contact support.';
                errorDiv.style.display = 'block';
            } else {
                alert('Unexpected server response. Please try again or contact support.');
            }
            hideGoogleLoading();
            return;
        }
        console.log('Google verification response:', data);
        if (data.success) {
            // Redirect based on server response
            if (data.redirect) {
                console.log('Redirecting to:', data.redirect);
                window.location.href = data.redirect;
            } else {
                // Fallback to dashboard
                console.log('Redirecting to dashboard');
                window.location.href = '/users/dashboard.php';
            }
        } else {
            console.error('Google login failed:', data.message);
            const errorDiv = document.getElementById('login-error');
            if (errorDiv) {
                errorDiv.textContent = 'Google login failed: ' + (data.message || 'Unknown error');
                errorDiv.style.display = 'block';
            } else {
                alert('Google login failed: ' + (data.message || 'Unknown error'));
            }
            hideGoogleLoading();
        }
    })
    .catch(error => {
        console.error('Error during Google Sign-In:', error);
        const errorDiv = document.getElementById('login-error');
        if (errorDiv) {
            errorDiv.textContent = 'An unexpected error occurred during login. Please try again.';
            errorDiv.style.display = 'block';
        } else {
            alert('An unexpected error occurred during login. Please try again.');
        }
        hideGoogleLoading();
    });
}

// Manual Google Sign-In function as fallback
function initiateGoogleSignIn() {
    console.log('Attempting manual Google Sign-In...');
    
    // Try to trigger the Google Sign-In flow manually
    if (typeof google !== 'undefined' && typeof google.accounts !== 'undefined' && typeof google.accounts.id !== 'undefined') {
        google.accounts.id.prompt();
    } else {
        // If Google library still isn't loaded, redirect to Google OAuth directly
        const clientId = '718581742318-e4q3putg0b10e08eab4ma2sr9urbqb31.apps.googleusercontent.com';
        const redirectUri = encodeURIComponent(window.location.origin + '/auth/google-verify.php');
        const scope = encodeURIComponent('email profile');
        const googleAuthUrl = `https://accounts.google.com/o/oauth2/v2/auth?client_id=${clientId}&redirect_uri=${redirectUri}&scope=${scope}&response_type=code`;
        
        window.location.href = googleAuthUrl;
    }
} 