import EvaluationReflexionForm from "./EvaluationReflexionForm.js";
import ApiEvaluation from "../../api/evaluation";

export default {
	name: "EvaluationReflexion",
	components: {
		EvaluationReflexionForm,
	},
	props:  {
		lvevaluierung_id: {
			type: [String, Number],
			default: null
		},
		lvevaluierung_lehrveranstaltung_id: {
			type: [String, Number],
			default: null
		}
	},
	data() {
		return {
			reflexionen: []
		}
	},
	created() {
		const apiCall = this.lvevaluierung_id
				? ApiEvaluation.getReflexionDataByLve(this.lvevaluierung_id)
				: ApiEvaluation.getReflexionDataByLveLv(this.lvevaluierung_lehrveranstaltung_id);

		this.$api
			.call(apiCall)
			.then(result => {
				this.reflexionen = result.data;
			})
			.catch(error => this.$fhcAlert.handleSystemError(error));
	},
	computed: {

	},
	methods: {

	},
	template: `
	<div class="evaluation-evaluation-reflexion">
		<h3 class="mb-4">LV-Reflexion</h3>
		<!-- Abschnitt Reflexionen der Lehrenden -->
		<div class="evaluation-evaluation-reflexion-formulare mb-3">
			<h4 class="mt-5 mb-3">LV-Reflexionen</h4>
			<div v-if="reflexionen.length > 0" class="row py-4 mb-3 gy-3 bg-light">
				<div 
					v-for="(reflexion, index) in reflexionen"
					:key="reflexion.lvevaluierung_id + '-' + index"
					class="col-lg-6 col-xl-4"
				>
					<evaluation-reflexion-form :reflexion="reflexion"></evaluation-reflexion-form>
				</div>
			</div>
			<div v-else class="card"><div class="card-body py-5">Keine Daten vorhanden oder noch nicht zur Ansicht verfügbar.</div></div>
		</div>
	</div>	
	`
}