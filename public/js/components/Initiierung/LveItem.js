import FormForm from "../../../../../js/components/Form/Form.js";
import FormInput from "../../../../../js/components/Form/Input.js";
import DateHelper from "../../helpers/DateHelper.js";
import ApiInitiierung from "../../api/initiierung.js";

export default {
	components: {
		FormForm,
		FormInput
	},
	emits: ["update-editable-checks"],
	data() {
		return {
			infoStudierendenlink: `
				Der Versand der E-Mail-Einladung zur LV-Evaluierung ist nur einmalig möglich. Jede*r Studierende*r erhält einen anonymen Zugangslink.
			`,
			isSendingMail: false
		}
	},
	props: {
		selLveLvId: {
			type: Number,
			required: true
		},
		selLveLvDetails: {
			type: Array,
			required: true
		},
		isPreview: {type: Boolean, default: false},
	},
	methods: {
		saveOrUpdateLvevaluierung(lveLvDetail){
			this.$api
				.call(ApiInitiierung.saveOrUpdateLvevaluierung({
					lvevaluierung_id: lveLvDetail.lvevaluierung_id,
					lvevaluierung_lehrveranstaltung_id: lveLvDetail.lvevaluierung_lehrveranstaltung_id,
					startzeit: lveLvDetail.startzeit,
					endezeit: lveLvDetail.endezeit,
					lehreinheit_id: lveLvDetail.lehreinheit_id
				}))
				.then(result => {
					if (result.data?.lvevaluierung_id) {
						lveLvDetail.lvevaluierung_id = result.data.lvevaluierung_id;
						lveLvDetail.insertamum = result.data.insertamum;
						lveLvDetail.insertvonFullName = result.data.insertvonFullName;
						lveLvDetail.updateamum = result.data.updateamum;
						lveLvDetail.updatevonFullName = result.data.updatevonFullName;

						this.$emit('update-editable-checks');

						this.$fhcAlert.alertSuccess(this.$p.t('ui/gespeichert'))
					}
				})
				.catch(error => this.$fhcAlert.handleSystemError(error));
		},
		onSendLinks(lveDetail) {
			if (this.isSendingMail) { return };

			let completed = 0;
			let isAllSent = null;

			// todo: delete after testing: Limit to max 2 students
			// const testStudents = lveDetail.studenten.slice(0, 1);
			// testStudents.forEach(student => {
			 lveDetail.studenten.forEach(student => {
				this.isSendingMail = true;
				this.$api
					.call(ApiInitiierung.generateCodesAndSendLinksToStudent(lveDetail.lvevaluierung_id))
					.then(result => {
						if (result?.data !== null) {
							// Update data
							lveDetail.codes_gemailt = result.data.codes_gemailt;
							lveDetail.codes_ausgegeben = result.data.codes_ausgegeben;
							lveDetail.sentByAnyEvaluierungOfLv = result.data.sentByAnyEvaluierungOfLv;
							lveDetail.editableCheck.isDisabledSendMailInfo = result.data.editableCheck.isDisabledSendMailInfo;
							isAllSent = result.data.isAllSent;
						}
					})
					.catch(error => this.$fhcAlert.handleSystemError(error))
					.finally(() => {
						completed++;
						// todo: delete after testing:
						// if (completed == testStudents.length) {
						if (completed == lveDetail.studenten.length) {
							this.$fhcAlert.alertSuccess('Erfolgreich gesendet!');
							this.$emit('update-editable-checks', isAllSent);
							this.isSendingMail = false;
						}
					})
			});
		},
		getLeGruppenInfoString(lveLvDetail) {
			let infoString = '';
			infoString = lveLvDetail.kurzbz + ' - ' + lveLvDetail.lehrform_kurzbz + ' - ';
			infoString+= lveLvDetail.gruppen.map(g => g.gruppe_bezeichnung).join('<br>');

			//infoString += ' | LE: ' + lveLvDetail.lehreinheit_id; // todo delete after testing

			return infoString;
		},
		getLektorenInfoString(lektoren) {
			return lektoren.map(l => l.vorname + ' ' + l.nachname).join(', ');
		},
		getStudierendeString(studenten) {
			return studenten.map(s => s.nachname + ' ' + s.vorname).join('<br>');
		},
		getStundenplanterminString(stundenplan) {
			return stundenplan.map(s => DateHelper.formatDate(s.datum)).join('<br>');
		},
		getSavedEvaluierungInfoString(lveLvDetail) {
			const isUpdate = !!lveLvDetail.updateamum;
			const date = isUpdate ? lveLvDetail.updateamum : lveLvDetail.insertamum;
			const person = isUpdate ? lveLvDetail.updatevonFullName : lveLvDetail.insertvonFullName;
			const verb = isUpdate ? 'Geändert' : 'Gespeichert';

			if (!date) return '';   // kein echtes Datum vorhanden -> nichts anzeigen

			return `${verb} am ${DateHelper.formatDate(date)} von ${person}`;
		},
		openEvaluationByLve(lvevaluierung_id){
			const url = this.$api.getUri() +
					'extensions/FHC-Core-Evaluierung/evaluation/Evaluation/lehre/' +
					'?lvevaluierung_id=' + lvevaluierung_id;

			window.open(url, '_blank');
		}
	},
	template: `
	<!-- Border um alle Evaluierungen, ggf. mit badge 'Voranzeige' -->
	<div class="mb-3 position-relative" :class="{'border border-primary rounded-3 bg-primary-subtle p-3': isPreview,  '': !isPreview}">
		<span class="badge bg-primary position-absolute top-0 start-0 translate-middle-y me-3" v-if="isPreview">Voranzeige</span>
		<!-- Loop Evaluierungen -->
		<div class="d-flex flex-wrap gap-3">
			<div class="card evaluation-card-flex align-self-start" v-for="lveLvDetail in selLveLvDetails" :key="lveLvDetail.lehreinheit_id">
				<div class="card-body pb-0 flex-grow-0">
					<!-- Gruppen badge -->
					<span
						class="badge bg-secondary-subtle text-secondary p-2 me-2"
						:title="getLeGruppenInfoString(lveLvDetail)"
						v-tooltip="getLeGruppenInfoString(lveLvDetail)"
						data-bs-html="true"
						data-bs-custom-class="tooltip-left"
					>
						Gruppen<i class="fa-solid fa-info-circle ms-2"></i>
					</span>
					<!-- Studierende badge -->
					<span
						class="badge bg-secondary-subtle text-secondary p-2 me-2" 
						:title="getStudierendeString(lveLvDetail.studenten)"
						v-tooltip="getStudierendeString(lveLvDetail.studenten)"
						data-bs-html="true"
						data-bs-custom-class="tooltip-left"
					>
						<span v-if="lveLvDetail.studenten && lveLvDetail.studenten.length > 0">
							{{ lveLvDetail.studenten.length }}
						</span>
						Studierende<i class="fa-solid fa-info-circle ms-2"></i>
					</span>
					<!-- LV-Plan badge -->
					<span
						class="badge bg-secondary-subtle text-secondary p-2" 
						:title="getStundenplanterminString(lveLvDetail.stundenplan)"
						v-tooltip="getStundenplanterminString(lveLvDetail.stundenplan)" 				
						data-bs-html="true"
						data-bs-custom-class="tooltip-left"
					>
						LV-Plan<i class="fa-solid fa-info-circle ms-2"></i> 
					</span>
				</div><!--.end card-body -->
				<!-- Lehrende -->
				<div class="card-body border-bottom flex-grow-0">
					<i class="d-lg-none fa fa-graduation-cap me-2"></i>
					<span class="d-none d-lg-inline me-2 fw-bold">{{ $p.t('lehre/lektorInnen') }}:</span>
					<span v-html="getLektorenInfoString(lveLvDetail.lektoren)"></span>
				</div><!--.end card body-->
				<!-- LV-Evaluierungen -->
				<div class="card-body pb-3">
					<!-- Zeitfenster -->
					<fieldset :disabled="lveLvDetail.editableCheck.isDisabledEvaluierung || isPreview" class="text-muted mb-3">
						<form-form @submit.prevent="saveOrUpdateLvevaluierung(lveLvDetail)">	
							<div class="evaluation-data-table-flex flex-column gap-2">
								<form-input 
									label="Startdatum" 
									type="datepicker"
									v-model="lveLvDetail.startzeit"
									name="lveLvDetail.startzeit"
									locale="de"
									text-input
									format="dd.MM.yyyy HH:mm"
									model-type="yyyy-MM-dd HH:mm:ss"
									:auto-apply="true"
									:disabled="lveLvDetail.editableCheck.isDisabledEvaluierung || isSendingMail || isPreview"
									:readonly-input="lveLvDetail.editableCheck.isDisabledEvaluierung || isPreview"
									:show-icon="!lveLvDetail.editableCheck.isDisabledEvaluierung && !isPreview"
									class="mb-3"	
								>
								</form-input>
								<form-input 
									label="Enddatum" 
									type="datepicker"
									v-model="lveLvDetail.endezeit"
									name="lveLvDetail.endezeit"
									locale="de"
									text-input
									format="dd.MM.yyyy HH:mm"
									model-type="yyyy-MM-dd HH:mm:ss"
									:auto-apply="true"
									:start-time="{hours: 0, minutes: 0}"
									:disabled="lveLvDetail.editableCheck.isDisabledEvaluierung || isSendingMail || isPreview"
									:readonly-input="lveLvDetail.editableCheck.isDisabledEvaluierung || isPreview"
									:show-icon="!lveLvDetail.editableCheck.isDisabledEvaluierung && !isPreview"
									class="mb-3"
								>
								</form-input>
								<button
									type="submit"  
									class="btn w-100 w-md-auto mb-2"
									:class="lveLvDetail.hasZeitfenster ? 'btn-outline-primary' : 'btn-primary'"
								>
								 	{{ lveLvDetail.hasZeitfenster ? 'Zeitfenster ändern' : 'Zeitfenster speichern' }}
								</button>
							</div>
							<div class="form-text mb-3">
								<span v-if="lveLvDetail.hasZeitfenster">
							<!--		<i class="fa fa-lg fa-circle-check text-success me-2"></i>-->
									{{getSavedEvaluierungInfoString(lveLvDetail)}}
								</span>
								<span v-else-if="lveLvDetail.editableCheck.isDisabledEvaluierungInfo.length > 0">
									{{lveLvDetail.editableCheck.isDisabledEvaluierungInfo.join(', ')}}
								</span>
							</div>
						</form-form><!--.end form -->
					</fieldset><!--.fieldset LV-Evaluierungen-->
					<!-- Codeversand -->
					<fieldset 
						v-if="lveLvDetail.editableCheck.isRenderedSendMail" 
						:disabled="lveLvDetail.editableCheck.isDisabledSendMail || isPreview"
					>
						<div class="d-flex align-items-center mb-2">
							<!-- Button -->
							<button class="btn btn-primary flex-grow-1" @click="onSendLinks(lveLvDetail)">
								Studierende zur LV-Evaluierung einladen
							</button>
							 <i
								class="fa fa-lg fa-info-circle text-primary ms-3 flex-shrink-0"
								:title="infoStudierendenlink"
								v-tooltip="infoStudierendenlink"
								data-bs-html="true"
								data-bs-custom-class="tooltip-left"
							 ></i>
						 </div>	
						<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
							<!-- Loading spinner -->
							<i class="fa-solid fa-spinner fa-pulse" v-if="isSendingMail"></i>
							<!-- Text -->
							 <template v-if="lveLvDetail.sentByAnyEvaluierungOfLv.length > 0">
						<!--	 	<i class="fa fa-lg fa-circle-check text-success me-2"></i>-->
								<span v-if="lveLvDetail.editableCheck.isDisabledSendMailInfo.length > 0"
									class="form-text ">
								{{lveLvDetail.editableCheck.isDisabledSendMailInfo.join(', ')}}
								</span>
								<span
								   class="badge bg-secondary-subtle text-secondary p-2 fw-bold"
								   :title="lveLvDetail.sentByAnyEvaluierungOfLv.map(s => s.nachname + ' ' + s.vorname).join('<br>')"
								   v-tooltip="lveLvDetail.sentByAnyEvaluierungOfLv.map(s => s.nachname + ' ' + s.vorname).join('<br>')"
								   data-bs-html="true"
								   data-bs-custom-class="tooltip-left"
								>
								  {{lveLvDetail.codes_ausgegeben}} Eingeladene <i class="fa-solid fa-info-circle ms-1"></i> 
				<!--				   <i class="fa-solid fa-info-circle ms-1"></i>-->
								</span>
							 </template>
						</div><!--.end d-flex -->
					</fieldset>
				</div><!--.end card-body -->
				<div class="card-footer bg-light" v-if="lveLvDetail.codes_ausgegeben">
				<!-- Ergebnisse -->
					<button 
						v-if="lveLvDetail.codes_ausgegeben"
						class="btn btn-primary w-100 w-md-auto mt-3 mb-2"
						:disabled="lveLvDetail.editableCheck.isDisabledBtnAuswertung"
						@click="openEvaluationByLve(lveLvDetail.lvevaluierung_id)"
					>
						<i class="fa fa-square-poll-horizontal me-2"></i>Ergebnisse LV-Evaluierung und LV-Reflexion
					</button>
					<div class="form-text mb-3" v-if="lveLvDetail.codes_ausgegeben && lveLvDetail.editableCheck.isDisabledBtnAuswertungInfo.length > 0">
					   {{lveLvDetail.editableCheck.isDisabledBtnAuswertungInfo.join(', ')}}
					</div>
		<!--			<div class="form-text" v-if="lveLvDetail.editableCheck.isDisabledBtnAuswertung === false">
						&lt;!&ndash;<i class="fa fa-lg fa-circle-check text-success me-2"></i> &ndash;&gt;Ergebnisse verfügbar
					</div>-->
</div>
			</div><!--.end card-->
		</div><!--.end d-flex -->
	</div><!--.end border-->
	`
}