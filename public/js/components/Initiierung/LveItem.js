import FormForm from "../../../../../js/components/Form/Form.js";
import FormInput from "../../../../../js/components/Form/Input.js";
import DateHelper from "../../helpers/DateHelper";
import ApiInitiierung from "../../api/initiierung";

export default {
	components: {
		FormForm,
		FormInput
	},
	data() {
		return {
			infoStudierendenlink: `
				Der Versand des Studierendenlinks ist nur einmalig möglich. Jede*r Studierende erhält einen anonymen Zugangslink per Email zugesendet.
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
	updated(){
		// Init Bootstrap tooltips
		let tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
		let tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
			return new bootstrap.Tooltip(tooltipTriggerEl)
		})
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

			// Limit to max 2 students
			const testStudents = lveDetail.studenten.slice(0, 1); // todo remove after testing!!!
			testStudents.forEach(student => {
			//lveDetail.studenten.forEach(student => { // todo add after testing!!!
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
						//if (completed == lveDetail.studenten.length) {		// todo add after testing!!!
						if (completed == testStudents.length) {
							this.$fhcAlert.alertDefault('info', 'TEST Mailversand', 'Testversand: max 1 Mail pro Buttonclick', true); // todo remove after testing!!!
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
			infoString+= lveLvDetail.gruppen.map(g => g.gruppe_bezeichnung).join(', ');

			if (lveLvDetail.studenten && lveLvDetail.studenten.length > 0) {
				infoString += ` | <i class="fa-solid fa-user"></i> ${lveLvDetail.studenten.length}`;
			}

			//infoString += ' | LE: ' + lveLvDetail.lehreinheit_id; // todo delete after testing

			return infoString;
		},
		getLektorenInfoString(lektoren) {
			return lektoren.map(l => l.vorname + ' ' + l.nachname).join(', ');
		},
		getBadgeStudierende(lveLvDetail) {
			let badge = '';
			if (lveLvDetail.studenten && lveLvDetail.studenten.length > 0) {
				// Add Icon and Tooltip
				const tooltipStudierende = lveLvDetail.studenten.map(s => s.nachname + ' ' + s.vorname).join('<br>');
				badge = ` 
 					<span 
 						class="badge rounded-pill border border-secondary text-secondary"
 						title="${tooltipStudierende}" 
 						data-bs-toggle="tooltip"
 						data-bs-html="true"
 						data-bs-custom-class="tooltip-left"
					>
						<i class="fa-solid fa-users ms-1"></i> 
						Studierende
						<i class="fa-solid fa-eye ms-1"></i> 
					</span>
				`;
			}

			return badge;
		},
		getBadgeStundenplan(lveLvDetail) {
			let badge = '';
			if (lveLvDetail.stundenplan && lveLvDetail.stundenplan.length > 0) {
				const tooltipStudienplan = lveLvDetail.stundenplan.map(s => DateHelper.formatDate(s.datum)).join('<br>');
				badge = ` 
 					<span
 						class="badge rounded-pill border border-secondary text-secondary" 
 						title="${tooltipStudienplan}"
 						data-bs-toggle="tooltip"
 						data-bs-html="true"
 						data-bs-custom-class="tooltip-left"
					>
						<i class="fa-solid fa-list ms-1"></i> 
						Stundenplan
						<i class="fa-solid fa-eye ms-1"></i> 
					</span>
				`;
			}
			return badge;
		},
		getSavedEvaluierungInfoString(lveLvDetail) {
			const lektor = lveLvDetail.lektoren.find(l => l.mitarbeiter_uid == lveLvDetail.insertvon);
			return `
				Saved on ${DateHelper.formatDate(lveLvDetail.insertamum)} 
				by ${lektor ? `${lektor.vorname} ${lektor.nachname}` : lveLvDetail.insertvon}
			`;
		}
	},
	template: `
		<div class="card mb-3" v-for="lveLvDetail in selLveLvDetails" :key="lveLvDetail.lehreinheit_id">
			<!-- Card title -->
			<div class="card-header" 
				:class="{'fhc-bgc-blue text-light': !lveLvDetail.editableCheck.isDisabledEvaluierung}">
				LV-Evaluierung
			</div><!--.end card-header -->
			<!-- Gruppen -->
			<div class="card-body pb-0">
				<i class="fa fa-users me-2"></i>
				<span class="d-none d-md-inline me-2">Gruppen:</span>
				<span v-html="getLeGruppenInfoString(lveLvDetail)"></span>
				<span v-html="getBadgeStudierende(lveLvDetail)" class="d-none d-md-inline"></span>
				<span v-html="getBadgeStundenplan(lveLvDetail)" class="d-none d-md-inline"></span>
			</div><!--.end card-body -->
			<!-- Lehrende -->
			<div class="card-body border-bottom">
				<i class="fa fa-graduation-cap me-2"></i>
				<span class="d-none d-md-inline me-2">Lehrende:</span>
				<span v-html="getLektorenInfoString(lveLvDetail.lektoren)"></span>
			</div><!--.end card body-->
			<!-- LV-Evaluierungen -->
			<div class="card-body mb-3">
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
								>
								</form-input>
							</div>
							<div class="flex-grow-1 flex-md-grow-0">
								<form-input 
									label="Endedatum" 
									type="datepicker"
									v-model="lveLvDetail.endezeit"
									name="lveLvDetail.endezeit"
									locale="de"
									format="dd.MM.yyyy HH:mm"
									model-type="yyyy-MM-dd HH:mm:ss"
									:minutes-increment="5"
									:auto-apply="true"
									:start-time="{hours: 0, minutes: 0}"
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
							<div class="ms-md-auto text-muted">	
								<div v-if="lveLvDetail.insertamum" class="small">{{getSavedEvaluierungInfoString(lveLvDetail)}}</div>
								<div v-if="lveLvDetail.editableCheck.isDisabledEvaluierungInfo.length > 0">
									<i 
										class="fa fa-ban fa-lg text-muted" 
										:title="lveLvDetail.editableCheck.isDisabledEvaluierungInfo.join(', ')"
										data-bs-toggle="tooltip"
										data-bs-html="true"
										data-bs-custom-class="tooltip-left">
									</i>
								</div>
								<!-- span v-if="lveLvDetail.editableCheck.isDisabledEvaluierungInfo.length > 0">{{lveLvDetail.editableCheck.isDisabledEvaluierungInfo.join(', ')}}</span>-->
							</div>
						</div><!--.d-flex -->
					</div><!--.col -->
				</form-form><!--.end form -->
				</fieldset><!--.fieldset LV-Evaluierungen-->
			</div><!--.end card-body -->
			<!-- Codes versenden -->
			<div class="card-footer bg-white mb-3" 
				v-if="lveLvDetail.lvevaluierung_id || lveLvDetail.sentByAnyEvaluierungOfLv.length > 0"
			>
				<fieldset :disabled="lveLvDetail.editableCheck.isDisabledSendMail">
				<div class="row gx-5">
					<div class="col-5 col-md-4">
						<span class="d-md-none"><i class="fa fa-envelope me-2"></i>Email</span>
						<span class="d-none d-md-inline"><i class="fa fa-envelope me-2"></i>Email Status</span>
					</div>
					<div class="col-7 col-md-8 text-end">
						<span v-if="isSendingMail"><i class="fa-solid fa-spinner fa-pulse"></i></span>
						<span 
							v-if="lveLvDetail.editableCheck.isDisabledSendMailInfo.length > 0" 
							class="text-muted ms-2 small">
							{{lveLvDetail.editableCheck.isDisabledSendMailInfo.join(', ')}}
						</span>
						<span 
							v-if="lveLvDetail.sentByAnyEvaluierungOfLv.length > 0"
							class="ms-2 badge rounded-pill border border-secondary text-secondary"
							:title="lveLvDetail.sentByAnyEvaluierungOfLv.map(s => s.vorname + ' ' + s.nachname).join('<br>')"
							data-bs-toggle="tooltip"
							data-bs-html="true"
							data-bs-custom-class="tooltip-left"
						>
							<i class="fa fa-users"></i>
							Mail erhalten
							<i class="fa-solid fa-eye ms-1"></i> 
						</span>	
						<span class="ms-2">
							<i 
								class="fa fa-info-circle text-primary fa-lg" 
								:title="infoStudierendenlink"
								data-bs-toggle="tooltip"
								data-bs-html="true"
								data-bs-custom-class="tooltip-left">
							</i>
						</span>							
					</div>
					<!-- Button -->
					<div class="col-12 text-end">
						<div class="d-grid d-md-block">
							<button class="btn btn-success mt-3" @click="onSendLinks(lveLvDetail)">
								Studierendenlinks versenden
							</button>
						</div>
					</div>
				</div><!--.end row -->
				</fieldset>
			</div><!--.end card-footer -->
		</div><!--.end card-->
	`
}