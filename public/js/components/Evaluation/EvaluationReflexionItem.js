import FhcForm from "../../../../../js/components/Form/Form.js";
import FormInput from "../../../../../js/components/Form/Input.js";
import FormValidation from "../../../../../js/components/Form/Validation.js";
import ApiEvaluation from "../../api/evaluation";

export default {
	name: "EvaluationReflexionItem",
	components: {
		FhcForm,
		FormInput,
		FormValidation,
	},
	props:  {
		lvevaluierung_id: {
			type: [String, Number],
			default: null
		}
	},
	data() {
		return {
			reflexion:  {
				reflexion: {
					lvevaluierung_reflexion_id: null,
					praesenz_kurzbz: null,
					nachvollziehbar_kurzbz: null,
					anmerkung_nachvollziehbarkeit: null,
					massnahmennoetig: null
				},
				isBearbeitungOffen: false,
				sperreGrund: [],
				zeitfensterVon: null,
				zeitfensterBis: null,
				vorname: '',
				nachname: '',
				display: {
					showSaveButton: true,
					showEinmeldungButton: true
				}
			}
		}
	},
	created() {
		if (this.lvevaluierung_id) {
			this.$api
				.call(ApiEvaluation.getReflexionDataByLve(this.lvevaluierung_id))
				.then(result => {
					if (result?.data) {

						this.reflexion = result.data;

						if (!this.reflexion.reflexion) {
							this.reflexion.reflexion = {
								lvevaluierung_reflexion_id: null,
								lvevaluierung_id: null,
								praesenz_kurzbz: null,
								nachvollziehbar_kurzbz: null,
								anmerkung_nachvollziehbarkeit: null,
								massnahmennoetig: null
							}
						}
					}
				})
				.catch(error => this.$fhcAlert.handleSystemError(error));
		}
	},
	computed: {
		isDisabledSubmitBtn() {
			// Pflichtfelder
			return this.reflexion.reflexion.praesenz_kurzbz === null || this.reflexion.reflexion.nachvollziehbar_kurzbz === null;
		},
		label() {
			const english = this.$p.user_language.value === 'English';
			return {
				praesenz: {
					question: english
						? '1. Was the course evaluation conducted during an on-campus session?'
						: '1. Wurde die LV-Evaluierung in Präsenz durchgeführt?',
					ja: english ? 'Yes' : 'Ja',
					nein: english ? 'No' : 'Nein',
					unknown: english ? 'I don’t know' : 'Ich weiß nicht'
				},
				nachvollziehbar: {
					question: english
						? '2. Are the results comprehensible?'
						: '2. Sind die Ergebnisse nachvollziehbar?',
					ja: english
						? 'Yes, mostly comprehensible'
						: 'Ja, überwiegend nachvollziehbar',
					nein: english
						? 'No, hardly comprehensible'
						: 'Nein, wenig nachvollziehbar',
					unknown: english
						? 'Cannot assess (e.g. insufficient N)'
						: 'Kann ich nicht beurteilen (z.B. weil nicht genügend N)'
				},
				anmerkung: {
					question: english
						? 'Remarks regarding the comprehensibility:'
						: 'Anmerkungen zur Nachvollziehbarkeit:'
				},
				massnahmen: {
					question: english
						? '3. Do any measures need to be taken as a result (nevertheless)?'
						: '3. Ergeben sich daraus (trotzdem) Maßnahmen?',
					ja: english ? 'Yes' : 'Ja',
					nein: english ? 'No' : 'Nein'
				}
			};
		}
	},
	methods: {
		saveReflexion(){
	 		if (this.$refs.reflexionForm){
	 			const reflexion = {...this.reflexion.reflexion};

	 			// this.$api
	 			// 	.call(ApiEvaluation.saveOrUpdateReflexion(this.lvevaluierung_id, reflexion))
	 			// 	.then(() => this.$fhcAlert.alertSuccess(this.$p.t('ui', 'gespeichert')))
	 			// 	.catch(this.$fhcAlert.handleSystemError);
			}
		}
	},
	template: `
	<fhc-form v-if="reflexion" ref="reflexionForm" @submit.prevent="saveReflexion()">
		<form-validation></form-validation>
		<fieldset :disabled="!reflexion.isBearbeitungOffen">
		<div class="card">
			<!-- Daten für Lehrende -->
			<div class="card-body">
				<table class="table table-sm mb-4">
					<tbody>
						<tr>
							<th>Lehrende*r</th>
							<td class="fw-bold">{{ reflexion.vorname + ' ' + reflexion.nachname }}</td>
						</tr>
						<tr>
							<th>Bearbeitung Zeitfenster</th>
							<td>
								{{ reflexion.zeitfensterVon }} - {{ reflexion.zeitfensterBis }}
							</td>
						</tr>
						<tr>
							<th>Bearbeitung verpflichtend</th>
							<td>{{ reflexion.verpflichtend ? 'Ja' : 'Nein'}}</td>
						</tr>
						<tr v-if="reflexion.isBearbeitungOffen === false && reflexion.sperreGrund.length > 0">
							<th>Bearbeitung möglich</th>
							<td>{{ reflexion.isBearbeitungOffen ? 'Ja' : 'Nein' }}</td>
						</tr>
		
						<tr v-if="reflexion.isBearbeitungOffen === false" v-for="(grund, index) in reflexion.sperreGrund" :key="'grund-'+index">
							<th v-if="index === 0">Sperre Grund</th>
							<th v-else></th>
							<td>{{ grund }}</td>
						</tr>
					</tbody>
				</table>
			</div><!--.end card-body-->
			<!-- Formular -->
			<div class="card-body d-flex flex-column gap-4">
				<div>
					<div class="fw-bold mb-2">{{label.praesenz.question}} *</div>
					<form-input
						type="radio"
						v-model="reflexion.reflexion.praesenz_kurzbz"
						name="praesenz_kurzbz"
						:label="label.praesenz.ja"
						value="ja"
						>
					</form-input>
					<form-input
						type="radio"
						v-model="reflexion.reflexion.praesenz_kurzbz"
						name="praesenz_kurzbz"
						:label="label.praesenz.nein"
						value="nein"
						>
					</form-input>
					<form-input
						type="radio"
						v-model="reflexion.reflexion.praesenz_kurzbz"
						name="praesenz_kurzbz"
						:label="label.praesenz.unknown"
						value="unknown"
						>
					</form-input>
				</div>
				<div>
					<div class="fw-bold mb-2">{{label.nachvollziehbar.question}} *</div>
					<form-input
						type="radio"
						v-model="reflexion.reflexion.nachvollziehbar_kurzbz"
						name="nachvollziehbar_kurzbz"
						:label="label.nachvollziehbar.ja"
						value="ja"
						>
					</form-input>
					<form-input
						type="radio"
						v-model="reflexion.reflexion.nachvollziehbar_kurzbz"
						name="nachvollziehbar_kurzbz"
						:label="label.nachvollziehbar.nein"
						value="nein"
						>
					</form-input>
					<form-input
						type="radio"
						v-model="reflexion.reflexion.nachvollziehbar_kurzbz"
						name="nachvollziehbar_kurzbz"
						:label="label.nachvollziehbar.unknown"
						value="unknown"
						>
					</form-input>
				</div>
				<div>
					<form-input
						type="textarea"
						v-model="reflexion.reflexion.anmerkung_nachvollziehbarkeit"
						name="anmerkung_nachvollziehbarkeit"
						:label="label.anmerkung.question"
						rows="4"
						>
					</form-input>
				</div>
				<div>
					<div class="fw-bold mb-2">{{label.massnahmen.question}}</div>
					<form-input
						type="radio"
						v-model="reflexion.reflexion.massnahmennoetig"
						name="massnahmennoetig"
						:label="label.massnahmen.ja"
						value="true"
						>
					</form-input>
					<form-input
						type="radio"
						v-model="reflexion.reflexion.massnahmennoetig"
						name="massnahmennoetig"
						:label="label.massnahmen.nein"
						value="false"
						>
					</form-input>
				</div>
				<div class="col d-flex justify-content-end">
					<button 
						type="submit" 
						class="btn btn-primary me-2" 
						:disabled="isDisabledSubmitBtn"
						v-if="reflexion.display.showSaveButton"
					>
						Speichern
					</button>
				</div>
			</div><!--.end card-body-->
			<!-- Einmeldung -->
			<div 
				v-if="reflexion.display.showEinmeldungButton" 
				class="card-body border-top py-4 d-flex flex-column gap-3">
				<div class="fw-bold">Einmeldung LV-Weiterentwicklung</div>
				<button 
					type="submit" 
					class="btn btn-primary me-2 w-100" 
					:disabled="isDisabledSubmitBtn"
				>
					Neue Einmeldung
				</button>
				<div class="form-text">
					<i class="fa fa-info-circle text-primary me-2"></i>Maßnahmenableitungen aus der LV-Evaluierung direkt hier eintragen!
				</div>
			</div><!--.end card-body-->
		</div><!--.end card-->
		</fieldset>
	</fhc-form>
	`
}