import {CoreFilterCmpt} from "../../../../../js/components/filter/Filter.js";
import ApiAdminFragebogen from "../../api/admin/fragebogen.js";
import FragebogenHelper from "../../helpers/FragebogenHelper.js";
import FragebogenBasis from "./FragebogenBasis.js";
import FragebogenZuordnung from "./FragebogenZuordnung.js";
import FragebogenAufbau from "./FragebogenAufbau.js";

export default {
	name: "FragebogenEditor",
	components: {
		CoreFilterCmpt,
		FragebogenBasis,
		FragebogenZuordnung,
		FragebogenAufbau,
	},
	data() {
		return {
			titles: {neu: 'Neuer Fragebogen', edit: 'Fragebogen bearbeiten', copy: 'Fragebogen kopieren'},
			tabs: [
				{key: 'basis', label: 'Basisdaten'},
				{key: 'zuordnung', label: 'Zuordnung'},
				{key: 'aufbau', label: 'Aufbau'}
			],
			tab: 'basis',
			mode: null,			// edit | copy | neu, null = geschlossen
			fragebogen: null	// Geladener Fragebogen inkl. Zuordnungen und Aufbau
		}
	},
	computed: {
		// Bereits beantwortet: Struktur nur lesbar
		isLocked() {
			return !!this.fragebogen?.hasAntworten;
		},
		tabulatorOptions() {
			return {
				ajaxURL: 'dummy',
				ajaxRequestFunc: () => this.$api.call(ApiAdminFragebogen.getFrageboegen()),
				ajaxResponse: (url, params, response) => response.data,
				layout: 'fitDataTable',
				maxHeight: '50vh',	// So hoch wie die Zeilen, max. halber Bildschirm
				autoResize: true,
				resizableColumnFit: true,
				selectable: false,
				index: 'fragebogen_id',
				columnDefaults: {
					headerTooltip: true
				},
				columns: [
					{
						title: 'ID',
						field: 'fragebogen_id',
						headerFilter: 'input',
						width: 90
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
						minWidth: 110
					},
					{
						title: 'Gültig bis',
						field: 'gueltig_bis',
						formatter: cell => this.formatDate(cell.getValue()),
						minWidth: 110
					},
					{
						title: 'Zuordnungen',
						field: 'anzahlZuordnungen',
						hozAlign: 'right',
						minWidth: 110
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
						minWidth: 100
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
						minWidth: 100
					},
					{
						title: 'Aktionen',
						formatter: cell => this.getActionButtons(cell.getData()),
						cellClick: (e, cell) => this.onActionClick(e, cell.getData()),
						headerSort: false,
						hozAlign: 'center',
						width: 150
					}
				]
			};
		}
	},
	methods: {
		// Bearbeiten oder Kopieren: Fragebogen laden, dann Ansicht zeigen
		openFragebogen(fragebogen_id, mode) {
			this.$api
				.call(ApiAdminFragebogen.getFragebogen(fragebogen_id))
				.then(result => {
					this.fragebogen = FragebogenHelper.prepareLoaded(result.data, false);
					this.mode = mode;
					this.tab = 'basis';
				})
				.catch(error => this.$fhcAlert.handleSystemError(error));
		},
		openNeu() {
			this.fragebogen = null;
			this.mode = 'neu';
			this.tab = 'basis';
		},
		// Zuordnung und Aufbau erst nach dem Anlegen
		isTabDisabled(key) {
			return key !== 'basis' && this.mode !== 'edit';
		},
		formatDate(value) {
			return value ? this.DateHelper.formatDate(value) : '';
		},
		// Aktionen: Bearbeiten, Kopieren, Löschen. Gesperrt mit Erklärung im Tooltip.
		getActionButtons(fb) {
			const titleEdit = fb.hasAntworten
					? 'Bereits beantwortet – nur Gültig bis änderbar'
					: fb.isVerwendet
						? 'In Evaluierung verwendet – Zuordnungen nicht mehr änderbar'
						: 'Bearbeiten';
			const titleDelete = fb.isVerwendet
				? 'Wird bereits in einer Evaluierung verwendet und kann nicht gelöscht werden'
				: 'Löschen';

			return `
				<div class="d-flex justify-content-center gap-2">
					<button type="button" class="btn btn-sm btn-outline-secondary" data-action="edit" title="${titleEdit}" aria-label="Bearbeiten">
						<i class="fa-solid fa-pen-to-square"></i>
					</button>
					<button type="button" class="btn btn-sm btn-outline-secondary" data-action="copy" title="Kopieren" aria-label="Kopieren">
						<i class="fa-solid fa-copy"></i>
					</button>
					<span title="${titleDelete}">
						<button type="button" class="btn btn-sm btn-outline-secondary" data-action="delete" aria-label="Löschen" ${fb.isVerwendet ? 'disabled' : ''}>
							<i class="fa-solid fa-xmark"></i>
						</button>
					</span>
				</div>`;
		},
		onActionClick(e, fb) {
			const button = e.target.closest('button');
			if (!button || button.disabled) return;

			if (button.dataset.action === 'edit') this.openFragebogen(fb.fragebogen_id, 'edit');
			if (button.dataset.action === 'copy') this.openFragebogen(fb.fragebogen_id, 'copy');
			if (button.dataset.action === 'delete') this.deleteFragebogen(fb);
		},
		// Nach dem Speichern: Tabelle neu laden, gespeicherten Fragebogen bearbeiten
		onSaved(fragebogen_id) {
			this.$refs.fbTable.reloadTable();
			this.openFragebogen(fragebogen_id, 'edit');
		},
		async deleteFragebogen(fb) {
			if (!await this.$fhcAlert.confirm({
				header: 'Bitte bestätigen Sie:',
				message: 'Fragebogen „' + fb.bezeichnung_de + '“ inkl. Zuordnungen und Aufbau löschen?'
			})) return;

			this.$api
					.call(ApiAdminFragebogen.deleteFragebogen(fb.fragebogen_id))
				.then(() => {
					this.$fhcAlert.alertSuccess('Fragebogen gelöscht');
					this.$refs.fbTable.reloadTable();

					// Geöffneter Fragebogen (oder Kopiervorlage) gelöscht: Bearbeitung schließen
					if (this.fragebogen?.fragebogen_id === fb.fragebogen_id) {
						this.mode = null;
						this.fragebogen = null;
					}
				})
				.catch(error => this.$fhcAlert.handleSystemError(error));
		},
	},
	template: `
	<div class="fragebogen-editor container-fluid">
		<h1 class="h2 mb-5 fhc-page-header">LV-Evaluierung<small class="fw-normal"> | Fragebögen verwalten</small></h1>

		<!-- Übersicht Fragebögen -->
		<core-filter-cmpt
			ref="fbTable"
			uniqueId="tabFrageboegen"
			table-only
			:side-menu="false"
			new-btn-show
			new-btn-label="Fragebogen"
			@click:new="openNeu"
			:tabulator-options="tabulatorOptions"
		></core-filter-cmpt>

		<!-- Bearbeiten, Neu oder Kopieren -->
		<template v-if="mode">
			<h2 class="h4 mt-5 mb-4">
				{{ titles[mode] }}<small v-if="fragebogen" class="fw-normal text-secondary"> | {{ fragebogen.bezeichnung_de }}</small>
			</h2>

			<ul class="nav nav-tabs">
				<!-- Tooltip am li, da gesperrter nav-link keine Maus-Events hat -->
				<li
					v-for="t in tabs"
					:key="t.key"
					class="nav-item"
					:title="isTabDisabled(t.key) ? 'Erst nach dem Speichern verfügbar' : null"
				>
					<button
						type="button"
						class="nav-link"
						:class="{active: tab === t.key}"
						:disabled="isTabDisabled(t.key)"
						@click="tab = t.key"
					>
						{{ t.label }}
					</button>
				</li>
			</ul>
			<!-- Components -->
			<div class="border border-top-0 rounded-bottom p-3 mb-4">
				<keep-alive>
					<fragebogen-basis
						v-if="tab === 'basis'"
						:fragebogen="fragebogen"
						:mode="mode"
						:is-locked="isLocked"
						@saved="onSaved"
					></fragebogen-basis>
					<fragebogen-zuordnung
						v-else-if="tab === 'zuordnung'"
						:zuordnungen="fragebogen.zuordnungen"
						:is-verwendet="fragebogen.isVerwendet"
						:fragebogen-id="fragebogen.fragebogen_id"
						@changed="$refs.fbTable.reloadTable()"
					></fragebogen-zuordnung>
					<fragebogen-aufbau
						v-else-if="tab === 'aufbau'"
						:gruppen="fragebogen.gruppen"
						:is-locked="isLocked"
						:fragebogen-id="fragebogen.fragebogen_id"
					></fragebogen-aufbau>
				</keep-alive>
			</div>
		</template>
	</div>
	`
}