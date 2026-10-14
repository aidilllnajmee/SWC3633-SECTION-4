// =====================================================================
// Customer Ticketing Portal Logic - Student 3 (Co-Member)
// =====================================================================

let activeCategoryId = '';
let searchDebounceTimer = null;
let currentEventForBooking = null;

document.addEventListener('DOMContentLoaded', async () => {
    await ensureCustomerAuthenticated();
    await loadCategories();
    loadCatalogEvents();
    updateBookingsCount();
});

async function ensureCustomerAuthenticated() {
    let token = ApiClient.getToken();
    let user = ApiClient.getUser();

    if (token && user) {
        document.getElementById('customer-name').textContent = user.full_name;
        const initials = user.full_name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        document.getElementById('customer-avatar').textContent = initials;
    } else {
        const nameEl = document.getElementById('customer-name');
        if (nameEl) nameEl.textContent = 'Guest User';
        const avatarEl = document.getElementById('customer-avatar');
        if (avatarEl) avatarEl.textContent = '👤';
        const navUser = document.querySelector('.nav-user');
        if (navUser) {
            const btn = navUser.querySelector('button');
            if (btn) {
                btn.textContent = 'Sign In';
                btn.className = 'btn btn-primary btn-sm';
                btn.onclick = () => window.location.href = 'login.html';
            }
        }
    }
}

// ---------------------------------------------------------------------
// 1. CATEGORIES & SEARCH
// ---------------------------------------------------------------------
async function loadCategories() {
    try {
        const res = await ApiClient.get('categories');
        const categories = Array.isArray(res.data) ? res.data : [];
        const container = document.getElementById('category-pills');
        const pills = categories.map(c => `
            <button class="pill" data-cat="${c.id}" onclick="selectCategory(this, '${c.id}')">${c.category_name}</button>
        `).join('');
        container.innerHTML = `<button class="pill active" data-cat="" onclick="selectCategory(this, '')">All Categories</button>` + pills;
    } catch (err) {
        console.error("Error loading categories:", err);
    }
}

function selectCategory(btn, catId) {
    document.querySelectorAll('.category-pills .pill').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    activeCategoryId = catId;
    loadCatalogEvents();
}

function debounceSearch() {
    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => {
        loadCatalogEvents();
    }, 300);
}

// ---------------------------------------------------------------------
// 2. EVENTS CATALOG
// ---------------------------------------------------------------------
async function loadCatalogEvents() {
    const grid = document.getElementById('catalog-grid');
    grid.innerHTML = `<div class="loading-state">Loading event listings from REST API...</div>`;

    const search = document.getElementById('catalog-search').value.trim();
    const sortVal = document.getElementById('catalog-sort').value;
    const [sortCol, sortOrder] = sortVal.split(':');

    const params = {
        status: 'published',
        limit: 50,
        sort: sortCol,
        order: sortOrder
    };

    if (search) params.search = search;
    if (activeCategoryId) params.category_id = activeCategoryId;

    try {
        const res = await ApiClient.get('events', params);
        const events = Array.isArray(res.data) ? res.data : [];
        document.getElementById('results-count').textContent = `Showing ${events.length} event(s)`;

        if (events.length === 0) {
            grid.innerHTML = `<div class="loading-state">No events found matching your query.</div>`;
            return;
        }

        grid.innerHTML = events.map(e => `
            <div class="event-card">
                <img class="event-banner" src="${e.banner_image || 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800'}" alt="${e.title}" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800';">
                <div class="event-body">
                    <span class="event-category-tag" style="color:${e.badge_color}">${e.category_name}</span>
                    <h3 class="event-title">${e.title}</h3>
                    <div class="event-meta">
                        📍 ${e.venue_name}, ${e.venue_city}<br>
                        📅 ${new Date(e.start_time).toLocaleDateString('en-MY', { weekday:'short', day:'numeric', month:'short', year:'numeric'})}
                    </div>
                    <p class="event-desc">${e.description.substring(0, 100)}...</p>
                    
                    <div class="event-footer">
                        <div>
                            <div class="ticket-price">RM ${Number(e.ticket_price).toFixed(2)}</div>
                            <span class="ticket-seats">${e.available_tickets} seats remaining</span>
                        </div>
                        <button class="btn btn-primary btn-sm" onclick='openBookingModal(${JSON.stringify(e)})' ${e.available_tickets <= 0 ? 'disabled' : ''}>
                            ${e.available_tickets > 0 ? 'Reserve Seat' : 'Sold Out'}
                        </button>
                    </div>
                </div>
            </div>
        `).join('');

    } catch (err) {
        grid.innerHTML = `<div class="loading-state text-danger">Error: ${err.message}</div>`;
    }
}

// ---------------------------------------------------------------------
// 3. BOOKING RESERVATION & CHECKOUT
// ---------------------------------------------------------------------
function openBookingModal(e) {
    if (!ApiClient.getToken()) {
        if (confirm("Please sign in with your Customer account to reserve event tickets.\n\nClick OK to open the Sign In page.")) {
            window.location.href = 'login.html';
        }
        return;
    }

    currentEventForBooking = e;
    document.getElementById('book-event-id').value = e.id;
    document.getElementById('book-event-title').textContent = e.title;
    document.getElementById('book-event-venue').textContent = `${e.venue_name} • ${e.venue_city}`;
    document.getElementById('book-event-unit-price').textContent = `RM ${Number(e.ticket_price).toFixed(2)}`;
    
    document.getElementById('book-qty').value = 1;
    document.getElementById('book-qty').max = Math.min(10, e.available_tickets);
    
    updateSummary();
    document.getElementById('booking-modal').classList.remove('hidden');
}

function closeBookingModal() {
    document.getElementById('booking-modal').classList.add('hidden');
    currentEventForBooking = null;
}

function changeQty(delta) {
    const qtyInput = document.getElementById('book-qty');
    let val = parseInt(qtyInput.value) + delta;
    const max = parseInt(qtyInput.max) || 10;
    if (val < 1) val = 1;
    if (val > max) val = max;
    qtyInput.value = val;
    updateSummary();
}

function updateSummary() {
    if (!currentEventForBooking) return;
    const qty = parseInt(document.getElementById('book-qty').value);
    const unit = parseFloat(currentEventForBooking.ticket_price);
    const total = qty * unit;

    document.getElementById('summary-qty').textContent = `${qty} pass(es)`;
    document.getElementById('summary-unit').textContent = `RM ${unit.toFixed(2)}`;
    document.getElementById('summary-total').textContent = `RM ${total.toFixed(2)}`;
}

async function handleBookingSubmit(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-confirm-checkout');
    btn.disabled = true;
    btn.textContent = 'Contacting External QR API...';

    const payload = {
        event_id: parseInt(document.getElementById('book-event-id').value),
        ticket_quantity: parseInt(document.getElementById('book-qty').value),
        payment_method: document.getElementById('book-payment-method').value
    };

    try {
        const res = await ApiClient.post('bookings', payload);
        showToast('Ticket reservation confirmed! Your PDF pass is ready.', 'success');
        closeBookingModal();
        
        const bookingData = {
            booking_reference: res.data.booking_reference,
            event_title: res.data.event_title || currentEventForBooking?.title,
            event_date: res.data.event_date || currentEventForBooking?.start_time,
            venue_name: res.data.venue_name || currentEventForBooking?.venue_name,
            venue_address: res.data.venue_address || currentEventForBooking?.venue_address,
            venue_city: res.data.venue_city || currentEventForBooking?.venue_city,
            ticket_quantity: res.data.ticket_quantity,
            total_amount: res.data.total_amount,
            payment_method: res.data.payment_method,
            customer_name: res.data.customer_name || ApiClient.getUser()?.name || 'Customer',
            customer_email: res.data.customer_email || ApiClient.getUser()?.email || '',
            qr_code_url: res.data.qr_code_url,
            created_at: res.data.created_at || new Date().toISOString()
        };

        // Show ticket pass modal immediately with Download PDF option
        viewTicketPass(bookingData);
        
        loadCatalogEvents();
        updateBookingsCount();
    } catch (err) {
        showToast(err.message, 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Confirm & Generate QR Pass';
    }
}

// ---------------------------------------------------------------------
// 4. MY BOOKINGS & DIGITAL PASSES
// ---------------------------------------------------------------------
async function updateBookingsCount() {
    try {
        const res = await ApiClient.get('bookings');
        const count = Array.isArray(res.data) ? res.data.length : 0;
        document.getElementById('my-bookings-count').textContent = count;
    } catch (err) {
        // quiet
    }
}

async function loadMyBookings() {
    const grid = document.getElementById('my-bookings-grid');
    grid.innerHTML = `<div class="loading-state">Loading your ticket passes...</div>`;

    try {
        const res = await ApiClient.get('bookings');
        const bookings = Array.isArray(res.data) ? res.data : [];
        document.getElementById('my-bookings-count').textContent = bookings.length;

        if (bookings.length === 0) {
            grid.innerHTML = `<div class="loading-state">You do not have any active bookings yet. Browse events to make a reservation!</div>`;
            return;
        }

        grid.innerHTML = bookings.map(b => {
            const bJson = encodeURIComponent(JSON.stringify(b));
            return `
            <div class="booking-card">
                <div class="booking-card-header">
                    <div>
                        <span class="booking-ref-badge">${b.booking_reference}</span>
                        <h4 style="margin-top:6px;font-size:16px;">${b.event_title}</h4>
                        <small class="text-muted">📍 ${b.venue_name} (${b.venue_city})</small>
                    </div>
                    <span class="booking-status-${b.booking_status}">${b.booking_status}</span>
                </div>

                <div style="font-size:13px;line-height:1.8;color:#475569;">
                    🎟️ <strong>Reserved Passes:</strong> ${b.ticket_quantity} tickets<br>
                    💳 <strong>Amount Paid:</strong> RM ${Number(b.total_amount).toFixed(2)} (${b.payment_method})<br>
                    🕒 <strong>Booking Date:</strong> ${new Date(b.created_at).toLocaleDateString()}
                </div>

                <div class="booking-actions">
                    <button class="btn btn-primary btn-sm" onclick="viewTicketPassFromData('${bJson}')">
                        📲 View Pass
                    </button>
                    <button class="btn btn-outline btn-sm" onclick="printBookingFromData('${bJson}')">
                        🖨️ Print / Save as PDF
                    </button>
                    ${b.booking_status === 'confirmed' ? `
                        <button class="btn btn-outline-danger btn-sm" onclick="cancelBooking(${b.id}, '${b.booking_reference}')">
                            Cancel
                        </button>
                    ` : ''}
                </div>
            </div>
            `;
        }).join('');

    } catch (err) {
        grid.innerHTML = `<div class="loading-state text-danger">Error: ${err.message}</div>`;
    }
}

let currentActiveBooking = null;

function viewTicketPassFromData(encoded) {
    try {
        const b = JSON.parse(decodeURIComponent(encoded));
        viewTicketPass(b);
    } catch(err) {
        console.error(err);
    }
}

function printBookingFromData(encoded) {
    try {
        const b = JSON.parse(decodeURIComponent(encoded));
        printTicketHtml(generateTicketHtml(b, b.qr_code_url), b.booking_reference);
    } catch(err) {
        console.error(err);
    }
}

function viewTicketPass(b) {
    currentActiveBooking = b;
    document.getElementById('ticket-qr-img').src = b.qr_code_url;
    document.getElementById('ticket-ref').textContent = b.booking_reference;
    document.getElementById('ticket-title').textContent = b.event_title;
    document.getElementById('ticket-venue').textContent = `📍 ${b.venue_name || 'Event Venue'}${b.venue_city ? ` (${b.venue_city})` : ''}`;
    document.getElementById('ticket-datetime').textContent = b.event_date ? '📅 ' + new Date(b.event_date).toLocaleString('en-MY', {dateStyle:'full', timeStyle:'short'}) : '';
    document.getElementById('ticket-qty').textContent = `${b.ticket_quantity} pass(es)`;
    document.getElementById('ticket-amount').textContent = `RM ${Number(b.total_amount).toFixed(2)} (${b.payment_method || 'Online'})`;
    document.getElementById('ticket-attendee').textContent = b.customer_name || ApiClient.getUser()?.name || 'Customer';
    document.getElementById('ticket-modal').classList.remove('hidden');
}

function closeTicketModal() {
    document.getElementById('ticket-modal').classList.add('hidden');
}

// ---------------------------------------------------------------------
// 5. PDF TICKET GENERATOR & PRINTER
// ---------------------------------------------------------------------
function generateTicketHtml(b, qrSrc) {
    const attendee = b.customer_name || ApiClient.getUser()?.name || 'Customer';
    const email = b.customer_email || ApiClient.getUser()?.email || '';
    const dateFormatted = b.event_date ? new Date(b.event_date).toLocaleString('en-MY', {dateStyle:'full', timeStyle:'short'}) : 'Scheduled Event';
    const totalRM = Number(b.total_amount).toFixed(2);
    const bookingDate = new Date(b.created_at || Date.now()).toLocaleDateString('en-MY');
    const qrImageSource = qrSrc || b.qr_code_url;

    return `
    <div style="font-family:'Plus Jakarta Sans', Arial, sans-serif; width:650px; margin:0 auto; background:#ffffff; border:2px solid #0f172a; border-radius:16px; overflow:hidden; color:#0f172a; box-sizing:border-box;">
        <!-- Header -->
        <div style="background:#0f172a; color:#ffffff; padding:22px 28px; display:flex; justify-content:space-between; align-items:center; border-bottom:3px solid #2563eb;">
            <div>
                <div style="font-size:11px; font-weight:800; letter-spacing:1.5px; text-transform:uppercase; color:#93c5fd; margin-bottom:4px;">EVENTHUB OFFICIAL ADMISSION PASS</div>
                <h1 style="font-size:20px; font-weight:800; margin:0; line-height:1.2; color:#ffffff;">${b.event_title}</h1>
            </div>
            <div style="text-align:right;">
                <div style="background:#10b981; color:#ffffff; padding:4px 12px; border-radius:20px; font-size:11px; font-weight:800; letter-spacing:0.5px; display:inline-block;">VERIFIED E-TICKET</div>
                <div style="font-size:11px; color:#cbd5e1; margin-top:4px; font-family:monospace;">${b.booking_reference}</div>
            </div>
        </div>

        <!-- Body -->
        <div style="padding:24px 28px; display:flex; gap:20px; align-items:center; background:#ffffff;">
            <!-- Left Info -->
            <div style="flex:1;">
                <div style="margin-bottom:12px;">
                    <div style="font-size:10px; color:#64748b; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">Scheduled Date & Time</div>
                    <div style="font-size:14px; font-weight:700; color:#0f172a;">📅 ${dateFormatted}</div>
                </div>
                
                <div style="margin-bottom:12px;">
                    <div style="font-size:10px; color:#64748b; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">Venue Location</div>
                    <div style="font-size:14px; font-weight:700; color:#0f172a;">📍 ${b.venue_name || 'Event Venue'}</div>
                    <div style="font-size:12px; color:#475569;">${b.venue_address || ''} ${b.venue_city ? `(${b.venue_city})` : ''}</div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:12px; padding-top:10px; border-top:1px dashed #cbd5e1;">
                    <div>
                        <div style="font-size:10px; color:#64748b; font-weight:700; text-transform:uppercase;">Registered Attendee</div>
                        <div style="font-size:13px; font-weight:700;">${attendee}</div>
                        ${email ? `<div style="font-size:11px; color:#64748b;">${email}</div>` : ''}
                    </div>
                    <div>
                        <div style="font-size:10px; color:#64748b; font-weight:700; text-transform:uppercase;">Total Passes</div>
                        <div style="font-size:15px; font-weight:800; color:#2563eb;">🎟️ ${b.ticket_quantity} Pass(es)</div>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:10px;">
                    <div>
                        <div style="font-size:10px; color:#64748b; font-weight:700; text-transform:uppercase;">Amount Paid</div>
                        <div style="font-size:14px; font-weight:700; color:#15803d;">RM ${totalRM}</div>
                    </div>
                    <div>
                        <div style="font-size:10px; color:#64748b; font-weight:700; text-transform:uppercase;">Payment Method</div>
                        <div style="font-size:12px; font-weight:600; color:#334155;">${b.payment_method || 'Online FPX'}</div>
                    </div>
                </div>
            </div>

            <!-- Right QR Code -->
            <div style="width:170px; text-align:center; padding-left:16px; border-left:2px dashed #cbd5e1; flex-shrink:0;">
                <div style="background:#ffffff; padding:8px; border:2px solid #e2e8f0; border-radius:10px; display:inline-block;">
                    <img src="${qrImageSource}" alt="Ticket QR Code" style="width:140px; height:140px; display:block;">
                </div>
                <div style="font-family:monospace; font-size:11px; font-weight:700; color:#1e293b; margin-top:6px;">${b.booking_reference}</div>
                <div style="font-size:10px; color:#64748b; margin-top:2px;">Scan at Entrance</div>
            </div>
        </div>

        <!-- Footer -->
        <div style="background:#f8fafc; border-top:1px solid #e2e8f0; padding:10px 28px; display:flex; justify-content:space-between; align-items:center; font-size:11px; color:#64748b;">
            <div>🔒 Official QR Admission Pass • Generated via QRServer REST API</div>
            <div>Issued: ${bookingDate}</div>
        </div>
    </div>
    `;
}

function printCurrentTicket() {
    if (!currentActiveBooking) return;
    printTicketHtml(generateTicketHtml(currentActiveBooking, currentActiveBooking.qr_code_url), currentActiveBooking.booking_reference);
}

function printTicketHtml(htmlContent, ref) {
    const printWindow = window.open('', '_blank', 'width=800,height=900');
    if (!printWindow) {
        alert('Please allow popups to print or save your ticket as PDF.');
        return;
    }
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>EventHub Admission Pass - ${ref}</title>
            <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
            <style>
                body { margin: 30px auto; max-width: 720px; font-family: 'Plus Jakarta Sans', sans-serif; background: #fff; }
                @media print {
                    body { margin: 0; padding: 0; max-width: 100%; }
                    @page { size: auto; margin: 10mm; }
                }
            </style>
        </head>
        <body>
            ${htmlContent}
            <script>
                const img = document.querySelector('img');
                if (img) {
                    if (img.complete) {
                        setTimeout(() => { window.focus(); window.print(); }, 200);
                    } else {
                        img.onload = () => { setTimeout(() => { window.focus(); window.print(); }, 200); };
                        img.onerror = () => { setTimeout(() => { window.focus(); window.print(); }, 200); };
                    }
                } else {
                    setTimeout(() => { window.focus(); window.print(); }, 200);
                }
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

async function cancelBooking(id, ref) {
    if (!confirm(`Are you sure you want to cancel booking '${ref}'? Your tickets will be released back to the event pool.`)) return;

    try {
        await ApiClient.delete(`bookings/${id}`);
        showToast(`Booking ${ref} cancelled successfully`, 'success');
        loadMyBookings();
        updateBookingsCount();
    } catch (err) {
        showToast(err.message, 'error');
    }
}

// ---------------------------------------------------------------------
// 5. VIEW TOGGLES
// ---------------------------------------------------------------------
function showCatalogView() {
    document.getElementById('catalog-view').classList.remove('hidden');
    document.getElementById('bookings-view').classList.add('hidden');
    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
    document.querySelector('.nav-link[href="#events"]').classList.add('active');
}

function showBookingsView() {
    document.getElementById('catalog-view').classList.add('hidden');
    document.getElementById('bookings-view').classList.remove('hidden');
    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
    document.querySelector('.nav-link[href="#my-bookings"]').classList.add('active');
    loadMyBookings();
}

function logoutCustomer() {
    ApiClient.logout('login.html');
}

