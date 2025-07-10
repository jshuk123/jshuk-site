<?php
$google_maps_key = 'AIzaSyCuWsQC7FxixO92QWyTplfHQamFhxp4C5E';
?>
<!-- Load Google Maps with proper async loading -->
<script>
function initGoogleMaps() {
    const script = document.createElement('script');
    script.src = `https://maps.googleapis.com/maps/api/js?key=<?php echo $google_maps_key; ?>&libraries=places&callback=initMap`;
    script.async = true;
    script.defer = true;
    document.head.appendChild(script);
}
</script> 