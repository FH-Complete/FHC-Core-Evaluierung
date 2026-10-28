import {CoreFilterCmpt} from "../../../../../js/components/filter/Filter.js";
import ApiAdminFragebogen from "../../api/admin/fragebogen.js";
import FragebogenBasis from "./FragebogenBasis.js";
import FragebogenAufbau from "./FragebogenAufbau.js";
import FragebogenZuordnung from "./FragebogenZuordnung.js";

export default {
	name: "FragebogenEditor",
	components: {
		CoreFilterCmpt,
		FragebogenBasis,
		FragebogenAufbau,
		FragebogenZuordnung
	},
	data() {
		return {
			tabs: [
				{key: 'fragebogen', label: 'Fragebogen'},
				{key: 'aufbau', label: 'Fragebogenaufbau'},
				{key: 'zuordnung', label: 'Zuordnung'}
			],
			tab: 'fragebogen',
			frageboegen: [],	// Geladene Fragebögen der Tabelle
			selFbId: null,		// Fragebogen, der unten verwaltet wird
			isNeu: false		// Neuer Fragebogen: leeres Formular im Reiter Fragebogen
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

					if (this.selFb) {
						this.isNeu = false;
					} else {
						this.selFbId = null;
					}
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
		// Markierung in der Tabelle aktualisieren; ohne Auswahl nur Reiter Fragebogen
		selFbId() {
			this.$refs.fbTable.tabulator.getRows().forEach(row => row.reformat());
			if (!this.selFbId) this.tab = 'fragebogen';
		}
	},
	methods: {
		formatDate(value) {
			return value ? this.DateHelper.formatDate(value) : '';
		},
		getActionButtons(fb) {
			const titleDelete = fb.isVerwendet ? 'In einer Evaluierung verwendet – nicht löschbar' : 'Löschen';

			return `
				<span data-tooltip="${titleDelete}">
					<button type="button" class="btn btn-sm btn-outline-secondary" data-action="delete" aria-label="Löschen" ${fb.isVerwendet ? 'disabled' : ''}>
						<i class="fa-solid fa-xmark"></i>
					</button>
				</span>`;
		},
		onActionClick(e, fb) {
			const button = e.target.closest('button');
			if (!button || button.disabled) return;

			this.deleteFragebogen(fb);
		},
		// Zeilenklick wählt den Fragebogen, nicht bei Klick auf Buttons
		onRowClick(e, row) {
			if (e.target.closest('button')) return;

			this.isNeu = false;
			this.selFbId = row.getData().fragebogen_id;
		},
		// Neuer Fragebogen: leeres Formular im Reiter Fragebogen
		onNew() {
			this.selFbId = null;
			this.isNeu = true;
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
			@click:new="onNew"
		></core-filter-cmpt>

		<!-- Fragebogen verwalten -->
		<h2 class="h4 mt-5 mb-3">Fragebogen verwalten</h2>
		<div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 fs-5 fw-medium mb-3">
			<span>{{ selFb ? selFb.bezeichnung_de : '' }}</span>
		</div>
		<!-- Gilt für alle Reiter -->
		<div v-if="isLocked" class="alert alert-warning">
			<strong>Nur Ansicht</strong> – Fragebogen bereits beantwortet. Nur „Gültig bis“ ist änderbar. Für Änderungen einen neuen Fragebogen anlegen.
		</div>
		<!-- Ohne Auswahl nur Reiter Fragebogen -->
		<ul class="nav nav-tabs">
			<li v-for="t in tabs" :key="t.key" class="nav-item">
				<button type="button" class="nav-link" :class="{active: tab === t.key}" :disabled="t.key !== 'fragebogen' && !selFb" @click="tab = t.key">{{ t.label }}</button>
			</li>
		</ul>
		<div class="p-3 mb-4">
			<p v-if="!selFb && !isNeu" class="text-body-secondary text-center my-3">Bitte Fragebogen wählen</p>
			<fragebogen-basis
				v-else-if="tab === 'fragebogen'"
				:sel-fragebogen="selFb"
				@saved="onSaved"
			></fragebogen-basis>
			<fragebogen-aufbau
				v-else-if="tab === 'aufbau'"
				:fragebogen-id="selFbId"
				:is-locked="isLocked"
			></fragebogen-aufbau>
			<fragebogen-zuordnung
				v-else
				:fragebogen-id="selFbId"
				:is-verwendet="isVerwendet"
				@changed="$refs.fbTable.reloadTable()"
			></fragebogen-zuordnung>
		</div>
	</div>
	`
}