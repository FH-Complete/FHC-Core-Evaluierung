import FormInput from "../../../../../js/components/Form/Input.js";
import {CoreFilterCmpt} from '../../../../../js/components/filter/Filter.js';
import ApiEvaluation from "../../api/evaluation";
import ApiFhc from "../../api/fhc";

export default {
	components: {
		FormInput,
		CoreFilterCmpt
	},
	data() {
		return {
			lists: {
				studiensemester: [],
				stgs: [],
			},
			selStudiensemester: null,
			selStgKz: null,
			table: null,
		}
	},
	created() {
		this.$api
			.call(ApiFhc.Studiensemester.getAll())
			.then(result => this.lists.studiensemester = result.data)
			.then(() => this.$api.call(ApiFhc.Studiensemester.getAktNext()))
			.then(result => {
				this.selStudiensemester = result.data[0].studiensemester_kurzbz;
				return this.$api.call(ApiEvaluation.getEntitledStgs(this.selStudiensemester))
			})
			.then(result => {
				this.lists.stgs = result.data
				this.selStgKz = result.data[0].studiengang_kz;
			})
			.catch(error => this.$fhcAlert.handleSystemError(error) );
	},
	watch: {
		selStudiensemester(newVal){
			if (newVal && this.selStgKz && this.table)
			{
				this.table.replaceData();
			}
		},
		selStgKz(newVal){
			if (newVal && this.selStudiensemester && this.table)
			{
				this.table.replaceData();
			}
		}
	},
	computed: {
		selStgFullName() {
			const stg = this.lists.stgs.find(s => s.studiengang_kz == this.selStgKz);
			const selStudiensemester = this.selStudiensemester;
			return stg ? `${selStudiensemester} - ${stg.kuerzel} ${stg.bezeichnung}` : "";
		},
		tabulatorOptions() {
			const self = this;
			return {
				ajaxURL: 'dummy',
				ajaxRequestFunc: () => {
					if (!this.selStudiensemester || !this.selStgKz) {
						return Promise.resolve({ data: [] });
					}
					return this.$api.call(ApiEvaluation.getLvListByStg(this.selStudiensemester, this.selStgKz))
				},
				ajaxResponse: (url, params, response) => response.data,
				layout: 'fitColumns',
				height:"calc(100vh - 350px)", // 350 for header and margin height
				autoResize: true,
				resizableColumnFit: true,
				selectable: false,
				index: 'lvevaluierung_lehrveranstaltung_id',
				columns: [
					{
						title:'LV-Bezeichnung',
						field:'bezeichnung',
						headerFilter:"input",
						bottomCalc:"count",
						bottomCalcFormatter:"plaintext",
						widthGrow: 3
					},
					{
						title:'OrgForm',
						field:'orgform_kurzbz',
						headerFilter:"input",
						minWidth: 100
					},
					{
						title:'Semester',
						field:'semester',
						headerFilter:"input",
						minWidth: 100
					},
					{
						title:'Verpflichtend',
						field:'verpflichtend',
						formatter:"tickCross",
						headerFilter: "list",
						headerFilterParams: {
							values: [
								{ value: "", label: "Alle" },
								{ value: true, label: "verpflichtend" },
								{ value: false, label: "nicht verpflichtend" }
							],
							clearable: true
						},
						hozAlign:"center",
						formatterParams: {
							tickElement: '<i class="fa fa-check text-success"></i>',
							crossElement: '<i class="fa fa-xmark text-danger"></i>'
						},
						editor: 'list',
						editorParams: {
							values: [
								{ value: true, label: "verpflichtend" },
								{ value: false, label: "nicht verpflichtend" }
							],
						},
						tooltip: (e, cell) => cell.getValue() ? "verpflichtend" : "nicht verpflichtend",
						minWidth: 100

					},
					{
						title:'Evaluationseinheit',
						field:'lv_aufgeteilt',
						formatter: (cell) => {
							return cell.getValue()
								? '<i class="fa-solid fa-expand text-dark"></i>'
								: '<i class="fa-solid fa-square-full text-dark"></i>';
						},
						headerFilter: "list",
						headerFilterParams: {
							values: [
								{ value: "", label: "Alle" },
								{ value: 0, label: "Gesamt-LV" },
								{ value: 1, label: "Gruppenbasis" }
							],
							clearable: true
						},
						hozAlign:"center",
						tooltip: (e, cell) => cell.getValue() ? "Gruppenbasis" : "Gesamt-LV",
						minWidth: 120
					},
					{
						title: "Rücklauf",
						field: "ruecklauf",
						headerFilter:"input",
						formatter: function(cell) {
							const submittedCodes = cell.getData().submittedCodes;
							const codesAusgegeben = cell.getData().codesAusgegeben;
							return `${submittedCodes}/${codesAusgegeben}`;
						},
						hozAlign: "right",
						minWidth: 100,
						tooltip: "Abgeschickte Fragebögen/Ausgesendete Codes",
					},
					{
						title:'RL-Quote',
						field:'ruecklaufQuote',
						headerFilter:"input",
						hozAlign:"left",
						formatter:"progress",
						formatterParams: {
							min: 0,
							max: 100,
							color: function(value) {
								return (value < 30) ? "red" : "";
							},
							legend: function(value) {
								return value + "%"
							},	// todo check later. disappears on reload. Tabulator 5.2. issue?
							legendAlign: "right"
						},
						sorter: "number",
						width: 200,
						bottomCalc:"avg",
						bottomCalcFormatter: function(cell) {
							const num = cell.getValue();
							return isNaN(num) ? "–" : num + "%";
						},
						tooltip: (e, cell) => (cell.getValue() < 30) ? "Sehr geringe Rücklaufquote" : "",
					},
					{
						title:'LV-Evaluation',
						formatter:() => '<button class="btn btn-outline-secondary"><i class="fa-solid fa-square-poll-horizontal me-2"></i>LV-Evaluation</button>',
						cellClick: (e, cell) => self.openEvaluationByLveLv(cell.getRow().getData().lvevaluierung_lehrveranstaltung_id),
						hozAlign:"center",
						headerSort:false,
						width: 140
					},
					{
						title:'LV-Weiterentwicklung (OP)',
						formatter:() => '<a href="#" target="_blank" role="button" class="btn btn-outline-secondary me-2"><i class="fa-solid fa-external-link me-2"></i>LV-Weiterentwicklung</a>',
						hozAlign:"center",
						headerSort:false,
						width: 220
					},
					{
						title:'Reviewed',
						field:'reviewed_stg',
						formatter:"tickCross",
						headerFilter: "list",
						headerFilterParams: {
							values: [
								{ value: "", label: "Alle" },
								{ value: true, label: "erledigt" },
								{ value: false, label: "nicht erledigt" }
							],
							clearable: true
						},
						hozAlign:"center",
						formatterParams: {
							tickElement: '<i class="fa fa-check text-success"></i>',
							crossElement: '<i class="fa fa-xmark text-danger"></i>'
						},
						editor: 'list',
						editorParams: {
							values: [
								{ value: true, label: "erledigt" },
								{ value: false, label: "nicht erledigt" }
							],
						},
						tooltip: (e, cell) => cell.getValue() ? "erledigt" : "nicht erledigt",
						width:120
					},
				]
			}
		}
	},
	methods: {
		openEvaluationByLveLv(lvevaluierung_lehrveranstaltung_id){
			const url = this.$api.getUri() +
					'extensions/FHC-Core-Evaluierung/evaluation/Evaluation/' +
					'?lvevaluierung_lehrveranstaltung_id=' + lvevaluierung_lehrveranstaltung_id;

			window.open(url, '_blank');
		},
		updateVerpflichtend(cell)
		{
			this.$api
				.call(ApiEvaluation.updateVerpflichtend(cell.getData().lvevaluierung_lehrveranstaltung_id, cell.getValue()))
				.then(result => {
					if (result.data) {
						this.$fhcAlert.alertSuccess(this.$p.t('ui', 'gespeichert'));
					}
				})
				.catch(error => this.$fhcAlert.handleSystemError(error));
		},
		updateReviewedLvInStg(cell)
		{
			this.$api
				.call(ApiEvaluation.updateReviewedLvInStg(cell.getData().lvevaluierung_lehrveranstaltung_id, cell.getValue()))
				.then(result => {
					if (result.data) {
						this.$fhcAlert.alertSuccess(this.$p.t('ui', 'gespeichert'));
					}
				})
				.catch(error => this.$fhcAlert.handleSystemError(error));
		},
		sendInfomail(){
			this.$fhcAlert
				.confirm({
					header: 'Bitte bestätigen Sie:',
					message:`--TestText von IT--Ich habe alle LV-Evaluierungen des Studiengangs ${this.selStgFullName} geprüft. Notwendige Maßnahmen für die STG-Weiterentwicklung wurden abgeleitet. Mit Klick auf "OK" wird die nächste LVE-KVP Instanz per mail zum weiteren Review informiert.`
				})
				.then()
		},
		onTableBuilt() {
			this.table = this.$refs.stgTable.tabulator;
		},
		onCellEdited(cell) {
			switch (cell.getField()){
				case 'verpflichtend':
					this.updateVerpflichtend(cell);
					break;
				case 'reviewed_stg':
					this.updateReviewedLvInStg(cell);
					break;
				default:
					break;
			}
		}
	},
	template: `
	<div class="evaluation-studiengaenge container-fluid overflow-hidden">
		<h1 class="mb-5">MALVE Übersicht<small class="fs-5 fw-normal text-muted"> | LV-Evaluationen & Auswertungen einsehen</small></h1>
	 	<div class="row align-items-center mb-3">
	 		<h4>{{ selStgFullName }}</h4>
			<div class="col-md-12">
				<div class="d-flex justify-content-end align-items-center">
					<div class="me-2">
						<form-input
							type="select"
							v-model="selStudiensemester"
							name="studiensemester_kurzbz"
							:label="$p.t('lehre/studiensemester')">
							<option 
								v-for="studSem in lists.studiensemester"
								:key="studSem.studiensemester_kurzbz" 
								:value="studSem.studiensemester_kurzbz">
								{{ studSem.studiensemester_kurzbz }}
							</option>
						</form-input>
					</div>
					<div>
						<form-input
							type="select"
							v-model="selStgKz"
							name="studiengang_kz"
							:label="$p.t('lehre/studiengang')"
							>
							<option v-for="stg in lists.stgs" :key="stg.studiengang_kz" :value="stg.studiengang_kz">
								{{ stg.kuerzel }} {{ stg.bezeichnung }}
							</option>
						</form-input>
					</div><!--.div right buttons -->
				</div><!--.d-flex-->
			</div><!--.col -->
	  	</div>
	  	<div class="evaluation-studiengaenge-table">
			<core-filter-cmpt
				ref="stgTable"
				uniqueId="tabStudiengaenge"
				table-only
				:side-menu="false"
				:tabulator-options="tabulatorOptions"
				:tabulator-events="[
					{event: 'tableBuilt', handler: onTableBuilt},
					{event: 'cellEdited', handler: onCellEdited},
				]">
				<template v-slot:actions>
					<button class="btn btn-primary" @click="sendInfomail"><i class="fa fa-envelope me-2"></i>Info an QM & Rektorat</button>
					<a type="button" class="btn btn-outline-secondary" href="#" target="_blank"><i class="fa fa-external-link me-2"></i>STG-Weiterentwicklung</a>
				</template>
			</core-filter-cmpt>
		</div>
	</div>
	`
};