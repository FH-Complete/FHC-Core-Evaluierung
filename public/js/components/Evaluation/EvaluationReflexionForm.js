import FhcForm from "../../../../../js/components/Form/Form.js";
import FormInput from "../../../../../js/components/Form/Input.js";
import FormValidation from "../../../../../js/components/Form/Validation.js";
import ApiEvaluation from "../../api/evaluation";

export default {
	name: "EvaluationReflexionForm",
	components: {
		FhcForm,
		FormInput,
		FormValidation,
	},
	props:  {
		reflexion: {
			type: Object,
			required: true
		}
	},
	data() {
		return {
		}
	},
	created() {
		// If lveReflexion does not exist, set default formfields
		if (!this.reflexion.lveReflexion) {
			this.reflexion.lveReflexion = {
				lvevaluierung_reflexion_id: null,
				lvevaluierung_id: null,
				mitarbeiter_uid: null,
				praesenz_kurzbz: null,
				nachvollziehbar_kurzbz: null,
				anmerkung_nachvollziehbarkeit: null,
				massnahmennoetig: null,
				verpflichtend: null
			};
		}
	},
	computed: {
		isDisabledSubmitBtn() {
			// Pflichtfelder
			return this.reflexion.lveReflexion.praesenz_kurzbz === null || this.reflexion.lveReflexion.nachvollziehbar_kurzbz === null;
		},
		rollenbezeichnung(){
			return this.reflexion.isLvLeitung ? 'LV-Leitung' : 'Lehrende*r';
		},
		verpflichtendText() {
			if (this.reflexion.isVerpflichtend === true) return 'Ja'
			if (this.reflexion.isVerpflichtend === false) return 'Nein'
			return '';
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
	 			const reflexion = {...this.reflexion.lveReflexion};

	 			this.$api
	 				.call(ApiEvaluation.saveOrUpdateReflexion(
						this.reflexion.lvevaluierung_reflexion_id,
						this.reflexion.lvevaluierung_id,
						this.reflexion.mitarbeiter_uid,
						reflexion
					))
	 				.then(() => this.$fhcAlert.alertSuccess(this.$p.t('ui', 'gespeichert')))
	 				.catch(this.$fhcAlert.handleSystemError);
			}
		}
	},
	template: `
	<fhc-form v-if="reflexion" ref="reflexionForm" @submit.prevent="saveReflexion()">
		<form-validation></form-validation>
		<fieldset :disabled="!reflexion.isBearbeitungOffen">
			<div class="card">
				<!-- Daten für Lehrende -->
				<div class="card-body d-flex flex-column gap-3">
					<!-- Badge Reflexion verpflichtend/optional -->
					<div class="d-flex justify-content-end">
<!--						<span class="badge" :class="reflexion.isVerpflichtend ? 'bg-primary' : 'border border-1 border-secondary-subtle text-secondary'">-->
						<span class="badge border border-1 border-secondary-subtle text-secondary">
							{{ reflexion.isVerpflichtend ? 'Reflexion verpflichtend' : 'Reflexion optional' }}
						</span>
					</div>
					<!-- Tabelle Infos -->
					<table class="table table-sm mb-4">
						<tbody>
							<!-- Zeile Lehrender -->
							<tr>
								<th>{{ rollenbezeichnung }}</th>
								<td class="fw-bold">{{ reflexion.vorname + ' ' + reflexion.nachname }}</td>
							</tr>
							<!-- Zeile Bearbeitung Zeitfenster -->
							<tr>
								<th>Bearbeitung Zeitfenster</th>
								<td>
									{{ reflexion.zeitfensterVon }} - {{ reflexion.zeitfensterBis }}
								</td>
							</tr>
							<!-- Zeile Bearbeitung möglich -->
							<tr>
								<th>Bearbeitung möglich</th>
								<td>{{ reflexion.isBearbeitungOffen ? 'Ja' : 'Nein' }}</td>
							</tr>
							<!-- Zeile Sperre Grund -->
							<tr>
								<th>Sperre Grund</th>
								<td>
									<div v-if="reflexion.sperreGrund?.length === 0">-</div>
									<div
										v-for="(grund, index) in reflexion.sperreGrund"
										:key="'grund-'+index"
									>
										{{ grund }}
									</div>
								</td>
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
							v-model="reflexion.lveReflexion.praesenz_kurzbz"
							name="praesenz_kurzbz"
							:label="label.praesenz.ja"
							value="ja"
							>
						</form-input>
						<form-input
							type="radio"
							v-model="reflexion.lveReflexion.praesenz_kurzbz"
							name="praesenz_kurzbz"
							:label="label.praesenz.nein"
							value="nein"
							>
						</form-input>
						<form-input
							type="radio"
							v-model="reflexion.lveReflexion.praesenz_kurzbz"
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
							v-model="reflexion.lveReflexion.nachvollziehbar_kurzbz"
							name="nachvollziehbar_kurzbz"
							:label="label.nachvollziehbar.ja"
							value="ja"
							>
						</form-input>
						<form-input
							type="radio"
							v-model="reflexion.lveReflexion.nachvollziehbar_kurzbz"
							name="nachvollziehbar_kurzbz"
							:label="label.nachvollziehbar.nein"
							value="nein"
							>
						</form-input>
						<form-input
							type="radio"
							v-model="reflexion.lveReflexion.nachvollziehbar_kurzbz"
							name="nachvollziehbar_kurzbz"
							:label="label.nachvollziehbar.unknown"
							value="unknown"
							>
						</form-input>
					</div>
					<div>
						<form-input
							type="textarea"
							v-model="reflexion.lveReflexion.anmerkung_nachvollziehbarkeit"
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
							v-model="reflexion.lveReflexion.massnahmennoetig"
							name="massnahmennoetig"
							:label="label.massnahmen.ja"
							value="true"
							>
						</form-input>
						<form-input
							type="radio"
							v-model="reflexion.lveReflexion.massnahmennoetig"
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
	<!-- Wenn aufgrund fehlender Berechtigung keine Reflexion zurückgeliefert wurde-->
	<div v-else class="card">
		<div class="card-body mt-3 mb-5 text-center">
			<span class="text-muted">Nicht zur Anzeige dieser LV-Reflexion berechtigt</span>
		</div>
	</div><!--.end card-body-->
	`
}