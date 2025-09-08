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
			lveLvWithLesAndGruppen: [],	// Lvs and Lehreinheiten Info of selected Lve-Lv-ID
			selLveLvDetails: [],		// Structured Lv (plus Les, if evaluation is done by LEs) data merged with lvevaluations
			lvevaluierungen: [],		// All Lvevaluierungen of selected Lve-Lv-ID
			lveLvPrestudenten: [],		// All students of selected Lve-Lehrveranstaltung-ID, that were already mailed
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
				this.getLveLvWithLesAndGruppenById(newId)
					.then(() => this.getLvevaluierungen(newId))
					.then(() => this.getLveLvPrestudenten(newId))
					.then(() => {
						if (this.selLveLv) {
							const structuredLveLvDetails = this.structureLveLvDetails() || [];
							this.selLveLvDetails = this.mergeEvaluierungenIntoDetails(structuredLveLvDetails);


							this.setAlreadySentByLv(this.selLveLvDetails);
						}
					})
					.catch(error => this.$fhcAlert.handleSystemError(error));
			}
			else {
				this.lveLvWithLesAndGruppen = [];
				this.lvevaluierungen = [];
				this.selLveLvDetails = [];
				this.lveLvPrestudenten = [];
			}
		},
		'selLveLv.lv_aufgeteilt'(newVal) {
			if (!this.selLveLvId || !this.lveLvWithLesAndGruppen?.length) return;

			const structuredLveLvDetails = this.structureLveLvDetails() || [];
			this.selLveLvDetails = this.mergeEvaluierungenIntoDetails(structuredLveLvDetails);
			this.setAlreadySentByLv(this.selLveLvDetails); // todo check ob hier nötig?
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

						this.setAlreadySentByLv(this.selLveLvDetails);

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
		getLveLvWithLesAndGruppenById(lvevaluierung_lehrveranstaltung_id) {
			// Get Lvs and Lehreinheiten Info of selected Lve-Lv-ID
			return this.$api
				.call(ApiInitiierung.getLveLvWithLesAndGruppenById(lvevaluierung_lehrveranstaltung_id))
				.then(result => {
					this.lveLvWithLesAndGruppen = result.data;
				});
		},
		getLvevaluierungen(lvevaluierung_lehrveranstaltung_id) {
			return this.$api
				.call(ApiInitiierung.getLvEvaluierungenByID(lvevaluierung_lehrveranstaltung_id))
				.then(result => {
					this.lvevaluierungen = result.data;
				});
		},
		getLveLvPrestudenten(lvevaluierung_lehrveranstaltung_id) {
			return this.$api
				.call(ApiInitiierung.getLveLvPrestudenten(lvevaluierung_lehrveranstaltung_id))
				.then(result => this.lveLvPrestudenten = result.data);
		},
		structureLveLvDetails() {
			if (!this.lveLvWithLesAndGruppen.length) {
				this.selLveLvDetails = [];
				return;
			}

			const isAufgeteilt = this.selLveLv?.lv_aufgeteilt;

			return isAufgeteilt
				? this.lveLvWithLesAndGruppen
				: this.groupByLv(this.lveLvWithLesAndGruppen);
		},
		groupByLv(data) {
			const grouped = [];

			// Deep clone lveLvWithLesAndGruppen to avoid mutating original, which causes cumulating gruppen or lektoren
			const clonedData = JSON.parse(JSON.stringify(data));

			clonedData.forEach(item => {
				const group = grouped.find(g => g.lehrveranstaltung_id === item.lehrveranstaltung_id);

				if (!group) {
					// Set lehreinheit_id to null, as we group by Lv here
					grouped.push({ ...item, lehreinheit_id: null });
				}
				else {
					// Uniquely collect lektoren of all Lehreinheiten
					item.gruppen.forEach(gruppe => {
						if (!group.gruppen.some(g =>
								g.kurzbzlang === gruppe.kurzbzlang &&
								g.semester === gruppe.semester &&
								g.verband === gruppe.verband &&
								g.gruppe === gruppe.gruppe
						)) group.gruppen.push(gruppe);
					});

					// Uniquely collect gruppen of all Lehreinheiten
					item.lektoren.forEach(lektor => {
						if (!group.lektoren.some(l => l.mitarbeiter_uid === lektor.mitarbeiter_uid))
							group.lektoren.push(lektor);
					});

					// Uniquely collect gruppen of all students
					item.studenten.forEach(student => {
						if (!group.studenten.some(s => s.prestudent_id === student.prestudent_id))
							group.studenten.push(student);
					});
				}
			});

			return grouped;
		},
		// Helper: merges start/ende/dauer from lvevaluierungen into detail list
		mergeEvaluierungenIntoDetails(selLveLvDetails) {
			const isAufgeteilt = this.selLveLv?.lv_aufgeteilt;
			const now = DateHelper.formatToSqlTimestamp(new Date());

			selLveLvDetails.forEach(detail => {
				const evalMatch = this.findMatchingEvaluierung(detail.lehreinheit_id, isAufgeteilt);

				if (detail.lvevaluierung_id == null) {
					detail.lvevaluierung_id = evalMatch?.lvevaluierung_id ?? '';
				}
				if (detail.startzeit == null) {
					detail.startzeit = evalMatch?.startzeit ?? now;
				}
				if (detail.endezeit == null) {
					detail.endezeit = evalMatch?.endezeit ?? '';
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
				if (detail.lvevaluierung_prestudenten == null) {
					detail.lvevaluierung_prestudenten = evalMatch?.lvevaluierung_prestudenten ?? [];
				}
			});

			return selLveLvDetails;
		},
		setAlreadySentByLv(selLveLvDetails) {
			selLveLvDetails.forEach(detail => {
				detail.alreadyMailedByLv = this.lveLvPrestudenten.filter(lvelvpst =>
						detail.studenten.some(sent => sent.prestudent_id === lvelvpst.prestudent_id)
				);
			});
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
			return lv.kurzbzlang + ' - ' + lv.semester + ': '+ lv.bezeichnung;
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

			return infoString;
		},
	},
	template: `
	<div class="lve-initiierung-body container-fluid d-flex flex-column min-vh-100">
		<h1>LV-Evaluierung initiieren</h1>
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
									  {{ getLvInfoString(lveLv) + ' - ' + lveLv.orgform_kurzbz + '  | LV-ID: ' + lveLv.lehrveranstaltung_id + ' LVE-LV-ID: ' + lveLv.lvevaluierung_lehrveranstaltung_id }} 
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
						<!-- Evaluierungskriterien festlegen -->
						<h5 class="card-title my-4">Evaluierungskriterien festlegen</h5>
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
											<div class="form-check form-check-inline ps-0">
												<form-input
													label="LV auf Gruppenbasis evaluieren"
													class="form-check-input"
													type="radio"
													:value="true"
													v-model="lveLv.lv_aufgeteilt"
													:disabled="lvevaluierungen.length > 0"
													@change="updateLvAufgeteilt"
												>
												</form-input>
											</div>
										</div>
										<div class="flex-grow-1 flex-md-grow-0 mt-2 mt-md-0 d-flex gap-2">
											<button class="btn btn-outline-danger w-100 ms-md-auto" :hidden="lvevaluierungen.length == 0">Zurücksetzen</button>
										</div>					
									</div>	
								</div><!--.card-body -->
							</div><!--.card -->
							<template v-for="lveLvDetail in selLveLvDetails" :key="lveLvDetail.lehreinheit_id">
								<div class="card mb-3">
									<div class="card-body pb-0" v-if="lveLv.lv_aufgeteilt">
										<i class="fa fa-users me-2"></i><span class="d-none d-md-inline me-2">Gruppen:</span>
										<span v-html="getLeGruppenInfoString(lveLvDetail)"></span>
										 | LE: {{lveLvDetail.lehreinheit_id}}
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
<!--														<div class="flex-grow-1">-->
<!--															<form-input-->
<!--																label="Dauer (HH:MM)" -->
<!--																type="datepicker"-->
<!--																v-model="lveLvDetail.dauer"-->
<!--																name="lveLvDetail.dauer"-->
<!--																locale="de"								-->
<!--																:time-picker="true"-->
<!--																:is-24="true"-->
<!--																:hide-input-icon="true"-->
<!--																:minutes-increment="5"-->
<!--																format="HH:mm"-->
<!--																model-type="HH:mm:ss"-->
<!--																:text-input="true"-->
<!--																:auto-apply="true"-->
<!--																:disabled="true"-->
<!--															>-->
<!--															</form-input>-->
<!--														</div>-->
													<div class="flex-grow-1 flex-md-grow-0 align-self-end">
														<button
															type="submit"  
															class="btn btn-primary w-100 w-md-auto"
														>
															Speichern
														</button>
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
									<div class="card-footer bg-white mb-3">
										<div class="row gx-5">
											<div class="col-6">
												<span><i class="fa fa-envelope me-2"></i>Email Status</span>
											</div>
											<div class="col-6 text-end">
												<span 
													v-if="!lveLvDetail.lvevaluierung_id && lveLvDetail.studenten.length > lveLvDetail.alreadyMailedByLv.length" 
													class="text-muted me-2">
													<i class="fa fa-triangle-exclamation text-warning"></i>
													Cannot send - Save dates first
												</span>
												<span 
													v-if="lveLvDetail.lvevaluierung_id && !lveLvDetail.codes_gemailt && !lveLvDetail.alreadyMailedByLv.length > 0" 
													class="text-muted">
													<i class="fa fa-check text-success"></i>
													Ready to send
												</span>
												<span 
													v-if="lveLvDetail.alreadyMailedByLv.length > 0"
													class="text-muted">
													<i class="fa fa-envelope-circle-check text-success"></i>
													 {{lveLvDetail.alreadyMailedByLv.length}} Emails sent to
													<i 
														class="fa fa-users text-muted mx-2" 
														:title="lveLvDetail.alreadyMailedByLv.map(s => s.vorname + ' ' + s.nachname).join(', ')"
														data-bs-toggle="tooltip"
														data-bs-html="true">
													</i>
												</span>	
												<span 
													v-if="lveLvDetail.codes_gemailt"
													class="text-muted">
													{{lveLvDetail.codes_ausgegeben}} Codes generated for
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