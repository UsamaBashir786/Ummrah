    // Initialize the map
    var map = L.map('map').setView([51.505, -0.09], 13); // Default to some location
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // Adding Geocoder for searching addresses
    L.Control.geocoder().addTo(map);

    // Function to get address from latlng using Nominatim API
    function getAddressFromCoordinates(lat, lon) {
      fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`)
        .then(response => response.json())
        .then(data => {
          if (data && data.address) {
            document.getElementById('pickup-location').value = data.display_name; // Set the address in the input field
          }
        })
        .catch(error => {
          console.error("Error fetching address:", error);
        });
    }

    // Add a marker when the user clicks on the map
    map.on('click', function(e) {
      var latlng = e.latlng;
      L.marker(latlng).addTo(map).bindPopup("You clicked here").openPopup();
      getAddressFromCoordinates(latlng.lat, latlng.lng); // Get the address based on clicked coordinates
    });

    // Fetch suggestions from Nominatim API as user types
    const inputField = document.getElementById('pickup-location');
    const suggestionsContainer = document.getElementById('suggestions');

    inputField.addEventListener('input', function() {
      const query = inputField.value;
      if (query.length < 3) {
        suggestionsContainer.style.display = 'none';
        return;
      }

      fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${query}`)
        .then(response => response.json())
        .then(data => {
          suggestionsContainer.innerHTML = ''; // Clear previous suggestions
          if (data.length > 0) {
            data.forEach(location => {
              const suggestionItem = document.createElement('div');
              suggestionItem.className = 'suggestion-item';
              suggestionItem.textContent = location.display_name; // Address only
              suggestionItem.onclick = function() {
                inputField.value = location.display_name; // Set the address in the input field
                suggestionsContainer.style.display = 'none'; // Hide suggestions
                map.setView([location.lat, location.lon], 13); // Center map on the selected location
                L.marker([location.lat, location.lon]).addTo(map).bindPopup(location.display_name).openPopup();
              };
              suggestionsContainer.appendChild(suggestionItem);
            });
            suggestionsContainer.style.display = 'block';
          } else {
            suggestionsContainer.style.display = 'none';
          }
        })
        .catch(error => {
          console.error("Error fetching suggestions:", error);
        });
    });
    // Function to update the transport options based on selected category
    document.getElementById('transport-category').addEventListener('change', function() {
      var category = this.value;
      var options = {
        luxury: [{
            value: 'luxury-sedan',
            text: 'Luxury Sedan'
          },
          {
            value: 'luxury-suv',
            text: 'Luxury SUV'
          },
          {
            value: 'luxury-luxury-car',
            text: 'Luxury Car'
          }
        ],
        vip: [{
            value: 'vip-shuttle',
            text: 'VIP Shuttle'
          },
          {
            value: 'vip-vip-limo',
            text: 'VIP Limo'
          },
          {
            value: 'vip-vip-van',
            text: 'VIP Van'
          }
        ],
        shared: [{
            value: 'shared-bus',
            text: 'Shared Bus'
          },
          {
            value: 'shared-mini-bus',
            text: 'Shared Mini Bus'
          },
          {
            value: 'shared-coach',
            text: 'Shared Coach'
          }
        ]
      };

      // Get the transport options based on selected category
      var transportOptions = options[category] || [];
      var transportSelect = document.getElementById('transport-option');

      // Clear existing options
      transportSelect.innerHTML = '';

      // Add new options based on selected category
      transportOptions.forEach(function(option) {
        var optionElement = document.createElement('option');
        optionElement.value = option.value;
        optionElement.textContent = option.text;
        transportSelect.appendChild(optionElement);
      });

      // Add default option if no category is selected
      if (category === "") {
        transportSelect.innerHTML = '<option value="">Please select a category first</option>';
      }
    });