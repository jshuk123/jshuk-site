document.addEventListener('DOMContentLoaded', function() {
    if (window.google && google.maps && google.maps.places) {
        var input = document.getElementById('location');
        if (input) {
            var autocomplete = new google.maps.places.Autocomplete(input);
        }
    }
}); 