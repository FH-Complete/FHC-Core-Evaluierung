import {CoreFilterCmpt} from "../../../../../js/components/filter/Filter.js";
import ApiAdminFragebogen from "../../api/admin/fragebogen.js";
import FragebogenHelper from "../../helpers/FragebogenHelper.js";
import FragebogenGruppeModal from "./FragebogenGruppeModal.js";
import FragebogenFrageModal from "./FragebogenFrageModal.js";

export default {
	name: "FragebogenAufbau",
	components: {
		CoreFilterCmpt,
		FragebogenGruppeModal,
		FragebogenFrageModal,
	},
	props: {
		fragebogenId: {default: null},	// null: noch kein Fragebogen gewählt
		isLocked: Boolean				// Bereits beantwortet: Aufbau nur lesbar
	},
	data() {
		return {
			gruppen: [],		// Alle Gruppen, aktive zuerst nach Sort
			selGruppeId: null,	// Gruppe, deren Fragen angezeigt werden
			fragen: [],			// Alle Fragen, aktive der Gruppe zuerst nach Sort
		}
	},
	computed: {
		aktivIds() {
			return this.gruppen
				.filter(gruppe => gruppe.aktiv)
				.map(gruppe => gruppe.lvevaluierung_fragebogen_gruppe_id);
		},
		aktivFrageIds() {
			return this.fragen
					.filter(frage => frage.aktiv)
					.map(frage => frage.lvevaluierung_frage_id);
		},
		selGruppe() {
			return this.gruppen.find(gruppe => gruppe.lvevaluierung_fragebogen_gruppe_id === this.selGruppeId);
		},
		// Fragen änderbar, solange die Gruppe im Fragebogen ist, nicht beantwortet und kein label
		kannFragenAendern() {
			return !!this.selGruppe && this.selGruppe.aktiv && !this.isLocked && this.selGruppe.typ !== FragebogenHelper.GRUPPE_TYP.LABEL;
		},
		gruppenEvents() {
			return [{event: 'rowClick', handler: this.onGruppeRowClick}];
		},
		gruppenOptions() {
			return {
				ajaxURL: 'dummy',
				ajaxRequestFunc: () => {
					if (!this.fragebogenId) return Promise.resolve({data: []});

					return this.$api.call(ApiAdminFragebogen.getGruppen(this.fragebogenId));
				},
				ajaxResponse: (url, params, response) => {
					this.gruppen = response.data;
					// Angezeigte Gruppe nicht mehr vorhanden: Fragen ausblenden
					if (!this.selGruppe) this.selGruppeId = null;
					return response.data;
				},
				placeholder: 'Bitte Fragebogen wählen.',
				// Gruppe, deren Fragen angezeigt werden, markieren
				rowFormatter: row => row.getElement().classList.toggle(
					'tabulator-selected',
					row.getData().lvevaluierung_fragebogen_gruppe_id === this.selGruppeId
				),
				layout: 'fitColumns',
				maxHeight: '50vh',
				selectable: false,
				index: 'lvevaluierung_fragebogen_gruppe_id',
				columnDefaults: {
					headerTooltip: true,
					headerWordWrap: true,
					tooltip: e => {
						const el = e.target.closest('[data-tooltip]');
						return el ? el.dataset.tooltip : '';
					}
				},
				columns: [
					{
						title: 'Sort',
						field: 'sort',
						formatter: cell => this.getSortHtml(cell.getData()),
						cellClick: (e, cell) => this.onGruppeAction(e, cell.getData()),
						sorterParams: {alignEmptyValues: 'bottom'},
						width: 90
					},
					{
						title: 'Im Fragebogen',
						field: 'aktiv',
						formatter: cell => this.getAktivHtml(cell.getData()),
						cellClick: (e, cell) => this.onAktivClick(e, cell.getData()),
						headerFilter: 'tickCross',
						headerFilterParams: {tristate: true},
						hozAlign: 'center',
						width: 100
					},
					{
						title: 'Bezeichnung DE',
						field: 'bezeichnung_de',
						headerFilter: 'input',
						tooltip: true,
						minWidth: 100
					},
					{
						title: 'Bezeichnung EN',
						field: 'bezeichnung_en',
						headerFilter: 'input',
						tooltip: true,
						minWidth: 100,
						visible: false,
					},
					{
						title: 'Typ',
						field: 'typ',
						headerFilter: 'input',
						width: 80
					},
					// {
					// 	title: 'Fragen',
					// 	field: 'anzahlFragen',
					// 	hozAlign: 'right',
					// 	width: 80
					// },
					{
						title: 'Aktionen',
						formatter: cell => this.getAktionenHtml(cell.getData()),
						cellClick: (e, cell) => this.onGruppeAction(e, cell.getData()),
						headerSort: false,
						hozAlign: 'center',
						width: 80
					}
				]
			};
		},
		fragenOptions() {
			return {
				ajaxURL: 'dummy',
				ajaxRequestFunc: () => {
					// Keine Gruppe gewählt oder label: Tabelle leeren
					if (!this.selGruppe || this.selGruppe.typ === FragebogenHelper.GRUPPE_TYP.LABEL) return Promise.resolve({data: []});

					return this.$api.call(ApiAdminFragebogen.getFragen(this.selGruppeId, this.fragebogenId));
				},
				ajaxResponse: (url, params, response) => {
					this.fragen = response.data;
					return response.data;
				},
				placeholder: () => this.selGruppe ? 'Gruppentyp label hat keine Fragen.' : 'Bitte  Fragengruppe wählen.',
				layout: 'fitColumns',
				maxHeight: '50vh',
				selectable: false,
				index: 'lvevaluierung_frage_id',
				columnDefaults: {
					headerTooltip: true,
					headerWordWrap: true,
					tooltip: e => {
						const el = e.target.closest('[data-tooltip]');
						return el ? el.dataset.tooltip : '';
					}
				},
				columns: [
					{
						title: 'Sort',
						field: 'sort',
						formatter: cell => this.getFrageSortHtml(cell.getData()),
						cellClick: (e, cell) => this.onFrageAction(e, cell.getData()),
						sorterParams: {alignEmptyValues: 'bottom'},
						width: 90
					},
					{
						title: 'In Gruppe',
						field: 'aktiv',
						formatter: cell => this.getFrageAktivHtml(cell.getData()),
						cellClick: (e, cell) => this.onFrageAktivClick(e, cell.getData()),
						headerFilter: 'tickCross',
						headerFilterParams: {tristate: true},
						hozAlign: 'center',
						width: 90
					},
					{
						title: 'Frage DE',
						field: 'bezeichnung_de',
						headerFilter: 'input',
						tooltip: true,
						minWidth: 100
					},
					{
						title: 'Frage EN',
						field: 'bezeichnung_en',
						headerFilter: 'input',
						tooltip: true,
						minWidth: 100,
						visible: false,
					},
					{
						title: 'Typ',
						field: 'typ',
						headerFilter: 'input',
						width: 130
					},
					{
						title: 'Pflichtfrage',
						field: 'verpflichtend',
						formatter: 'tickCross',
						formatterParams: {
							tickElement: '<i class="fa fa-check text-success"></i>',
							crossElement: '<i class="fa fa-xmark text-danger"></i>'
						},
						headerFilter: 'tickCross',
						headerFilterParams: {tristate: true},
						hozAlign: 'center',
						width: 90
					},
					{
						title: 'Aktionen',
						formatter: cell => this.getFrageAktionenHtml(cell.getData()),
						cellClick: (e, cell) => this.onFrageAction(e, cell.getData()),
						headerSort: false,
						hozAlign: 'center',
						width: 80
					}
				]
			};
		}
	},
	watch: {
		// Anderer Fragebogen: Gruppen neu laden, Fragen leeren
		fragebogenId() {
			this.selGruppeId = null;
			this.$refs.gruppenTable.reloadTable();
		},
		// Markierung aktualisieren und Fragen der gewählten Gruppe laden
		selGruppeId() {
			this.$refs.gruppenTable.tabulator.getRows().forEach(row => row.reformat());
			this.$refs.fragenTable.reloadTable();
		}
	},
	methods: {
		getSortHtml(gruppe) {
			const index = this.aktivIds.indexOf(gruppe.lvevaluierung_fragebogen_gruppe_id);
			if (index < 0) return '';

			return `
				<div class="d-flex align-items-center gap-1">
					<span class="me-1">${index + 1}</span>
					<button type="button" class="btn btn-sm btn-link py-0 px-1" data-action="up" data-tooltip="Nach oben" aria-label="Nach oben" ${this.isLocked || index === 0 ? 'disabled' : ''}>
						<i class="fa-solid fa-arrow-up"></i>
					</button>
					<button type="button" class="btn btn-sm btn-link py-0 px-1" data-action="down" data-tooltip="Nach unten" aria-label="Nach unten" ${this.isLocked || index === this.aktivIds.length - 1 ? 'disabled' : ''}>
						<i class="fa-solid fa-arrow-down"></i>
					</button>
				</div>`;
		},
		getAktivHtml(gruppe) {
			const isNeu = gruppe.aktiv && !gruppe.inAnderenFrageboegen;
			let title = gruppe.aktiv ? 'Aus diesem Fragebogen entfernen' : 'In diesem Fragebogen verwenden';
			if (isNeu) title = 'Neue Gruppe, nur in diesem Fragebogen – zum Entfernen löschen';
			if (this.isLocked) title = 'Bereits beantwortet – nicht änderbar';

			return `
				<div class="form-check form-switch d-flex justify-content-center m-0" data-tooltip="${title}">
					<input class="form-check-input" type="checkbox" role="switch" aria-label="${title}" ${gruppe.aktiv ? 'checked' : ''} ${this.isLocked || isNeu ? 'disabled' : ''}>
				</div>`;
		},
		getAktionenHtml(gruppe) {
			const canDelete = !this.isLocked && gruppe.aktiv && !gruppe.inAnderenFrageboegen;
			let titleDelete = 'Gruppe löschen';
			if (!gruppe.aktiv) titleDelete = 'Nicht löschbar: Gruppe in anderem Fragebogen verwendet.';
			else if (gruppe.inAnderenFrageboegen) titleDelete = 'Nicht löschbar: Gruppe in anderem Fragebogen verwendet. Über „Im Fragebogen“ aus diesem Fragebogen entfernen.';
			if (this.isLocked) titleDelete = 'Nicht löschbar: Fragebogen wurde bereits beantwortet.';

			return `
				<div class="d-flex justify-content-center gap-1">
					<button type="button" class="btn btn-sm btn-outline-secondary" data-action="edit" data-tooltip="Gruppe bearbeiten" aria-label="Gruppe bearbeiten">
						<i class="fa-solid fa-pen"></i>
					</button>
					<span data-tooltip="${titleDelete}">
						<button type="button" class="btn btn-sm btn-outline-secondary" data-action="delete" aria-label="Gruppe löschen" ${canDelete ? '' : 'disabled'}>
							<i class="fa-solid fa-xmark"></i>
						</button>
					</span>
				</div>`;
		},
		onGruppeAction(e, gruppe) {
			const button = e.target.closest('button');
			if (!button || button.disabled) return;

			const action = button.dataset.action;
			if (action === 'up') this.sortGruppe(gruppe, -1);
			if (action === 'down') this.sortGruppe(gruppe, 1);
			if (action === 'edit') this.$refs.gruppeModal.open(gruppe, this.getNurLesenGrund(gruppe));
			if (action === 'delete') this.deleteGruppe(gruppe);
		},
		onAktivClick(e, gruppe) {
			const input = e.target.closest('input');
			if (!input || input.disabled) return;

			this.toggleGruppe(gruppe);
		},
		onGruppeRowClick(e, row) {
			if (e.target.closest('button, input')) return;

			this.selGruppeId = row.getData().lvevaluierung_fragebogen_gruppe_id;
		},
		// Nur ansehen: beantwortet oder bestehende Gruppe
		getNurLesenGrund(gruppe) {
			if (this.isLocked) return 'Bereits beantwortet – nur Ansicht.';
			if (!gruppe.aktiv || gruppe.inAnderenFrageboegen) return 'Bestehende Gruppe (auch in anderen Fragebögen) – Bezeichnung und Typ nicht änderbar.';
			return null;
		},
		toggleGruppe(gruppe) {
			const gruppe_id = gruppe.lvevaluierung_fragebogen_gruppe_id;
			const tabulator = this.$refs.gruppenTable.tabulator;

			const apiCall = gruppe.aktiv
				? ApiAdminFragebogen.deleteGruppe(gruppe_id)
				: ApiAdminFragebogen.uebernehmenGruppe(this.fragebogenId, gruppe_id);

			this.$api
				.call(apiCall)
				.then(result => {
					// Angezeigte Gruppe: zur übernommenen wechseln
					if (gruppe_id === this.selGruppeId)
					{
						this.selGruppeId = gruppe.aktiv ? null : result.data;
					}
				})
				.then(() => this.$api.call(ApiAdminFragebogen.getGruppen(this.fragebogenId)))
				.then(result => {
					this.gruppen = result.data;
					return tabulator.updateRow(
						gruppe_id,
						result.data.find(g => g.typ === gruppe.typ && g.bezeichnung_de === gruppe.bezeichnung_de)
					);
				})
				.then(() => tabulator.getRows().forEach(row => row.reformat()))    // Schalter und Sort-Nummern
				.catch(error => {this.$fhcAlert.handleSystemError(error);});
		},
		sortGruppe(gruppe, schritt) {
			const gruppe_id = gruppe.lvevaluierung_fragebogen_gruppe_id;
			const ids = this.aktivIds.filter(id => id !== gruppe_id);
			ids.splice(this.aktivIds.indexOf(gruppe_id) + schritt, 0, gruppe_id);

			this.$api
				.call(ApiAdminFragebogen.sortGruppen(this.fragebogenId, ids))
				.then(() => {
					this.$fhcAlert.alertSuccess('Gespeichert');
					this.$refs.gruppenTable.reloadTable()
				})
				.catch(error => this.$fhcAlert.handleSystemError(error));
		},
		async deleteGruppe(gruppe) {
			if (!await this.$fhcAlert.confirm({
				header: 'Bitte bestätigen Sie:',
				message: 'Gruppe „' + gruppe.bezeichnung_de + '“ löschen? Fragen dieser Gruppe, die keiner anderen Gruppe zugeordnet sind und noch nie beantwortet wurden, werden mitgelöscht.'
			})) return;

			this.$api
					.call(ApiAdminFragebogen.deleteGruppe(gruppe.lvevaluierung_fragebogen_gruppe_id))
					.then(() => {
						this.$fhcAlert.alertSuccess('Gruppe gelöscht');
						this.$refs.gruppenTable.reloadTable();
					})
					.catch(error => this.$fhcAlert.handleSystemError(error));
		},
		getFrageSortHtml(frage) {
			const index = this.aktivFrageIds.indexOf(frage.lvevaluierung_frage_id);
			if (index < 0) return '';
			const aktiv = this.fragen.filter(f => f.aktiv);
			const fest = f => !f || f.inVerwendetemFragebogen;	// kein Nachbar oder bestehende Frage

			let title = '';
			if (aktiv.some(f => f.inVerwendetemFragebogen)) title = 'Neue Fragen nur untereinander verschiebbar.';
			if (frage.inVerwendetemFragebogen) title = 'Reihenfolge fest: Frage wird in einem bereits verwendeten Fragebogen genutzt.';

			return `
				<div class="d-flex align-items-center gap-1" data-tooltip="${title}">
					<span class="me-1">${index + 1}</span>
					<button type="button" class="btn btn-sm btn-link py-0 px-1" data-action="up" data-tooltip="Nach oben" aria-label="Nach oben" ${!this.kannFragenAendern || fest(frage) || fest(aktiv[index - 1]) ? 'disabled' : ''}>
						<i class="fa-solid fa-arrow-up"></i>
					</button>
					<button type="button" class="btn btn-sm btn-link py-0 px-1" data-action="down" data-tooltip="Nach unten" aria-label="Nach unten" ${!this.kannFragenAendern || fest(frage) || fest(aktiv[index + 1]) ? 'disabled' : ''}>
						<i class="fa-solid fa-arrow-down"></i>
					</button>
				</div>`;
		},
		// Eine Frage nur einmal je Fragebogen: schon in anderer Gruppe = gesperrt
		getFrageAktivHtml(frage) {
			let title = frage.aktiv ? 'Aus dieser Gruppe entfernen' : 'In dieser Gruppe verwenden';
			if (frage.andereGruppe) title = 'Bereits in Gruppe „' + frage.andereGruppe + '“ dieses Fragebogens';
			if (this.selGruppe && !this.selGruppe.aktiv) title = 'Gruppe nicht in diesem Fragebogen';
			if (this.isLocked) title = 'Bereits beantwortet – nicht änderbar';
			const disabled = !this.kannFragenAendern || !!frage.andereGruppe;

			return `
				<div class="form-check form-switch d-flex justify-content-center m-0" title="${title}">
					<input class="form-check-input" type="checkbox" role="switch" aria-label="${title}" ${frage.aktiv ? 'checked' : ''} ${disabled ? 'disabled' : ''}>
				</div>`;
		},
		getFrageAktionenHtml(frage) {
			const canDelete = !this.isLocked && !frage.inAnderenFrageboegen;
			let titleDelete = 'Frage löschen';
			if (frage.inAnderenFrageboegen) titleDelete = frage.aktiv
					? 'Nicht löschbar: Frage ist auch in anderen Fragebögen verwendet. Über „In Gruppe“ aus dieser Gruppe entfernen.'
					: 'Nicht löschbar: Frage ist auch in anderen Fragebögen verwendet.';
			if (this.isLocked) titleDelete = 'Nicht löschbar: Fragebogen wurde bereits beantwortet.';

			return `
				<div class="d-flex justify-content-center gap-1">
					<button type="button" class="btn btn-sm btn-outline-secondary" data-action="edit" data-tooltip="Frage bearbeiten" aria-label="Frage bearbeiten">
						<i class="fa-solid fa-pen"></i>
					</button>
					<span data-tooltip="${titleDelete}">
						<button type="button" class="btn btn-sm btn-outline-secondary" data-action="delete" aria-label="Frage löschen" ${canDelete ? '' : 'disabled'}>
							<i class="fa-solid fa-xmark"></i>
						</button>
					</span>
				</div>`;
		},
		onFrageAktivClick(e, frage) {
			const input = e.target.closest('input');
			if (!input || input.disabled) return;

			this.toggleFrage(frage);
		},
		onFrageAction(e, frage) {
			const button = e.target.closest('button');
			if (!button || button.disabled) return;

			const action = button.dataset.action;
			if (action === 'up') this.sortFrage(frage, -1);
			if (action === 'down') this.sortFrage(frage, 1);
			if (action === 'edit') this.$refs.frageModal.open(frage.lvevaluierung_frage_id, this.getFrageNurLesenGrund(frage));
			if (action === 'delete') this.deleteFrage(frage);
		},
		// Nur ansehen: beantwortet oder bestehende Frage
		getFrageNurLesenGrund(frage) {
			if (this.isLocked) return 'Bereits beantwortet – nur Ansicht.';
			if (frage.inAnderenFrageboegen) return 'Bestehende Frage in anderen Fragebögen verwendet – nicht änderbar. Für Änderungen eine neue Frage anlegen.';
			return null;
		},
		// Schalter: Frage der Gruppe zuordnen bzw. entfernen
		toggleFrage(frage) {
			const apiCall = frage.aktiv
					? ApiAdminFragebogen.deleteGruppeFrage(this.selGruppeId, frage.lvevaluierung_frage_id)
					: ApiAdminFragebogen.insertGruppeFrage(this.selGruppeId, frage.lvevaluierung_frage_id);

			this.$api
					.call(apiCall)
					.catch(error => this.$fhcAlert.handleSystemError(error))
					.finally(() => this.reloadFragen());	// bei Fehler Schalter zurücksetzen
		},
		sortFrage(frage, schritt) {
			const frage_id = frage.lvevaluierung_frage_id;
			const ids = this.aktivFrageIds.filter(id => id !== frage_id);
			ids.splice(this.aktivFrageIds.indexOf(frage_id) + schritt, 0, frage_id);

			this.$api
					.call(ApiAdminFragebogen.sortFragen(this.selGruppeId, ids))
					.then(() => {
						this.$fhcAlert.alertSuccess('Gespeichert');
						this.$refs.fragenTable.reloadTable();
					})
					.catch(error => this.$fhcAlert.handleSystemError(error));
		},
		async deleteFrage(frage) {
			if (!await this.$fhcAlert.confirm({
				header: 'Bitte bestätigen Sie:',
				message: 'Frage „' + frage.bezeichnung_de + '“ samt Antwortoptionen löschen?'
			})) return;

			this.$api
					.call(ApiAdminFragebogen.deleteFrage(frage.lvevaluierung_frage_id, this.fragebogenId))
					.then(() => {
						this.$fhcAlert.alertSuccess('Frage gelöscht');
						this.reloadFragen();
					})
					.catch(error => this.$fhcAlert.handleSystemError(error));
		},
		// Fragen und Anzahl Fragen in der Gruppen-Tabelle aktualisieren
		reloadFragen() {
			this.$refs.fragenTable.reloadTable();
			this.$refs.gruppenTable.reloadTable();
		}
	},
	template: `
	<div class="d-flex flex-column gap-3">
		<div v-if="isLocked" class="alert alert-warning mb-0">
			Bereits beantwortet – nur Ansicht. Für Änderungen einen neuen Fragebogen anlegen und die Gruppen übernehmen.
		</div>

		<!-- ab lg nebeneinander, darunter untereinander -->
		<div class="row g-4">
			<div class="col-lg-6">
				<h3 class="h5">Fragegruppen</h3>
				<core-filter-cmpt
					ref="gruppenTable"
					uniqueId="tabFragebogenGruppen"
					table-only
					:side-menu="false"
					new-btn-show
					new-btn-label="Fragegruppe"
					:new-btn-disabled="isLocked || !fragebogenId"
					:tabulator-options="gruppenOptions"
					:tabulator-events="gruppenEvents"
					@click:new="$refs.gruppeModal.open(null, null)"
				></core-filter-cmpt>
			</div>
			<div class="col-lg-6">
				<h3 class="h5">Fragen<small v-if="selGruppe" class="fw-normal text-secondary"> | {{ selGruppe.bezeichnung_de }}</small></h3>
				<div v-if="selGruppe && !selGruppe.aktiv && !isLocked" class="alert alert-warning">
					Gruppe ist nicht in diesem Fragebogen – Fragen nur zur Ansicht.
				</div>
				<core-filter-cmpt
					ref="fragenTable"
					uniqueId="tabFragebogenFragen"
					table-only
					:side-menu="false"
					new-btn-show
					new-btn-label="Frage"
					:new-btn-disabled="!kannFragenAendern"
					:tabulator-options="fragenOptions"
					@click:new="$refs.frageModal.open(null, null)"
				></core-filter-cmpt>
			</div>
		</div>

		<fragebogen-gruppe-modal
			ref="gruppeModal"
			:fragebogen-id="fragebogenId"
			@saved="$refs.gruppenTable.reloadTable()"
		></fragebogen-gruppe-modal>
		<fragebogen-frage-modal
			ref="frageModal"
			:fragebogen-id="fragebogenId"
			:gruppe-id="selGruppeId"
			@saved="reloadFragen"
		></fragebogen-frage-modal>
	</div>
	`
}