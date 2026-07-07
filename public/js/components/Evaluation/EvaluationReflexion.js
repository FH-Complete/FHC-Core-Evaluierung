import EvaluationReflexionForm from "./EvaluationReflexionForm.js";
import EvaluationReflexionUebersicht from "./EvaluationReflexionUebersicht.js";
import ApiEvaluation from "../../api/evaluation";

export default {
	name: "EvaluationReflexion",
	components: {
		EvaluationReflexionForm,
		EvaluationReflexionUebersicht
	},
	inject: [
		'evalData'
	],
	props:  {
		lvevaluierung_id: {
			type: [String, Number],
			default: null
		},
		lvevaluierung_lehrveranstaltung_id: {
			type: [String, Number],
			default: null
		},
		lehrveranstaltung_template_id: {
			type: [String, Number],
			default: null
		},
		studiensemester: {
			type: String,
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
			reflexionenLveLvId: null, // for quellkurs lvs dropdown
		}
	},
	created() {
		let apiCall = null;

		if (this.lehrveranstaltung_template_id && this.studiensemester) {
			apiCall = ApiEvaluation.getReflexionDataByLvTemplate(this.lehrveranstaltung_template_id, this.studiensemester);
		} else if (this.lvevaluierung_lehrveranstaltung_id) {
			apiCall = ApiEvaluation.getReflexionDataByLveLv(this.lvevaluierung_lehrveranstaltung_id);
		} else if (this.lvevaluierung_id) {
			apiCall = ApiEvaluation.getReflexionDataByLve(this.lvevaluierung_id, this.role)
		}

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
		loadReflexionen(lvevaluierung_lehrveranstaltung_id) {
			this.reflexionenLveLvId = lvevaluierung_lehrveranstaltung_id;

			// Load Reflexionen
			this.$api
				.call(ApiEvaluation.getReflexionDataByLveLv(lvevaluierung_lehrveranstaltung_id))
				.then(result => this.reflexionen = result.data.reflexionen)
				.catch(error => this.$fhcAlert.handleSystemError(error));
		}
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
			<!-- Template view only -->
			<template class="mb-3" v-if="lehrveranstaltung_template_id && evalData.lveLvs">
				<div class="row my-3 mb-3 pt-2">
					<div class="col-12 col-md-auto">
						<select
							class="form-select"
							v-model="reflexionenLveLvId"
							@change="loadReflexionen(reflexionenLveLvId)"
						>
							<option :value="null">Lehrveranstaltung auswählen</option>
							<option
								v-for="lveLv in evalData.lveLvs"
								:key="lveLv.lvevaluierung_lehrveranstaltung_id"
								:value="lveLv.lvevaluierung_lehrveranstaltung_id"
							>
								{{ lveLv.kurzbzlang }}-{{ lveLv.semester }}: {{lveLv.bezeichnung}} - {{ lveLv.orgform_kurzbz }}
							</option>
						</select>
					</div>
				</div>
			</template>
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
				<div v-if="lehrveranstaltung_template_id && !reflexionenLveLvId">
					Lehrveranstaltung auswählen, um Daten zu laden.
				</div>
				<div v-else>Keine Daten verfügbar.</div>
			</div>
		</div>
	</div>	
	`
}