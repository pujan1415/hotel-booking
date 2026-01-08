document.addEventListener('DOMContentLoaded', function () {

    // --- Hotel Filtering Logic ---
    const filters = document.querySelectorAll('.filter-input');
    const displayContainer = document.getElementById('hotelResults');

    function fetchHotels() {
        if (!displayContainer) return;

        let formData = new FormData();
        const currentDest = document.getElementById('destinationInput')?.value || '';
        formData.append('destination', currentDest);

        const priceRange = document.getElementById('priceRange');
        if (priceRange) formData.append('max_price', priceRange.value);

        document.querySelectorAll('input[name="stars"]:checked').forEach((el) => {
            formData.append('stars[]', el.value);
        });

        const adults = document.getElementById('input_adults')?.value || 1;
        const children = document.getElementById('input_children')?.value || 0;
        const rooms = document.getElementById('input_rooms')?.value || 1;
        const cin = document.getElementById('checkIn')?.value || '';
        const cout = document.getElementById('checkOut')?.value || '';

        formData.append('adults', adults);
        formData.append('children', children);
        formData.append('rooms', rooms);
        formData.append('check_in', cin);
        formData.append('check_out', cout);

        // Fetch filtered results
        fetch('ajax/filter_hotels.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.text())
            .then(html => {
                displayContainer.innerHTML = html;
            })
            .catch(err => console.error('Filter Error:', err));
    }

    // Attach listeners
    filters.forEach(filter => {
        filter.addEventListener('change', fetchHotels);
        // For range slider, also trigger on input for real-time feedback or wait for change?
        if (filter.type === 'range') {
            filter.addEventListener('input', function () {
                document.getElementById('priceValue').innerText = this.value;
            });
            // Trigger fetch on 'change' (mouse up) to avoid spamming
        }
    });
});