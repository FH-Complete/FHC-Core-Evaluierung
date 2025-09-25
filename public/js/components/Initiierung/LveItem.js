import FormForm from "../../../../../js/components/Form/Form.js";
import FormInput from "../../../../../js/components/Form/Input.js";
import DateHelper from "../../helpers/DateHelper";
import Infobox from "../../widgets/Infobox";
import ApiInitiierung from "../../api/initiierung";

export default {
	components: {
		FormForm,
		FormInput,
		Infobox
	},
	data() {
		return {
			infoGesamtLv:  `
				Diese LV wird auf Gruppenbasis evaluiert.<br><br>
				Sie können die Voreinstellungen zum Start der Evaluierung und der Dauer der Evaluierung aktiv verändern/anpassen.<br><br>
				Der Zugriff auf die Evaluierung ist für Studierende nur in diesem Zeitfenster möglich. Sie können den Zeitraum jederzeit korrigieren, solange die Evaluierung noch nicht abgeschlossen wurde.
			`,
			infoStudierendenlink: `
				Der Versand des Studierendenlinks ist nur einmalig möglich. Jede*r Studierende erhält einen anonymen Zugangslink per Email zugesendet.
			`,
			infoGruppenbasis: `
				Infotext über Versand an Lektor*innen hier rein.
			`
		}
	},
	props: {
		lveLvDetail: {
			type: Object,
			required: true
		},
		lvevaluierungen: {
			type: Array,
			default: () => []
		}
	},
	computed: {
		isDisabledLveItem() {return this.isDisabledLvevaluierung(this.lveLvDetail)}
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
							lveLvDetail.insertamum = DateHelper.formatDate(result.data.insertamum),
							lveLvDetail.insertvon = result.data.insertvon

							// Update in lvevaluierungen array (has gui effects like disable button)
							const foundEvaluierung = this.lvevaluierungen.find(lve =>
									lve.lvevaluierung_lehrveranstaltung_id === lveLvDetail.lvevaluierung_lehrveranstaltung_id &&
									lve.lehreinheit_id === lveLvDetail.lehreinheit_id
							);

							if (!foundEvaluierung) {
								this.lvevaluierungen.push({
									...lveLvDetail
								});
							}

							this.$fhcAlert.alertSuccess(this.$p.t('ui/gespeichert'))
						}
					})
					.catch(error => this.$fhcAlert.handleSystemError(error));
		},
		onSendLinks(lveDetail) {
			this.$api
					.call(ApiInitiierung.generateCodesAndSendLinksToStudents(lveDetail.lvevaluierung_id))
					.then(result => {
						if (result.data)
						{
							// Tell user about students, that did not get mail (and code was not generated)
							if (result.data.failedMailStudenten.length > 0)
							{
								let msg = 'Could not mail to students: ';
								result.data.failedMailStudenten.forEach(student => {
									msg += student.vorname + ' ' + student.nachname + ' ';
								})

								this.$fhcAlert.alertWarning(msg);
							}

							// Update data
							lveDetail.codes_gemailt = result.data.codes_gemailt;
							lveDetail.codes_ausgegeben = result.data.codes_ausgegeben;
							lveDetail.mailedPrestudenten = result.data.mailedPrestudenten;

							this.$emit('update-mailed-prestudenten', result.data.mailedPrestudentenByLv);

							// Success info
							this.$fhcAlert.alertSuccess('Erfolgreich gesendet!');
						}
					})
					.catch(error => this.$fhcAlert.handleSystemError(error));
		},
		isDisabledLvevaluierung(lveLvDetail) {
			return lveLvDetail.isReadonly;
		},
		isDiabledLvevaluierungDates(lveLvDetail) {
			if (!lveLvDetail?.lvevaluierung_id) return false;

			// Disable if Evaluierungperiod already started
			const today =  new Date();
			const startzeit = new Date(lveLvDetail.startzeit);

			return today >= startzeit
		},
		getLeGruppenInfoString(lveLvDetail) {
			let infoString = '';
			infoString = lveLvDetail.kurzbz + ' - ' + lveLvDetail.lehrform_kurzbz + ' - ';

			if (lveLvDetail.gruppen && lveLvDetail.gruppen.length > 0) {
				infoString += lveLvDetail.gruppen.map(g => {
					let str = '';
					if (g.kurzbzlang) str += g.kurzbzlang;
					if (g.semester || g.verband || g.gruppe) {
						str += '-';
						str += (g.semester ?? '') + (g.verband ?? '') + (g.gruppe ?? '');
					}
					return str;
				}).join(', ');
			}

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
		emailStatus(lveLvDetail) {
			return {
				allSent: lveLvDetail.studenten.length > 0 &&
						(lveLvDetail.studenten.length <= lveLvDetail.mailedPrestudentenFoundInLv.length)
			}
		}
	},
	template: `
	<fieldset :disabled="isDisabledLveItem">
		<div class="card mb-3">
			<div class="card-header" :class="{'fhc-bgc-blue text-light': !isDisabledLveItem}">LV-Evaluierung</div>
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
			<fieldset :disabled="isDiabledLvevaluierungDates(lveLvDetail)" class="text-muted">
				<div class="card-body mb-3">
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
								<div class="flex-grow-1 flex-md-grow-0 ms-auto text-muted" v-if="lveLvDetail.insertamum" >
									Saved on {{lveLvDetail.insertamum}} by {{lveLvDetail.insertvon}}
								</div>
							</div><!--.d-flex -->
						</div><!--.col -->
						 <!--Infobox -->
						<div class="col-12 order-2 mt-3">
<!--					<div class="bg-light border rounded p-3 h-100">-->
<!--						<Infobox -->
<!--							collapseBreakpoint="all" -->
<!--							:text="infoGesamtLv"-->
<!--						>-->
<!--						</Infobox>-->
<!--					</div>-->
					</div><!--.end Infobox cols -->
					</form-form><!--.end form -->
				</div><!--.end card-body -->
			</fieldset>
			<!-- Codes versenden -->
			<div class="card-footer bg-white mb-3" v-if="lveLvDetail.lvevaluierung_id">
				<div class="row gx-5">
					<div class="col-4">
						<span><i class="fa fa-envelope me-2"></i>Email Status</span>
					</div>
					<div class="col-8 text-end">
						<!-- Cannot send: Save dates first  -->
						<span 
							v-if="!lveLvDetail.lvevaluierung_id && lveLvDetail.studenten.length > lveLvDetail.mailedPrestudentenFoundInLv.length" 
							class="text-muted me-2">
							<i class="fa fa-triangle-exclamation text-warning"></i>
							Cannot send - Save dates first
						</span>
						<!-- Ready to send -->
						<span 
							v-if="lveLvDetail.lvevaluierung_id && !lveLvDetail.codes_gemailt && !lveLvDetail.mailedPrestudentenFoundInLv.length > 0" 
							class="text-muted">
							<i class="fa fa-check text-success"></i>
							Ready to send
						</span>
						<!-- Codes generated for -->
						<span 
							v-if="lveLvDetail.codes_gemailt"
							class="text-muted me-2 d-none d-md-block">
							{{lveLvDetail.codes_ausgegeben}} Codes generated - 
						</span>	
						<!-- Emails sent to -->
						<span 
							v-if="lveLvDetail.mailedPrestudentenFoundInLv.length > 0"
							class="text-muted">
							<i class="fa fa-envelope-circle-check text-success"></i>
							 {{lveLvDetail.mailedPrestudentenFoundInLv.length}} Emails sent to
							<i 
								class="fa fa-users text-muted mx-2" 
								:title="lveLvDetail.mailedPrestudentenFoundInLv.map(s => s.vorname + ' ' + s.nachname).join(', ')"
								data-bs-toggle="tooltip"
								data-bs-html="true">
							</i>
						</span>								
					</div>
					<!-- Button -->
					<div class="col-12 text-end">
						<div class="d-grid d-md-block">
							<button 
								:disabled="!lveLvDetail.lvevaluierung_id && !lveLvDetail.codes_gemailt || emailStatus(lveLvDetail).allSent" 
								class="btn btn-success mt-3"
								@click="onSendLinks(lveLvDetail)"
							>
							Studierendenlinks versenden
							</button>
						</div>
					</div>
				</div><!--.end row -->
			</div><!--.end card-footer -->
		</div><!--.end card-->
	</fieldset>
	`
}