/**
 * Free Bookly Frontend Engine
 * Supporta: Modulo Classico Step-by-Step, Catalogo Servizi Moderno, Staff-First e Modulo Ricerca per Data.
 * Scritto in puro Vanilla JavaScript (Zero dipendenze esterne).
 */

document.addEventListener('DOMContentLoaded', function() {

	// ========================================================
	// 1. INIZIALIZZATORE DEL WIZARD CLASSICO
	// ========================================================
	function initClassicWizard(container) {
		if (!container) return;

		const preServiceId  = parseInt(container.getAttribute('data-pre-service'), 10) || 0;
		const preCategoryId = parseInt(container.getAttribute('data-pre-category'), 10) || 0;
		const preStaffId    = container.getAttribute('data-pre-staff') || 'any';

		let bookingData = {
			service_id: preServiceId || null,
			service_title: '',
			service_price: '',
			category_id: preCategoryId || null,
			staff_id: preStaffId,
			staff_name: 'Qualsiasi collaboratore disponibile',
			date: null,
			time: null,
			user: { name: '', email: '', phone: '' }
		};

		let allCategories = [];
		let allServices = [];
		let allStaff = [];

		const categorySelect = container.querySelector('#fb-category-select');
		const serviceSelect  = container.querySelector('#fb-service-select');
		const staffSelect    = container.querySelector('#fb-staff-select');
		const categoryInfo   = container.querySelector('#fb-category-info');
		const serviceInfo    = container.querySelector('#fb-service-info');
		const staffInfo      = container.querySelector('#fb-staff-info');
		const datePicker     = container.querySelector('#fb-date-picker');
		const timeSlotsDiv   = container.querySelector('#fb-time-slots');
		const btnToStep2     = container.querySelector('#btn-to-step-2');
		const btnToStep3     = container.querySelector('#btn-to-step-3');
		const btnConfirm     = container.querySelector('#fb-confirm-btn');

		// Carica Categorie e Servizi
		loadCategories();
		loadServices(preCategoryId);

		function loadCategories() {
			if (!categorySelect) return;
			const data = new FormData();
			data.append('action', 'fb_get_categories');
			data.append('nonce', fb_ajax.nonce);

			fetch(fb_ajax.ajax_url, { method: 'POST', body: data })
				.then(r => r.json())
				.then(res => {
					if (res.success && res.data) {
						allCategories = res.data;
						categorySelect.innerHTML = '<option value="0">-- Tutte le Categorie --</option>';
						allCategories.forEach(cat => {
							const opt = document.createElement('option');
							opt.value = cat.id;
							opt.textContent = cat.name;
							if (preCategoryId === cat.id) opt.selected = true;
							categorySelect.appendChild(opt);
						});
					}
				})
				.catch(e => console.error('Errore categorie:', e));
		}

		function loadServices(catId) {
			if (!serviceSelect) return;
			const data = new FormData();
			data.append('action', 'fb_get_services');
			data.append('nonce', fb_ajax.nonce);
			if (catId && catId > 0) {
				data.append('category_id', catId);
			}

			fetch(fb_ajax.ajax_url, { method: 'POST', body: data })
				.then(r => r.json())
				.then(res => {
					if (res.success && res.data) {
						allServices = res.data;
						serviceSelect.innerHTML = '<option value="">-- Seleziona un Servizio --</option>';
						allServices.forEach(s => {
							const opt = document.createElement('option');
							opt.value = s.id;
							opt.textContent = `${s.title} (${s.duration} min - ${s.formatted_price})`;
							opt.setAttribute('data-info', s.info || '');
							opt.setAttribute('data-price', s.formatted_price || '');
							if (preServiceId === s.id) {
								opt.selected = true;
								bookingData.service_id = s.id;
								bookingData.service_title = opt.textContent;
								bookingData.service_price = s.formatted_price;
								if (btnToStep2) btnToStep2.removeAttribute('disabled');
							}
							serviceSelect.appendChild(opt);
						});

						if (bookingData.service_id) {
							loadStaff(bookingData.service_id);
						}
					}
				})
				.catch(e => console.error('Errore servizi:', e));
		}

		function loadStaff(serviceId) {
			if (!staffSelect) return;
			const data = new FormData();
			data.append('action', 'fb_get_staff');
			data.append('nonce', fb_ajax.nonce);
			if (serviceId) data.append('service_id', serviceId);

			fetch(fb_ajax.ajax_url, { method: 'POST', body: data })
				.then(r => r.json())
				.then(res => {
					if (res.success && res.data) {
						allStaff = res.data;
						staffSelect.innerHTML = '<option value="any">Qualsiasi collaboratore disponibile</option>';
						allStaff.forEach(st => {
							const opt = document.createElement('option');
							opt.value = st.id;
							const priceSuffix = st.custom_price ? ` (${st.formatted_price})` : '';
							opt.textContent = st.full_name + priceSuffix;
							opt.setAttribute('data-info', st.info || '');
							if (preStaffId && (preStaffId == st.id)) {
								opt.selected = true;
								bookingData.staff_id = st.id;
								bookingData.staff_name = st.full_name;
							}
							staffSelect.appendChild(opt);
						});
					}
				})
				.catch(e => console.error('Errore staff:', e));
		}

		// Eventi di cambio
		if (categorySelect) {
			categorySelect.addEventListener('change', function() {
				const cId = parseInt(this.value, 10) || 0;
				bookingData.category_id = cId;
				const selCat = allCategories.find(c => c.id === cId);
				if (categoryInfo) {
					if (selCat && selCat.description) {
						categoryInfo.innerHTML = selCat.description;
						categoryInfo.style.display = 'block';
					} else {
						categoryInfo.style.display = 'none';
					}
				}
				loadServices(cId);
			});
		}

		if (serviceSelect) {
			serviceSelect.addEventListener('change', function() {
				const sId = parseInt(this.value, 10);
				bookingData.service_id = sId;
				const opt = this.options[this.selectedIndex];
				bookingData.service_title = opt ? opt.textContent : '';
				bookingData.service_price = opt ? opt.getAttribute('data-price') : '';

				if (serviceInfo) {
					const info = opt ? opt.getAttribute('data-info') : '';
					if (info) {
						serviceInfo.innerHTML = info;
						serviceInfo.style.display = 'block';
					} else {
						serviceInfo.style.display = 'none';
					}
				}

				if (btnToStep2) {
					if (sId) {
						btnToStep2.removeAttribute('disabled');
					} else {
						btnToStep2.setAttribute('disabled', 'disabled');
					}
				}

				loadStaff(sId);
			});
		}

		if (staffSelect) {
			staffSelect.addEventListener('change', function() {
				bookingData.staff_id = this.value;
				const opt = this.options[this.selectedIndex];
				bookingData.staff_name = opt ? opt.textContent : '';

				if (staffInfo) {
					const info = opt ? opt.getAttribute('data-info') : '';
					if (info) {
						staffInfo.innerHTML = info;
						staffInfo.style.display = 'block';
					} else {
						staffInfo.style.display = 'none';
					}
				}
			});
		}

		// Navigazione Step
		container.querySelectorAll('.fb-next-step, .fb-prev-step').forEach(btn => {
			btn.addEventListener('click', function() {
				const targetStep = parseInt(this.getAttribute('data-target'), 10);
				if (this.classList.contains('fb-next-step')) {
					if (!validateStep(targetStep - 1)) return;
				}
				showStep(targetStep);
			});
		});

		function showStep(stepNumber) {
			container.querySelectorAll('.fb-step-content').forEach(el => el.style.display = 'none');
			const targetEl = container.querySelector('#fb-step-' + stepNumber);
			if (targetEl) targetEl.style.display = 'block';

			container.querySelectorAll('.fb-step-indicator').forEach(el => el.classList.remove('active'));
			const ind = container.querySelector('.fb-step-indicator[data-step="' + stepNumber + '"]');
			if (ind) ind.classList.add('active');

			if (stepNumber === 4) {
				const nameInput  = container.querySelector('#fb-user-name');
				const emailInput = container.querySelector('#fb-user-email');
				const phoneInput = container.querySelector('#fb-user-phone');

				bookingData.user.name  = nameInput ? nameInput.value.trim() : '';
				bookingData.user.email = emailInput ? emailInput.value.trim() : '';
				bookingData.user.phone = phoneInput ? phoneInput.value.trim() : '';
				updateSummary();
			}
		}

		function validateStep(step) {
			if (step === 1) {
				if (!bookingData.service_id) {
					alert('Seleziona un servizio prima di procedere.');
					return false;
				}
			}
			if (step === 2) {
				if (!bookingData.date || !bookingData.time) {
					alert('Seleziona sia la data che un orario disponibile.');
					return false;
				}
			}
			if (step === 3) {
				const nameInput  = container.querySelector('#fb-user-name');
				const emailInput = container.querySelector('#fb-user-email');
				if (!nameInput.value.trim() || !emailInput.value.trim()) {
					alert('Nome ed email sono campi obbligatori.');
					return false;
				}
			}
			return true;
		}

		// Selezione Data & Caricamento Slot
		if (datePicker) {
			datePicker.addEventListener('change', function() {
				bookingData.date = this.value;
				if (!timeSlotsDiv) return;

				timeSlotsDiv.innerHTML = '<p class="fb-no-slots">Caricamento orari disponibili...</p>';
				if (btnToStep3) btnToStep3.setAttribute('disabled', 'disabled');

				const data = new FormData();
				data.append('action', 'fb_get_available_slots');
				data.append('nonce', fb_ajax.nonce);
				data.append('service_id', bookingData.service_id);
				data.append('staff_id', bookingData.staff_id || 'any');
				data.append('date', bookingData.date);

				fetch(fb_ajax.ajax_url, { method: 'POST', body: data })
					.then(r => r.json())
					.then(res => {
						if (res.success) {
							renderSlots(res.data);
						} else {
							timeSlotsDiv.innerHTML = `<p class="fb-no-slots">${res.data || 'Errore nel caricamento'}</p>`;
						}
					})
					.catch(() => {
						timeSlotsDiv.innerHTML = '<p class="fb-no-slots">Errore di connessione al server.</p>';
					});
			});
		}

		function renderSlots(slots) {
			if (!timeSlotsDiv) return;
			timeSlotsDiv.innerHTML = '';

			if (!slots || slots.length === 0) {
				timeSlotsDiv.innerHTML = '<p class="fb-no-slots">Nessun orario disponibile per la data selezionata.</p>';
				return;
			}

			slots.forEach(slot => {
				const btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'fb-slot-btn';
				btn.textContent = slot;
				btn.addEventListener('click', function() {
					timeSlotsDiv.querySelectorAll('.fb-slot-btn').forEach(b => b.classList.remove('selected'));
					this.classList.add('selected');
					bookingData.time = slot;
					if (btnToStep3) btnToStep3.removeAttribute('disabled');
				});
				timeSlotsDiv.appendChild(btn);
			});
		}

		function updateSummary() {
			const summaryDiv = container.querySelector('#fb-summary');
			if (!summaryDiv) return;

			summaryDiv.innerHTML = `
				<div style="margin-bottom: 8px;"><strong>Servizio:</strong> ${bookingData.service_title}</div>
				<div style="margin-bottom: 8px;"><strong>Collaboratore:</strong> ${bookingData.staff_name}</div>
				<div style="margin-bottom: 8px;"><strong>Data e Ora:</strong> ${bookingData.date} alle ${bookingData.time}</div>
				<div style="margin-bottom: 8px;"><strong>Cliente:</strong> ${bookingData.user.name} &bull; ${bookingData.user.email} ${bookingData.user.phone ? ' &bull; ' + bookingData.user.phone : ''}</div>
			`;
		}

		// Conferma Finale e Salvataggio AJAX
		if (btnConfirm) {
			btnConfirm.addEventListener('click', function() {
				this.disabled = true;
				this.textContent = 'Conferma in corso...';

				const data = new FormData();
				data.append('action', 'fb_save_appointment');
				data.append('nonce', fb_ajax.nonce);
				data.append('payload', JSON.stringify(bookingData));

				fetch(fb_ajax.ajax_url, { method: 'POST', body: data })
					.then(r => r.json())
					.then(res => {
						if (res.success) {
							container.querySelectorAll('.fb-step-content').forEach(el => el.style.display = 'none');
							container.querySelector('.fb-progress').style.display = 'none';
							const successScreen = container.querySelector('#fb-step-success');
							const successMsg = container.querySelector('#fb-success-message');

							const statusMsg = res.data && res.data.status === 'approved' 
								? 'La tua prenotazione è stata confermata immediatamente!' 
								: 'La tua richiesta di prenotazione è stata ricevuta ed è in attesa di approvazione.';

							if (successMsg) successMsg.textContent = statusMsg;
							if (successScreen) successScreen.style.display = 'block';
						} else {
							alert('Errore: ' + (res.data || 'Impossibile completare la prenotazione.'));
							btnConfirm.disabled = false;
							btnConfirm.textContent = 'Conferma Prenotazione';
						}
					})
					.catch(() => {
						alert('Errore di connessione. Riprova più tardi.');
						btnConfirm.disabled = false;
						btnConfirm.textContent = 'Conferma Prenotazione';
					});
			});
		}
	}

	// Inizializza wizard classico se presente in pagina
	const primaryWizard = document.getElementById('free-bookly-wizard');
	if (primaryWizard) {
		initClassicWizard(primaryWizard);
	}

	// ========================================================
	// 2. MODULO CATALOGO SERVIZI MODERNO
	// ========================================================
	const catalogContainer = document.querySelector('.fb-modern-catalog-container');
	if (catalogContainer) {
		// Filtro Categorie (Pillole)
		catalogContainer.querySelectorAll('.fb-pill-btn').forEach(btn => {
			btn.addEventListener('click', function() {
				catalogContainer.querySelectorAll('.fb-pill-btn').forEach(b => b.classList.remove('active'));
				this.classList.add('active');
				const catId = this.getAttribute('data-category');

				catalogContainer.querySelectorAll('.fb-service-card').forEach(card => {
					if (catId === '0' || card.getAttribute('data-category-id') === catId) {
						card.style.display = 'flex';
					} else {
						card.style.display = 'none';
					}
				});
			});
		});

		// Pulsante "Prenota Ora" sulla card
		catalogContainer.querySelectorAll('.fb-btn-book-service').forEach(btn => {
			btn.addEventListener('click', function() {
				const sId = this.getAttribute('data-service-id');
				const cId = this.getAttribute('data-category-id');
				openModalWithWizard('fb-catalog-modal', 'fb-catalog-wizard-mount', sId, cId, 'any');
			});
		});
	}

	// ========================================================
	// 3. MODULO STAFF-FIRST MODERNO
	// ========================================================
	const staffContainer = document.querySelector('.fb-modern-staff-container');
	if (staffContainer) {
		staffContainer.querySelectorAll('.fb-btn-book-staff').forEach(btn => {
			btn.addEventListener('click', function() {
				const staffId = this.getAttribute('data-staff-id');
				openModalWithWizard('fb-staff-modal', 'fb-staff-wizard-mount', 0, 0, staffId);
			});
		});
	}

	// ========================================================
	// 4. MODULO RICERCA PER DATA
	// ========================================================
	const searchContainer = document.querySelector('.fb-modern-search-container');
	if (searchContainer) {
		const searchBtn = searchContainer.querySelector('#fb-btn-execute-search');
		const resultsDiv = searchContainer.querySelector('#fb-search-results');
		const slotsGrid  = searchContainer.querySelector('#fb-search-slots-grid');

		if (searchBtn && slotsGrid) {
			searchBtn.addEventListener('click', function() {
				const dateVal = searchContainer.querySelector('#fb-search-date').value;
				const serviceVal = searchContainer.querySelector('#fb-search-service').value;
				const timeframe = searchContainer.querySelector('#fb-search-timeframe').value;

				if (!dateVal || !serviceVal) {
					alert('Seleziona sia la data che il servizio desiderato.');
					return;
				}

				slotsGrid.innerHTML = '<p class="fb-no-slots">Ricerca orari disponibili in corso...</p>';
				resultsDiv.style.display = 'block';

				const data = new FormData();
				data.append('action', 'fb_get_available_slots');
				data.append('nonce', fb_ajax.nonce);
				data.append('service_id', serviceVal);
				data.append('staff_id', 'any');
				data.append('date', dateVal);

				fetch(fb_ajax.ajax_url, { method: 'POST', body: data })
					.then(r => r.json())
					.then(res => {
						slotsGrid.innerHTML = '';
						if (res.success && res.data && res.data.length > 0) {
							// Filtra per fascia oraria se specificata
							const filtered = res.data.filter(slot => {
								const hour = parseInt(slot.split(':')[0], 10);
								if (timeframe === 'morning') return hour >= 8 && hour < 13;
								if (timeframe === 'afternoon') return hour >= 13 && hour < 18;
								if (timeframe === 'evening') return hour >= 18 && hour <= 23;
								return true;
							});

							if (filtered.length === 0) {
								slotsGrid.innerHTML = '<p class="fb-no-slots">Nessuno slot disponibile nella fascia oraria scelta. Prova a selezionare "Tutto il giorno".</p>';
								return;
							}

							filtered.forEach(slot => {
								const btn = document.createElement('button');
								btn.type = 'button';
								btn.className = 'fb-slot-btn';
								btn.textContent = slot;
								btn.addEventListener('click', function() {
									openModalWithWizard('fb-search-modal', 'fb-search-wizard-mount', serviceVal, 0, 'any', dateVal, slot);
								});
								slotsGrid.appendChild(btn);
							});
						} else {
							slotsGrid.innerHTML = '<p class="fb-no-slots">Nessun orario disponibile per la data selezionata.</p>';
						}
					})
					.catch(() => {
						slotsGrid.innerHTML = '<p class="fb-no-slots">Errore durante la ricerca.</p>';
					});
			});
		}
	}

	// ========================================================
	// 5. HELPER APERTURA MODALE CON WIZARD INIETTATO
	// ========================================================
	function openModalWithWizard(modalId, mountId, serviceId, categoryId, staffId, preDate, preTime) {
		const modal = document.getElementById(modalId);
		const mount = document.getElementById(mountId);
		if (!modal || !mount) return;

		// Clona il template HTML del wizard classico
		const template = `
			<div id="${mountId}-wizard" class="fb-wizard-container" 
			     data-pre-service="${serviceId || 0}" 
			     data-pre-category="${categoryId || 0}" 
			     data-pre-staff="${staffId || 'any'}" style="box-shadow: none; border: none; padding: 0;">
				<div class="fb-progress">
					<div class="fb-step-indicator active" data-step="1">1. Servizio</div>
					<div class="fb-step-indicator" data-step="2">2. Data e Ora</div>
					<div class="fb-step-indicator" data-step="3">3. Dettagli</div>
					<div class="fb-step-indicator" data-step="4">4. Conferma</div>
				</div>
				<div class="fb-step-content active" id="fb-step-1">
					<div class="fb-field-block">
						<label for="fb-category-select"><strong>Categoria</strong></label>
						<select id="fb-category-select"><option value="0">-- Tutte le Categorie --</option></select>
						<div id="fb-category-info" class="fb-info-box" style="display:none;"></div>
					</div>
					<div class="fb-field-block" style="margin-top: 14px;">
						<label for="fb-service-select"><strong>Servizio *</strong></label>
						<select id="fb-service-select" required><option value="">-- Seleziona un Servizio --</option></select>
						<div id="fb-service-info" class="fb-info-box" style="display:none;"></div>
					</div>
					<div class="fb-field-block" style="margin-top: 14px;">
						<label for="fb-staff-select"><strong>Collaboratore</strong></label>
						<select id="fb-staff-select"><option value="any">Qualsiasi collaboratore disponibile</option></select>
						<div id="fb-staff-info" class="fb-info-box" style="display:none;"></div>
					</div>
					<button class="fb-btn fb-next-step" data-target="2" id="btn-to-step-2" style="margin-top: 18px;">Avanti</button>
				</div>
				<div class="fb-step-content" id="fb-step-2" style="display:none;">
					<div class="fb-field-block">
						<label for="fb-date-picker"><strong>Data *</strong></label>
						<input type="date" id="fb-date-picker" value="${preDate || ''}" />
					</div>
					<div class="fb-field-block" style="margin-top: 16px;">
						<label><strong>Orari Disponibili</strong></label>
						<div id="fb-time-slots" class="fb-slots-container">
							<p class="fb-no-slots">Seleziona una data per visualizzare gli orari.</p>
						</div>
					</div>
					<div style="margin-top: 20px; display: flex; gap: 10px;">
						<button class="fb-btn fb-btn-secondary fb-prev-step" data-target="1">Indietro</button>
						<button class="fb-btn fb-next-step" data-target="3" disabled id="btn-to-step-3">Avanti</button>
					</div>
				</div>
				<div class="fb-step-content" id="fb-step-3" style="display:none;">
					<h3>I tuoi dati</h3>
					<div class="fb-field-block" style="margin-bottom: 12px;">
						<input type="text" id="fb-user-name" placeholder="Nome Completo *" required />
					</div>
					<div class="fb-field-block" style="margin-bottom: 12px;">
						<input type="email" id="fb-user-email" placeholder="Indirizzo Email *" required />
					</div>
					<div class="fb-field-block" style="margin-bottom: 12px;">
						<input type="tel" id="fb-user-phone" placeholder="Numero di Telefono" />
					</div>
					<div style="margin-top: 20px; display: flex; gap: 10px;">
						<button class="fb-btn fb-btn-secondary fb-prev-step" data-target="2">Indietro</button>
						<button class="fb-btn fb-next-step" data-target="4" id="btn-to-step-4">Avanti</button>
					</div>
				</div>
				<div class="fb-step-content" id="fb-step-4" style="display:none;">
					<h3>Riepilogo</h3>
					<div id="fb-summary" class="fb-summary-card"></div>
					<div style="margin-top: 20px; display: flex; gap: 10px;">
						<button class="fb-btn fb-btn-secondary fb-prev-step" data-target="3">Indietro</button>
						<button class="fb-btn fb-submit" id="fb-confirm-btn">Conferma Prenotazione</button>
					</div>
				</div>
				<div class="fb-step-content" id="fb-step-success" style="display:none; text-align: center; padding: 20px;">
					<div style="font-size: 42px; color: #16a34a; margin-bottom: 10px;">&#10004;</div>
					<h3>Prenotazione Ricevuta!</h3>
					<p id="fb-success-message" style="color: #64748b; font-size: 14px;"></p>
				</div>
			</div>
		`;

		mount.innerHTML = template;
		modal.style.display = 'flex';

		const wizardInst = document.getElementById(`${mountId}-wizard`);
		initClassicWizard(wizardInst);

		// Se già specificato servizio o data/ora, avanza opportunamente
		if (serviceId && preDate && preTime) {
			setTimeout(() => {
				const dp = wizardInst.querySelector('#fb-date-picker');
				if (dp) {
					dp.value = preDate;
					dp.dispatchEvent(new Event('change'));
				}
			}, 300);
		}
	}

	// Chiusura Modali
	document.querySelectorAll('.fb-modal-close, .fb-modal-overlay').forEach(el => {
		el.addEventListener('click', function() {
			const m = this.closest('.fb-modal');
			if (m) m.style.display = 'none';
		});
	});
});
