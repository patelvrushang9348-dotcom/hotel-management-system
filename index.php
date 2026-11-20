<?php
// index.php
require_once __DIR__ . '/config/database.php';
// Ensures database and default tables exist on initial page load
Database::getConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Aurelia Boutique Hotel — Management System</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

  <div id="sidebarBackdrop" class="sidebar-backdrop" onclick="toggleSidebar(false)"></div>

  <!-- Sidebar Navigation -->
  <aside id="sidebar" class="sidebar">
    <div class="brand">
      <div class="brand-logo">
        <div class="brand-icon"><i class="fa-solid fa-crown"></i></div>
        <div>Aurelia <span>Hotel</span></div>
      </div>
      <button class="mobile-close-btn" onclick="toggleSidebar(false)" aria-label="Close Menu">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <ul class="nav">
      <li><button class="nav-btn active" onclick="switchTab('dashboard')"><i class="fa-solid fa-chart-pie"></i> Dashboard</button></li>
      <li><button class="nav-btn" onclick="switchTab('rooms')"><i class="fa-solid fa-bed"></i> Rooms Module</button></li>
      <li><button class="nav-btn" onclick="switchTab('guests')"><i class="fa-solid fa-users"></i> Guests Module</button></li>
      <li><button class="nav-btn" onclick="switchTab('services')"><i class="fa-solid fa-bell-concierge"></i> Services Module</button></li>
      <li><button class="nav-btn" onclick="switchTab('bookings')"><i class="fa-solid fa-calendar-check"></i> Bookings Module</button></li>
    </ul>

    <div class="sidebar-footer">
      <span class="status-dot"></span> PHP + MySQL Live Sync
    </div>
  </aside>

  <!-- Main Workspace -->
  <div class="main">
    <div class="top-nav-bar">
      <div class="top-bar-left">
        <button class="menu-toggle-btn" onclick="toggleSidebar(true)" aria-label="Open Menu">
          <i class="fa-solid fa-bars"></i>
        </button>
        <div style="font-weight:700;font-size:0.95rem;">
          <i class="fa-solid fa-hotel" style="color:var(--gold);margin-right:6px;"></i> Property Operations Portal
        </div>
      </div>

      <div class="top-controls">

        <button class="theme-toggle-btn" onclick="toggleTheme()" title="Toggle Dark/Light Theme">
          <i id="themeIcon" class="fa-solid fa-moon"></i>
        </button>
      </div>
    </div>

    <div id="alertBox" class="alert"></div>

    <!-- 1. DASHBOARD TAB -->
    <div id="tab-dashboard" class="tab-content active">
      <div class="header">
        <h1>Executive Dashboard</h1>
        <span id="dashDate" class="date-pill"><i class="fa-regular fa-calendar"></i> Loading...</span>
      </div>

      <div class="stats-grid">
        <div class="stat-card" style="--card-accent: #2563eb;">
          <div class="stat-top">
            <span class="stat-label">Total Rooms</span>
            <div class="stat-icon-box"><i class="fa-solid fa-bed"></i></div>
          </div>
          <div class="stat-value" id="statTotalRooms">0</div>
          <div class="stat-actions"><a onclick="scrollToDashCard('dashAllRoomsCard')" class="stat-link">View Inventory &rarr;</a></div>
        </div>

        <div class="stat-card" style="--card-accent: #d97706;">
          <div class="stat-top">
            <span class="stat-label">Occupied Today</span>
            <div class="stat-icon-box"><i class="fa-solid fa-door-closed"></i></div>
          </div>
          <div class="stat-value" id="statOccupiedRooms">0</div>
          <div class="stat-actions"><a onclick="scrollToDashCard('dashOccupiedCard')" class="stat-link">Active Stays &rarr;</a></div>
        </div>

        <div class="stat-card" style="--card-accent: #059669;">
          <div class="stat-top">
            <span class="stat-label">Total Revenue</span>
            <div class="stat-icon-box"><i class="fa-solid fa-sack-dollar"></i></div>
          </div>
          <div class="stat-value" id="statRevenue">$0.00</div>
          <div class="stat-actions"><a onclick="switchTab('bookings')" class="stat-link">View Bookings &rarr;</a></div>
        </div>

        <div class="stat-card" style="--card-accent: #7c3aed;">
          <div class="stat-top">
            <span class="stat-label">Registered Guests</span>
            <div class="stat-icon-box"><i class="fa-solid fa-users"></i></div>
          </div>
          <div class="stat-value" id="statTotalGuests">0</div>
          <div class="stat-actions"><a onclick="switchTab('guests')" class="stat-link">Guest Directory &rarr;</a></div>
        </div>

        <div class="stat-card" style="--card-accent: #0891b2;">
          <div class="stat-top">
            <span class="stat-label">Today's Check-Ins</span>
            <div class="stat-icon-box"><i class="fa-solid fa-arrow-right-to-bracket"></i></div>
          </div>
          <div class="stat-value" id="statCheckIns">0</div>
          <div class="stat-actions"><a onclick="scrollToDashCard('dashCheckInsCard')" class="stat-link">Arrivals &rarr;</a></div>
        </div>

        <div class="stat-card" style="--card-accent: #e11d48;">
          <div class="stat-top">
            <span class="stat-label">Today's Check-Outs</span>
            <div class="stat-icon-box"><i class="fa-solid fa-arrow-right-from-bracket"></i></div>
          </div>
          <div class="stat-value" id="statCheckOuts">0</div>
          <div class="stat-actions"><a onclick="scrollToDashCard('dashCheckOutsCard')" class="stat-link">Departures &rarr;</a></div>
        </div>
      </div>

      <!-- Occupied Rooms Today -->
      <div class="card" id="dashOccupiedCard">
        <div class="card-title">
          <span class="card-title-text"><i class="fa-solid fa-door-closed" style="color:var(--gold);"></i> Occupied Rooms Today</span>
          <button class="btn btn-outline btn-sm" onclick="switchTab('bookings')"><i class="fa-solid fa-plus"></i> New Booking</button>
        </div>
        <div class="table-responsive">
          <table id="tableOccupiedRooms">
            <thead>
              <tr><th>Room #</th><th>Type</th><th>Rate/Night</th><th>Guest</th><th>Phone</th><th>Check-In</th><th>Check-Out</th><th>Status</th></tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>

      <!-- All Rooms Overview -->
      <div class="card" id="dashAllRoomsCard">
        <div class="card-title">
          <span class="card-title-text"><i class="fa-solid fa-bed" style="color:var(--primary);"></i> Room Availability Overview</span>
          <button class="btn btn-outline btn-sm" onclick="switchTab('rooms')">Manage Rooms</button>
        </div>
        <div class="table-responsive">
          <table id="tableAllRoomsDash">
            <thead>
              <tr><th>Room #</th><th>Type</th><th>Price/Night</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>

      <!-- Today's Check-Ins & Check-Outs -->
      <div class="card" id="dashCheckInsCard">
        <div class="card-title"><span class="card-title-text"><i class="fa-solid fa-arrow-right-to-bracket" style="color:var(--success);"></i> Today's Check-Ins</span></div>
        <div class="table-responsive">
          <table id="tableCheckIns">
            <thead><tr><th>Booking #</th><th>Guest</th><th>Room</th><th>Status</th><th>Total</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
      </div>

      <div class="card" id="dashCheckOutsCard">
        <div class="card-title"><span class="card-title-text"><i class="fa-solid fa-arrow-right-from-bracket" style="color:var(--danger);"></i> Today's Check-Outs</span></div>
        <div class="table-responsive">
          <table id="tableCheckOuts">
            <thead><tr><th>Booking #</th><th>Guest</th><th>Room</th><th>Status</th><th>Total</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- 2. ROOMS MODULE -->
    <div id="tab-rooms" class="tab-content">
      <div class="header"><h1>Rooms Management</h1></div>
      <div class="card">
        <div class="card-title"><span class="card-title-text"><i class="fa-solid fa-plus-circle" style="color:var(--primary);"></i> Add New Room</span></div>
        <form id="formAddRoom" onsubmit="handleAddRoom(event)">
          <div class="form-grid">
            <div class="form-group">
              <label>Room Number</label>
              <input type="text" id="addRoomNum" placeholder="e.g. 302" required>
            </div>
            <div class="form-group">
              <label>Room Type</label>
              <select id="addRoomType" required>
                <option value="Single">Single</option>
                <option value="Double">Double</option>
                <option value="Suite">Suite</option>
              </select>
            </div>
            <div class="form-group">
              <label>Price per Night (<span class="curr-sym">$</span>)</label>
              <input type="number" step="0.01" min="1" id="addRoomPrice" placeholder="150.00" required>
            </div>
          </div>
          <button type="submit" class="btn btn-primary" style="margin-top:16px;"><i class="fa-solid fa-plus"></i> Add Room</button>
        </form>
      </div>

      <div class="card">
        <div class="card-title"><span class="card-title-text"><i class="fa-solid fa-list"></i> All Rooms</span></div>
        <div class="table-responsive">
          <table id="tableRooms">
            <thead><tr><th>Room #</th><th>Type</th><th>Price/Night</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- 3. GUESTS MODULE -->
    <div id="tab-guests" class="tab-content">
      <div class="header"><h1>Guests Directory</h1></div>
      <div class="card">
        <div class="card-title"><span class="card-title-text"><i class="fa-solid fa-user-plus" style="color:var(--primary);"></i> Register New Guest</span></div>
        <form id="formAddGuest" onsubmit="handleAddGuest(event)">
          <div class="form-grid">
            <div class="form-group">
              <label>Full Name</label>
              <input type="text" id="addGuestName" placeholder="e.g. Sophia Patel" required>
            </div>
            <div class="form-group">
              <label>Email</label>
              <input type="email" id="addGuestEmail" placeholder="sophia@example.com">
            </div>
            <div class="form-group">
              <label>Phone</label>
              <input type="tel" id="addGuestPhone" placeholder="+91-98250-12345">
            </div>
          </div>
          <button type="submit" class="btn btn-primary" style="margin-top:16px;"><i class="fa-solid fa-user-check"></i> Register Guest</button>
        </form>
      </div>

      <div class="card">
        <div class="card-title"><span class="card-title-text"><i class="fa-solid fa-address-book"></i> All Registered Guests</span></div>
        <div class="table-responsive">
          <table id="tableGuests">
            <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Actions</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- 4. SERVICES MODULE -->
    <div id="tab-services" class="tab-content">
      <div class="header"><h1>Hospitality Services</h1></div>
      <div class="card">
        <div class="card-title"><span class="card-title-text"><i class="fa-solid fa-bell-concierge" style="color:var(--primary);"></i> Add Add-On Service</span></div>
        <form id="formAddService" onsubmit="handleAddService(event)">
          <div class="form-grid">
            <div class="form-group">
              <label>Service Name</label>
              <input type="text" id="addServiceName" placeholder="e.g. Candlelight Dinner" required>
            </div>
            <div class="form-group">
              <label>Price (<span class="curr-sym">$</span>)</label>
              <input type="number" step="0.01" min="0" id="addServicePrice" placeholder="60.00" required>
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
              <label>Description</label>
              <input type="text" id="addServiceDesc" placeholder="Rooftop dining experience for two">
            </div>
          </div>
          <button type="submit" class="btn btn-primary" style="margin-top:16px;"><i class="fa-solid fa-plus"></i> Add Service</button>
        </form>
      </div>

      <div class="card">
        <div class="card-title"><span class="card-title-text"><i class="fa-solid fa-list-ul"></i> Available Services</span></div>
        <div class="table-responsive">
          <table id="tableServices">
            <thead><tr><th>ID</th><th>Name</th><th>Price</th><th>Description</th><th>Actions</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- 5. BOOKINGS MODULE -->
    <div id="tab-bookings" class="tab-content">
      <div class="header"><h1>Reservations & Bookings</h1></div>
      <div class="card">
        <div class="card-title"><span class="card-title-text"><i class="fa-solid fa-calendar-plus" style="color:var(--primary);"></i> Create New Booking</span></div>
        <form id="formBookRoom" onsubmit="handleBookRoom(event)">
          <div class="form-grid">
            <div class="form-group">
              <label>Select Room</label>
              <select id="bookRoomSelect" required></select>
            </div>
            <div class="form-group">
              <label>Select Guest</label>
              <select id="bookGuestSelect" required></select>
            </div>
            <div class="form-group">
              <label>Check-In Date</label>
              <input type="date" id="bookCheckIn" required>
            </div>
            <div class="form-group">
              <label>Check-Out Date</label>
              <input type="date" id="bookCheckOut" required>
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
              <label>Add-On Services (Hold Ctrl/Cmd for multiple)</label>
              <select id="bookServicesSelect" multiple size="3" style="height:auto;"></select>
            </div>
          </div>
          <button type="submit" class="btn btn-primary" style="margin-top:16px;"><i class="fa-solid fa-check"></i> Confirm Reservation</button>
        </form>
      </div>

      <div class="card">
        <div class="card-title"><span class="card-title-text"><i class="fa-solid fa-filter"></i> Filter Reservations</span></div>
        <div class="form-grid">
          <div class="form-group">
            <label>Guest Name or ID</label>
            <input type="text" id="filterGuest" placeholder="Search guest..." oninput="applyBookingFilters()">
          </div>
          <div class="form-group">
            <label>Room Number</label>
            <input type="text" id="filterRoom" placeholder="e.g. 201" oninput="applyBookingFilters()">
          </div>
          <div class="form-group">
            <label>Check-In Date</label>
            <input type="date" id="filterCheckIn" oninput="applyBookingFilters()">
          </div>
          <div class="form-group" style="justify-content:flex-end;">
            <button type="button" class="btn btn-outline" onclick="clearBookingFilters()"><i class="fa-solid fa-rotate-left"></i> Reset</button>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-title"><span class="card-title-text"><i class="fa-solid fa-clipboard-list"></i> All Reservations</span></div>
        <div class="table-responsive">
          <table id="tableBookings">
            <thead>
              <tr>
                <th>ID</th><th>Guest</th><th>Room</th><th>Check-In</th><th>Check-Out</th>
                <th>Status</th><th>Services</th><th>Total</th><th>Live</th><th>Actions</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal -->
  <div id="editModal" class="modal-overlay" onclick="if(event.target===this) closeModal()">
    <div class="modal">
      <div class="modal-header">
        <h3 id="modalTitle">Edit</h3>
        <button class="close-btn" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div id="modalBody"></div>
    </div>
  </div>

  <script src="assets/js/app.js"></script>
</body>
</html>
