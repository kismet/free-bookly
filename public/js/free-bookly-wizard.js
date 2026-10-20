document.addEventListener('DOMContentLoaded', function() {
	const wizard = document.getElementById('free-bookly-wizard');
	if (!wizard) return;

	let bookingData = {
		service_id: null,
		service_title: '',
		staff_id: null,
		staff_name: 'Qualsiasi dipendente disponibile',
		date: null,
		time: null,
		user: { name: '', email: '', phone: '' }
	};

	const serviceSelect = document.getElementById('fb-service-select');
	const staffSelect = document.getElementById('fb-staff-select');

	// Caricamento iniziale dei servizi
	loadServices();

	function loadServices() {
		const data = new FormData();
		data.append('action', 'fb_get_services');
		data.append('nonce', fb_ajax.nonce);

		fetch(fb_ajax.ajax_url, {
			method: 'POST',
			body: data
		})
		.then(res => res.json())
		.then(response => {
			if (response.success && response.data) {
				serviceSelect.innerHTML = '<option value="">-- Seleziona un Servizio --</option>';
				response.data.forEach(service => {
					const opt = document.createElement('option');
					opt.value = service.id;
					opt.textContent = `${service.title} (${service.duration} min - €${service.price})`;
					serviceSelect.appendChild(opt);
				});
			}
		})
		.catch(err => console.error('Errore caricamento servizi:', err));
	}

	// Al cambio servizio, ricarica lo staff abilitato
	if (serviceSelect) {
		serviceSelect.addEventListener('change', function() {
			const sId = this.value;
			bookingData.service_id = sId;
			const selectedOption = this.options[this.selectedIndex];
			bookingData.service_title = selectedOption ? selectedOption.textContent : '';

			loadStaff(sId);
		});
	}

	function loadStaff(serviceId) {
		const data = new FormData();
		data.append('action', 'fb_get_staff');
		data.append('nonce', fb_ajax.nonce);
		if (serviceId) {
			data.append('service_id', serviceId);
		}

		fetch(fb_ajax.ajax_url, {
			method: 'POST',
			body: data
		})
		.then(res => res.json())
		.then(response => {
			if (response.success && response.data) {
				staffSelect.innerHTML = '<option value="any">Qualsiasi dipendente disponibile</option>';
				response.data.forEach(staff => {
					const opt = document.createElement('option');
					opt.value = staff.id;
					opt.textContent = staff.full_name;
					staffSelect.appendChild(opt);
				});
			}
		})
		.catch(err => console.error('Errore caricamento staff:', err));
	}

	if (staffSelect) {
		staffSelect.addEventListener('change', function() {
			bookingData.staff_id = this.value;
			const opt = this.options[this.selectedIndex];
			bookingData.staff_name = opt ? opt.textContent : '';
		});
	}

	// Gestione navigazione Step
	document.querySelectorAll('.fb-next-step, .fb-prev-step').forEach(btn => {
		btn.addEventListener('click', function() {
			const targetStep = this.getAttribute('data-target');
			
			// Validazione prima di procedere
			if (this.classList.contains('fb-next-step')) {
				if (!validateStep(targetStep - 1)) return;
			}

			showStep(targetStep);
		});
	});

	function showStep(stepNumber) {
		document.querySelectorAll('.fb-step-content').forEach(el => el.style.display = 'none');
		document.getElementById('fb-step-' + stepNumber).style.display = 'block';

		document.querySelectorAll('.fb-step-indicator').forEach(el => el.classList.remove('active'));
		document.querySelector('.fb-step-indicator[data-step="' + stepNumber + '"]').classList.add('active');

		if (stepNumber == 2) {
			bookingData.service_id = document.getElementById('fb-service-select').value;
			bookingData.staff_id = document.getElementById('fb-staff-select').value;
		}

		if (stepNumber == 4) {
			bookingData.user.name = document.getElementById('fb-user-name').value;
			bookingData.user.email = document.getElementById('fb-user-email').value;
			bookingData.user.phone = document.getElementById('fb-user-phone').value;
			updateSummary();
		}
	}

	function validateStep(step) {
		if (step == 1) {
			const s = document.getElementById('fb-service-select').value;
			if (!s) { alert("Seleziona un servizio"); return false; }
		}
		if (step == 2) {
			if (!bookingData.time) { alert("Seleziona un orario"); return false; }
		}
		if (step == 3) {
			if (!document.getElementById('fb-user-name').value || !document.getElementById('fb-user-email').value) {
				alert("Inserisci nome ed email"); return false;
			}
		}
		return true;
	}

	// Fetch orari disponibili (AJAX)
	document.getElementById('fb-date-picker').addEventListener('change', function() {
		bookingData.date = this.value;
		document.getElementById('fb-time-slots').innerHTML = 'Caricamento orari...';

		const data = new FormData();
		data.append('action', 'fb_get_available_slots');
		data.append('nonce', fb_ajax.nonce);
		data.append('service_id', bookingData.service_id);
		data.append('staff_id', bookingData.staff_id);
		data.append('date', bookingData.date);

		fetch(fb_ajax.ajax_url, {
			method: 'POST',
			body: data
		})
		.then(res => res.json())
		.then(response => {
			if (response.success) {
				renderSlots(response.data);
			} else {
				document.getElementById('fb-time-slots').innerHTML = 'Errore: ' + response.data;
			}
		});
	});

	function renderSlots(slots) {
		const container = document.getElementById('fb-time-slots');
		container.innerHTML = '';
		
		if (slots.length === 0) {
			container.innerHTML = 'Nessun orario disponibile per questa data.';
			return;
		}

		slots.forEach(slot => {
			const btn = document.createElement('button');
			btn.className = 'fb-slot-btn';
			btn.innerText = slot;
			btn.addEventListener('click', function() {
				document.querySelectorAll('.fb-slot-btn').forEach(b => b.classList.remove('selected'));
				this.classList.add('selected');
				bookingData.time = slot;
				document.getElementById('btn-to-step-3').removeAttribute('disabled');
			});
			container.appendChild(btn);
		});
	}

	function updateSummary() {
		document.getElementById('fb-summary').innerHTML = `
			<p><strong>Servizio:</strong> ${bookingData.service_title || 'Selezionato'}</p>
			<p><strong>Staff:</strong> ${bookingData.staff_name || 'Qualsiasi disponibile'}</p>
			<p><strong>Data:</strong> ${bookingData.date} alle ore ${bookingData.time}</p>
			<p><strong>Cliente:</strong> ${bookingData.user.name} (${bookingData.user.email} - ${bookingData.user.phone})</p>
		`;
	}

	// Submit (AJAX)
	document.getElementById('fb-confirm-btn').addEventListener('click', function() {
		this.innerText = 'Invio in corso...';
		this.disabled = true;

		const data = new FormData();
		data.append('action', 'fb_save_appointment');
		data.append('nonce', fb_ajax.nonce);
		data.append('payload', JSON.stringify(bookingData));

		fetch(fb_ajax.ajax_url, {
			method: 'POST',
			body: data
		})
		.then(res => res.json())
		.then(response => {
			if (response.success) {
				document.getElementById('fb-step-4').innerHTML = '<h3>Prenotazione Confermata!</h3><p>Grazie, riceverai un\'email a breve.</p>';
			} else {
				alert('Errore: ' + response.data);
				this.innerText = 'Conferma Prenotazione';
				this.disabled = false;
			}
		});
	});
});
