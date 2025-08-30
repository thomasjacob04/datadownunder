<!DOCTYPE html>
<html>
<head>
  <title>Australian Power Substations</title>
  <link rel="stylesheet" type="text/css" href="styles.css" />
</head>
<body>
  <div id="controls">
    <label>
      State:
      <select id="filterState">
        <option value="">All</option>
      </select>
    </label>

    <label>
      Status:
      <select id="filterStatus">
        <option value="">All</option>
      </select>
    </label>

    <label>
      Voltage (kV):
      <select id="filterVoltage">
        <option value="">All</option>
      </select>
    </label>

    <label>
      Search:
      <input type="text" id="filterSearch" placeholder="Name or locality">
    </label>
  </div>

  <div id="map"></div>

  <script>
  let map;
  let allStations = [];
  let allMarkers = [];

  function initMap() {
    map = new google.maps.Map(document.getElementById("map"), {
      center: { lat: -25.2744, lng: 133.7751 },
      zoom: 5
    });

    fetch("data.php")
      .then(res => res.json())
      .then(data => {
        allStations = data;
        createMarkers(data);
        populateFilters(data);
      });
  }

  function createMarkers(data) {
    // Clear existing markers
    allMarkers.forEach(m => m.setMap(null));
    allMarkers = [];

    data.forEach(station => {
      const lat = parseFloat(station["y_coordinate"]);
      const lng = parseFloat(station["x_coordinate"]);

      if (!isNaN(lat) && !isNaN(lng)) {
        const marker = new google.maps.Marker({
          position: { lat: lat, lng: lng },
          map: map,
          title: station.name,
          icon: '/img/red-dot.svg'
        });

        const info = new google.maps.InfoWindow({
          content: `
            <div class="info-box">
              <strong>${station.name}</strong><br>
              <em>${station.featuretype} - ${station.class}</em><br>
              Status: ${station.operationalstatus}<br>
              State: ${station.state}<br>
              Voltage: ${station.voltagekv || "N/A"} kV<br>
              Locality: ${station.locality || "N/A"}
            </div>`
        });

        marker.addListener("click", () => {
          info.open(map, marker);
        });

        allMarkers.push(marker);
      }
    });
  }

  function populateFilters(data) {
    const states = [...new Set(data.map(d => d.state).filter(Boolean))].sort();
    const statuses = [...new Set(data.map(d => d.operationalstatus).filter(Boolean))].sort();
    const voltages = [...new Set(data.map(d => d.voltagekv).filter(Boolean))].sort((a,b) => a-b);

    const stateSel = document.getElementById("filterState");
    const statusSel = document.getElementById("filterStatus");
    const voltageSel = document.getElementById("filterVoltage");

    states.forEach(s => stateSel.add(new Option(s, s)));
    statuses.forEach(s => statusSel.add(new Option(s, s)));
    voltages.forEach(v => voltageSel.add(new Option(v, v)));

    stateSel.addEventListener("change", applyFilters);
    statusSel.addEventListener("change", applyFilters);
    voltageSel.addEventListener("change", applyFilters);
    document.getElementById("filterSearch").addEventListener("input", applyFilters);
  }

  function applyFilters() {
    const state = document.getElementById("filterState").value.toLowerCase();
    const status = document.getElementById("filterStatus").value.toLowerCase();
    const voltage = document.getElementById("filterVoltage").value;
    const search = document.getElementById("filterSearch").value.toLowerCase();

    const filtered = allStations.filter(station => {
      return (!state || station.state.toLowerCase() === state) &&
             (!status || station.operationalstatus.toLowerCase() === status) &&
             (!voltage || station.voltagekv === voltage) &&
             (!search || 
               (station.name && station.name.toLowerCase().includes(search)) ||
               (station.locality && station.locality.toLowerCase().includes(search))
             );
    });

    createMarkers(filtered);
  }
  </script>

  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDEwZFMJx7EkIGR2Ddawcn_EXBn6l-2Wtk&callback=initMap" async defer></script>
</body>
</html>
