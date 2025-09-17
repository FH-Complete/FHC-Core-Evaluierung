import FormForm from "../../../../../js/components/Form/Form.js";
import FormInput from "../../../../../js/components/Form/Input.js";
import Infobox from "../../widgets/Infobox";
import DateHelper from "../../helpers/DateHelper";
import ApiFhc from "../../api/fhc.js";
import ApiInitiierung from "../../api/initiierung.js";

export default {
	components: {
		FormForm,
		FormInput,
		Infobox
	},
	created() {
		this.$api
			.call(ApiFhc.Studiensemester.getAll())
			.then(result => this.studiensemester = result.data)
			.then(() => this.$api.call(ApiFhc.Studiensemester.getAktNext()))
			.then(result => this.selStudiensemester = result.data[0].studiensemester_kurzbz)
			.then(() => this.$api.call(ApiInitiierung.getLveLvs(this.selStudiensemester)))
			.then(result => this.lveLvs = result.data)
			.catch(error => this.$fhcAlert.handleSystemError(error) );
	},
	data() {
		return {
			studiensemester: [],
			selStudiensemester: '',
			lveLvs: [],					// All Lvs to be evaluated, where user is assigned to at least one Le as a Lektor.
			selLveLvId: '',				// Lve-Lv-ID of selected Lv
			selLveLvDetails: [],		// Structured Lv (plus Les, if evaluation is done by LEs) data merged with lvevaluations
			selLveLvDataGroupedByLv: [],	// Basis data for selLveLvDetails, grouped for Gesamt-LV Evaluierung
			selLveLvDataGroupedByLeUnique: [],	// Basis data for selLveLvDetails, grouped for Gruppenbasis Evaluierung
			lvevaluierungen: [],		// All Lvevaluierungen of selected Lve-Lv-ID
			lvLeitungen: null,
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
	computed: {
		selLveLv() {
			return this.lveLvs.find(lv => lv.lvevaluierung_lehrveranstaltung_id === this.selLveLvId);
		}
	},
	watch: {
		selLveLvId(newId) {
			if (newId) {
				this.$api
					.call(ApiInitiierung.getLveLvDataGroups(newId))
					.then(result => {
						// Set LV-Leitung
						this.lvLeitungen = result.data.lvLeitungen;

						// Set basic data sets
						this.selLveLvDataGroupedByLv = result.data.selLveLvDataGroupedByLv;
						this.selLveLvDataGroupedByLeUnique = result.data.selLveLvDataGroupedByLeUnique;

						// Set final data set depending on selected Evaluierungsart (Gesamt-LV or Gruppenbasis)
						this.selLveLvDetails =  this.decideLveLvDataGroup() || [];
					})
					.then(() => this.fetchAndSetLvevaluierungen(newId))
					.then(() => this.selLveLvDetails = this.mergeLvevaluierungenIntoDetails(this.selLveLvDetails))
					.then(() => this.fetchAndSetLveLvPrestudenten(newId))
					.catch(error => this.$fhcAlert.handleSystemError(error));
			}
			else {
				this.lvevaluierungen = [];
				this.selLveLvDetails = [];
				this.lveLvPrestudenten = [];
			}
		},
		'selLveLv.lv_aufgeteilt'(newVal) {
			if (!this.selLveLvId) return;

			this.selLveLvDetails  = this.decideLveLvDataGroup() || [];
			this.selLveLvDetails = this.mergeLvevaluierungenIntoDetails(this.selLveLvDetails);
		}
	},
	mounted() {
		// Add Event Listener to load evaluation data only when an accordion item is expanded
		const accordion = document.getElementById('accordionFlush');
		if (accordion) {
			accordion.addEventListener('shown.bs.collapse', this.handleAccordionShown);
		}
	},
	methods: {
		onChangeStudiensemester(e) {
			this.$api
				.call(ApiInitiierung.getLveLvs(this.selStudiensemester))
				.then(result => this.lveLvs = result.data)
				.catch(error => this.$fhcAlert.handleSystemError(error));
		},
		onChangeLv() {
			const collapseEl = document.getElementById('flush-collapse' + this.selLveLvId);
			if (collapseEl) {
				// Get Bootstrap Collapse-Instance oder erstelle neue (toggle: false = nicht automatisch umschalten)
				const bsCollapse = bootstrap.Collapse.getInstance(collapseEl) || new bootstrap.Collapse(collapseEl, { toggle: false });
				bsCollapse.show();
			}
		},
		updateLvAufgeteilt(e) {
			if (this.lvevaluierungen.length > 0) return;

			this.$api
					.call(ApiInitiierung.updateLvAufgeteilt(this.selLveLvId, this.selLveLv.lv_aufgeteilt))
					.catch(error => this.$fhcAlert.handleSystemError(error));
		},
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
						const newId = result.data.lvevaluierung_id;

						// Update in selLveLvDetails source array (persistent)
						const foundInDetails = this.selLveLvDetails?.find(item =>
								item.lvevaluierung_lehrveranstaltung_id === lveLvDetail.lvevaluierung_lehrveranstaltung_id &&
								item.lehreinheit_id === lveLvDetail.lehreinheit_id
						);
						if (foundInDetails) {
							foundInDetails.lvevaluierung_id = newId;
						}

						// Update in lvevaluierungen array (has gui effects like disable button)
						const foundEvaluierung = this.lvevaluierungen.find(lve =>
								lve.lvevaluierung_lehrveranstaltung_id === lveLvDetail.lvevaluierung_lehrveranstaltung_id &&
								lve.lehreinheit_id === lveLvDetail.lehreinheit_id
						);
						lveLvDetail.insertamum = DateHelper.formatDate(result.data.insertamum),
						lveLvDetail.insertvon = result.data.insertvon

						if (!foundEvaluierung) {
							this.lvevaluierungen.push({
								...lveLvDetail,
								lvevaluierung_id: newId
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
						lveDetail.lvevaluierung_prestudenten = result.data.lvevaluierung_prestudenten;

						this.lveLvPrestudenten = result.data.lveLvPrestudenten;

						// Success info
						this.$fhcAlert.alertSuccess('Erfolgreich gesendet!');
					}
				})
				.catch(error => this.$fhcAlert.handleSystemError(error));
		},
		handleAccordionShown(e) {
			const accBtn = e.target;

			// Get ID from selected item
			if (accBtn){
				this.selLveLvId = Number(accBtn.dataset.lveLvId);
			}
		},
		fetchAndSetLvevaluierungen(lvevaluierung_lehrveranstaltung_id) {
			return this.$api
				.call(ApiInitiierung.getLvEvaluierungenByID(lvevaluierung_lehrveranstaltung_id))
				.then(result => {
					this.lvevaluierungen = result.data;
				});
		},
		fetchAndSetLveLvPrestudenten(lvevaluierung_lehrveranstaltung_id) {
			return this.$api
				.call(ApiInitiierung.getLveLvPrestudenten(lvevaluierung_lehrveranstaltung_id))
				.then(result => this.lveLvPrestudenten = result.data);
		},
		decideLveLvDataGroup() {
			const isAufgeteilt = this.selLveLv?.lv_aufgeteilt;

			return isAufgeteilt
				? this.selLveLvDataGroupedByLeUnique
				: this.selLveLvDataGroupedByLv
		},
		// Helper: merges start/ende/dauer from lvevaluierungen into detail list
		mergeLvevaluierungenIntoDetails(selLveLvDetails) {
			const isAufgeteilt = this.selLveLv?.lv_aufgeteilt;
			let startzeit = new Date();
			let endezeit = new Date();
			endezeit.setDate(startzeit.getDate() + 3);

			startzeit = DateHelper.formatToSqlTimestamp(startzeit);
			endezeit = DateHelper.formatToSqlTimestamp(endezeit);

			selLveLvDetails.forEach(detail => {
				const evalMatch = this.findMatchingEvaluierung(detail.lehreinheit_id, isAufgeteilt);

				if (detail.lvevaluierung_id == null) {
					detail.lvevaluierung_id = evalMatch?.lvevaluierung_id ?? '';
				}
				if (detail.startzeit == null) {
					detail.startzeit = evalMatch?.startzeit ?? startzeit;
				}
				if (detail.endezeit == null) {
					detail.endezeit = evalMatch?.endezeit ?? endezeit;
				}
				if (detail.dauer == null) {
					detail.dauer = evalMatch?.dauer ?? '';
				}
				if (detail.codes_gemailt == null) {
					detail.codes_gemailt = evalMatch?.codes_gemailt ?? false;
				}
				if (detail.codes_ausgegeben == null) {
					detail.codes_ausgegeben = evalMatch?.codes_ausgegeben ?? 0;
				}
				if (detail.insertvon == null) {
					detail.insertvon = evalMatch?.insertvon ?? '';
				}
				if (detail.insertamum == null) {
					detail.insertamum = evalMatch?.insertamum ? DateHelper.formatDate(evalMatch.insertamum) : '';
				}

				// Merge also mailed Studenten
				if (detail.lvevaluierung_prestudenten == null) {
					detail.lvevaluierung_prestudenten = evalMatch?.lvevaluierung_prestudenten ?? [];
				}
			});

			return selLveLvDetails;
		},
		// Helper: finds the correct evaluierung for a given Lehreinheit
		findMatchingEvaluierung(lehreinheit_id, isAufgeteilt) {
			if (isAufgeteilt) {
				return this.lvevaluierungen.find(ev =>
					ev.lehreinheit_id === lehreinheit_id &&
					ev.lvevaluierung_lehrveranstaltung_id === this.selLveLvId
				);
			}
			else {
				return this.lvevaluierungen.find(ev =>
					ev.lvevaluierung_lehrveranstaltung_id === this.selLveLvId
				);
			}
		},
		getLvInfoString(lv){
			// return lv.kurzbzlang + ' - ' + lv.semester + ': '+ lv.bezeichnung + ' - ' + lv.orgform_kurzbz + '  | LV-ID: ' + lv.lehrveranstaltung_id + ' LVE-LV-ID: ' + lv.lvevaluierung_lehrveranstaltung_id; // todo delete after testing.
			return lv.kurzbzlang + ' - ' + lv.semester + ': '+ lv.bezeichnung + ' - ' + lv.orgform_kurzbz ;
		},
		getLeGruppenInfoString(item) {
			let infoString = '';
			infoString = item.kurzbz + ' - ' + item.lehrform_kurzbz + ' - ';

			if (item.gruppen && item.gruppen.length > 0) {
				infoString += item.gruppen.map(g => {
					let str = '';
					if (g.kurzbzlang) str += g.kurzbzlang;
					if (g.semester || g.verband || g.gruppe) {
						str += '-';
						str += (g.semester ?? '') + (g.verband ?? '') + (g.gruppe ?? '');
					}
					return str;
				}).join(', ');
			}

			if (item.studenten) {
				infoString += ' | <i class="fa-solid fa-user"></i> ' + item.studenten.length;
			}

		//	infoString += ' | LE: ' + item.lehreinheit_id; // todo delete after testing

			return infoString;
		},
	},
	template: `
	<div class="lve-initiierung-body container-fluid d-flex flex-column min-vh-100">
		<h1 class="mb-5">LV-Evaluierung starten<small class="fs-5 fw-normal text-muted"> | Evalueriungskriterien festlegen und Codes an Studierende mailen</small></h1>
		
		<!-- Dropdowns -->
		<div class="row">
			<div class="col-sm-10 col-lg-3 offset-lg-7 mb-3">
				<form-input
					type="select"
					v-model="selLveLvId"
					name="lehrveranstaltung"
					:label="$p.t('lehre/lehrveranstaltung')"
					@change="onChangeLv">
					<option 
						v-for="lveLv in lveLvs"
						:key="lveLv.lvevaluierung_lehrveranstaltung_id" 
						:value="lveLv.lvevaluierung_lehrveranstaltung_id"
					>
						{{ getLvInfoString(lveLv) }}
					</option>
				</form-input>
			</div>
			<div class="col-sm-2 mb-3">
				<form-input
					type="select"
					v-model="selStudiensemester"
					name="studiensemester"
					:label="$p.t('lehre/studiensemester')"
					@change="onChangeStudiensemester">
					<option 
						v-for="studSem in studiensemester"
						:key="studSem.studiensemester_kurzbz" 
						:value="studSem.studiensemester_kurzbz">
						{{ studSem.studiensemester_kurzbz }}
					</option>
				</form-input>
			</div>
		</div><!--.end row -->
		<!-- Placeholder Card: If no LV for Evaluation found -->
		<div class="card card flex-grow-1 mb-3" v-if="lveLvs.length == 0">
			<div class="card-body d-flex justify-content-center align-items-center text-center">
				<span class="h5 text-muted">
					Keine Lehrveranstaltungen zur Evaluierung freigegeben in {{ selStudiensemester}}
				</span>
			</div>
		</div>	
		<!-- LV Accordion List -->
		<div class="row">
			<div class="accordion accordion-flush" id="accordionFlush">
				<template v-for="lveLv in lveLvs" :key="lveLv.lvevaluierung_lehrveranstaltung_id">	
					<div class="accordion-item">
						<h2 class="accordion-header" :id="'flush-heading' + lveLv.lvevaluierung_lehrveranstaltung_id">
						  	<button 
						  		class="accordion-button collapsed" 
						  		type="button" 
						  		data-bs-toggle="collapse" 
						  		:data-bs-target="'#flush-collapse' + lveLv.lvevaluierung_lehrveranstaltung_id" 
						  		aria-expanded="false" 
						  		aria-controls="flush-collapse' + lveLv.lvevaluierung_lehrveranstaltung_id"
							>
								<span class="gap-2">
									<i 
										:class="[
										  'fa-solid',
										  lveLv.lv_aufgeteilt ? 'fa-expand' : 'fa-square-full',
										  'text-dark'
										]"
										:title="lveLv.lv_aufgeteilt ? 'LV wird auf Gruppenbasis evaluiert' : 'Gesamt-LV wird evaluiert'"
										data-bs-toggle="tooltip"
									>
									</i> |
									<i 
										:class="[
										  'fa-solid',
										  lveLv.verpflichtend ? 'fa-asterisk text-success' : 'fa-asterisk text-light',
										  'me-2'
										]"
										:title="lveLv.verpflichtend  ? 'Evaluierung muss durchgeführt werden (verpflichtend)' : 'Evaluierung kann durchgeführt werden (nicht verpflichtend)'"
										data-bs-toggle="tooltip"
									>
									</i>
									  {{ getLvInfoString(lveLv)}}
								</span>
						  	</button>
						</h2>
						<div 
							:id="'flush-collapse' + lveLv.lvevaluierung_lehrveranstaltung_id" 
							class="accordion-collapse collapse md-mx-3" 
							:aria-labelledby="'flush-heading' + lveLv.lvevaluierung_lehrveranstaltung_id" 
							data-bs-parent="#accordionFlush"
							:data-lve-lv-id="lveLv.lvevaluierung_lehrveranstaltung_id"
						>
							<!-- Radio Buttons -->
							<div class="card my-3">
								<div class="card-body">
									<div class="d-flex flex-wrap justify-content-md-between align-items-center">
										<div class="flex-grow-1 flex-md-grow-0">
											<div class="form-check form-check-inline ps-0">
												<form-input
													label="Gesamt-LV evaluieren"
													class="form-check-input"
													type="radio"
													:value="false"
													v-model="lveLv.lv_aufgeteilt"
													:disabled="lvevaluierungen.length > 0"
													@change="updateLvAufgeteilt"
												>
												</form-input>
											</div>
											<div 
												class="form-check form-check-inline ps-0" 													
												:title="selLveLvDataGroupedByLeUnique.length === 0 
												  	? 'Nur verfügbar, wenn Gruppen eindeutig Lehrenden zugeordnet sind.' 
												  	: ''"
												data-bs-toggle="tooltip"
												data-bs-placement="top"
											>
												<form-input
													label="LV auf Gruppenbasis evaluieren"
													class="form-check-input"
													type="radio"
													:value="true"
													v-model="lveLv.lv_aufgeteilt"
													:disabled="lvevaluierungen.length > 0 || selLveLvDataGroupedByLeUnique.length === 0"
													@change="updateLvAufgeteilt"
												>
												</form-input>
											</div>
										</div>
									</div>	
								</div><!--.card-body -->
							</div><!--.card -->
							<template v-for="lveLvDetail in selLveLvDetails" :key="lveLvDetail.lehreinheit_id">
								<div class="card mb-3">
									<div class="card-header">LV-Evaluierung</div>
									<div class="card-body pb-0">
										<i class="fa fa-users me-2"></i><span class="d-none d-md-inline me-2">Gruppen:</span>
										<span v-html="getLeGruppenInfoString(lveLvDetail)"></span>
									</div><!--.end card-body -->
									<div class="card-body border-bottom">
										<i class="fa fa-graduation-cap me-2"></i><span class="d-none d-md-inline me-2">LektorInnen:</span>
										<template v-for="(lektor, i) in lveLvDetail.lektoren" :key="i">
											<span class="">
												{{ i !== 0 ? ', ' : '' }}{{ lektor.fullname }}
											</span>
											<span
												v-if="lektor.lehrfunktion_kurzbz === 'LV-Leitung'"
												class="badge rounded-pill bg-dark ms-1 me-2"
											>
											LV-Leitung
											</span>
										</template>
									</div><!--.end card body-->
									<div class="card-body mb-3">
										 <!-- Form 2: Date/Time Inputs -->
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
<!--													<div class="bg-light border rounded p-3 h-100">-->
<!--														<Infobox -->
<!--															collapseBreakpoint="all" -->
<!--															:text="infoGesamtLv"-->
<!--														>-->
<!--														</Infobox>-->
<!--													</div>-->
											</div><!--.end Infobox cols -->
										</div><!--.end row -->
										</form-form><!--.end form -->
									</div><!--.end card-body -->
									<!-- Studierendenlinks versenden -->
									<div class="card-footer bg-white mb-3" v-if="lveLvDetail.lvevaluierung_id">
										<div class="row gx-5">
											<div class="col-6">
												<span><i class="fa fa-envelope me-2"></i>Email Status</span>
											</div>
											<div class="col-6 text-end">
												<span 
													v-if="lveLvDetail.lvevaluierung_id && !lveLvDetail.codes_gemailt" 
													class="text-muted">
													<i class="fa fa-check text-success"></i>
													Ready to send
												</span>
												<span 
													v-if="lveLvDetail.codes_gemailt"
													class="text-muted">
													<i class="fa fa-envelope-circle-check text-success"></i>
													{{lveLvDetail.codes_ausgegeben}} Emails sent to
													<i class="fa fa-users text-muted mx-2" 
														:title="lveLvDetail.lvevaluierung_prestudenten.map(s => s.vorname + ' ' + s.nachname).join(', ')"
														data-bs-toggle="tooltip"
														data-bs-html="true"
													>
													</i>
												</span>								
											</div>
											<!-- Button -->
											<div class="col-12 text-end">
												<div class="d-grid d-md-block">
													<button 
														:disabled="!lveLvDetail.lvevaluierung_id && !lveLvDetail.codes_gemailt || lveLvDetail.studenten.length > 0 && (lveLvDetail.studenten.length == lveLvDetail.alreadyMailedByLv.length)" 
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
							</template>
						</div><!--.end accordion-collapse -->
					  </div><!--.end accordion-item -->
				</template><!--.end template v-for -->
			</div><!--.end accordion -->
		</div><!--.end row -->
	</div><!--.end div -->
	`
}