import ApiStudiensemester from '../../../../../js/api/factory/studiensemester.js';
import ApiExport from '../../api/export.js';
import FormForm from "../../../../../js/components/Form/Form.js";
import FormInput from "../../../../../js/components/Form/Input.js";

export default {
	name: "ExportComponent",
	components: {
		FormForm,
		FormInput
	},
	data() {
		return {
			allSem: null,
			curSem: null,
			studiensemesterOptions: null,
			filterStartDate: null,
			filterEndDate: null,
			rowsToBeFetched: null
		}
	},
	provide() {
		return {
			
		}
	},
	created() {
		this.$api.call(ApiExport.getExportRowCount(null, null, null)).then(res => {
			this.rowsToBeFetched = res.data
		})
		this.$api.call(ApiStudiensemester.getAllStudiensemesterAndAktOrNext()).then(res => {
			this.allSem = res.data[0];
			const all = { studiensemester_kurzbz: 'Alle' };
			this.curSem = all;
			this.studiensemesterOptions = [all, ...this.allSem];
		})
	},
	computed: {
		
	},
	methods: {
		startDateChanged(val) {
			this.$api.call(ApiExport.getExportRowCount(this.curSem, val, this.filterEndDate)).then(res => {
				this.rowsToBeFetched = res.data
			})
		},
		endDateChanged(val) {
			this.$api.call(ApiExport.getExportRowCount(this.curSem, this.filterStartDate, val)).then(res => {
				this.rowsToBeFetched = res.data
			})
		},
		semesterChanged(e) {
			const sem = e.target.value
			const semOpt = this.studiensemesterOptions.find(opt => opt.studiensemester_kurzbz === sem)
			if(semOpt.start && semOpt.ende) {
				this.filterStartDate = semOpt.start
				this.filterEndDate = semOpt.ende
			}

			this.$api.call(ApiExport.getExportRowCount(sem, this.filterStartDate, this.filterEndDate)).then(res => {
				this.rowsToBeFetched = res.data
			})
		},
		exportAllCursor() {
			const sem = this.curSem != 'Alle' && this.curSem?.studiensemester_kurzbz != 'Alle' ? this.curSem : ''

			const params = new URLSearchParams();
			if (sem) params.set('studiensemester', sem);
			if (this.filterStartDate) params.set('von', this.filterStartDate);
			if (this.filterEndDate) params.set('bis', this.filterEndDate);
			const url = `/extensions/FHC-Core-Evaluierung/api/Export/exportAllToExcelCursor?${params.toString()}`;

			window.open(FHC_JS_DATA_STORAGE_OBJECT.app_root + FHC_JS_DATA_STORAGE_OBJECT.ci_router + url)

		}
	},
	template: `
	<div class="lve-initiierung-body container-fluid d-flex flex-column min-vh-100">
		<h1 class="mb-5">Export Component<small class="fs-5 fw-normal text-muted"></small></h1>
		<!-- Dropdowns -->
		<div class="row">
			<div class="col-sm-2 mb-3">
				<form-input
					type="select"
					v-model="curSem"
					name="studiensemester"
					:label="$p.t('lehre/studiensemester')"
					@change="semesterChanged">
					<option 
						v-for="studSem in studiensemesterOptions"
						:key="studSem.studiensemester_kurzbz" 
						:value="studSem.studiensemester_kurzbz">
						{{ studSem.studiensemester_kurzbz }}
					</option>
				</form-input>
			</div>
			<div class="col-sm-2 mb-3">
				<form-input 
					label="Startdatum" 
					type="datepicker"
					v-model="filterStartDate"
					name="filterStartDate"
					@update:model-value="startDateChanged"
					locale="de"
					text-input
					format="dd.MM.yyyy"
					model-type="yyyy-MM-dd"
					:auto-apply="true"
				>
				</form-input>
			</div>
			<div class="col-sm-2 mb-3">
				<form-input 
					label="Enddatum" 
					type="datepicker"
					v-model="filterEndDate"
					name="filterEndDate"
					@update:model-value="endDateChanged"
					locale="de"
					text-input
					format="dd.MM.yyyy"
					model-type="yyyy-MM-dd"
					:auto-apply="true"
				>
				</form-input>
			</div>
		</div>
		<div class="col-sm-2 mb-3">
			<button 
				class="btn btn-primary w-100 w-md-auto" 
				@click.prevent="exportAllCursor()"
			>
				Export
			</button>
			
		</div>
		<div class="col-sm-2 mb-3" v-if="rowsToBeFetched !== null">
			rowcount: {{ rowsToBeFetched }}
		</div>
	</div>
	`
}