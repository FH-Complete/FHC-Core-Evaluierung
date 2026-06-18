import EvaluationReflexionForm from "./EvaluationReflexionForm.js";
import EvaluationReflexionUebersicht from "./EvaluationReflexionUebersicht.js";
import ApiEvaluation from "../../api/evaluation";

export default {
	name: "EvaluationReflexion",
	components: {
		EvaluationReflexionForm,
		EvaluationReflexionUebersicht
	},
	props:  {
		lvevaluierung_id: {
			type: [String, Number],
			default: null
		},
		lvevaluierung_lehrveranstaltung_id: {
			type: [String, Number],
			default: null
		},
		evaluationView: {
			type: Object,
		},
		role: null
	},
	emits: ['changeView'],
	data() {
		return {
			reflexionen: [],
			reflexionenUebersicht: [],
		}
	},
	created() {
		const apiCall = this.lvevaluierung_id
				? ApiEvaluation.getReflexionDataByLve(this.lvevaluierung_id, this.role)
				: ApiEvaluation.getReflexionDataByLveLv(this.lvevaluierung_lehrveranstaltung_id);

		this.$api
			.call(apiCall)
			.then(result => {
				if (result.data)
				{
					this.reflexionen = result.data.reflexionen;
					this.reflexionenUebersicht = result.data.reflexionenUebersicht;
				}
			})
			.catch(error => this.$fhcAlert.handleSystemError(error));
	},
	methods: {
		changeView(view) {
			this.$emit('changeView', view);
		},
	},
	template: `
	<div class="evaluation-evaluation-reflexion">
		<h3 class="mb-4">LV-Reflexion</h3>
		<!-- Reflexion Übersicht-->
		<evaluation-reflexion-uebersicht 
			v-if="reflexionenUebersicht.show"
			:uebersicht-data = "reflexionenUebersicht.data"
		>
		</evaluation-reflexion-uebersicht>
		
		<!-- Reflexion Form -->
		<div class="evaluation-evaluation-reflexion-formulare mb-3">
			<h4 class="mt-5 mb-4">LV-Reflexionen</h4>
			<div v-if="evaluationView.open && reflexionen.length > 0" class="row py-4 mb-3 gy-3 bg-light">
				<div 
					v-for="(reflexion, index) in reflexionen"
					:key="reflexion.lvevaluierung_id + '-' + index"
					class="col-lg-6 col-xl-4"
				>
					<evaluation-reflexion-form 
						:reflexion="reflexion"
						@change-view="changeView"
					>
					</evaluation-reflexion-form>
				</div>
			</div>
			<div v-else class="border rounded p-5 mb-5 text-center text-secondary">
				<i class="fa fa-chart-column fa-3x mb-3"></i>
				<div>Keine Daten verfügbar.</div>
			</div>
		</div>
	</div>	
	`
}