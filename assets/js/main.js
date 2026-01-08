document.addEventListener('DOMContentLoaded', function() {
    
    // --- Global Configuration ---
    const CONFIG = {
        minGuests: { adult: 1, child: 0, room: 1 },
        selectors: {
            guestToggle: '#guestToggle',
            guestDropdown: '#homeGuestDropdown', // Used in index/hotels
            guestLabel: '#guestLabel',
            destinationInput: '#destinationInput',
            suggestionsList: '#suggestionsList',
            checkIn: '#checkIn',
            checkOut: '#checkOut',
            // Inputs for form submission
            inputAdults: '#inputAdults',
            inputChildren: '#inputChildren',
            inputRooms: '#inputRooms',
            // Display counters
            qtyAdult: '#hAdultQty',
            qtyChild: '#hChildQty',
            qtyRoom: '#hRoomQty'
        }
    };

    // --- State Management ---
    let guestState = {
        adult: parseInt(document.getElementById('inputAdults')?.value || 1),
        child: parseInt(document.getElementById('inputChildren')?.value || 0),
        room: parseInt(document.getElementById('inputRooms')?.value || 1)
    };

    // --- Guest Dropdown Logic ---
    const guestToggle = document.querySelector(CONFIG.selectors.guestToggle);
    const guestDropdown = document.querySelector(CONFIG.selectors.guestDropdown);
    const guestLabel = document.querySelector(CONFIG.selectors.guestLabel);

    if (guestToggle && guestDropdown) {
        // Toggle Dropdown
        guestToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const isVisible = guestDropdown.style.display === 'block';
            guestDropdown.style.display = isVisible ? 'none' : 'block';
        });

        // Close when clicking outside
        document.addEventListener('click', (e) => {
            if (!guestDropdown.contains(e.target) && !guestToggle.contains(e.target)) {
                guestDropdown.style.display = 'none';
            }
        });

        // Expose update function globally or handle via event delegation
        window.updateHomeGuest = function(type, change) {
            if (type === 'adult') guestState.adult = Math.max(CONFIG.minGuests.adult, guestState.adult + change);
            if (type === 'child') guestState.child = Math.max(CONFIG.minGuests.child, guestState.child + change);
            if (type === 'room') guestState.room = Math.max(CONFIG.minGuests.room, guestState.room + change);

            updateGuestUI();
        };

        window.closeHomeGuest = function() {
            guestDropdown.style.display = 'none';
        };

        // Initial UI Sync
        updateGuestUI();
    }

    function updateGuestUI() {
        // Update counters
        const elAdult = document.querySelector(CONFIG.selectors.qtyAdult);
        const elChild = document.querySelector(CONFIG.selectors.qtyChild);
        const elRoom = document.querySelector(CONFIG.selectors.qtyRoom);

        if (elAdult) elAdult.innerText = guestState.adult;
        if (elChild) elChild.innerText = guestState.child;
        if (elRoom) elRoom.innerText = guestState.room;

        // Update Label
        if (guestLabel) {
            guestLabel.innerText = `${guestState.adult} Adult, ${guestState.child} Child, ${guestState.room} Room`;
        }

        // Update Hidden Inputs
        const inAdult = document.querySelector(CONFIG.selectors.inputAdults);
        const inChild = document.querySelector(CONFIG.selectors.inputChildren);
        const inRoom = document.querySelector(CONFIG.selectors.inputRooms);

        if (inAdult) inAdult.value = guestState.adult;
        if (inChild) inChild.value = guestState.child;
        if (inRoom) inRoom.value = guestState.room;
    }

    // --- Date Validation Logic ---
    const checkIn = document.querySelector(CONFIG.selectors.checkIn);
    const checkOut = document.querySelector(CONFIG.selectors.checkOut);

    if (checkIn && checkOut) {
        // Set defaults if empty
        if (!checkIn.value) {
            const today = new Date().toISOString().split('T')[0];
            const tomorrow = new Date(new Date().setDate(new Date().getDate() + 1)).toISOString().split('T')[0];
            checkIn.value = today;
            checkIn.min = today;
            checkOut.value = tomorrow;
            checkOut.min = tomorrow;
        } else {
             // If values exist, just ensure min is correct
             const today = new Date().toISOString().split('T')[0];
             if(checkIn.value < today) checkIn.min = today; 
        }

        checkIn.addEventListener('change', function() {
            checkOut.min = checkIn.value;
            if (checkOut.value <= checkIn.value) {
                let nextDay = new Date(checkIn.value);
                nextDay.setDate(nextDay.getDate() + 1);
                checkOut.value = nextDay.toISOString().split('T')[0];
            }
        });
    }

    // --- Autocomplete Logic ---
    const destinationInput = document.querySelector(CONFIG.selectors.destinationInput);
    const suggestionsList = document.querySelector(CONFIG.selectors.suggestionsList);

    if (destinationInput && suggestionsList) {
        let debounceTimer;

        destinationInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const query = this.value;

            if (query.length < 2) {
                suggestionsList.style.display = 'none';
                return;
            }

            debounceTimer = setTimeout(() => {
                // Determine correct relative path for ajax
                // If we are in root (index.php), ajax/ is fine. 
                // But it's safer to use a base url if available, or just assume relative.
                // Since this runs on pages in root, 'ajax/' should work.
                fetch(`ajax/get_locations.php?term=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        suggestionsList.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(location => {
                                const item = document.createElement('div');
                                item.className = 'suggestion-item'; // Ensure CSS exists for this
                                item.textContent = location;
                                item.style.padding = '8px';
                                item.style.cursor = 'pointer';
                                item.style.borderBottom = '1px solid #eee';
                                
                                item.addEventListener('mouseover', () => item.style.backgroundColor = '#f8f9fa');
                                item.addEventListener('mouseout', () => item.style.backgroundColor = 'white');

                                item.addEventListener('click', () => {
                                    destinationInput.value = location;
                                    suggestionsList.style.display = 'none';
                                });
                                suggestionsList.appendChild(item);
                            });
                            suggestionsList.style.display = 'block';
                            suggestionsList.style.backgroundColor = 'white';
                            suggestionsList.style.border = '1px solid #ddd';
                            suggestionsList.style.position = 'absolute';
                            suggestionsList.style.width = '100%';
                            suggestionsList.style.zIndex = '1000';
                            suggestionsList.style.maxHeight = '200px';
                            suggestionsList.style.overflowY = 'auto';

                        } else {
                            suggestionsList.style.display = 'none';
                        }
                    })
                    .catch(err => console.error('Autocomplete Error:', err));
            }, 300);
        });

        // Close suggestions on outside click
        document.addEventListener('click', (e) => {
            if (!destinationInput.contains(e.target) && !suggestionsList.contains(e.target)) {
                suggestionsList.style.display = 'none';
            }
        });
    }

});
