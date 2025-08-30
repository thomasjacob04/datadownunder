<!DOCTYPE html>
<html>
<head>
  <title>Australian Utility Facilities Map</title>
  <link rel="stylesheet" type="text/css" href="styles.css" />
  <style>
    #controls {
      margin-bottom: 12px;
      padding: 8px 0;
    }
    #map {
      width: 100%;
      height: 600px;
      margin-top: 10px;
      border: 1px solid #ccc;
    }
    label {
      margin-right: 10px;
    }
    .hidden {
      display: none;
    }
  </style>
</head>
<body>
  <div id="controls">
    <label>
      <strong>Dataset:</strong>
      <select id="datasetSelector">
        <option value="substations">Substations</option>
        <option value="waste">Waste Facilities</option>
      </select>
    </label>

    <!-- Substation Filters -->
    <span id="substationFilters">
      <label>
        State:
        <select id="filterState_sub">
          <option value="">All</option>
        </select>
      </label>
      <label>
        Status:
        <select id="filterStatus_sub">
          <option value="">All</option>
        </select>
      </label>
      <label>
        Voltage (kV):
        <select id="filterVoltage_sub">
          <option value="">All</option>
        </select>
      </label>
      <label>
        Search:
        <input type="text" id="filterSearch_sub" placeholder="Name or locality">
      </label>
    </span>

    <!-- Waste Facility Filters -->
    <span id="wasteFilters" class="hidden">
      <label>
        State:
        <select id="filterState_waste">
          <option value="">All</option>
        </select>
      </label>
      <label>
        Owner:
        <select id="filterOwner_waste">
          <option value="">All</option>
        </select>
      </label>
      <label>
        Management Type:
        <select id="filterMgmtType_waste">
          <option value="">All</option>
        </select>
      </label>
      <label>
        Status:
        <select id="filterStatus_waste">
          <option value="">All</option>
        </select>
      </label>
      <label>
        Postcode:
        <input type="text" id="filterPostcode_waste" placeholder="e.g. 3000">
      </label>
      <label>
        Search:
        <input type="text" id="filterSearch_waste" placeholder="Facility name, suburb, or address">
      </label>
    </span>
  </div>

  <div id="map"></div>

  <script>
  let map;
  let allData = [];
  let allMarkers = [];
  let currentDataset = "substations";

  function getApiUrl() {
    if (currentDataset === "substations") {
      return "data.php?source=substations";
    } else {
      return "data.php?source=waste";
    }
  }

  function fetchDataAndSetup() {
    fetch(getApiUrl())
      .then(res => res.json())
      .then(data => {
        allData = data;
        map.setZoom(5);
        map.setCenter({ lat: -25.2744, lng: 133.7751 }); // Australia center
        if (currentDataset === "substations") {
          populateSubstationFilters(data);
          applySubstationFilters();
        } else {
          populateWasteFilters(data);
          applyWasteFilters();
        }
      });
  }

  function initMap() {
    map = new google.maps.Map(document.getElementById("map"), {
      center: { lat: -25.2744, lng: 133.7751 },
      zoom: 5
    });
    fetchDataAndSetup();
  }

  // --- Substation Filters, Markers ---
  function populateSubstationFilters(data) {
    const stateSel = document.getElementById("filterState_sub");
    const statusSel = document.getElementById("filterStatus_sub");
    const voltageSel = document.getElementById("filterVoltage_sub");

    // Remove all but first option
    [stateSel, statusSel, voltageSel].forEach(sel => {
      sel.options.length = 1;
    });

    const states = Array.from(new Set(data.map(d => d.state).filter(Boolean))).sort();
    const statuses = Array.from(new Set(data.map(d => d.operationalstatus).filter(Boolean))).sort();
    const voltages = Array.from(new Set(data.map(d => d.voltagekv).filter(Boolean))).sort((a,b) => a-b);

    states.forEach(s => stateSel.add(new Option(s, s)));
    statuses.forEach(s => statusSel.add(new Option(s, s)));
    voltages.forEach(v => voltageSel.add(new Option(v, v)));

    stateSel.onchange = applySubstationFilters;
    statusSel.onchange = applySubstationFilters;
    voltageSel.onchange = applySubstationFilters;
    document.getElementById("filterSearch_sub").oninput = applySubstationFilters;
  }

  function applySubstationFilters() {
    const state = document.getElementById("filterState_sub").value.toLowerCase();
    const status = document.getElementById("filterStatus_sub").value.toLowerCase();
    const voltage = document.getElementById("filterVoltage_sub").value;
    const search = document.getElementById("filterSearch_sub").value.toLowerCase();

    const filtered = allData.filter(station => {
      return (!state || (station.state && station.state.toLowerCase() === state)) &&
             (!status || (station.operationalstatus && station.operationalstatus.toLowerCase() === status)) &&
             (!voltage || (station.voltagekv === voltage)) &&
             (!search ||
               (station.name && station.name.toLowerCase().includes(search)) ||
               (station.locality && station.locality.toLowerCase().includes(search))
             );
    });

    createSubstationMarkers(filtered);
  }

  function createSubstationMarkers(data) {
    clearMarkers();
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

        const infoHtml = `
            <div class="info-box">
              <strong>${station.name}</strong><br>
              <em>${station.featuretype} - ${station.class}</em><br>
              Status: ${station.operationalstatus}<br>
              State: ${station.state}<br>
              Voltage: ${station.voltagekv || "N/A"} kV<br>
              Locality: ${station.locality || "N/A"}
            </div>`;
        const info = new google.maps.InfoWindow({ content: infoHtml });

        marker.addListener("click", () => { info.open(map, marker); });
        allMarkers.push(marker);
      }
    });
  }

  // --- Waste Filters, Markers ---
  function populateWasteFilters(data) {
    const stateSel = document.getElementById("filterState_waste");
    const ownerSel = document.getElementById("filterOwner_waste");
    const mgmtSel  = document.getElementById("filterMgmtType_waste");
    const statusSel = document.getElementById("filterStatus_waste");

    // Remove all but first option
    [stateSel, ownerSel, mgmtSel, statusSel].forEach(sel => { sel.options.length = 1; });

    // Robustly extract unique options, skip empty strings and undefined/null
    const states = Array.from(new Set(data.map(d => d.state).filter(v => v && v.trim() !== ""))).sort();
    const owners = Array.from(new Set(data.map(d => d.facility_owner).filter(v => v && v.trim() !== ""))).sort();
    const mgmts  = Array.from(new Set(data.map(d => d.facility_management_type).filter(v => v && v.trim() !== ""))).sort();
    const statuses = Array.from(new Set(data.map(d => d.operational_status).filter(v => v && v.trim() !== ""))).sort();

    states.forEach(v => stateSel.add(new Option(v, v)));
    owners.forEach(v => ownerSel.add(new Option(v, v)));
    mgmts.forEach(v => mgmtSel.add(new Option(v, v)));
    statuses.forEach(v => statusSel.add(new Option(v, v)));

    stateSel.onchange = applyWasteFilters;
    ownerSel.onchange = applyWasteFilters;
    mgmtSel.onchange = applyWasteFilters;
    statusSel.onchange = applyWasteFilters;
    document.getElementById("filterPostcode_waste").oninput = applyWasteFilters;
    document.getElementById("filterSearch_waste").oninput = applyWasteFilters;
  }

  function applyWasteFilters() {
    const state = document.getElementById("filterState_waste").value.toLowerCase();
    const owner = document.getElementById("filterOwner_waste").value.toLowerCase();
    const mgmt  = document.getElementById("filterMgmtType_waste").value.toLowerCase();
    const status = document.getElementById("filterStatus_waste").value.toLowerCase();
    const postcode = document.getElementById("filterPostcode_waste").value.trim();
    const search = document.getElementById("filterSearch_waste").value.toLowerCase();

    const filtered = allData.filter(fac => {
      // Handle undefined keys gracefully
      return (!state || (fac.state && fac.state.toLowerCase() === state)) &&
             (!owner || (fac.facility_owner && fac.facility_owner.toLowerCase() === owner)) &&
             (!mgmt  || (fac.facility_management_type && fac.facility_management_type.toLowerCase() === mgmt)) &&
             (!status || (fac.operational_status && fac.operational_status.toLowerCase() === status)) &&
             (!postcode || (fac.postcode && String(fac.postcode).toLowerCase().includes(postcode.toLowerCase()))) &&
             (!search ||
              (fac.facility_name && fac.facility_name.toLowerCase().includes(search)) ||
              (fac.suburb && fac.suburb.toLowerCase().includes(search)) ||
              (fac.address && fac.address.toLowerCase().includes(search))
             );
    });

    createWasteMarkers(filtered);
  }

  function createWasteMarkers(data) {
    clearMarkers();
    data.forEach(facility => {
      // Waste CSV: X=longitude, Y=latitude
      const lat = parseFloat(facility["Y"]);
      const lng = parseFloat(facility["X"]);
      if (!isNaN(lat) && !isNaN(lng)) {
        const marker = new google.maps.Marker({
          position: {lat: lat, lng: lng},
          map: map,
          title: facility.facility_name || facility.FacilityName,
          icon: '/img/red-dot.svg'
        });
        const infoHtml = `
          <div class="info-box">
            <strong>${facility.facility_name || ""}</strong><br>
            Owner: ${facility.facility_owner || "-"}<br>
            Management: ${facility.facility_management_type || "-"}<br>
            Status: ${facility.operational_status || "-"}<br>
            State: ${facility.state || "-"}<br>
            Postcode: ${facility.postcode || "-"}<br>
            Suburb: ${facility.suburb || "-"}<br>
            Address: ${facility.address || "-"}
          </div>`;
        const info = new google.maps.InfoWindow({ content: infoHtml });
        marker.addListener("click", () => { info.open(map, marker); });
        allMarkers.push(marker);
      }
    });
  }

  // --- Utility ---
  function clearMarkers() {
    allMarkers.forEach(m => m.setMap(null));
    allMarkers = [];
  }

  // --- Dataset selector event ---
  document.addEventListener("DOMContentLoaded", function() {
    const datasetSel = document.getElementById("datasetSelector");
    datasetSel.onchange = function() {
      currentDataset = datasetSel.value;
      // Show/hide filter sets
      document.getElementById("substationFilters").className = (currentDataset === "substations") ? "" : "hidden";
      document.getElementById("wasteFilters").className = (currentDataset === "waste") ? "" : "hidden";
      fetchDataAndSetup();
    };
  });

  </script>
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDEwZFMJx7EkIGR2Ddawcn_EXBn6l-2Wtk&callback=initMap" async defer></script>
</body>
</html>