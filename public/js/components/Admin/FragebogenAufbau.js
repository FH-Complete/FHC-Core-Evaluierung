import {CoreFilterCmpt} from "../../../../../js/components/filter/Filter.js";
import ApiAdminFragebogen from "../../api/admin/fragebogen.js";
import FragebogenHelper from "../../helpers/FragebogenHelper.js";
import FragebogenGruppeModal from "./FragebogenGruppeModal.js";

export default {
	name: "FragebogenAufbau",
	components: {
		CoreFilterCmpt,
		FragebogenGruppeModal
	},
	props: {
		fragebogenId: {default: null},	// null: noch kein Fragebogen gewählt
		isLocked: Boolean				// Bereits beantwortet: Aufbau nur lesbar
	},
	data() {
		return {
			gruppen: [],		// Alle Gruppen, aktive zuerst nach Sort
			selGruppeId: null	// Gruppe, deren Fragen angezeigt werden
		}
	},
	computed: {
		aktivIds() {
			return this.gruppen
				.filter(gruppe => gruppe.aktiv)
				.map(gruppe => gruppe.lvevaluierung_fragebogen_gruppe_id);
		},
		selGruppe() {
			return this.gruppen.find(gruppe => gruppe.lvevaluierung_fragebogen_gruppe_id === this.selGruppeId);
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
				placeholder: 'Bitte zuerst einen Fragebogen anklicken.',
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
					headerWordWrap: true
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
					if (!this.selGruppeId) return Promise.resolve({data: []});

					return this.$api.call(ApiAdminFragebogen.getFragen(this.selGruppeId));
				},
				ajaxResponse: (url, params, response) => response.data,
				placeholder: 'Bitte zuerst eine Gruppe anklicken.',
				layout: 'fitColumns',
				maxHeight: '50vh',
				selectable: false,
				index: 'lvevaluierung_frage_id',
				columnDefaults: {
					headerTooltip: true,
					headerWordWrap: true
				},
				columns: [
					{
						title: 'In Gruppe',
						field: 'aktiv',
						formatter: 'tickCross',
						formatterParams: {
							tickElement: '<i class="fa-solid fa-check"></i>',
							crossElement: false
						},
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
						minWidth: 100
					},
					{
						title: 'Typ',
						field: 'typ',
						headerFilter: 'input',
						width: 130
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
		// Sort-Nummer mit Pfeilen, tauscht mit der benachbarten aktiven Gruppe
		getSortHtml(gruppe) {
			const index = this.aktivIds.indexOf(gruppe.lvevaluierung_fragebogen_gruppe_id);
			if (index < 0) return '';

			return `
				<div class="d-flex align-items-center gap-1">
					<span class="me-1">${index + 1}</span>
					<button type="button" class="btn btn-sm btn-link py-0 px-1" data-action="up" title="Nach oben" aria-label="Nach oben" ${this.isLocked || index === 0 ? 'disabled' : ''}>
						<i class="fa-solid fa-arrow-up"></i>
					</button>
					<button type="button" class="btn btn-sm btn-link py-0 px-1" data-action="down" title="Nach unten" aria-label="Nach unten" ${this.isLocked || index === this.aktivIds.length - 1 ? 'disabled' : ''}>
						<i class="fa-solid fa-arrow-down"></i>
					</button>
				</div>`;
		},
		// Neue Gruppen (nur hier) werden gelöscht statt entfernt
		getAktivHtml(gruppe) {
			const isNeu = gruppe.aktiv && !gruppe.inAnderenFrageboegen;
			let title = gruppe.aktiv ? 'Aus diesem Fragebogen entfernen' : 'In diesem Fragebogen verwenden';
			if (isNeu) title = 'Neue Gruppe, nur in diesem Fragebogen – zum Entfernen löschen';
			if (this.isLocked) title = 'Bereits beantwortet – nicht änderbar';

			return `
				<div class="form-check form-switch d-flex justify-content-center m-0" title="${title}">
					<input class="form-check-input" type="checkbox" role="switch" aria-label="${title}" ${gruppe.aktiv ? 'checked' : ''} ${this.isLocked || isNeu ? 'disabled' : ''}>
				</div>`;
		},
		getAktionenHtml(gruppe) {
			const canDelete = !this.isLocked && gruppe.aktiv && !gruppe.inAnderenFrageboegen;
			let titleDelete = 'Gruppe löschen';
			if (!gruppe.aktiv) titleDelete = 'Bestehende Gruppe – nicht löschbar';
			else if (gruppe.inAnderenFrageboegen) titleDelete = 'Bestehende Gruppe – über „Im Fragebogen“ entfernen';
			if (this.isLocked) titleDelete = 'Bereits beantwortet – nicht löschbar';

			return `
				<div class="d-flex justify-content-center gap-1">
					<button type="button" class="btn btn-sm btn-outline-secondary" data-action="edit" title="Gruppe bearbeiten" aria-label="Gruppe bearbeiten">
						<i class="fa-solid fa-pen"></i>
					</button>
					<span title="${titleDelete}">
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
		// Zeilenklick zeigt die Fragen der Gruppe, nicht bei Buttons und Schaltern; label hat keine Fragen
		onGruppeRowClick(e, row) {
			const gruppe = row.getData();
			if (e.target.closest('button, input') || gruppe.typ === FragebogenHelper.GRUPPE_TYP.LABEL) return;

			this.selGruppeId = gruppe.lvevaluierung_fragebogen_gruppe_id;
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
				message: 'Gruppe „' + gruppe.bezeichnung_de + '“ löschen? Fragen, die sonst nirgends verwendet werden, werden mitgelöscht.'
			})) return;

			this.$api
				.call(ApiAdminFragebogen.deleteGruppe(gruppe.lvevaluierung_fragebogen_gruppe_id))
				.then(() => {
					this.$fhcAlert.alertSuccess('Gruppe gelöscht');
					this.$refs.gruppenTable.reloadTable();
				})
				.catch(error => this.$fhcAlert.handleSystemError(error));
		}
	},
	template: `
	<div class="d-flex flex-column gap-3">
		<div v-if="isLocked" class="alert alert-warning mb-0">
			Bereits beantwortet – Aufbau nur lesbar. Für Änderungen einen neuen Fragebogen anlegen und die Gruppen übernehmen.
		</div>

		<!-- ab lg nebeneinander, darunter untereinander -->
		<div class="row g-4">
			<div class="col-lg-6">
				<h3 class="h5">Gruppen</h3>
				<core-filter-cmpt
					ref="gruppenTable"
					uniqueId="tabFragebogenGruppen"
					table-only
					:side-menu="false"
					new-btn-show
					new-btn-label="Gruppe"
					:new-btn-disabled="isLocked || !fragebogenId"
					:tabulator-options="gruppenOptions"
					:tabulator-events="gruppenEvents"
					@click:new="$refs.gruppeModal.open(null, null)"
				></core-filter-cmpt>
			</div>
			<div class="col-lg-6">
				<h3 class="h5">Fragen<small v-if="selGruppe" class="fw-normal text-secondary"> | {{ selGruppe.bezeichnung_de }}</small></h3>
				<core-filter-cmpt
					ref="fragenTable"
					uniqueId="tabFragebogenFragen"
					table-only
					:side-menu="false"
					:tabulator-options="fragenOptions"
				></core-filter-cmpt>
			</div>
		</div>

		<fragebogen-gruppe-modal
			ref="gruppeModal"
			:fragebogen-id="fragebogenId"
			@saved="$refs.gruppenTable.reloadTable()"
		></fragebogen-gruppe-modal>
	</div>
	`
}