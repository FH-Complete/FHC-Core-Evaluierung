import FormForm from "../../../../../js/components/Form/Form.js";
import FormInput from "../../../../../js/components/Form/Input.js";
import MdlSrcCourseWrapper from '../../../../FHC-Core-LVKVP/js/components/einmeldung/mdlSrcCourseWrapper.js';

export default {
	name: "EvaluationEinmeldung",
	components: {
		FormForm,
		FormInput,
		einmeldung: MdlSrcCourseWrapper
	},
	inject: [
		'evalData'
	],
	data() {
		return {

		}
	},
	props:  {
		evaluationView: {
			type: Object,
		}
	},
	computed: {
	},
	methods: {
	},
	template: `
	<div class="evaluation-evaluation-einmeldung">
		<h3 class="mb-4">Einmeldung LV Weiterentwicklung</h3>
		<div v-if="evaluationView.open" class="row mb-3">
			<div class="col-12 mb-3">
				<einmeldung
					:templateid="evalData.lehrveranstaltung_template_id"
					:sprache="evalData.sprache"
					source="lvevaluierung"
					:lvevalsem="evalData.studiensemester_kurzbz"
				>
				</einmeldung>			
			</div>
		</div>
		<div v-else class="card"><div class="card-body py-5">Keine Daten vorhanden oder nicht zur Ansicht verfügbar.</div></div>
	</div>	
	`
}