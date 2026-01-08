document.addEventListener('DOMContentLoaded', function () {

    // --- Global State ---
    const checkInEl = document.getElementById('check_in');
    const checkOutEl = document.getElementById('check_out');
    const summaryDatesEl = document.getElementById('summaryDates');
    const inputCheckIn = document.getElementById('inputCheckIn');
    const inputCheckOut = document.getElementById('inputCheckOut');
    const grandTotalEl = document.getElementById('grandTotal');
    const selectedContainer = document.getElementById('selectedRoomsContainer');
    const bookBtn = document.getElementById('bookBtn');

    let selectedRooms = {}; // { roomId: { qty, price, type } }

    // Config default guests if not set globally
    let guests = window.initialGuests || { adult: 1, child: 0, room: 1 };

    // --- Guest Picker Logic ---
    const guestTrigger = document.getElementById('guestTrigger');
    const guestDropdown = document.getElementById('guestDropdown');

    if (guestTrigger) {
        guestTrigger.addEventListener('click', function (e) {
            e.stopPropagation();
            guestDropdown.style.display = (guestDropdown.style.display === 'block') ? 'none' : 'block';
        });
    }

    // Close dropdown on outside click
    document.addEventListener('click', function (event) {
        if (guestDropdown && guestTrigger && !guestDropdown.contains(event.target) && !guestTrigger.contains(event.target)) {
            guestDropdown.style.display = 'none';
        }
    });

    function updateGuestDisplay() {
        // Top search bar trigger
        if (document.getElementById('guestSummary')) {
            document.getElementById('guestSummary').innerText = `${guests.adult} Adult, ${guests.child} Child, ${guests.room} Room`;
        }
        // Sidebar Booking Summary
        if (document.getElementById('summaryGuests')) {
            document.getElementById('summaryGuests').innerText = `${guests.adult} Adult, ${guests.child} Child`;
        }
        // Recalculate capacity check
        if (Object.keys(selectedRooms).length > 0) validateCapacity();
    }

    // Initialize display on page load
    if (document.getElementById('adultQty')) document.getElementById('adultQty').innerText = guests.adult;
    if (document.getElementById('childQty')) document.getElementById('childQty').innerText = guests.child;
    if (document.getElementById('roomQty')) document.getElementById('roomQty').innerText = guests.room;
    updateGuestDisplay();

    window.toggleGuestDropdown = function () {
        if (guestDropdown) guestDropdown.style.display = 'none';
    };

    // --- Date Logic ---
    function getDaysDifference() {
        if (!checkInEl || !checkOutEl) return 0;
        const inDate = new Date(checkInEl.value);
        const outDate = new Date(checkOutEl.value);

        if (isNaN(inDate.getTime()) || isNaN(outDate.getTime()) || inDate >= outDate) {
            return 0; // Invalid or negative duration
        }

        const diffTime = Math.abs(outDate - inDate);
        const days = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return days > 0 ? days : 1;
    }

    function updateDates() {
        const days = getDaysDifference();

        // Update Summary Sidebar
        if (summaryDatesEl) {
            if (days > 0) {
                summaryDatesEl.innerText = `${checkInEl.value} to ${checkOutEl.value} (${days} Nights)`;
            } else {
                summaryDatesEl.innerText = "Select Valid Dates";
            }
        }

        // Update Hidden Inputs
        if (days > 0) {
            if (inputCheckIn) inputCheckIn.value = checkInEl.value;
            if (inputCheckOut) inputCheckOut.value = checkOutEl.value;
        }

        // Recalculate prices whenever dates change
        calculateTotal();
    }

    if (checkInEl && checkOutEl) {
        checkInEl.addEventListener('change', updateDates);
        checkOutEl.addEventListener('change', updateDates);
        // Initial sync
        updateDates();
    }


    // --- Room Selection Logic ---
    // Make updateSelection global so onchange works
    window.updateSelection = function (selectEl) {
        let id = selectEl.getAttribute('data-id');
        let price = parseFloat(selectEl.getAttribute('data-price')) || 0;
        let type = selectEl.getAttribute('data-type');
        let capacity = parseInt(selectEl.getAttribute('data-capacity')) || 2;
        let qty = parseInt(selectEl.value) || 0;

        if (qty > 0) {
            selectedRooms[id] = { qty, price, type, capacity };
            if (bookBtn) bookBtn.disabled = false;
        } else {
            delete selectedRooms[id];
            // Check if any rooms left
            if (Object.keys(selectedRooms).length === 0) {
                if (bookBtn) bookBtn.disabled = true;
            }
        }

        renderSidebar();
    };

    function renderSidebar() {
        if (!selectedContainer) return;
        selectedContainer.innerHTML = '';

        const keys = Object.keys(selectedRooms);
        if (keys.length === 0) {
            selectedContainer.innerHTML = '<small class="text-muted fst-italic">No rooms selected</small>';
            if (grandTotalEl) grandTotalEl.innerText = 'NPR 0';
            // Clear any previous warnings
            let existingWarning = document.getElementById('capacityWarning');
            if (existingWarning) existingWarning.remove();
            return;
        }

        keys.forEach(key => {
            let item = selectedRooms[key];
            let row = document.createElement('div');
            row.className = 'd-flex justify-content-between mb-2 small';
            row.innerHTML = `<span>${item.type} <span class="text-muted">x${item.qty}</span></span> <span class="fw-bold">NPR ${item.price * item.qty}</span>`;
            selectedContainer.appendChild(row);
        });

        validateCapacity();
        calculateTotal();
    }

    function validateCapacity() {
        let existingWarning = document.getElementById('capacityWarning');
        if (existingWarning) existingWarning.remove();

        // Calculate total guests vs total capacity
        let totalGuests = guests.adult + guests.child;
        let totalCapacity = 0;

        const keys = Object.keys(selectedRooms);
        keys.forEach(key => {
            let item = selectedRooms[key];
            totalCapacity += (item.qty * item.capacity);
        });

        if (totalCapacity < totalGuests) {
            let warning = document.createElement('div');
            warning.id = 'capacityWarning';
            warning.className = 'alert alert-danger p-2 small mt-2 mb-2';
            warning.innerHTML = `<i class="fas fa-exclamation-circle"></i> <strong>Capacity Exceeded!</strong><br> You selected rooms for ${totalCapacity} people, but have ${totalGuests} guests. Please select more rooms.`;
            selectedContainer.appendChild(warning);
        }
    }

    // Call validation when guests change too
    window.updateQty = function (type, change) {
        if (type === 'adult') {
            guests.adult = Math.max(1, guests.adult + change);
            if (document.getElementById('adultQty')) document.getElementById('adultQty').innerText = guests.adult;
        } else if (type === 'child') {
            guests.child = Math.max(0, guests.child + change);
            if (document.getElementById('childQty')) document.getElementById('childQty').innerText = guests.child;
        } else if (type === 'room') {
            guests.room = Math.max(1, guests.room + change);
            if (document.getElementById('roomQty')) document.getElementById('roomQty').innerText = guests.room;
        }
        updateGuestDisplay();

        // Re-validate logic if rooms are selected
        const keys = Object.keys(selectedRooms);
        if (keys.length > 0) {
            validateCapacity();
        }
    };

    function calculateTotal() {
        const keys = Object.keys(selectedRooms);
        if (keys.length === 0) {
            if (grandTotalEl) grandTotalEl.innerText = 'NPR 0';
            return;
        }

        let days = getDaysDifference();
        if (days === 0) days = 1; // Fallback for display if dates invalid

        let total = 0;
        keys.forEach(key => {
            let item = selectedRooms[key];
            total += (item.price * item.qty * days);
        });

        if (grandTotalEl) grandTotalEl.innerText = 'NPR ' + total.toLocaleString();
    }

    // --- Booking Submission ---
    window.submitBooking = function () {
        const cin = document.getElementById('inputCheckIn') ? document.getElementById('inputCheckIn').value : '';
        const cout = document.getElementById('inputCheckOut') ? document.getElementById('inputCheckOut').value : '';

        if (Object.keys(selectedRooms).length === 0) {
            alert("Please select at least one room.");
            return;
        }

        if (!cin || !cout) {
            alert("Please select valid check-in and check-out dates.");
            return;
        }

        // Prepare JSON data for room selection
        // Structure: [ {id: 1, qty: 2}, ... ]
        let roomData = [];
        Object.keys(selectedRooms).forEach(key => {
            roomData.push({
                id: key,
                qty: selectedRooms[key].qty
            });
        });

        let roomJson = encodeURIComponent(JSON.stringify(roomData));

        // Redirect with params
        window.location.href = `booking.php?rooms=${roomJson}&check_in=${cin}&check_out=${cout}`;
    };

    window.checkAvailability = function () {
        // Reload page with selected dates AND guest counts to trigger backend availability check
        const cin = document.getElementById('check_in').value;
        const cout = document.getElementById('check_out').value;
        const url = new URL(window.location.href);
        url.searchParams.set('check_in', cin);
        url.searchParams.set('check_out', cout);
        url.searchParams.set('adults', guests.adult);
        url.searchParams.set('children', guests.child);
        url.searchParams.set('rooms_count', guests.room);

        const btn = document.querySelector('button[onclick="checkAvailability()"]');
        if (btn) btn.innerText = "Checking...";

        window.location.href = url.toString();
    };

    // --- Scrollspy Logic ---
    const sections = document.querySelectorAll('#overview, #availability, #amenities');
    const navLinks = document.querySelectorAll('.nav-link');
    const navHeight = 100; // rough height of sticky header

    window.addEventListener('scroll', () => {
        let current = '';

        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            if (pageYOffset >= (sectionTop - navHeight)) {
                current = section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href').includes(current)) {
                link.classList.add('active');
            }
        });
    });

});
