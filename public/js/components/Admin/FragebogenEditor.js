import {CoreFilterCmpt} from "../../../../../js/components/filter/Filter.js";
import ApiAdminFragebogen from "../../api/admin/fragebogen.js";
import FragebogenModal from "./FragebogenModal.js";
import FragebogenAufbau from "./FragebogenAufbau.js";
import FragebogenZuordnung from "./FragebogenZuordnung.js";

export default {
	name: "FragebogenEditor",
	components: {
		CoreFilterCmpt,
		FragebogenModal,
		FragebogenAufbau,
		FragebogenZuordnung
	},
	data() {
		return {
			tabs: [
				{key: 'aufbau', label: 'Aufbau'},
				{key: 'zuordnung', label: 'Zuordnung'}
			],
			tab: 'aufbau',
			frageboegen: [],	// Geladene Fragebögen der Tabelle
			selFbId: null		// Fragebogen, der unten verwaltet wird
		}
	},
	computed: {
		selFb() {
			return this.frageboegen.find(fb => fb.fragebogen_id === this.selFbId);
		},
		// Bereits beantwortet: Aufbau nur lesbar
		isLocked() {
			return !!this.selFb && this.selFb.hasAntworten;
		},
		// In Evaluierung verwendet: Zuordnungen nur lesbar
		isVerwendet() {
			return !!this.selFb && this.selFb.isVerwendet;
		},
		tabulatorEvents() {
			return [{event: 'rowClick', handler: this.onRowClick}];
		},
		tabulatorOptions() {
			return {
				ajaxURL: 'dummy',
				ajaxRequestFunc: () => this.$api.call(ApiAdminFragebogen.getFrageboegen()),
				ajaxResponse: (url, params, response) => {
					this.frageboegen = response.data;
					// Gewählter Fragebogen nicht mehr vorhanden: Auswahl aufheben
					if (!this.selFb) this.selFbId = null;
					return response.data;
				},
				// Gewählten Fragebogen markieren
				rowFormatter: row => row.getElement().classList.toggle(
					'tabulator-selected',
					row.getData().fragebogen_id === this.selFbId
				),
				layout: 'fitColumns',
				maxHeight: '50vh',
				autoResize: true,
				resizableColumnFit: true,
				selectable: false,
				index: 'fragebogen_id',
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
						title: 'ID',
						field: 'fragebogen_id',
						headerFilter: 'input',
						width: 60
					},
					{
						title: 'Bezeichnung DE',
						field: 'bezeichnung_de',
						headerFilter: 'input',
						widthGrow: 2
					},
					{
						title: 'Bezeichnung EN',
						field: 'bezeichnung_en',
						headerFilter: 'input',
						widthGrow: 2
					},
					{
						title: 'Gültig von',
						field: 'gueltig_von',
						formatter: cell => this.formatDate(cell.getValue()),
						minWidth: 100
					},
					{
						title: 'Gültig bis',
						field: 'gueltig_bis',
						formatter: cell => this.formatDate(cell.getValue()),
						minWidth: 100
					},
					{
						title: 'Zuordnungen',
						field: 'anzahlZuordnungen',
						hozAlign: 'right',
						minWidth: 60
					},
					{
						title: 'Verwendet',
						field: 'isVerwendet',
						formatter: 'tickCross',
						formatterParams: {
							tickElement: '<i class="fa-solid fa-check"></i>',
							crossElement: false
						},
						headerFilter: 'tickCross',
						headerFilterParams: {tristate: true},
						tooltip: (e, cell) => cell.getValue() ? 'In einer LV-Evaluierung verwendet' : 'Noch nicht verwendet',
						hozAlign: 'center',
						minWidth: 60
					},
					{
						title: 'Beantwortet',
						field: 'hasAntworten',
						formatter: 'tickCross',
						formatterParams: {
							tickElement: '<i class="fa-solid fa-check"></i>',
							crossElement: false
						},
						headerFilter: 'tickCross',
						headerFilterParams: {tristate: true},
						tooltip: (e, cell) => cell.getValue() ? 'Bereits beantwortet' : 'Noch nicht beantwortet',
						hozAlign: 'center',
						minWidth: 60
					},
					{
						title: 'Aktionen',
						formatter: cell => this.getActionButtons(cell.getData()),
						cellClick: (e, cell) => this.onActionClick(e, cell.getData()),
						headerSort: false,
						hozAlign: 'center',
						width: 100
					}
				]
			};
		}
	},
	watch: {
		// Markierung in der Tabelle aktualisieren
		selFbId() {
			this.$refs.fbTable.tabulator.getRows().forEach(row => row.reformat());
		}
	},
	methods: {
		formatDate(value) {
			return value ? this.DateHelper.formatDate(value) : '';
		},
		getActionButtons(fb) {
			const titleEdit = fb.hasAntworten ? 'Bereits beantwortet – nur Gültig bis änderbar' : 'Bearbeiten';
			const titleDelete = fb.isVerwendet ? 'In einer Evaluierung verwendet – nicht löschbar' : 'Löschen';

			return `
				<div class="d-flex justify-content-center gap-1">
					<button type="button" class="btn btn-sm btn-outline-secondary" data-action="edit" data-tooltip="${titleEdit}" aria-label="Bearbeiten">
						<i class="fa-solid fa-pen"></i>
					</button>
					<span data-tooltip="${titleDelete}">
						<button type="button" class="btn btn-sm btn-outline-secondary" data-action="delete" aria-label="Löschen" ${fb.isVerwendet ? 'disabled' : ''}>
							<i class="fa-solid fa-xmark"></i>
						</button>
					</span>
				</div>`;
		},
		onActionClick(e, fb) {
			const button = e.target.closest('button');
			if (!button || button.disabled) return;

			if (button.dataset.action === 'edit') this.$refs.fbModal.open(fb);
			if (button.dataset.action === 'delete') this.deleteFragebogen(fb);
		},
		// Zeilenklick wählt den Fragebogen, nicht bei Klick auf Buttons
		onRowClick(e, row) {
			if (e.target.closest('button')) return;

			this.selFbId = row.getData().fragebogen_id;
		},
		// Gespeicherten Fragebogen wählen und Tabelle neu laden
		onSaved(fragebogen_id) {
			this.selFbId = fragebogen_id;
			this.$refs.fbTable.reloadTable();
		},
		async deleteFragebogen(fb) {
			if (!await this.$fhcAlert.confirm({
				header: 'Bitte bestätigen Sie:',
				message: 'Fragebogen „' + fb.bezeichnung_de + '“ löschen?'
			})) return;

			this.$api
				.call(ApiAdminFragebogen.deleteFragebogen(fb.fragebogen_id))
				.then(() => {
					this.$fhcAlert.alertSuccess('Fragebogen gelöscht');
					this.$refs.fbTable.reloadTable();
				})
				.catch(error => this.$fhcAlert.handleSystemError(error));
		}
	},
	template: `
	<div class="fragebogen-editor container-fluid">
		<h1 class="h2 mb-3 fhc-page-header">LV-Evaluierung<small class="fw-normal"> | Fragebögen verwalten</small></h1>

		<!-- Übersicht: Zeilenklick wählt den Fragebogen -->
		<core-filter-cmpt
			ref="fbTable"
			uniqueId="tabFrageboegen"
			table-only
			:side-menu="false"
			new-btn-show
			new-btn-label="Fragebogen"
			:tabulator-options="tabulatorOptions"
			:tabulator-events="tabulatorEvents"
			@click:new="$refs.fbModal.open(null)"
		></core-filter-cmpt>

		<!-- Fragebogen verwalten -->
		<h2 class="h4 mt-5 mb-4">
			Fragebogen verwalten<small v-if="selFb" class="fw-normal text-secondary"> | {{ selFb.bezeichnung_de }}</small>
		</h2>
		<ul class="nav nav-tabs">
			<li v-for="t in tabs" :key="t.key" class="nav-item">
				<button type="button" class="nav-link" :class="{active: tab === t.key}" @click="tab = t.key">{{ t.label }}</button>
			</li>
		</ul>
		<div class="p-3 mb-4">
			<fragebogen-aufbau
				v-if="tab === 'aufbau'"
				:fragebogen-id="selFbId"
				:is-locked="isLocked"
			></fragebogen-aufbau>
			<fragebogen-zuordnung
				v-else-if="selFb"
				:fragebogen-id="selFbId"
				:is-verwendet="isVerwendet"
				@changed="$refs.fbTable.reloadTable()"
			></fragebogen-zuordnung>
			<p v-else class="text-secondary mb-0">Bitte zuerst einen Fragebogen anklicken.</p>
		</div>

		<fragebogen-modal ref="fbModal" @saved="onSaved"></fragebogen-modal>
	</div>
	`
}