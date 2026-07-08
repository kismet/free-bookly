<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div id="free-bookly-wizard" class="fb-wizard-container">
	
	<!-- Progress Bar -->
	<div class="fb-progress">
		<div class="fb-step-indicator active" data-step="1">1. Servizio</div>
		<div class="fb-step-indicator" data-step="2">2. Data e Ora</div>
		<div class="fb-step-indicator" data-step="3">3. Dettagli</div>
		<div class="fb-step-indicator" data-step="4">4. Conferma</div>
	</div>

	<!-- Step 1: Selezione Servizio e Staff -->
	<div class="fb-step-content active" id="fb-step-1">
		<h3>Scegli il Servizio</h3>
		<select id="fb-service-select">
			<option value="">-- Seleziona un Servizio --</option>
			<!-- Popolato via AJAX -->
		</select>

		<h3 style="margin-top:20px;">Scegli lo Staff (Opzionale)</h3>
		<select id="fb-staff-select">
			<option value="any">Qualsiasi dipendente disponibile</option>
			<!-- Popolato via AJAX -->
		</select>
		
		<button class="fb-btn fb-next-step" data-target="2">Avanti</button>
	</div>

	<!-- Step 2: Calendario e Selezione Slot -->
	<div class="fb-step-content" id="fb-step-2" style="display:none;">
		<h3>Seleziona la Data</h3>
		<input type="date" id="fb-date-picker" />

		<h3 style="margin-top:20px;">Orari Disponibili</h3>
		<div id="fb-time-slots" class="fb-slots-container">
			<p>Seleziona una data per visualizzare gli orari.</p>
		</div>

		<button class="fb-btn fb-prev-step" data-target="1">Indietro</button>
		<button class="fb-btn fb-next-step" data-target="3" disabled id="btn-to-step-3">Avanti</button>
	</div>

	<!-- Step 3: Dettagli Utente -->
	<div class="fb-step-content" id="fb-step-3" style="display:none;">
		<h3>I tuoi dati</h3>
		<input type="text" id="fb-user-name" placeholder="Nome Completo" required />
		<input type="email" id="fb-user-email" placeholder="Indirizzo Email" required />
		<input type="tel" id="fb-user-phone" placeholder="Numero di Telefono" />

		<button class="fb-btn fb-prev-step" data-target="2">Indietro</button>
		<button class="fb-btn fb-next-step" data-target="4">Avanti</button>
	</div>

	<!-- Step 4: Conferma -->
	<div class="fb-step-content" id="fb-step-4" style="display:none;">
		<h3>Riepilogo</h3>
		<div id="fb-summary"></div>
		
		<button class="fb-btn fb-prev-step" data-target="3">Indietro</button>
		<button class="fb-btn fb-submit" id="fb-confirm-btn">Conferma Prenotazione</button>
	</div>

</div>
