import FormInput from "../../../../../js/components/Form/Input.js";
import {CoreFilterCmpt} from '../../../../../js/components/filter/Filter.js';
import ApiFhc from "../../api/fhc";
import ApiEvaluation from "../../api/evaluation";

export default {
	name: "Studienbereich",
	components: {
		FormInput,
		CoreFilterCmpt
	},
	data() {
		return {
			lists: {
				studiensemester: [],
				oes: []
			},
			selStudiensemester: null,
			selOeKurzbz: null,
			table: null,
			malve: null
		}
	},
	created() {
		this.$api
				.call(ApiFhc.Studiensemester.getAll())
				.then(result => this.lists.studiensemester = result.data)
				.then(() => this.$api.call(ApiFhc.Studiensemester.getAktNext()))
				.then(result => {
					// Selected Studiensemester
					this.selStudiensemester = result.data[0].studiensemester_kurzbz;

					// Dropdown Kompetenzfelder
					return this.$api.call(ApiEvaluation.getEntitledKfs()) // todo studiensemester?
				})
				.then(result => {
					this.lists.oes = result.data
					this.selOeKurzbz = result.data[0].oe_kurzbz;

					// MALVE Status
					return this.$api.call(ApiEvaluation.getMalveByKf(this.selOeKurzbz, this.selStudiensemester))
				})
				.then(result => this.malve = result.data)
				.catch(error => this.$fhcAlert.handleSystemError(error));
	},
	computed: {
		selOeFullName() {
			const oe = this.lists.oes.find(oe => oe.oe_kurzbz == this.selOeKurzbz);
			return oe ? `${oe.bezeichnung}` : "";
		},
		site_url_opLvKvp() {
			return this.$api.getUri() + 'extensions/FHC-Core-LVKVP/cis/Einmeldung/RedirectToOPByLvId/';
		},
		site_url_opStgKvp() {
			return this.$api.getUri() + 'extensions/FHC-Core-LVKVP/Redirect/toStg/' + this.selOeKurzbz;
		},
		isDisabledSubmitMalveBtn() {
			return this.malve?.length > 0;
		},
		malveAbgeschlossenTxt() {
			if (this.malve !== null) return 'MALVE-KFL abgeschlossen am ' + this.DateHelper.formatDate(this.malve[0].insertamum)
		},
		tabulatorOptions() {
			const self = this;
			return {
				ajaxURL: 'dummy',
				ajaxRequestFunc: () => {
					if (!this.selStudiensemester || !this.selOeKurzbz) {
						return Promise.resolve({data: []});
					}

					return this.$api.call(ApiEvaluation.getLvListByKf(
							this.selStudiensemester,
							this.selOeKurzbz
					))
				},
				ajaxResponse: (url, params, response) => response.data,
				layout: 'fitColumns',
				height: "calc(100vh - 350px)", // 350 for header and margin height
				autoResize: true,
				resizableColumnFit: true,
				selectable: false,
				index: 'lvevaluierung_lehrveranstaltung_id',
				columnDefaults: {
					headerTooltip: true
				},
				columns: [
					{
						title: 'STG-Kurzbz',
						field: 'kurzbzlang',
						headerFilter: "input",
						minWidth: 100
					},
					{
						title: 'Studiengang',
						field: 'stg_bezeichnung',
						headerFilter: "input",
						widthGrow: 2,
						visible: false
					},
					{
						title: 'LV-Bezeichnung',
						field: 'lv_bezeichnung',
						headerFilter: "input",
						bottomCalc: "count",
						bottomCalcFormatter: function (cell) {
							const num = cell.getValue();
							return isNaN(num) ? "–" : "Anzahl: " + num;
						},
						widthGrow: 3
					},
					{
						title: 'OrgForm',
						field: 'orgform_kurzbz',
						headerFilter: "input",
						minWidth: 100
					},
					{
						title: 'Semester',
						field: 'semester',
						headerFilter: "input",
						minWidth: 100
					},
					{
						title: 'Ausgewählt',
						field: 'verpflichtend',
						formatter: "tickCross",
						headerFilter: 'tickCross',
						headerFilterParams: {"tristate": true},
						hozAlign: "center",
						formatterParams: {
							tickElement: '<i class="fa fa-check text-success"></i>',
							crossElement: '<i class="fa fa-xmark text-danger"></i>'
						},
						tooltip: (e, cell) => cell.getValue() ? "verbindlich" : "abgewählt",
						minWidth: 100

					},
					{
						title: 'Evaluationsebene',
						field: 'lv_aufgeteilt',
						formatter: (cell) => {
							return cell.getValue()
									? '<i class="fa-solid fa-expand text-dark"></i>'
									: '<i class="fa-solid fa-square-full text-dark"></i>';
						},
						headerFilter: "list",
						headerFilterParams: {
							values: [
								{value: "", label: "Alle"},
								{value: 0, label: "Evaluierung der LV auf Gesamt-Ebene"},
								{value: 1, label: "Evaluierung der LV auf Gruppen-Ebene"}
							],
							clearable: true
						},
						hozAlign: "center",
						tooltip: (e, cell) => cell.getValue() ? "Evaluierung der LV erfolgt auf Gruppen-Ebene" : "Evaluierung der LV erfolgt auf Gesamt-Ebene",
						minWidth: 120
					},
					{
						title: "Rücklauf",
						field: "ruecklauf",
						headerFilter: "input",
						headerFilterFunc: (filterValue, rowValue, rowData) => {
							if (filterValue === "") return true

							const filter = String(filterValue)

							const submitted = String(rowData.submittedCodes ?? "")
							const issued = String(rowData.codesAusgegeben ?? "")

							// match:
							// 8  → 8/26
							// 2  → 8/26
							// 26 → 8/26
							// 0  → 0/0, 13/0, 0/12
							return (
									submitted.includes(filter) ||
									issued.includes(filter)
							)
						},
						formatter: function (cell) {
							const submittedCodes = cell.getData().submittedCodes;
							const codesAusgegeben = cell.getData().codesAusgegeben;
							return `${submittedCodes}/${codesAusgegeben}`;
						},
						hozAlign: "right",
						minWidth: 100,
						tooltip: "Abgeschlossene LV-Evaluierungen / zur LV-Evaluierung eingeladene Studierende",
					},
					{
						title: 'RL-Quote',
						field: 'ruecklaufQuote',
						headerFilter: "input",
						hozAlign: "right",
						formatter: cell => {
							const value = cell.getValue();
							return value !== null ? `${value}%` : '-'
						},
						sorter: "number",
						width: 200,
						bottomCalc: values => {
							const nums = values.filter(v => typeof v === 'number')
							if (!nums.length) return null
							return nums.reduce((a, b) => a + b, 0) / nums.length
						},
						bottomCalcFormatter: function (cell) {
							const num = cell.getValue();
							return typeof num === 'number' ? num.toFixed(2) + "%" : "–";
						}
					},
					{
						title: 'LV-Evaluation',
						formatter: (cell) => {
							// disable button if not for evaluation ausgewählt
							const enabled = cell.getData().verpflichtend;

							return `<button class="btn btn-outline-secondary"
									  ${!enabled ? 'disabled' : ''}>
									  <i class="fa-solid fa-square-poll-horizontal me-2"></i>
									  LV-Evaluation
									</button>`;
						},
						cellClick: (e, cell) => {
							if (!cell.getData().verpflichtend) return;
							self.openEvaluationByLveLv(cell.getData().lvevaluierung_lehrveranstaltung_id)
						},
						hozAlign: "center",
						headerSort: false,
						width: 140
					},
					{
						title: 'MALVE-LV-Weiterentwicklung (OP)',
						formatter(cell) {
							const templateId = cell.getData().lehrveranstaltung_template_id;
							if (templateId === null) {
								return `<small class=text-muted">LV mit keinem Quellkurs verknüpft</small>`;
							} else {
								const lvId = cell.getData().lehrveranstaltung_id;
								const url = self.site_url_opLvKvp + lvId;
								return `
									<a 
										href="${url}" 
										target="_blank" 
										role="button" 
										class="btn btn-outline-secondary me-2" 
										
									>
										<span 
											v-tooltip 
											title="Schnittstelle zur Maßnahmenableitung für die einzelnen LVs in OP"
										>
											<i class="fa-solid fa-external-link me-2"></i>LV-Weiterentwicklung
										</span>
									</a>`
							}

						},
						hozAlign: "center",
						headerSort: false,
						width: 240
					},
					{
						title: 'Geprüft',
						field: 'reviewed_kf',
						formatter: "tickCross",
						headerFilter: 'tickCross',
						headerFilterParams: {"tristate": true},
						headerTooltip: 'Optional zur besseren persönlichen Übersicht',
						hozAlign: "center",
						formatterParams: {
							tickElement: '<i class="fa fa-check text-success"></i>',
							crossElement: '<i class="fa fa-xmark text-danger"></i>'
						},
						editable: true,
						cellClick: (e, cell) => {
							const value = cell.getValue()
							cell.setValue(!value, true)
						},
						tooltip: (e, cell) => cell.getValue() ? "ja" : "nein",
						width: 120
					},
				]
			}
		},
	},
	methods: {
		onStudiensemesterChange() {
			if (!this.selStudiensemester || !this.table) return;

			this.table.replaceData();
		},
		onOeChange() {
			if (!this.selOeKurzbz || !this.selStudiensemester || !this.table) return;

			this.table.replaceData();

			this.$api
				.call(ApiEvaluation.getMalveByKf(this.selOeKurzbz, this.selStudiensemester))
				.then(result => this.malve = result.data)
				.catch(error => this.$fhcAlert.handleSystemError(error));

		},
		openEvaluationByLveLv(lvevaluierung_lehrveranstaltung_id) {
			const url = this.$api.getUri() +
					'extensions/FHC-Core-Evaluierung/evaluation/Evaluation/stg/' +
					'?lvevaluierung_lehrveranstaltung_id=' + lvevaluierung_lehrveranstaltung_id;

			window.open(url, '_blank');
		},
		updateReviewedLvInKf(cell) {
			this.$api
				.call(ApiEvaluation.updateReviewedLvInKf(cell.getData().lvevaluierung_lehrveranstaltung_id, cell.getValue()))
				.then(result => result.data && this.$fhcAlert.alertSuccess(this.$p.t('ui', 'gespeichert')))
				.catch(error => this.$fhcAlert.handleSystemError(error));
		},
		async submitMalve() {
			if (await this.$fhcAlert.confirm({
				header: 'Bitte bestätigen Sie:',
				message: `Ich habe alle LV-Evaluierungen des Kompetenzfelds - ${this.selOeFullName} - im ${this.selStudiensemester} geprüft. Notwendige Maßnahmen für die KF-Weiterentwicklung wurden abgeleitet.`
			}) === false
			) {
				return;
			}

			this.$api.call(ApiEvaluation.saveMalveByKf(this.selOeKurzbz, this.selStudiensemester))
				.then(result => {
					if (result.data) {
						this.malve = result.data;
						this.$fhcAlert.alertSuccess(this.$p.t('ui', 'gespeichert'));
					}
				})
				.catch(error => {
					cell.restoreOldValue();
					this.$fhcAlert.handleSystemError(error);
				});
		},
		onTableBuilt() {
			this.table = this.$refs.kfTable.tabulator;
		},
		onCellEdited(cell) {
			switch (cell.getField()) {
				case 'reviewed_kf':
					this.updateReviewedLvInKf(cell);
					break;
				default:
					break;
			}
		}
	},
	template: `
	<div class="evaluation-studienbereich container-fluid overflow-hidden">
		<h1 class="mb-5">LV-Evaluation | Übersicht Kompetenzfeldleitung</h1>
	 	<div class="row align-items-center mb-3">
	 		<h2>{{selStudiensemester}} - {{ selOeFullName }}</h2>
			<div class="col-md-12">
				<div class="d-flex justify-content-end align-items-center">
					<div class="me-2">
						<form-input
							type="select"
							v-model="selStudiensemester"
							name="studiensemester_kurzbz"
							:label="$p.t('lehre/studiensemester')"
							 @change="onStudiensemesterChange"
						 >
							<option 
								v-for="studSem in lists.studiensemester"
								:key="studSem.studiensemester_kurzbz" 
								:value="studSem.studiensemester_kurzbz">
								{{ studSem.studiensemester_kurzbz }}
							</option>
						</form-input>
					</div>
					<div class="me-2">
						<form-input
							type="select"
							v-model="selOeKurzbz"
							name="oe_kurzbz"
							:label="$p.t('lehre/kompetenzfeld')"
							@change="onOeChange"
						>
							<option v-for="oe in lists.oes" :key="oe.oe_kurzbz" :value="oe.oe_kurzbz">
								{{ oe.bezeichnung }}
							</option>
						</form-input>
					</div><!--.div right buttons -->
				</div><!--.d-flex-->
			</div><!--.col -->
	  	</div>
	  	<div class="evaluation-studienbereich-table">
			<core-filter-cmpt
				v-if="selStudiensemester && selOeKurzbz"
				ref="kfTable"
				uniqueId="tabStudienbereich"
				table-only
				:side-menu="false"
				:tabulator-options="tabulatorOptions"
				:tabulator-events="[
					{event: 'tableBuilt', handler: onTableBuilt},
					{event: 'cellEdited', handler: onCellEdited},
				]">
				<template v-slot:actions>
					<button 
						v-if="malve !== null"
						class="btn"
						:class="malve?.length > 0 ? 'btn-success' : 'btn-primary'" 
						@click="submitMalve" 
						:disabled="isDisabledSubmitMalveBtn"
						>
						<i v-if="malve?.length > 0" class="fa fa-circle-check fa-lg me-2"></i>
						{{ malve.length > 0 ? 'MALVE-KFL abgeschlossen' : 'MALVE-KFL abschließen' }}
					</button>
					<span v-if="malve !== null && malve.length > 0" class="text-success ms-2"><i class="fa fa-circle-check fa-lg text-success me-2"></i>{{ malveAbgeschlossenTxt }}</span>
				</template>
			</core-filter-cmpt>
		</div>
	</div>
	`
};

