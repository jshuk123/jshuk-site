/**
 * JShuk Map System
 * Provides interactive map functionality for business discovery
 * Uses Leaflet.js for mapping (free, open-source alternative to Google Maps)
 */

class BusinessMap {
    constructor() {
        this.map = null;
        this.markers = [];
        this.infoWindow = null;
        this.currentView = 'grid'; // 'grid' or 'map'
        this.businessData = [];
        
        this.init();
    }
    
    init() {
        this.loadLeaflet();
        this.bindViewToggleEvents();
        this.bindMapControlEvents();
    }
    
    loadLeaflet() {
        // Load Leaflet CSS if not already loaded
        if (!document.querySelector('link[href*="leaflet.css"]')) {
            const leafletCSS = document.createElement('link');
            leafletCSS.rel = 'stylesheet';
            leafletCSS.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            leafletCSS.integrity = 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=';
            leafletCSS.crossOrigin = '';
            document.head.appendChild(leafletCSS);
        }
        
        // Load Leaflet JS if not already loaded
        if (typeof L === 'undefined') {
            const leafletJS = document.createElement('script');
            leafletJS.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            leafletJS.integrity = 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=';
            leafletJS.crossOrigin = '';
            leafletJS.onload = () => this.initializeMap();
            document.head.appendChild(leafletJS);
        } else {
            this.initializeMap();
        }
    }
    
    initializeMap() {
        // Initialize the map centered on London
        this.map = L.map('map-canvas').setView([51.5074, -0.1278], 10);
        
        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 18
        }).addTo(this.map);
        
        // Initialize business data
        if (window.businessMapData) {
            this.businessData = window.businessMapData;
            this.createMarkers();
        }
        
        console.log('🗺️ Map initialized successfully');
    }
    
    bindViewToggleEvents() {
        const gridBtn = document.getElementById('grid-view-btn');
        const mapBtn = document.getElementById('map-view-btn');
        const gridArea = document.querySelector('.results-grid-area');
        const mapArea = document.getElementById('map-view-area');
        
        if (gridBtn && mapBtn) {
            gridBtn.addEventListener('click', () => this.switchToView('grid'));
            mapBtn.addEventListener('click', () => this.switchToView('map'));
        }
    }
    
    bindMapControlEvents() {
        const fitBoundsBtn = document.getElementById('fit-bounds-btn');
        const centerMapBtn = document.getElementById('center-map-btn');
        
        if (fitBoundsBtn) {
            fitBoundsBtn.addEventListener('click', () => this.fitAllMarkers());
        }
        
        if (centerMapBtn) {
            centerMapBtn.addEventListener('click', () => this.centerOnLondon());
        }
    }
    
    switchToView(view) {
        const gridBtn = document.getElementById('grid-view-btn');
        const mapBtn = document.getElementById('map-view-btn');
        const gridArea = document.querySelector('.results-grid-area');
        const mapArea = document.getElementById('map-view-area');
        
        if (view === 'map') {
            // Switch to map view
            gridBtn.classList.remove('active');
            mapBtn.classList.add('active');
            gridArea.style.display = 'none';
            mapArea.style.display = 'block';
            
            // Trigger map resize to ensure proper rendering
            setTimeout(() => {
                if (this.map) {
                    this.map.invalidateSize();
                    this.fitAllMarkers();
                }
            }, 100);
            
            this.currentView = 'map';
            console.log('🗺️ Switched to map view');
        } else {
            // Switch to grid view
            mapBtn.classList.remove('active');
            gridBtn.classList.add('active');
            mapArea.style.display = 'none';
            gridArea.style.display = 'block';
            
            this.currentView = 'grid';
            console.log('📋 Switched to grid view');
        }
    }
    
    createMarkers() {
        if (!this.map || !this.businessData.length) return;
        
        // Clear existing markers
        this.clearMarkers();
        
        // Create new markers
        this.businessData.forEach(business => {
            if (business.lat && business.lng) {
                const marker = this.createMarker(business);
                this.markers.push(marker);
                marker.addTo(this.map);
            }
        });
        
        // Update business count
        this.updateBusinessCount();
        
        console.log(`📍 Created ${this.markers.length} markers`);
    }
    
    createMarker(business) {
        // Create custom marker icon based on subscription tier
        const markerIcon = this.createMarkerIcon(business.subscription_tier);
        
        const marker = L.marker([business.lat, business.lng], {
            icon: markerIcon,
            title: business.name
        });
        
        // Create info window content
        const infoContent = this.createInfoWindowContent(business);
        
        // Add click event
        marker.on('click', () => {
            if (this.infoWindow) {
                this.map.closePopup(this.infoWindow);
            }
            this.infoWindow = L.popup({
                maxWidth: 300,
                className: 'map-info-window'
            })
            .setLatLng([business.lat, business.lng])
            .setContent(infoContent)
            .openOn(this.map);
        });
        
        return marker;
    }
    
    createMarkerIcon(subscriptionTier) {
        const colors = {
            'premium_plus': '#ffc107', // Elite - Gold
            'premium': '#007bff',      // Premium - Blue
            'basic': '#6c757d'         // Basic - Gray
        };
        
        const color = colors[subscriptionTier] || colors.basic;
        
        return L.divIcon({
            className: 'custom-marker',
            html: `<div style="
                background: ${color};
                border: 2px solid #1d2a40;
                border-radius: 50% 50% 50% 0;
                transform: rotate(-45deg);
                width: 20px;
                height: 20px;
                position: relative;
                cursor: pointer;
                transition: all 0.2s ease;
            "></div>`,
            iconSize: [20, 20],
            iconAnchor: [10, 20]
        });
    }
    
    createInfoWindowContent(business) {
        const stars = this.generateStars(business.rating);
        const badgeClass = this.getBadgeClass(business.subscription_tier);
        const badgeText = this.getBadgeText(business.subscription_tier);
        
        return `
            <div class="map-info-window">
                <h5>
                    <a href="${business.url}" style="color: #1d2a40; text-decoration: none;">
                        ${business.name}
                    </a>
                </h5>
                <div class="business-category">
                    <i class="fas fa-folder text-muted me-1"></i>
                    ${business.category}
                </div>
                <div class="business-rating">
                    ${stars}
                    ${business.review_count > 0 ? `<span class="text-muted ms-1">(${business.review_count} reviews)</span>` : ''}
                </div>
                <div class="business-location">
                    <i class="fas fa-map-marker-alt text-muted me-1"></i>
                    ${business.location || 'Location not specified'}
                </div>
                ${business.description ? `<p style="font-size: 0.85rem; color: #6c757d; margin-bottom: 0.75rem;">${business.description}</p>` : ''}
                <div class="d-flex justify-content-between align-items-center">
                    <span class="badge ${badgeClass}">${badgeText}</span>
                    <a href="${business.url}" class="btn-view">View Details</a>
                </div>
            </div>
        `;
    }
    
    generateStars(rating) {
        const fullStars = Math.floor(rating);
        const hasHalfStar = (rating - fullStars) >= 0.5;
        const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
        
        let stars = '';
        for (let i = 0; i < fullStars; i++) {
            stars += '<i class="fas fa-star text-warning"></i>';
        }
        if (hasHalfStar) {
            stars += '<i class="fas fa-star-half-alt text-warning"></i>';
        }
        for (let i = 0; i < emptyStars; i++) {
            stars += '<i class="far fa-star text-warning"></i>';
        }
        
        return stars;
    }
    
    getBadgeClass(subscriptionTier) {
        const classes = {
            'premium_plus': 'bg-warning text-dark',
            'premium': 'bg-primary',
            'basic': 'bg-secondary'
        };
        return classes[subscriptionTier] || classes.basic;
    }
    
    getBadgeText(subscriptionTier) {
        const texts = {
            'premium_plus': 'Elite',
            'premium': 'Premium',
            'basic': 'Basic'
        };
        return texts[subscriptionTier] || texts.basic;
    }
    
    clearMarkers() {
        this.markers.forEach(marker => {
            if (this.map) {
                this.map.removeLayer(marker);
            }
        });
        this.markers = [];
    }
    
    updateBusinessData(newData) {
        this.businessData = newData;
        this.createMarkers();
    }
    
    updateBusinessCount() {
        const countElement = document.getElementById('map-business-count');
        if (countElement) {
            countElement.textContent = this.markers.length;
        }
    }
    
    fitAllMarkers() {
        if (!this.map || this.markers.length === 0) return;
        
        const group = new L.featureGroup(this.markers);
        this.map.fitBounds(group.getBounds().pad(0.1));
        console.log('🗺️ Fitted all markers to view');
    }
    
    centerOnLondon() {
        if (!this.map) return;
        
        this.map.setView([51.5074, -0.1278], 10);
        console.log('🗺️ Centered map on London');
    }
    
    // Public method to get current view
    getCurrentView() {
        return this.currentView;
    }
    
    // Public method to check if map is initialized
    isInitialized() {
        return this.map !== null;
    }
}

// Initialize map system when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the map system
    window.businessMap = new BusinessMap();
    
    // Add map functionality to existing AJAX filter system
    if (window.businessFilter) {
        const originalUpdatePageContent = window.businessFilter.updatePageContent;
        
        window.businessFilter.updatePageContent = function(response) {
            // Call original function
            originalUpdatePageContent.call(this, response);
            
            // Update map data if available
            if (response.map_data && window.businessMap) {
                window.businessMap.updateBusinessData(response.map_data);
            }
        };
    }
    
    console.log('🗺️ Map system initialized');
}); 