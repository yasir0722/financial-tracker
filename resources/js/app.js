import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const vehicleSelect = document.querySelector('#maintenance-form select[name="vehicle_id"]');

if (vehicleSelect) {
	const vehicleSearch = document.createElement('input');
	vehicleSearch.type = 'search';
	vehicleSearch.className = 'form-control mb-2';
	vehicleSearch.placeholder = 'Search vehicle';
	vehicleSearch.setAttribute('aria-label', 'Search vehicle');
	vehicleSelect.parentElement.insertBefore(vehicleSearch, vehicleSelect);

	vehicleSearch.addEventListener('input', () => {
		const searchTerm = vehicleSearch.value.trim().toLowerCase();

		[...vehicleSelect.options].forEach(option => {
			option.hidden = option.value !== '' && !option.text.toLowerCase().includes(searchTerm);
		});
	});
}
