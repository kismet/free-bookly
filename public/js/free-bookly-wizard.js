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
			service_price_num: 0,
			service_duration_num: 60,
			extras: [],
			extras_duration: 0,
			extras_price: 0,
			category_id: preCategoryId || null,
			staff_id: preStaffId,
			staff_name: 'Qualsiasi collaboratore disponibile',
			date: null,
			time: null,
			user: { name: '', email: '', phone: '' },
			custom_fields: []
		};

		let allCategories = [];
		let allServices = [];
		let allStaff = [];

		const categorySelect       = container.querySelector('#fb-category-select');
		const serviceSelect        = container.querySelector('#fb-service-select');
		const staffSelect          = container.querySelector('#fb-staff-select');
		const categoryInfo         = container.querySelector('#fb-category-info');
		const serviceInfo          = container.querySelector('#fb-service-info');
		const staffInfo            = container.querySelector('#fb-staff-info');
		const extrasContainer      = container.querySelector('#fb-extras-container');
		const extrasList           = container.querySelector('#fb-extras-list');
		const customFieldsContainer = container.querySelector('#fb-custom-fields-container');
		const datePicker           = container.querySelector('#fb-date-picker');
		const timeSlotsDiv         = container.querySelector('#fb-time-slots');
		const btnToStep2           = container.querySelector('#btn-to-step-2');
		const btnToStep3           = container.querySelector('#btn-to-step-3');
		const btnConfirm           = container.querySelector('#fb-confirm-btn');

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
							opt.setAttribute('data-price-num', s.price || '0');
							opt.setAttribute('data-duration', s.duration || '60');
							if (preServiceId === s.id) {
								opt.selected = true;
								bookingData.service_id = s.id;
								bookingData.service_title = opt.textContent;
								bookingData.service_price = s.formatted_price;
								bookingData.service_price_num = parseFloat(s.price) || 0;
								bookingData.service_duration_num = parseInt(s.duration, 10) || 60;
								if (btnToStep2) btnToStep2.removeAttribute('disabled');
							}
							serviceSelect.appendChild(opt);
						});

						if (bookingData.service_id) {
							loadStaff(bookingData.service_id);
							loadExtras(bookingData.service_id);
							loadCustomFields(bookingData.service_id);
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

		function loadExtras(serviceId) {
			if (!extrasContainer || !extrasList) return;
			extrasList.innerHTML = '';
			bookingData.extras = [];
			bookingData.extras_duration = 0;
			bookingData.extras_price = 0;

			const data = new FormData();
			data.append('action', 'fb_get_service_extras');
			data.append('nonce', fb_ajax.nonce);
			data.append('service_id', serviceId);

			fetch(fb_ajax.ajax_url, { method: 'POST', body: data })
				.then(r => r.json())
				.then(res => {
					if (res.success && res.data && res.data.length > 0) {
						extrasContainer.style.display = 'block';
						res.data.forEach(ex => {
							const label = document.createElement('label');
							label.style.display = 'flex';
							label.style.alignItems = 'center';
							label.style.gap = '8px';
							label.style.padding = '8px 12px';
							label.style.background = '#f8fafc';
							label.style.border = '1px solid #e2e8f0';
							label.style.borderRadius = '6px';
							label.style.cursor = 'pointer';

							const cb = document.createElement('input');
							cb.type = 'checkbox';
							cb.value = ex.id;
							cb.setAttribute('data-title', ex.title);
							cb.setAttribute('data-price', ex.price);
							cb.setAttribute('data-duration', ex.duration);

							cb.addEventListener('change', function() {
								recalcExtras();
							});

							const span = document.createElement('span');
							const durText = ex.duration > 0 ? ` (+${ex.duration} min)` : '';
							span.innerHTML = `<strong>${ex.title}</strong> &bull; +${ex.formatted_price}${durText}`;

							label.appendChild(cb);
							label.appendChild(span);
							extrasList.appendChild(label);
						});
					} else {
						extrasContainer.style.display = 'none';
					}
				})
				.catch(e => console.error('Errore extra:', e));
		}

		function recalcExtras() {
			if (!extrasList) return;
			const selected = [];
			let extraDur = 0;
			let extraPrc = 0;

			extrasList.querySelectorAll('input[type="checkbox"]:checked').forEach(cb => {
				const id = parseInt(cb.value, 10);
				const title = cb.getAttribute('data-title');
				const price = parseFloat(cb.getAttribute('data-price')) || 0;
				const dur = parseInt(cb.getAttribute('data-duration'), 10) || 0;
				selected.push({ id: id, title: title, price: price, duration: dur });
				extraDur += dur;
				extraPrc += price;
			});

			bookingData.extras = selected;
			bookingData.extras_duration = extraDur;
			bookingData.extras_price = extraPrc;

			// Ricalcola slot se la data è già selezionata
			if (bookingData.date && datePicker && typeof datePicker.dispatchEvent === 'function') {
				datePicker.dispatchEvent(new Event('change'));
			}
		}

		function loadCustomFields(serviceId) {
			if (!customFieldsContainer) return;
			customFieldsContainer.innerHTML = '';

			const data = new FormData();
			data.append('action', 'fb_get_custom_fields');
			data.append('nonce', fb_ajax.nonce);
			data.append('service_id', serviceId);

			fetch(fb_ajax.ajax_url, { method: 'POST', body: data })
				.then(r => r.json())
				.then(res => {
					if (res.success && res.data && res.data.length > 0) {
						const title = document.createElement('h4');
						title.textContent = 'Informazioni Aggiuntive';
						title.style.margin = '16px 0 10px 0';
						title.style.fontSize = '14px';
						title.style.color = '#334155';
						customFieldsContainer.appendChild(title);

						res.data.forEach(f => {
							const block = document.createElement('div');
							block.className = 'fb-field-block';
							block.style.marginBottom = '12px';

							const reqMark = f.is_required ? ' *' : '';
							const labelEl = document.createElement('label');
							labelEl.innerHTML = `<strong>${f.label}${reqMark}</strong>`;
							labelEl.style.display = 'block';
							labelEl.style.marginBottom = '4px';
							block.appendChild(labelEl);

							if (f.field_type === 'text') {
								const inp = document.createElement('input');
								inp.type = 'text';
								inp.setAttribute('type', 'text');
								inp.className = 'fb-cf-input';
								inp.setAttribute('data-cf-id', f.id);
								inp.setAttribute('data-cf-label', f.label);
								inp.setAttribute('data-cf-type', f.field_type);
								inp.setAttribute('data-required', f.is_required);
								inp.placeholder = f.label;
								block.appendChild(inp);
							} else if (f.field_type === 'textarea') {
								const ta = document.createElement('textarea');
								ta.className = 'fb-cf-input';
								ta.rows = 3;
								ta.setAttribute('data-cf-id', f.id);
								ta.setAttribute('data-cf-label', f.label);
								ta.setAttribute('data-cf-type', f.field_type);
								ta.setAttribute('data-required', f.is_required);
								ta.placeholder = f.label;
								block.appendChild(ta);
							} else if (f.field_type === 'select') {
								const sel = document.createElement('select');
								sel.className = 'fb-cf-input';
								sel.setAttribute('data-cf-id', f.id);
								sel.setAttribute('data-cf-label', f.label);
								sel.setAttribute('data-cf-type', f.field_type);
								sel.setAttribute('data-required', f.is_required);
								sel.innerHTML = '<option value="">-- Seleziona un\'opzione --</option>';
								(f.options_array || []).forEach(opt => {
									const o = document.createElement('option');
									o.value = opt;
									o.textContent = opt;
									sel.appendChild(o);
								});
								block.appendChild(sel);
							} else if (f.field_type === 'radio') {
								const radioGroup = document.createElement('div');
								radioGroup.style.display = 'flex';
								radioGroup.style.flexDirection = 'column';
								radioGroup.style.gap = '6px';
								(f.options_array || []).forEach(opt => {
									const rLab = document.createElement('label');
									rLab.style.display = 'flex';
									rLab.style.alignItems = 'center';
									rLab.style.gap = '6px';
									rLab.style.cursor = 'pointer';

									const rad = document.createElement('input');
									rad.type = 'radio';
									rad.name = `fb_cf_${f.id}`;
									rad.value = opt;
									rad.className = 'fb-cf-input';
									rad.setAttribute('data-cf-id', f.id);
									rad.setAttribute('data-cf-label', f.label);
									rad.setAttribute('data-cf-type', f.field_type);
									rad.setAttribute('data-required', f.is_required);

									const rText = document.createElement('span');
									rText.textContent = opt;

									rLab.appendChild(rad);
									rLab.appendChild(rText);
									radioGroup.appendChild(rLab);
								});
								block.appendChild(radioGroup);
							} else if (f.field_type === 'checkbox') {
								const cbLab = document.createElement('label');
								cbLab.style.display = 'flex';
								cbLab.style.alignItems = 'center';
								cbLab.style.gap = '6px';
								cbLab.style.cursor = 'pointer';

								const cb = document.createElement('input');
								cb.type = 'checkbox';
								cb.className = 'fb-cf-input';
								cb.setAttribute('data-cf-id', f.id);
								cb.setAttribute('data-cf-label', f.label);
								cb.setAttribute('data-cf-type', f.field_type);
								cb.setAttribute('data-required', f.is_required);

								const cbText = document.createElement('span');
								cbText.textContent = f.label + reqMark;

								cbLab.appendChild(cb);
								cbLab.appendChild(cbText);
								block.innerHTML = '';
								block.appendChild(cbLab);
							}

							customFieldsContainer.appendChild(block);
						});
					}
				})
				.catch(e => console.error('Errore custom fields:', e));
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
				bookingData.service_price_num = opt ? parseFloat(opt.getAttribute('data-price-num') || '0') : 0;
				bookingData.service_duration_num = opt ? parseInt(opt.getAttribute('data-duration') || '60', 10) : 60;
				bookingData.extras = [];
				bookingData.extras_duration = 0;
				bookingData.extras_price = 0;

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

				if (sId) {
					loadStaff(sId);
					loadExtras(sId);
					loadCustomFields(sId);
				} else {
					if (extrasContainer) extrasContainer.style.display = 'none';
					if (customFieldsContainer) customFieldsContainer.innerHTML = '';
				}
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

				// Validazione Campi Personalizzati Dinamici
				const cfValues = [];
				if (customFieldsContainer) {
					const processedRadio = new Set();
					const inputs = customFieldsContainer.querySelectorAll('.fb-cf-input');

					for (let i = 0; i < inputs.length; i++) {
						const el = inputs[i];
						const cfId = el.getAttribute('data-cf-id');
						const cfLabel = el.getAttribute('data-cf-label') || 'Campo';
						const cfType = el.getAttribute('data-cf-type');
						const isReq = el.getAttribute('data-required') === '1';

						if (cfType === 'radio') {
							if (processedRadio.has(cfId)) continue;
							processedRadio.add(cfId);
							const checked = customFieldsContainer.querySelector(`input[name="fb_cf_${cfId}"]:checked`);
							if (isReq && !checked) {
								alert(`Il campo obbligatorio "${cfLabel}" deve essere selezionato.`);
								return false;
							}
							if (checked) {
								cfValues.push({ id: cfId, label: cfLabel, value: checked.value });
							}
						} else if (cfType === 'checkbox') {
							if (isReq && !el.checked) {
								alert(`È necessario spuntare la casella obbligatoria "${cfLabel}".`);
								return false;
							}
							if (el.checked) {
								cfValues.push({ id: cfId, label: cfLabel, value: 'Sì' });
							}
						} else {
							const val = el.value.trim();
							if (isReq && !val) {
								alert(`Il campo obbligatorio "${cfLabel}" non può essere vuoto.`);
								el.focus();
								return false;
							}
							if (val) {
								cfValues.push({ id: cfId, label: cfLabel, value: val });
							}
						}
					}
				}
				bookingData.custom_fields = cfValues;
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
				data.append('extras_duration', bookingData.extras_duration || 0);

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

			let extrasHtml = '';
			if (bookingData.extras && bookingData.extras.length > 0) {
				const extraNames = bookingData.extras.map(e => `${e.title} (+€${e.price.toFixed(2)}${e.duration ? ', +' + e.duration + 'm' : ''})`).join(', ');
				const totalDur = (bookingData.service_duration_num || 60) + (bookingData.extras_duration || 0);
				const totalPrc = (bookingData.service_price_num || 0) + (bookingData.extras_price || 0);
				extrasHtml = `
					<div style="margin-bottom: 8px;"><strong>Extra Selezionati:</strong> ${extraNames}</div>
					<div style="margin-bottom: 8px;"><strong>Durata Totale:</strong> ${totalDur} min</div>
					<div style="margin-bottom: 8px;"><strong>Prezzo Totale:</strong> € ${totalPrc.toFixed(2)}</div>
				`;
			}

			let cfHtml = '';
			if (bookingData.custom_fields && bookingData.custom_fields.length > 0) {
				const cfLines = bookingData.custom_fields.map(cf => `<div>&bull; <strong>${cf.label}:</strong> ${cf.value}</div>`).join('');
				cfHtml = `<div style="margin-bottom: 8px; margin-top: 6px;"><strong>Dettagli Aggiuntivi:</strong><div style="padding-left: 8px; margin-top: 4px;">${cfLines}</div></div>`;
			}

			summaryDiv.innerHTML = `
				<div style="margin-bottom: 8px;"><strong>Servizio:</strong> ${bookingData.service_title}</div>
				<div style="margin-bottom: 8px;"><strong>Collaboratore:</strong> ${bookingData.staff_name}</div>
				<div style="margin-bottom: 8px;"><strong>Data e Ora:</strong> ${bookingData.date} alle ${bookingData.time}</div>
				${extrasHtml}
				<div style="margin-bottom: 8px;"><strong>Cliente:</strong> ${bookingData.user.name} &bull; ${bookingData.user.email} ${bookingData.user.phone ? ' &bull; ' + bookingData.user.phone : ''}</div>
				${cfHtml}
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
					<div id="fb-extras-container" class="fb-field-block" style="display:none; margin-top: 14px;">
						<label><strong>Servizi Extra Opzionali</strong></label>
						<div id="fb-extras-list" style="margin-top: 6px; display: flex; flex-direction: column; gap: 6px;"></div>
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
					<div id="fb-custom-fields-container" style="margin-top: 14px;"></div>
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
