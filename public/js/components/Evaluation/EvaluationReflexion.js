import FhcForm from "../../../../../js/components/Form/Form.js";
import FormInput from "../../../../../js/components/Form/Input.js";
import FormValidation from "../../../../../js/components/Form/Validation.js";
import ApiEvaluation from "../../api/evaluation";

export default {
	name: "EvaluationReflexion",
	components: {
		FhcForm,
		FormInput,
		FormValidation,
	},
	data() {
		return {
			formData: {
				id: 1, // todo adapt
				presenceOrSync: null,
				resultsClear: null,
				resultsClearTextAnswer: '',
			}
		}
	},
	created() {
		if (this.lvevaluierung_id || this.lvevaluierung_lehrveranstaltung_id) {
			const apiCallReflexionData = this.lvevaluierung_id
					? ApiEvaluation.getReflexionDataByLve(this.lvevaluierung_id)
					: ApiEvaluation.getReflexionDataByLveLv(this.lvevaluierung_lehrveranstaltung_id);

			this.$api
					.call(apiCallReflexionData)
					.then(result => {
						console.log(result.data);
					})
					.catch(error => this.$fhcAlert.handleSystemError(error));
		}
	},
	computed: {
		resultsClearTextAnswerLabel() {
			switch (this.formData.resultsClear) {
				case 'yes': return 'Welche Maßnahmen ergeben sich gegebenenfalls daraus?';
				case 'no': return 'Warum nicht und ergeben sich daraus (trotzdem) Maßnahmen?';
				case 'unknown': return 'Warum nicht und ergeben sich daraus (trotzdem) Maßnahmen?';
				default: return '';
			}
		},
		showResultsClearTextarea() {
			return this.formData.resultsClear !== '' && this.formData.resultsClear !== null;
		},
		showResultsClearHelpText() {
			return this.formData.resultsClear === 'yes' || this.formData.resultsClear === 'no' || this.formData.resultsClear === 'unknown';
		},
		isDisabledSubmitBtn() {
			return true;
			//return this.formData.presenceOrSync === null || this.formData.resultsClear === null;
		},
		isDisabledReflexion(){
			return true; // todo make conditionally
		}
	},
	methods: {
		saveReflexion(){
			console.log(this.formData);
	 		if (this.$refs.reflexionForm){
	 			const formData = {...this.formData};

	 			// todo
	 			// this.$api
	 			// 	.call()
	 			// 	.then(result => {
		 		// 		this.$fhcAlert.alertSuccess('Form Successful sent');
	 			// 	})
	 			// 	.catch(this.$fhcAlert.handleSystemError);
			}
		}
	},
	template: `
	<div class="evaluation-evaluation-reflexion">
		<h3 class="mb-4">LV-Reflexion</h3>
		<fhc-form ref="reflexionForm" @submit.prevent="saveReflexion">
			<form-validation></form-validation>
			<!--<fieldset :disabled="isDisabledReflexion">-->
			<fieldset>
			<div class="row mb-3">
				<div class="col-12 col-lg-6 mb-3">
					<div class="card">
						<div class="card-header">LV-Reflexion</div>
						<div class="card-body d-flex flex-column gap-4">
							<div>
								<div class="fw-bold mb-2">1. Wurde die LV-Evaluierung in Präsenz durchgeführt?</div>
								<form-input
									type="radio"
									v-model="formData.presenceOrSync"
									name="presenceOrSync"
									label="Ja"
									value="yes"
									>
								</form-input>
								<form-input
									type="radio"
									v-model="formData.presenceOrSync"
									name="presenceOrSync"
									label="Nein"
									value="no"
									>
								</form-input>
								<form-input
									type="radio"
									v-model="formData.presenceOrSync"
									name="presenceOrSync"
									label="Ich weiß nicht"
									value="unknown"
									>
								</form-input>
							</div>
							<div>
								<div class="fw-bold mb-2">2. Sind die Ergebnisse nachvollziehbar?</div>
								<form-input
									type="radio"
									v-model="formData.resultsClear"
									name="resultsClear"
									label="Ja, überwiegend nachvollziehbar"
									value="yes"
									>
								</form-input>
								<form-input
									type="radio"
									v-model="formData.resultsClear"
									name="resultsClear"
									label="Nein, wenig nachvollziehbar"
									value="no"
									>
								</form-input>
								<form-input
									type="radio"
									v-model="formData.resultsClear"
									name="resultsClear"
									label="Kann ich nicht beurteilen (z.B. weil nicht genügend N)"
									value="unknown"
									>
								</form-input>
							</div>
							<div>
								<form-input
									v-if="showResultsClearTextarea"
									type="textarea"
									v-model="formData.resultsClearTextAnswer"
									name="resultsClearTextAnswer"
									:label="resultsClearTextAnswerLabel"
									rows="4"
									>
								</form-input>
								<div v-if="showResultsClearHelpText" class="form-text">
									<i class="fa fa-info-circle text-primary me-2"></i>Falls zutreffend: bitte zusätzlich ins Einmelde-Tool zur LV-Weiterentwicklung eintragen.
								</div>
							</div>
							<div class="col d-flex justify-content-end">
								<button 
									type="submit" 
									class="btn btn-primary me-2" 
									:disabled="isDisabledSubmitBtn"
								>
									Speichern
								</button>
							</div>
						</div><!--.end card-body-->
					</div><!--.end card-->
				</div><!--.end col-->
			</div><!--.end row-->
			</fieldset>
		</fhc-form>
	</div>	
	`
}