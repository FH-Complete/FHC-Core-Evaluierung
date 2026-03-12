import EvaluationReflexionItem from "./EvaluationReflexionItem.js";
import ApiEvaluation from "../../api/evaluation";

export default {
	name: "EvaluationReflexion",
	components: {
		EvaluationReflexionItem,
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
			lvevaluierungIds: [],
		}
	},
	created() {
		if (this.lvevaluierung_id) {
			this.lvevaluierungIds.push(this.lvevaluierung_id);
		}

		if (this.lvevaluierung_lehrveranstaltung_id) {
			// todo
		}
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
			<h4 class="mt-5 mb-3">LV-Reflexionen der Lehrenden</h4>
			<div v-if="lvevaluierungIds.length > 0" class="row py-4 mb-3 gy-3 bg-light">
				<div 
					v-for="lvevaluierungId in lvevaluierungIds"
					:key="lvevaluierungId"
					class="col-lg-6 col-xl-4">
						<evaluation-reflexion-item :lvevaluierung_id="lvevaluierungId"></evaluation-reflexion-item>
				</div>
			</div>
			<div v-else class="card"><div class="card-body">Keine Daten vorhanden.</div></div>
		</div>
	</div>	
	`
}