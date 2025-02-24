var map = L.map('map').setView([30.3753, 69.3451], 5); // Default to Pakistan

// Load OpenStreetMap Tiles
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

var marker;

// Function to update location input & hidden fields
function updateLocation(lat, lng) {
  document.getElementById('latitude').value = lat;
  document.getElementById('longitude').value = lng;
  fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
    .then(response => response.json())
    .then(data => {
      document.getElementById('location').value = data.display_name || `${lat}, ${lng}`;
    });
}

// Click event to set marker
map.on('click', function(e) {
  if (marker) map.removeLayer(marker);
  marker = L.marker([e.latlng.lat, e.latlng.lng]).addTo(map);
  updateLocation(e.latlng.lat, e.latlng.lng);
});

// Add Search Control on Map
var searchControl = L.Control.geocoder({
  defaultMarkGeocode: false
}).on('markgeocode', function(e) {
  var latlng = e.geocode.center;
  if (marker) map.removeLayer(marker);
  marker = L.marker(latlng).addTo(map);
  map.setView(latlng, 13);
  updateLocation(latlng.lat, latlng.lng);
}).addTo(map);