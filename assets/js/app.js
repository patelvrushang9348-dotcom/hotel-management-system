// assets/js/app.js
let currentCurrency = localStorage.getItem('hotel_currency') || 'USD';
let currentTheme = localStorage.getItem('hotel_theme') || 'light';
const USD_TO_INR = 83.0;
const API_URL = 'api/router.php';

let allRooms = [];
let allGuests = [];
let allServices = [];
let allBookings = [];

// Apply saved theme immediately
document.documentElement.setAttribute('data-theme', currentTheme);

function toggleTheme() {
  currentTheme = currentTheme === 'light' ? 'dark' : 'light';
  localStorage.setItem('hotel_theme', currentTheme);
  document.documentElement.setAttribute('data-theme', currentTheme);
  updateThemeIcon();
}

function updateThemeIcon() {
  const icon = document.getElementById('themeIcon');
  if (icon) {
    icon.className = currentTheme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
  }
}

function toggleSidebar(open) {
  document.getElementById('sidebar').classList.toggle('open', open);
  document.getElementById('sidebarBackdrop').classList.toggle('open', open);
}

function formatPrice(usdAmount) {
  const val = parseFloat(usdAmount) || 0;
  if (currentCurrency === 'INR') {
    const inr = val * USD_TO_INR;
    return '₹' + inr.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
  return '$' + val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function getCurrencySymbol() {
  return currentCurrency === 'INR' ? '₹' : '$';
}

function setCurrency(curr) {
  currentCurrency = curr;
  localStorage.setItem('hotel_currency', curr);
  updateCurrencyUI();
  refreshAll();
}

function updateCurrencyUI() {
  document.getElementById('btnUSD')?.classList.toggle('active', currentCurrency === 'USD');
  document.getElementById('btnINR')?.classList.toggle('active', currentCurrency === 'INR');
  document.querySelectorAll('.curr-sym').forEach(el => {
    el.textContent = getCurrencySymbol();
  });
}

function scrollToDashCard(cardId) {
  const el = document.getElementById(cardId);
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    el.style.borderColor = 'var(--primary)';
    setTimeout(() => { el.style.borderColor = 'var(--border)'; }, 1800);
  }
}

function showAlert(msg, isError = false) {
  const box = document.getElementById('alertBox');
  box.innerHTML = `<i class="fa-solid ${isError ? 'fa-circle-exclamation' : 'fa-circle-check'}" style="margin-right:8px;"></i>${msg}`;
  box.className = 'alert ' + (isError ? 'alert-error' : 'alert-success');
  box.style.display = 'block';
  clearTimeout(window._alertTimer);
  window._alertTimer = setTimeout(() => { box.style.display = 'none'; }, 4500);
}

function switchTab(tabId) {
  document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.nav-btn').forEach(el => el.classList.remove('active'));
  document.getElementById('tab-' + tabId).classList.add('active');
  const btn = Array.from(document.querySelectorAll('.nav-btn')).find(b => b.getAttribute('onclick').includes(tabId));
  if (btn) btn.classList.add('active');
  toggleSidebar(false);
  refreshAll();
}

function closeModal() {
  document.getElementById('editModal').style.display = 'none';
}

async function api(action, options = {}, id = null) {
  let url = `${API_URL}?action=${encodeURIComponent(action)}`;
  if (id !== null) url += `&id=${encodeURIComponent(id)}`;

  try {
    const res = await fetch(url, {
      headers: { 'Content-Type': 'application/json' },
      ...options
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Request failed');
    return data;
  } catch (err) {
    showAlert(err.message, true);
    throw err;
  }
}

async function refreshAll() {
  updateCurrencyUI();
  updateThemeIcon();
  await Promise.all([
    loadDashboard(),
    loadRooms(),
    loadGuests(),
    loadServices(),
    loadBookings()
  ]).catch(() => {});
}

// 1. Dashboard
async function loadDashboard() {
  const d = await api('dashboard');
  document.getElementById('dashDate').innerHTML = `<i class="fa-regular fa-calendar"></i> ${d.date}`;
  document.getElementById('statTotalRooms').textContent = d.total_rooms;
  document.getElementById('statOccupiedRooms').textContent = d.occupied_rooms_today;
  document.getElementById('statTotalGuests').textContent = d.total_guests;
  document.getElementById('statRevenue').textContent = formatPrice(d.total_revenue);
  document.getElementById('statCheckIns').textContent = d.todays_check_ins_count;
  document.getElementById('statCheckOuts').textContent = d.todays_check_outs_count;

  document.querySelector('#tableOccupiedRooms tbody').innerHTML = d.occupied_rooms.length
    ? d.occupied_rooms.map(o => `
        <tr>
          <td><strong>Room ${o.room_number}</strong></td>
          <td>${o.room_type}</td>
          <td>${formatPrice(o.price_per_night)}</td>
          <td><strong>${o.guest_name}</strong></td>
          <td>${o.guest_phone || '-'}</td>
          <td>${o.check_in_date}</td>
          <td>${o.check_out_date}</td>
          <td><span class="badge badge-occupied"><i class="fa-solid fa-door-closed"></i> ${o.status}</span></td>
        </tr>
      `).join('')
    : '<tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:18px;">No rooms occupied today.</td></tr>';

  document.querySelector('#tableAllRoomsDash tbody').innerHTML = d.all_rooms.length
    ? d.all_rooms.map(r => `
        <tr>
          <td><strong>Room ${r.room_number}</strong></td>
          <td>${r.room_type}</td>
          <td>${formatPrice(r.price_per_night)}</td>
          <td>
            <span class="badge ${r.availability_status === 'Available' ? 'badge-available' : 'badge-occupied'}">
              <i class="fa-solid ${r.availability_status === 'Available' ? 'fa-circle-check' : 'fa-circle-xmark'}"></i>
              ${r.availability_status}
            </span>
          </td>
          <td><button class="btn btn-outline btn-sm" onclick="openEditRoom('${r.room_number}')"><i class="fa-solid fa-pen"></i> Edit</button></td>
        </tr>
      `).join('')
    : '<tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:18px;">No rooms found.</td></tr>';

  document.querySelector('#tableCheckIns tbody').innerHTML = d.todays_check_ins.length
    ? d.todays_check_ins.map(b => `
        <tr>
          <td><strong>#${b.booking_id}</strong></td>
          <td><strong>${b.guest_name}</strong></td>
          <td>Room ${b.room_number} (${b.room_type})</td>
          <td><span class="badge badge-booked">${b.status}</span></td>
          <td><strong>${formatPrice(b.total_amount)}</strong></td>
        </tr>
      `).join('')
    : '<tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:18px;">No check-ins scheduled today.</td></tr>';

  document.querySelector('#tableCheckOuts tbody').innerHTML = d.todays_check_outs.length
    ? d.todays_check_outs.map(b => `
        <tr>
          <td><strong>#${b.booking_id}</strong></td>
          <td><strong>${b.guest_name}</strong></td>
          <td>Room ${b.room_number} (${b.room_type})</td>
          <td><span class="badge badge-booked">${b.status}</span></td>
          <td><strong>${formatPrice(b.total_amount)}</strong></td>
        </tr>
      `).join('')
    : '<tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:18px;">No check-outs scheduled today.</td></tr>';
}

// 2. Rooms Module
async function loadRooms() {
  allRooms = await api('rooms');
  document.querySelector('#tableRooms tbody').innerHTML = allRooms.map(r => `
    <tr>
      <td><strong>Room ${r.room_number}</strong></td>
      <td>${r.room_type}</td>
      <td>${formatPrice(r.price_per_night)}</td>
      <td>
        <span class="badge ${r.availability_status === 'Available' ? 'badge-available' : 'badge-occupied'}">
          <i class="fa-solid ${r.availability_status === 'Available' ? 'fa-circle-check' : 'fa-circle-xmark'}"></i>
          ${r.availability_status}
        </span>
      </td>
      <td style="display:flex;gap:6px;">
        <button class="btn btn-outline btn-sm" onclick="openEditRoom('${r.room_number}')"><i class="fa-solid fa-pen"></i> Edit</button>
        <button class="btn btn-danger btn-sm" onclick="deleteRoom('${r.room_number}')"><i class="fa-solid fa-trash"></i> Delete</button>
      </td>
    </tr>
  `).join('');

  document.getElementById('bookRoomSelect').innerHTML = allRooms
    .map(r => `<option value="${r.room_number}">Room ${r.room_number} (${r.room_type} - ${formatPrice(r.price_per_night)}/night)</option>`)
    .join('');
}

async function handleAddRoom(e) {
  e.preventDefault();
  const rawPrice = parseFloat(document.getElementById('addRoomPrice').value);
  const priceInUSD = currentCurrency === 'INR' ? rawPrice / USD_TO_INR : rawPrice;
  await api('rooms', {
    method: 'POST',
    body: JSON.stringify({
      room_number: document.getElementById('addRoomNum').value,
      room_type: document.getElementById('addRoomType').value,
      price_per_night: priceInUSD
    })
  });
  showAlert('Room added successfully!');
  document.getElementById('formAddRoom').reset();
  refreshAll();
}

function openEditRoom(roomNum) {
  const r = allRooms.find(x => x.room_number === roomNum);
  if (!r) return;
  const displayPrice = currentCurrency === 'INR' ? (r.price_per_night * USD_TO_INR).toFixed(2) : r.price_per_night.toFixed(2);
  document.getElementById('modalTitle').innerHTML = `<i class="fa-solid fa-pen"></i> Edit Room ${r.room_number}`;
  document.getElementById('modalBody').innerHTML = `
    <form onsubmit="saveEditRoom(event, '${r.room_number}')">
      <div class="form-group" style="margin-bottom:14px;">
        <label>Room Type</label>
        <select id="editRoomType">
          <option value="Single" ${r.room_type === 'Single' ? 'selected' : ''}>Single</option>
          <option value="Double" ${r.room_type === 'Double' ? 'selected' : ''}>Double</option>
          <option value="Suite" ${r.room_type === 'Suite' ? 'selected' : ''}>Suite</option>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:16px;">
        <label>Price per Night (${getCurrencySymbol()})</label>
        <input type="number" step="0.01" min="1" id="editRoomPrice" value="${displayPrice}" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
    </form>
  `;
  document.getElementById('editModal').style.display = 'flex';
}

async function saveEditRoom(e, roomNum) {
  e.preventDefault();
  const val = parseFloat(document.getElementById('editRoomPrice').value);
  const priceInUSD = currentCurrency === 'INR' ? val / USD_TO_INR : val;
  await api('rooms', {
    method: 'PUT',
    body: JSON.stringify({
      room_type: document.getElementById('editRoomType').value,
      price_per_night: priceInUSD
    })
  }, roomNum);
  closeModal();
  showAlert('Room updated successfully!');
  refreshAll();
}

async function deleteRoom(roomNum) {
  if (!confirm(`Delete Room ${roomNum}? (Only allowed if no active/upcoming bookings exist)`)) return;
  await api('rooms', { method: 'DELETE' }, roomNum);
  showAlert('Room deleted!');
  refreshAll();
}

// 3. Guests Module
async function loadGuests() {
  allGuests = await api('guests');
  document.querySelector('#tableGuests tbody').innerHTML = allGuests.map(g => `
    <tr>
      <td><strong>#${g.guest_id}</strong></td>
      <td><strong>${g.name}</strong></td>
      <td>${g.email || '-'}</td>
      <td>${g.phone || '-'}</td>
      <td><button class="btn btn-outline btn-sm" onclick="openEditGuest(${g.guest_id})"><i class="fa-solid fa-pen"></i> Edit</button></td>
    </tr>
  `).join('');

  document.getElementById('bookGuestSelect').innerHTML = allGuests
    .map(g => `<option value="${g.guest_id}">${g.name} (#${g.guest_id})</option>`)
    .join('');
}

async function handleAddGuest(e) {
  e.preventDefault();
  await api('guests', {
    method: 'POST',
    body: JSON.stringify({
      name: document.getElementById('addGuestName').value,
      email: document.getElementById('addGuestEmail').value,
      phone: document.getElementById('addGuestPhone').value
    })
  });
  showAlert('Guest registered!');
  document.getElementById('formAddGuest').reset();
  refreshAll();
}

function openEditGuest(guestId) {
  const g = allGuests.find(x => x.guest_id == guestId);
  if (!g) return;
  document.getElementById('modalTitle').innerHTML = `<i class="fa-solid fa-user-pen"></i> Edit Guest #${g.guest_id}`;
  document.getElementById('modalBody').innerHTML = `
    <form onsubmit="saveEditGuest(event, ${g.guest_id})">
      <div class="form-group" style="margin-bottom:14px;">
        <label>Full Name</label>
        <input type="text" id="editGuestName" value="${g.name}" required>
      </div>
      <div class="form-group" style="margin-bottom:14px;">
        <label>Email</label>
        <input type="email" id="editGuestEmail" value="${g.email || ''}">
      </div>
      <div class="form-group" style="margin-bottom:16px;">
        <label>Phone</label>
        <input type="tel" id="editGuestPhone" value="${g.phone || ''}">
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
    </form>
  `;
  document.getElementById('editModal').style.display = 'flex';
}

async function saveEditGuest(e, guestId) {
  e.preventDefault();
  await api('guests', {
    method: 'PUT',
    body: JSON.stringify({
      name: document.getElementById('editGuestName').value,
      email: document.getElementById('editGuestEmail').value,
      phone: document.getElementById('editGuestPhone').value
    })
  }, guestId);
  closeModal();
  showAlert('Guest updated!');
  refreshAll();
}

// 4. Services Module
async function loadServices() {
  allServices = await api('services');
  document.querySelector('#tableServices tbody').innerHTML = allServices.map(s => `
    <tr>
      <td><strong>#${s.service_id}</strong></td>
      <td><strong>${s.name}</strong></td>
      <td>${formatPrice(s.price)}</td>
      <td>${s.description || '-'}</td>
      <td><button class="btn btn-outline btn-sm" onclick="openEditService(${s.service_id})"><i class="fa-solid fa-pen"></i> Edit</button></td>
    </tr>
  `).join('');

  document.getElementById('bookServicesSelect').innerHTML = allServices
    .map(s => `<option value="${s.service_id}">${s.name} (+${formatPrice(s.price)})</option>`)
    .join('');
}

async function handleAddService(e) {
  e.preventDefault();
  const rawPrice = parseFloat(document.getElementById('addServicePrice').value);
  const priceInUSD = currentCurrency === 'INR' ? rawPrice / USD_TO_INR : rawPrice;
  await api('services', {
    method: 'POST',
    body: JSON.stringify({
      name: document.getElementById('addServiceName').value,
      description: document.getElementById('addServiceDesc').value,
      price: priceInUSD
    })
  });
  showAlert('Service added!');
  document.getElementById('formAddService').reset();
  refreshAll();
}

function openEditService(serviceId) {
  const s = allServices.find(x => x.service_id == serviceId);
  if (!s) return;
  const displayPrice = currentCurrency === 'INR' ? (s.price * USD_TO_INR).toFixed(2) : s.price.toFixed(2);
  document.getElementById('modalTitle').innerHTML = `<i class="fa-solid fa-pen"></i> Edit Service #${s.service_id}`;
  document.getElementById('modalBody').innerHTML = `
    <form onsubmit="saveEditService(event, ${s.service_id})">
      <div class="form-group" style="margin-bottom:14px;">
        <label>Service Name</label>
        <input type="text" id="editServiceName" value="${s.name}" required>
      </div>
      <div class="form-group" style="margin-bottom:14px;">
        <label>Price (${getCurrencySymbol()})</label>
        <input type="number" step="0.01" min="0" id="editServicePrice" value="${displayPrice}" required>
      </div>
      <div class="form-group" style="margin-bottom:16px;">
        <label>Description</label>
        <input type="text" id="editServiceDesc" value="${s.description || ''}">
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
    </form>
  `;
  document.getElementById('editModal').style.display = 'flex';
}

async function saveEditService(e, serviceId) {
  e.preventDefault();
  const val = parseFloat(document.getElementById('editServicePrice').value);
  const priceInUSD = currentCurrency === 'INR' ? val / USD_TO_INR : val;
  await api('services', {
    method: 'PUT',
    body: JSON.stringify({
      name: document.getElementById('editServiceName').value,
      description: document.getElementById('editServiceDesc').value,
      price: priceInUSD
    })
  }, serviceId);
  closeModal();
  showAlert('Service updated!');
  refreshAll();
}

// 5. Bookings Module
async function loadBookings() {
  allBookings = await api('bookings');
  applyBookingFilters();
}

function renderBookings(list) {
  const tbody = document.querySelector('#tableBookings tbody');
  if (!list.length) {
    tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;color:var(--text-muted);padding:24px;">No bookings match the criteria.</td></tr>';
    return;
  }
  tbody.innerHTML = list.map(b => {
    const srvNames = b.services.map(s => `${s.name} (${formatPrice(s.price)})`).join(', ') || 'None';
    const activeBadge = b.is_active ? '<span class="badge badge-active"><i class="fa-solid fa-bolt"></i> Active</span>' : '-';
    return `
      <tr>
        <td><strong>#${b.booking_id}</strong></td>
        <td><strong>${b.guest_name}</strong></td>
        <td>Room ${b.room_number}</td>
        <td>${b.check_in_date}</td>
        <td>${b.check_out_date}</td>
        <td><span class="badge ${b.status === 'Cancelled' ? 'badge-cancelled' : 'badge-booked'}">${b.status}</span></td>
        <td><small>${srvNames}</small></td>
        <td><strong>${formatPrice(b.total_amount)}</strong></td>
        <td>${activeBadge}</td>
        <td style="display:flex;gap:6px;">
          <button class="btn btn-outline btn-sm" onclick="openAddServicesModal(${b.booking_id})"><i class="fa-solid fa-plus"></i> Service</button>
          <button class="btn btn-outline btn-sm" onclick="openStatusModal(${b.booking_id}, '${b.status}')"><i class="fa-solid fa-sliders"></i> Status</button>
        </td>
      </tr>
    `;
  }).join('');
}

function applyBookingFilters() {
  const gFilter = document.getElementById('filterGuest').value.trim().toLowerCase();
  const rFilter = document.getElementById('filterRoom').value.trim().toLowerCase();
  const inFilter = document.getElementById('filterCheckIn').value;

  const filtered = allBookings.filter(b => {
    if (gFilter && !b.guest_name.toLowerCase().includes(gFilter) && b.guest_id.toString() !== gFilter) return false;
    if (rFilter && b.room_number.toLowerCase() !== rFilter) return false;
    if (inFilter && b.check_in_date !== inFilter) return false;
    return true;
  });
  renderBookings(filtered);
}

function clearBookingFilters() {
  document.getElementById('filterGuest').value = '';
  document.getElementById('filterRoom').value = '';
  document.getElementById('filterCheckIn').value = '';
  renderBookings(allBookings);
}

async function handleBookRoom(e) {
  e.preventDefault();
  const sOptions = document.getElementById('bookServicesSelect').selectedOptions;
  const sIds = Array.from(sOptions).map(o => parseInt(o.value));

  await api('bookings', {
    method: 'POST',
    body: JSON.stringify({
      room_number: document.getElementById('bookRoomSelect').value,
      guest_id: parseInt(document.getElementById('bookGuestSelect').value),
      check_in_date: document.getElementById('bookCheckIn').value,
      check_out_date: document.getElementById('bookCheckOut').value,
      service_ids: sIds
    })
  });
  showAlert('Booking created successfully!');
  document.getElementById('formBookRoom').reset();
  refreshAll();
}

function openAddServicesModal(bookingId) {
  document.getElementById('modalTitle').innerHTML = `<i class="fa-solid fa-bell-concierge"></i> Add Services to #${bookingId}`;
  document.getElementById('modalBody').innerHTML = `
    <form onsubmit="saveAddServices(event, ${bookingId})">
      <div class="form-group" style="margin-bottom:16px;">
        <label>Select Services</label>
        <select id="modalServicesSelect" multiple size="4" style="height:auto;" required>
          ${allServices.map(s => `<option value="${s.service_id}">${s.name} (+${formatPrice(s.price)})</option>`).join('')}
        </select>
        <small style="color:var(--text-muted);">Hold Ctrl/Cmd to select multiple</small>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa-solid fa-plus"></i> Add Services</button>
    </form>
  `;
  document.getElementById('editModal').style.display = 'flex';
}

async function saveAddServices(e, bookingId) {
  e.preventDefault();
  const sOptions = document.getElementById('modalServicesSelect').selectedOptions;
  const sIds = Array.from(sOptions).map(o => parseInt(o.value));
  await api('booking_services', {
    method: 'POST',
    body: JSON.stringify({ service_ids: sIds })
  }, bookingId);
  closeModal();
  showAlert('Services added to booking!');
  refreshAll();
}

function openStatusModal(bookingId, currentStatus) {
  document.getElementById('modalTitle').innerHTML = `<i class="fa-solid fa-sliders"></i> Booking #${bookingId} Status`;
  document.getElementById('modalBody').innerHTML = `
    <form onsubmit="saveBookingStatus(event, ${bookingId})">
      <div class="form-group" style="margin-bottom:16px;">
        <label>Status</label>
        <select id="editBookingStatus">
          <option value="Booked" ${currentStatus === 'Booked' ? 'selected' : ''}>Booked</option>
          <option value="Checked In" ${currentStatus === 'Checked In' ? 'selected' : ''}>Checked In</option>
          <option value="Checked Out" ${currentStatus === 'Checked Out' ? 'selected' : ''}>Checked Out</option>
          <option value="Cancelled" ${currentStatus === 'Cancelled' ? 'selected' : ''}>Cancelled</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa-solid fa-check"></i> Update Status</button>
    </form>
  `;
  document.getElementById('editModal').style.display = 'flex';
}

async function saveBookingStatus(e, bookingId) {
  e.preventDefault();
  await api('booking_status', {
    method: 'PUT',
    body: JSON.stringify({ status: document.getElementById('editBookingStatus').value })
  }, bookingId);
  closeModal();
  showAlert('Status updated!');
  refreshAll();
}

// Start
refreshAll();
