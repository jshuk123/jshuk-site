// Hamburger menu toggle
(function() {
  const hamburger = document.getElementById('hamburger');
  const nav = document.getElementById('mainNav');
  if (!hamburger || !nav) return;
  hamburger.addEventListener('click', function() {
    const isOpen = nav.classList.toggle('open');
    hamburger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  });
  // Close nav on outside click (mobile)
  document.addEventListener('click', function(e) {
    if (window.innerWidth > 700) return;
    if (!nav.contains(e.target) && !hamburger.contains(e.target)) {
      nav.classList.remove('open');
      hamburger.setAttribute('aria-expanded', 'false');
    }
  });
})();

// Dropdowns
(function() {
  const dropdowns = document.querySelectorAll('.has-dropdown');
  dropdowns.forEach(drop => {
    const toggle = drop.querySelector('.dropdown-toggle');
    const menu = drop.querySelector('.dropdown-menu');
    if (!toggle || !menu) return;
    toggle.addEventListener('click', function(e) {
      e.stopPropagation();
      // Close other open dropdowns
      dropdowns.forEach(d => { if (d !== drop) d.classList.remove('open'); });
      const isOpen = drop.classList.toggle('open');
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
    // Close dropdown on outside click
    document.addEventListener('click', function(e) {
      if (!drop.contains(e.target)) {
        drop.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
      }
    });
    // Keyboard navigation
    toggle.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        drop.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.focus();
      }
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        const first = menu.querySelector('a');
        if (first) first.focus();
      }
    });
    menu.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        drop.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.focus();
      }
    });
  });
})();

// Header functionality
document.addEventListener('DOMContentLoaded', function() {
  // Region selection handlers
  document.querySelectorAll('.region-select').forEach(function(el) {
    el.addEventListener('click', function(e) {
      e.preventDefault();
      setRegion(this.getAttribute('data-region'));
    });
  });

  var changeBtn = document.getElementById('changeRegion');
  if (changeBtn) {
    changeBtn.addEventListener('click', function(e) {
      e.preventDefault();
      clearRegion();
    });
  }

  // Auto-detect region on first visit - with proper error handling
  if (!document.cookie.match(/region=/)) {
    if (navigator.geolocation) {
      navigator.permissions.query({ name: 'geolocation' }).then(function(permissionStatus) {
        if (permissionStatus.state === 'granted') {
          navigator.geolocation.getCurrentPosition(
            // Success callback
            function(pos) {
              var userLat = pos.coords.latitude;
              var userLon = pos.coords.longitude;
              var regions = {
                'london': { lat: 51.5074, lon: -0.1278 },
                'manchester': { lat: 53.4808, lon: -2.2426 },
                'gateshead': { lat: 54.9621, lon: -1.6018 },
                'jerusalem': { lat: 31.7683, lon: 35.2137 }
              };
              var minDist = Infinity, bestRegion = null;
              
              for (var key in regions) {
                var r = regions[key];
                var dLat = (r.lat - userLat) * Math.PI/180;
                var dLon = (r.lon - userLon) * Math.PI/180;
                var a = Math.sin(dLat/2) * Math.sin(dLat/2) + 
                        Math.cos(userLat * Math.PI/180) * Math.cos(r.lat * Math.PI/180) * 
                        Math.sin(dLon/2) * Math.sin(dLon/2);
                var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                var dist = 6371 * c;
                if (dist < minDist) { 
                  minDist = dist; 
                  bestRegion = key; 
                }
              }
              
              if (bestRegion) {
                setRegion(bestRegion);
              }
            },
            // Error callback
            function(error) {
              console.log('Geolocation error:', error.message);
              // Silently fail - user will need to select region manually
            },
            // Options
            {
              enableHighAccuracy: false,
              timeout: 5000,
              maximumAge: 0
            }
          );
        }
        // If permission is denied or prompt, we just let the user select manually
      }).catch(function(error) {
        console.log('Permission check error:', error.message);
        // Silently fail - user will need to select region manually
      });
    }
  }

  // Hide logo when mobile navbar is open
  handleNavbarLogoReel();

  // Make logo swing only once, then settle and pull up
  setTimeout(function() {
    var swing = document.querySelector('.swing-logo');
    var logoContainer = document.querySelector('.elite-logo-container');
    if (swing && logoContainer) {
      setTimeout(function() {
        swing.classList.add('still');
        // After border fades, pull up the logo
        setTimeout(function() {
          logoContainer.classList.add('pulled-up');
        }, 800); // match border fade duration
      }, 4000); // match animation duration
    }
  }, 1000);

  // Live search functionality
  var searchInput = document.getElementById('liveSearch');
  var searchResults = document.getElementById('searchResults');
  
  if (searchInput && searchResults) {
    var searchTimeout;
    
    searchInput.addEventListener('input', function() {
      clearTimeout(searchTimeout);
      var query = this.value.trim();
      
      if (query.length < 2) {
        searchResults.classList.remove('show');
        return;
      }
      
      searchTimeout = setTimeout(function() {
        performLiveSearch(query);
      }, 300);
    });
    
    // Hide search results when clicking outside
    document.addEventListener('click', function(e) {
      if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
        searchResults.classList.remove('show');
      }
    });
  }
});

// Region functions
function setRegion(region) {
  document.cookie = 'region=' + region + ';path=/;max-age=' + (60*60*24*30);
  location.reload();
}

function clearRegion() {
  document.cookie = 'region=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/;';
  location.reload();
}

function handleNavbarLogoReel() {
  var navbar = document.getElementById('mainNavbar');
  var logo = document.querySelector('.elite-logo-container');
  if (!navbar || !logo) return;
  
  navbar.addEventListener('show.bs.collapse', function() {
    logo.classList.add('reel-up');
  });
  navbar.addEventListener('hide.bs.collapse', function() {
    logo.classList.remove('reel-up');
  });
}

// Live search function
function performLiveSearch(query) {
  var searchResults = document.getElementById('searchResults');
  
  // Show loading state
  searchResults.innerHTML = '<div class="p-3 text-center"><i class="fa fa-spinner fa-spin"></i> Searching...</div>';
  searchResults.classList.add('show');
  
  // Make AJAX request to search endpoint
  fetch('/search.php?ajax=1&q=' + encodeURIComponent(query))
    .then(response => response.json())
    .then(data => {
      if (data.results && data.results.length > 0) {
        var html = '';
        data.results.forEach(function(result) {
          html += '<a href="' + result.url + '" class="d-block p-3 border-bottom text-decoration-none text-dark">';
          html += '<div class="fw-bold">' + result.title + '</div>';
          html += '<div class="text-muted small">' + result.description + '</div>';
          html += '</a>';
        });
        searchResults.innerHTML = html;
      } else {
        searchResults.innerHTML = '<div class="p-3 text-center text-muted">No results found</div>';
      }
    })
    .catch(error => {
      console.error('Search error:', error);
      searchResults.innerHTML = '<div class="p-3 text-center text-danger">Search error occurred</div>';
    });
} 