function handleGoogleSignIn(response) {
    console.log('Google Sign-In response received:', response);
    
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
    });
} 