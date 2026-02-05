import FormForm from "../../../../../js/components/Form/Form.js";
import FormInput from "../../../../../js/components/Form/Input.js";
import DateHelper from "../../helpers/DateHelper";
import ApiInitiierung from "../../api/initiierung";

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
		}
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
						lveLvDetail.insertamum = DateHelper.formatDate(result.data.insertamum);
						lveLvDetail.insertvon = result.data.insertvon;

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
						if (result.data !== null) {
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
			const lektor = lveLvDetail.lektoren.find(l => l.mitarbeiter_uid == lveLvDetail.insertvon);
			return `
				Gespeichert am ${DateHelper.formatDate(lveLvDetail.insertamum)} 
				von ${lektor ? `${lektor.vorname} ${lektor.nachname}` : lveLvDetail.insertvon}
			`;
		},
		openEvaluationByLve(lvevaluierung_id){
			const url = this.$api.getUri() +
					'extensions/FHC-Core-Evaluierung/evaluation/Evaluation/' +
					'?lvevaluierung_id=' + lvevaluierung_id;

			window.open(url, '_blank');
		}
	},
	template: `
		<div class="card mb-3" v-for="lveLvDetail in selLveLvDetails" :key="lveLvDetail.lehreinheit_id">
			<!-- Card title -->
			<div class="card-header d-flex justify-content-between align-items-center">
				<div>LV-Evaluierung</div>
				<div>
					<button 
						class="btn btn-outline-secondary"
						@click="openEvaluationByLve(lveLvDetail.lvevaluierung_id)"
					>
						<i class="fa fa-square-poll-horizontal me-2"></i>Ergebnisse LV-Evaluierung und LV-Reflexion
					</button>
				</div>
			</div><!--.end card-header -->
			<!-- Gruppen -->
			<div class="card-body pb-0">
				<span
					class="badge border border-secondary text-secondary me-2"
					:title="getLeGruppenInfoString(lveLvDetail)"
					v-tooltip="getLeGruppenInfoString(lveLvDetail)"
					data-bs-html="true"
					data-bs-custom-class="tooltip-left"
				>
					Gruppen<i class="fa-solid fa-arrow-pointer ms-2"></i>
				</span>

				<span
 						class="badge border border-secondary text-secondary me-2" 
						:title="getStudierendeString(lveLvDetail.studenten)"
 						v-tooltip="getStudierendeString(lveLvDetail.studenten)"
 						data-bs-html="true"
 						data-bs-custom-class="tooltip-left"
					>
						<span v-if="lveLvDetail.studenten && lveLvDetail.studenten.length > 0">
							{{ lveLvDetail.studenten.length }}
						</span>
						Studierende<i class="fa-solid fa-arrow-pointer ms-2"></i>
					</span>
					<span
 						class="badge border border-secondary text-secondary" 
						:title="getStundenplanterminString(lveLvDetail.stundenplan)"
 						v-tooltip="getStundenplanterminString(lveLvDetail.stundenplan)" 				
 						data-bs-html="true"
 						data-bs-custom-class="tooltip-left"
					>
						LV-Plan<i class="fa-solid fa-arrow-pointer ms-2"></i> 
					</span>
			</div><!--.end card-body -->
			<!-- Lehrende -->
			<div class="card-body border-bottom">
				<i class="d-lg-none fa fa-graduation-cap me-2"></i>
				<span class="d-none d-lg-inline me-2">{{ $p.t('lehre/lektorInnen') }}:</span>
				<span v-html="getLektorenInfoString(lveLvDetail.lektoren)"></span>
			</div><!--.end card body-->
			<!-- LV-Evaluierungen -->
			<div class="card-body pb-3 border-bottom">
				<fieldset :disabled="lveLvDetail.editableCheck.isDisabledEvaluierung" class="text-muted">
					<form-form @submit.prevent="saveOrUpdateLvevaluierung(lveLvDetail)">	
					<div class="row gx-5">
					<!-- Form Inputs + Button -->
					<div class="col-12 order-1">
						<div class="d-flex flex-wrap flex-md-nowrap gap-3">
							<div class="flex-grow-1 flex-md-grow-0">
								<form-input 
									label="Startdatum" 
									type="datepicker"
									v-model="lveLvDetail.startzeit"
									name="lveLvDetail.startzeit"
									locale="de"
									format="dd.MM.yyyy HH:mm"
									model-type="yyyy-MM-dd HH:mm:ss"
									:auto-apply="true"
								  	:disabled="lveLvDetail.editableCheck.isDisabledEvaluierung || isSendingMail"
  									:readonly-input="lveLvDetail.editableCheck.isDisabledEvaluierung"
  									:show-icon="!lveLvDetail.editableCheck.isDisabledEvaluierung"
								>
								</form-input>
							</div>
							<div class="flex-grow-1 flex-md-grow-0">
								<form-input 
									label="Enddatum" 
									type="datepicker"
									v-model="lveLvDetail.endezeit"
									name="lveLvDetail.endezeit"
									locale="de"
									format="dd.MM.yyyy HH:mm"
									model-type="yyyy-MM-dd HH:mm:ss"
									:auto-apply="true"
									:start-time="{hours: 0, minutes: 0}"
									:disabled="lveLvDetail.editableCheck.isDisabledEvaluierung || isSendingMail"
  									:readonly-input="lveLvDetail.editableCheck.isDisabledEvaluierung"
  									:show-icon="!lveLvDetail.editableCheck.isDisabledEvaluierung"
								>
								</form-input>
							</div>
							<div class="flex-grow-1 flex-md-grow-0 align-self-end">
								<button
									type="submit"  
									class="btn btn-primary w-100 w-md-auto"
								>
									Speichern
								</button>
							</div>
<!--							<div class="ms-auto text-muted d-flex gap-2 text-end align-items-baseline">	-->
<!--								<div v-if="lveLvDetail.insertamum" class="small">{{getSavedEvaluierungInfoString(lveLvDetail)}}</div>-->
<!--								<i -->
<!--									v-if="lveLvDetail.editableCheck.isDisabledEvaluierungInfo.length > 0"-->
<!--									class="fa fa-ban fa-lg text-muted" -->
<!--									:title="lveLvDetail.editableCheck.isDisabledEvaluierungInfo.join(', ')"-->
<!--									v-tooltip="lveLvDetail.editableCheck.isDisabledEvaluierungInfo.join(', ')"-->
<!--									data-bs-html="true"-->
<!--									data-bs-custom-class="tooltip-left">-->
<!--								</i>-->
<!--								&lt;!&ndash; span v-if="lveLvDetail.editableCheck.isDisabledEvaluierungInfo.length > 0">{{lveLvDetail.editableCheck.isDisabledEvaluierungInfo.join(', ')}}</span>&ndash;&gt;-->
<!--							</div>-->
						</div><!--.d-flex -->
					</div><!--.col -->
					</div><!--.row-->
				
					<div class="row">
						<div class="col-12 pt-3 form-text">
							<span v-if="lveLvDetail.insertamum">{{getSavedEvaluierungInfoString(lveLvDetail)}}</span>
							<span v-else-if="lveLvDetail.editableCheck.isDisabledEvaluierungInfo.length > 0">
								{{lveLvDetail.editableCheck.isDisabledEvaluierungInfo.join(', ')}}
							</span>
						</div>
					</div>
				</form-form><!--.end form -->
				</fieldset><!--.fieldset LV-Evaluierungen-->
			</div><!--.end card-body -->
			<!-- Codes versenden -->
			<div class="card-body mb-3" 
				v-if="lveLvDetail.lvevaluierung_id || lveLvDetail.sentByAnyEvaluierungOfLv.length > 0"
			>
				<fieldset :disabled="lveLvDetail.editableCheck.isDisabledSendMail">
				<div class="row gx-5">
					<div class="col-6 col-md-5">
						<span class="d-lg-none"><i class="fa fa-envelope"></i></span>
						<span class="d-none d-lg-inline">E-Mail Status:</span>
						<span v-if="isSendingMail"><i class="fa-solid fa-spinner fa-pulse ms-2"></i></span>
						<span 
							v-if="lveLvDetail.editableCheck.isDisabledSendMailInfo.length > 0" 
							class="ms-2">
							{{lveLvDetail.editableCheck.isDisabledSendMailInfo.join(', ')}}
						</span>
					</div>
					<div class="col-6 col-md-7 text-end">
						<span 
							v-if="lveLvDetail.sentByAnyEvaluierungOfLv.length > 0"
							class="mx-2 badge border border-secondary text-secondary"
							:title="lveLvDetail.sentByAnyEvaluierungOfLv.map(s => s.nachname + ' ' + s.vorname).join('<br>')"
							v-tooltip="lveLvDetail.sentByAnyEvaluierungOfLv.map(s => s.nachname + ' ' + s.vorname).join('<br>')"
							data-bs-html="true"
							data-bs-custom-class="tooltip-left"
						>
							{{lveLvDetail.codes_ausgegeben }} eingeladene Studierende
							<i class="fa-solid fa-arrow-pointer ms-1"></i> 
						</span>	
						<span class="ms-2">
							<i 
								class="fa fa-info-circle text-primary fa-lg" 
								:title="infoStudierendenlink"
								v-tooltip="infoStudierendenlink"
								data-bs-html="true"
								data-bs-custom-class="tooltip-left">
							</i>
						</span>							
					</div>
					<!-- Button -->
					<div class="col-12 text-end">
						<div class="d-grid d-md-block">
							<button class="btn btn-success mt-3" @click="onSendLinks(lveLvDetail)">
								Studierende zur LV-Evaluierung einladen
							</button>
						</div>
					</div>
				</div><!--.end row -->
				</fieldset>
			</div><!--.end card-footer -->
		</div><!--.end card-->
	`
}