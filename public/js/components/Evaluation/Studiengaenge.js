import {CoreFilterCmpt} from '../../../../../js/components/filter/Filter.js';

export default {
	components: {
		CoreFilterCmpt
	},
	data() {
		return {
			table: null,
			data: [
				{ lvevaluierung_lehrveranstaltung_id: 1677, studiengang_kz: 227, lvBezeichnung: 'Lehrveranstaltung 1', orgform_kurzbz: 'VZ', semester: 1, verpflichtend: true, lv_aufgeteilt: false, rlQuote: 87.32, submitted: 0, codes_ausgegeben: 0, reviewed: false, },
				{ lvevaluierung_lehrveranstaltung_id: 1677, studiengang_kz: 227, lvBezeichnung: 'Lehrveranstaltung 2', orgform_kurzbz: 'VZ', semester: 1, verpflichtend: true, lv_aufgeteilt: false, rlQuote: 43.67, submitted: 0, codes_ausgegeben: 0 },
				{ lvevaluierung_lehrveranstaltung_id: 1677, studiengang_kz: 227, lvBezeichnung: 'Lehrveranstaltung 3', orgform_kurzbz: 'VZ', semester: 1, verpflichtend: true, lv_aufgeteilt: false, rlQuote: 92.11, submitted: 0, codes_ausgegeben: 0 },
				{ lvevaluierung_lehrveranstaltung_id: 1677, studiengang_kz: 227, lvBezeichnung: 'Lehrveranstaltung 4', orgform_kurzbz: 'VZ', semester: 1, verpflichtend: true, lv_aufgeteilt: false, rlQuote: 37.94, submitted: 0, codes_ausgegeben: 0 },
				{ lvevaluierung_lehrveranstaltung_id: 1677, studiengang_kz: 227, lvBezeichnung: 'Lehrveranstaltung 5', orgform_kurzbz: 'VZ', semester: 1, verpflichtend: true, lv_aufgeteilt: true, rlQuote: 58.26, submitted: 0, codes_ausgegeben: 0 },
				{ lvevaluierung_lehrveranstaltung_id: 1677, studiengang_kz: 227, lvBezeichnung: 'Lehrveranstaltung 6', orgform_kurzbz: 'VZ', semester: 1, verpflichtend: true, lv_aufgeteilt: false, rlQuote: 14.23, submitted: 0, codes_ausgegeben: 0 },
				{ lvevaluierung_lehrveranstaltung_id: 1677, studiengang_kz: 227, lvBezeichnung: 'Lehrveranstaltung 7', orgform_kurzbz: 'VZ', semester: 1, verpflichtend: true, lv_aufgeteilt: false, rlQuote: 66.12, submitted: 0, codes_ausgegeben: 0 },
				{ lvevaluierung_lehrveranstaltung_id: 1677, studiengang_kz: 227, lvBezeichnung: 'Lehrveranstaltung 8', orgform_kurzbz: 'VZ', semester: 1, verpflichtend: true, lv_aufgeteilt: false, rlQuote: 25.39, submitted: 0, codes_ausgegeben: 0 },
				{ lvevaluierung_lehrveranstaltung_id: 1677, studiengang_kz: 227, lvBezeichnung: 'Lehrveranstaltung 9', orgform_kurzbz: 'VZ', semester: 1, verpflichtend: true, lv_aufgeteilt: true, rlQuote: 73.54, submitted: 0, codes_ausgegeben: 0 },
				{ lvevaluierung_lehrveranstaltung_id: 1677, studiengang_kz: 227, lvBezeichnung: 'Lehrveranstaltung 10', orgform_kurzbz: 'VZ', semester: 1, verpflichtend: true, lv_aufgeteilt: false, rlQuote: 31.77, submitted: 0, codes_ausgegeben: 0 },
			]
		}
	},
	computed: {
		tabulatorOptions() {
			const self = this;
			return {
				layout: 'fitColumns',
				autoResize: true,
				resizableColumnFit: true,
				selectable: false,
				index: 'lvevaluierung_lehrveranstaltung_id',
				columns: [
					{
						title:'LV-Bezeichnung',
						field:'lvBezeichnung',
						headerFilter:"input",
						bottomCalc:"count",
						bottomCalcFormatter:"plaintext",
						widthGrow: 4
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
						tooltip: (cell) => {
							return cell.getValue() ? "verpflichtend" : "nicht verpflichtend"
						},
						minWidth: 100

					},
					{
						title:'Evaluationseinheit',
						field:'lv_aufgeteilt',
						formatter: (cell) => {
							return cell.getValue()
								? '<i class="fa-solid fa-expand text-dark" title="Gruppenbasis"></i>'
								: '<i class="fa-solid fa-square-full text-dark" title="Gesamt-LV"></i>';
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
						headerFilterFunc: (headerValue, rowValue) => {
							if (headerValue === "" || headerValue === undefined) return true;
							return Number(rowValue) === Number(headerValue);
						},
						hozAlign:"center",
						tooltip: (cell) => {
							return cell.getValue() ? "Gruppenbasis" : "Gesamt-LV"
						},
						minWidth: 120
					},
					{
						title: "Rücklauf",
						field: "ruecklauf",
						headerFilter:"input",
						mutator: function(value, data){
							return `${data.submitted}/${data.codes_ausgegeben}`;
						},
						hozAlign: "right",
						minWidth: 100
					},
					{
						title:'RL-Quote',
						field:'rlQuote',
						headerFilter:"input",
						hozAlign:"left",
						formatter:"progress",
						formatterParams: {
							min: 0,
							max: 100,
							legend: (value) => {
								return value !== null && value !== undefined ? value + "%" : "";
							},
							legendAlign: "right"
						},
						sorter: "number",
						width: 200,
						bottomCalc:"avg",
						bottomCalcFormatter: function(cell) {
							const raw = cell.getValue();
							const num = parseFloat(raw);
							return isNaN(num) ? "–" : num.toFixed(2) + "%";
						}
					},
					{
						title:'LV-Evaluation',
						formatter:() => '<button class="btn btn-outline-secondary"><i class="fa-solid fa-square-poll-horizontal me-2"></i>LV-Evaluation</button>',
						cellClick: () => self.openEvaluationByLveLv(1617), // todo remove test 1617
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
						field:'reviewed',
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
						tooltip: (cell) => {
							return cell.getValue() ? "erledigt" : "nicht erledigt"
						},
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
		async onTableBuilt(){
			this.table = this.$refs.stgTable.tabulator;
			this.table.setData(this.data); // todo change
		}
	},
	template: `
	<div class="evaluation-studiengaenge container-fluid overflow-hidden">
		<h1 class="mb-5">MALVE Übersicht<small class="fs-5 fw-normal text-muted"> | LV-Evaluationen & Auswertungen einsehen</small></h1>
	 	<div class="row align-items-center mb-3">
			<div class="col-md-12">
				<div class="d-flex justify-content-end align-items-center">
					<div>
						<select class="form-select d-inline w-auto me-2">
							<option>2025/26</option>
							<option>2024/25</option>
							<option>2023/24</option>
						</select>
						<select class="form-select d-inline w-auto me-2">
							<option>BIF</option>
							<option>BBE</option>
							<option>BEL</option>
						</select>
						<select class="form-select d-inline w-auto me-2">
							<option>VZ</option>
							<option>BB</option>
							<option>DUA</option>
						</select>
						<select class="form-select d-inline w-auto me-2">
							<option>1</option>
							<option>2</option>
							<option>3</option>
						</select>
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
				:tabulator-events="[{event: 'tableBuilt', handler: onTableBuilt}]">
				<template v-slot:actions>
					<button class="btn btn-primary"><i class="fa fa-envelope me-2"></i>Info an QM & Rektorat</button>
					<a type="button" class="btn btn-outline-secondary" href="+" target="_blank"><i class="fa fa-external-link me-2"></i>STG-Weiterentwicklung</a>
				</template>
			</core-filter-cmpt>
		</div>
	</div>
	`
};