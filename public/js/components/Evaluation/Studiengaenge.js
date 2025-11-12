import {CoreFilterCmpt} from '../../../../../js/components/filter/Filter.js';

export default {
	components: {
		CoreFilterCmpt
	},
	data() {
		return {
			table: null,
			data: [
				{ lv: 'Lehrveranstaltung 1', verpflichtend: true, rl: 87.32, quote: 76.45, reviewed: true, },
				{ lv: 'Lehrveranstaltung 2', verpflichtend: false, rl: 43.67, quote: 55.89, reviewed: true },
				{ lv: 'Lehrveranstaltung 3', verpflichtend: true, rl: 92.11, quote: 89.02, reviewed: true },
				{ lv: 'Lehrveranstaltung 4', verpflichtend: false, rl: 37.94, quote: 68.77, reviewed: false },
				{ lv: 'Lehrveranstaltung 5', verpflichtend: true, rl: 58.26, quote: 81.45, reviewed: true },
				{ lv: 'Lehrveranstaltung 6', verpflichtend: false, rl: 14.23, quote: 27.68, reviewed: false },
				{ lv: 'Lehrveranstaltung 7', verpflichtend: true, rl: 66.12, quote: 44.09, reviewed: true },
				{ lv: 'Lehrveranstaltung 8', verpflichtend: false, rl: 25.39, quote: 90.41, reviewed: true },
				{ lv: 'Lehrveranstaltung 9', verpflichtend: true, rl: 73.54, quote: 63.33, reviewed: false },
				{ lv: 'Lehrveranstaltung 10', verpflichtend: false, rl: 31.77, quote: 59.21, reviewed: true },
			]
		}
	},
	computed: {
		tabulatorOptions() {
			const self = this;
			return {
				layout: 'fitColumns',
				autoResize: false,
				resizableColumnFit: true,
				selectable: false,
				index: 'lvevaluierung_lehrveranstaltung_id',
				columns: [
					// Select-Checkbox
					{
						formatter: 'rowSelection',
						titleFormatter: 'rowSelection',
						titleFormatterParams: {
							rowRange: "active" // Only toggle the values of the active filtered rows
						},
						frozen: true,
						width: 70
					},
					{
						title:'LV-Bezeichnung',
						field:'lv',
						headerFilter:"input",
						widthGrow: 1
					},
					{
						title:'Verpflichtend',
						field:'verpflichtend',
						formatter:"tickCross",
						headerFilter:"tickCross",
						headerFilterParams:{"tristate": true},
						hozAlign:"center",
						formatterParams: {
							tickElement: '<i class="fa fa-check text-success"></i>',
							crossElement: '<i class="fa fa-xmark text-danger"></i>'
						},

					},
					{
						title:'RL-Quote',
						field:'rl',
						headerFilter:"input",
						hozAlign:"right",
						formatter:"money",
						formatterParams: {precision: 2, symbol: "%", symbolAfter: true},
					},
					{
						title:'LV-Evaluation',
						formatter:() => '<button class="btn btn-outline-secondary"><i class="fa-solid fa-square-poll-horizontal me-2"></i>LV-Evaluation</button>',
						cellClick: () => self.openEvaluation(),
						hozAlign:"center",
						headerSort:false,
						width: 130
					},
					{
						title:'LV-Weiterentwicklung (OP)',
						formatter:() => '<a href="#" target="_blank" role="button" class="btn btn-outline-secondary me-2"><i class="fa-solid fa-external-link me-2"></i>LV-Weiterentwicklungsprojekt</a>',
						hozAlign:"center",
						headerSort:false,
						width: 250
					},
					{
						title:'Reviewed',
						field:'reviewed',
						formatter:"tickCross",
						headerFilter:"tickCross",
						headerFilterParams:{"tristate": true},
						hozAlign:"center",
						formatterParams: {
							tickElement: '<i class="fa fa-check text-success"></i>',
							crossElement: '<i class="fa fa-xmark text-danger"></i>'
						},
					},
				]
			}
		}
	},
	methods: {
		openEvaluation(){
			window.open(this.$api.getUri() + 'extensions/FHC-Core-Evaluierung/evaluation/Evaluation/', '_blank');
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
				<div class="d-flex justify-content-between align-items-center">
					<div>
						<button class="btn btn-primary">Einmeldung STG-Weiterentwicklung</button>
					</div>
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
  		<core-filter-cmpt
			ref="stgTable"
			uniqueId="tabStudiengaenge"
			table-only
			:side-menu="false"
			:tabulator-options="tabulatorOptions"
			:tabulator-events="[{event: 'tableBuilt', handler: onTableBuilt}]">
		</core-filter-cmpt>
	</div>
	`
};