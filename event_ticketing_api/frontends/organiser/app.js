// =====================================================================
// Organiser Portal Logic - Student 2 (Co-Member)
// =====================================================================

let currentTab = 'events';
let cachedVenues = [];

document.addEventListener('DOMContentLoaded', async () => {
    setupTabNavigation();
    await ensureOrganiserAuthenticated();
    await loadFormData();
    loadMyEvents();
});

function ensureOrganiserAuthenticated() {
    const token = ApiClient.getToken();
    const user = ApiClient.getUser();

    if (!token || (user?.role !== 'organiser' && user?.role !== 'admin')) {
        window.location.replace('login.html');
        return false;
    }

    document.getElementById('organiser-name').textContent = user.full_name || 'Sarah Tan';
    document.getElementById('organiser-email').textContent = user.email || 'sarah.organiser@eventhub.my';
    const initials = (user.full_name || 'ST').split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
    document.getElementById('organiser-initials').textContent = initials;
    return true;
}

function setupTabNavigation() {
    const navItems = document.querySelectorAll('.sidebar-nav .nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            navItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');

            const tab = item.getAttribute('data-tab');
            currentTab = tab;
            switchTab(tab);
        });
    });
}

function switchTab(tab) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    const target = document.getElementById(`tab-${tab}`);
    if (target) target.classList.add('active');

    const titles = {
        events: 'My Hosted Events',
        create: 'Schedule New Event',
        roster: 'Attendee Bookings Roster'
    };
    document.getElementById('page-title').textContent = titles[tab] || 'Organiser Panel';

    if (tab === 'events') loadMyEvents();
    if (tab === 'roster') loadAttendeeRoster();
}

async function loadFormData() {
    try {
        const [catRes, venRes] = await Promise.all([
            ApiClient.get('categories'),
            ApiClient.get('venues')
        ]);

        const categories = Array.isArray(catRes.data) ? catRes.data : [];
        const catSelect = document.getElementById('event-category');
        catSelect.innerHTML = `<option value="">Select Category</option>` + categories.map(c => `
            <option value="${c.id}">${c.category_name}</option>
        `).join('');

        cachedVenues = Array.isArray(venRes.data) ? venRes.data : [];
        const venSelect = document.getElementById('event-venue');
        venSelect.innerHTML = `<option value="">Select Venue</option>` + cachedVenues.map(v => `
            <option value="${v.id}">${v.venue_name} (${v.city} - ${v.capacity} seats)</option>
        `).join('');

    } catch (err) {
        console.error("Failed to load categories/venues:", err);
    }
}

function handleVenueChange() {
    const venId = parseInt(document.getElementById('event-venue').value);
    const venue = cachedVenues.find(v => v.id === venId);
    const capInput = document.getElementById('event-capacity');
    const capHint = document.getElementById('venue-cap-hint');

    if (venue) {
        capHint.textContent = `(Max venue limit: ${venue.capacity} seats)`;
        capInput.max = venue.capacity;
        if (!capInput.value || parseInt(capInput.value) > venue.capacity) {
            capInput.value = venue.capacity;
        }
    } else {
        capHint.textContent = '';
    }
}

// ---------------------------------------------------------------------
// 1. MY HOSTED EVENTS
// ---------------------------------------------------------------------
async function loadMyEvents() {
    const grid = document.getElementById('my-events-grid');
    grid.innerHTML = `<div class="loading-state">Loading your hosted events...</div>`;

    try {
        const res = await ApiClient.get('events', { limit: 50 });
        const allEvents = Array.isArray(res.data) ? res.data : [];
        const user = ApiClient.getUser();
        
        // Filter events hosted by Sarah (or show all if admin/test)
        const myEvents = allEvents.filter(e => e.organiser_id === user?.id || user?.role === 'admin' || !e.organiser_id);

        if (myEvents.length === 0) {
            grid.innerHTML = `<div class="loading-state">You have not scheduled any events yet. Click "+ Schedule Event" to begin!</div>`;
            return;
        }

        grid.innerHTML = myEvents.map(e => {
            const sold = e.total_tickets - e.available_tickets;
            const pct = Math.round((sold / e.total_tickets) * 100);
            return `
                <div class="event-card">
                    <img class="event-card-img" src="${e.banner_image || 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800'}" alt="${e.title}" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800';">
                    <div class="event-card-body">
                        <div class="event-card-title">${e.title}</div>
                        <div class="event-card-meta">
                            📍 ${e.venue_name} (${e.venue_city})<br>
                            🕒 ${new Date(e.start_time).toLocaleDateString()} at ${new Date(e.start_time).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}
                        </div>
                        <p style="font-size:12px;color:#64748b;margin-bottom:8px;">${e.description.substring(0, 90)}...</p>
                        
                        <div style="font-size:12px;display:flex;justify-content:space-between;">
                            <span>Ticket Sales: <strong>${sold} / ${e.total_tickets}</strong></span>
                            <span><strong>${pct}%</strong></span>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar" style="width:${pct}%"></div>
                        </div>

                        <div class="card-actions">
                            <button class="btn btn-secondary btn-sm" onclick='editEvent(${JSON.stringify(e)})'>Edit Event</button>
                            <button class="btn btn-outline-danger btn-sm" onclick="deleteEvent(${e.id})">Delete</button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

    } catch (err) {
        grid.innerHTML = `<div class="loading-state text-danger">Failed to load events: ${err.message}</div>`;
    }
}

function editEvent(e) {
    document.getElementById('form-event-title').textContent = `Edit Event: ${e.title}`;
    document.getElementById('event-id').value = e.id;
    document.getElementById('event-title').value = e.title;
    document.getElementById('event-desc').value = e.description;
    document.getElementById('event-category').value = e.category_id;
    document.getElementById('event-venue').value = e.venue_id;
    document.getElementById('event-price').value = e.ticket_price;
    document.getElementById('event-capacity').value = e.total_tickets;
    document.getElementById('event-banner').value = e.banner_image || '';
    previewEventImage(e.banner_image || '');

    // Convert SQL datetime to datetime-local string
    const formatLocal = (sqlStr) => sqlStr.replace(' ', 'T').substring(0, 16);
    document.getElementById('event-start').value = formatLocal(e.start_time);
    document.getElementById('event-end').value = formatLocal(e.end_time);

    document.getElementById('event-save-btn').textContent = 'Update Event Details';
    switchTab('create');
}

async function handleEventSubmit(e) {
    e.preventDefault();
    const id = document.getElementById('event-id').value;

    const payload = {
        title: document.getElementById('event-title').value,
        description: document.getElementById('event-desc').value,
        category_id: parseInt(document.getElementById('event-category').value),
        venue_id: parseInt(document.getElementById('event-venue').value),
        start_time: document.getElementById('event-start').value.replace('T', ' ') + ':00',
        end_time: document.getElementById('event-end').value.replace('T', ' ') + ':00',
        ticket_price: parseFloat(document.getElementById('event-price').value),
        total_tickets: parseInt(document.getElementById('event-capacity').value),
        banner_image: document.getElementById('event-banner').value || undefined
    };

    try {
        if (id) {
            await ApiClient.put(`events/${id}`, payload);
            showToast('Event details updated successfully!', 'success');
        } else {
            await ApiClient.post('events', payload);
            showToast('Event published successfully!', 'success');
        }

        document.getElementById('event-form').reset();
        document.getElementById('event-id').value = '';
        const previewContainer = document.getElementById('image-preview-container');
        if (previewContainer) previewContainer.style.display = 'none';
        document.getElementById('event-save-btn').textContent = 'Publish Event';
        document.getElementById('form-event-title').textContent = 'Schedule New Event';
        switchTab('events');
    } catch (err) {
        showToast(err.message, 'error');
    }
}

async function deleteEvent(id) {
    if (!confirm(`Are you sure you want to cancel and remove event ID ${id}?`)) return;

    try {
        await ApiClient.delete(`events/${id}`);
        showToast('Event removed successfully', 'success');
        loadMyEvents();
    } catch (err) {
        showToast(err.message, 'error');
    }
}

// ---------------------------------------------------------------------
// 2. ATTENDEE ROSTER
// ---------------------------------------------------------------------
async function loadAttendeeRoster() {
    const tbody = document.getElementById('roster-table-body');
    tbody.innerHTML = `<tr><td colspan="7" class="text-center">Loading attendee registry...</td></tr>`;

    try {
        const res = await ApiClient.get('bookings');
        const bookings = Array.isArray(res.data) ? res.data : [];
        if (bookings.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center">No ticket bookings recorded yet.</td></tr>`;
            return;
        }

        tbody.innerHTML = bookings.map(b => `
            <tr>
                <td><strong>${b.booking_reference}</strong></td>
                <td>${b.customer_name}<br><small class="text-muted">${b.customer_email}</small></td>
                <td>${b.event_title}</td>
                <td>${b.ticket_quantity} pass(es)</td>
                <td>RM ${Number(b.total_amount).toFixed(2)}</td>
                <td><span style="color:#15803d;font-weight:600;">${b.booking_status}</span></td>
                <td>
                    <button class="btn btn-outline btn-sm" onclick="viewQrModal('${b.qr_code_url}', '${b.booking_reference}')">View QR Pass</button>
                </td>
            </tr>
        `).join('');

    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">Error: ${err.message}</td></tr>`;
    }
}

function viewQrModal(url, ref) {
    document.getElementById('qr-preview-img').src = url;
    document.getElementById('qr-modal-ref').textContent = ref;
    document.getElementById('qr-modal').classList.remove('hidden');
}

function closeQrModal() {
    document.getElementById('qr-modal').classList.add('hidden');
}

function logoutOrganiser() {
    ApiClient.logout('login.html');
}

// ---------------------------------------------------------------------
// 4. LIVE IMAGE PREVIEW & ERROR HANDLING
// ---------------------------------------------------------------------
function previewEventImage(url) {
    const container = document.getElementById('image-preview-container');
    const img = document.getElementById('image-preview');
    const status = document.getElementById('image-preview-status');
    if (!container || !img || !status) return;

    if (!url || !url.trim()) {
        container.style.display = 'none';
        return;
    }

    container.style.display = 'block';
    img.src = url.trim();
    status.style.color = '#0284c7';
    status.textContent = '🔄 Loading image preview...';

    img.onload = () => {
        status.style.color = '#10b981';
        status.textContent = '✓ Direct image link verified!';
    };
}

function handleImagePreviewError() {
    const status = document.getElementById('image-preview-status');
    if (!status) return;
    status.style.color = '#ef4444';
    status.textContent = '⚠️ Invalid image link! This is a webpage or broken link. Please right-click the photo itself and select "Copy Image Address".';
}
