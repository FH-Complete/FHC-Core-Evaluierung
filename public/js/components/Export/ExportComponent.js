import ApiExport from "../../api/export.js";

export default {
	name: "ExportComponent",
	components: {

	},
	data() {
		return {
		
		}
	},
	provide() {
		return {
			
		}
	},
	created() {
		
	},
	computed: {
		
	},
	methods: {
		exportAll() {
			// const url = `/api/frontend/v1/Abgabe/getStudentProjektarbeitAbgabeFile?paabgabe_id=${termin.paabgabe_id}&student_uid=${this.projektarbeit.student_uid}&projektarbeit_id=${this.projektarbeit.projektarbeit_id}`;
			
			const url = `/extensions/FHC-Core-Evaluierung/api/Export/exportAllToExcel`;

			window.open(FHC_JS_DATA_STORAGE_OBJECT.app_root + FHC_JS_DATA_STORAGE_OBJECT.ci_router + url)
			
			// this.$api.call(ApiExport.exportAllToExcel())
		}
	},
	template: `
	<div class="container-fluid d-flex flex-column vh-100 p-0">
		Export Component
		
		<button 
			class="btn btn-primary" 
			@click.prevent="exportAll()"
		>
			ExportBtn
		</button>
	</div>
	`
}