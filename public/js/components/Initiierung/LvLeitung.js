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
			lveLvDetails: [],			// Structured Lv (plus Les, if evaluation is done by LEs) data merged with lvevaluations
			lvevaluierungen: [],		// All Lvevaluierungen of selected Lve-Lv-ID
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
		activeLveLv() {
			return this.lveLvs.find(lv => lv.lvevaluierung_lehrveranstaltung_id === this.selLveLvId);
		}
	},
	watch: {
		selLveLvId(newId) {
			if (newId) {
				this.getLveLvWithLesAndGruppenById(newId)
					.then(() => this.getLvevaluierungen(newId))
					.then(() => {
						const structuredLveLvDetails = this.structureLveLvDetails();
						this.lveLvDetails = this.mergeEvaluierungenIntoDetails(structuredLveLvDetails);
					});
			}
			else {
				this.lveLvWithLesAndGruppen = [];
				this.lvevaluierungen = [];
				this.lveLvDetails = [];
			}
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
		onSave(data) {
			console.log('Saved:', data);
		},
		onSendLinks(data) {
			console.log('Send Links:', data);
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
		structureLveLvDetails() {
			if (!this.lveLvWithLesAndGruppen.length) {
				this.lveLvDetails = [];
				return;
			}

			const isAufgeteilt = this.activeLveLv?.lv_aufgeteilt;

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
					grouped.push({ ...item });
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
				}
			});

			return grouped;
		},
		// Helper: merges start/ende/dauer from lvevaluierungen into detail list
		mergeEvaluierungenIntoDetails(details) {
			const isAufgeteilt = this.activeLveLv?.lv_aufgeteilt;
			const now = DateHelper.formatToSqlTimestamp(new Date());

			details.forEach(detail => {
				const evalMatch = this.findMatchingEvaluierung(detail.lehreinheit_id, isAufgeteilt);

				if (detail.startzeit == null) {
					detail.startzeit = evalMatch?.startzeit ?? now;
				}
				if (detail.endezeit == null) {
					detail.endezeit = evalMatch?.endezeit ?? '';
				}
				if (detail.dauer == null) {
					detail.dauer = evalMatch?.dauer ?? '';
				}
			});

			return details;
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
				const gruppenStrings = item.gruppen.map(g => {
					const parts = [];
					if (g.kurzbzlang) parts.push(g.kurzbzlang);
					if (g.semester !== null && g.semester !== undefined) parts.push(g.semester);
					if (g.verband) parts.push(g.verband);
					if (g.gruppe) parts.push(g.gruppe);
					return parts.join('-');
				});
				infoString += gruppenStrings.join(', ');
			}

			if (item.studentcount) {
				infoString += ' | <i class="fa-solid fa-user"></i> ' + item.studentcount;
			}

			return infoString;
		},
	},
	template: `
	<div class="lve-initiierung-body container-fluid">
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
										:title="lveLv.lv_aufgeteilt ? 'LV auf Gruppenbasis evaluieren' : 'Gesamt-LV evaluieren'"
										data-bs-toggle="tooltip"
									>
									</i> |
									<i 
										:class="[
										  'fa-solid',
										  lveLv.codes_gemailt ? 'fa-envelope-circle-check text-success' : 'fa-envelope text-muted',
										  'me-2'
										]"
										:title="lveLv.codes_gemailt  ? 'Codes wurden an Studierende versendet' : 'Codes wurden noch nicht an Studierende versendet'"
										data-bs-toggle="tooltip"
									>
									</i>
									  {{ getLvInfoString(lveLv) + ' - ' + lveLv.orgform_kurzbz + '  | LV-ID: ' + lveLv.lehrveranstaltung_id + ' LVE-LV-ID: ' + lveLv.lvevaluierung_lehrveranstaltung_id }} 
								</span>
						  	</button>
						</h2>
						<div 
							:id="'flush-collapse' + lveLv.lvevaluierung_lehrveranstaltung_id" 
							class="accordion-collapse collapse" 
							:aria-labelledby="'flush-heading' + lveLv.lvevaluierung_lehrveranstaltung_id" 
							data-bs-parent="#accordionFlush"
							:data-lve-lv-id="lveLv.lvevaluierung_lehrveranstaltung_id"
						>
							<form-form ref="form" class="lve-initiierung-form p-md-3">
								<!-- Radio Buttons -->
								<div class="card mb-3">
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
													>
													</form-input>
												</div>
											</div>
											<div class="flex-grow-1 flex-md-grow-0 mt-2 mt-md-0 d-flex gap-2">
												<button class="btn btn-outline-danger w-100 ms-md-auto" :hidden="lvevaluierungen.length == 0">Zurücksetzen</button>
												<button class="btn btn-primary w-100 ms-md-auto" :disabled="lvevaluierungen.length > 0">Speichern</button>
											</div>					
										</div>			
									</div><!--.card-body -->
								</div><!--.card -->
								<template v-for="lveLvDetail in lveLvDetails" :key="lveLvDetail.lehreinheit_id">
									<div class="card mb-3">
										<div class="card-body border-bottom" v-if="lveLv.lv_aufgeteilt">
											LE: {{lveLvDetail.lehreinheit_id}} | <span v-html="getLeGruppenInfoString(lveLvDetail)"></span>
										</div><!--.end card-body -->
										<div class="card-body border-bottom">
											 LektorInnen:
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
										<div class="card-body">
											<!-- Evaluierungskriterien festlegen -->
											<h5 class="card-title mb-3">Evaluierungskriterien festlegen</h5>
											<div class="row gx-5">
												<!-- Form Inputs + Button -->
												<div class="col-12 col-lg-4 order-1">
													<div class="d-flex flex-wrap flex-md-nowrap gap-3">
														<div class="flex-grow-1">
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
														<div class="flex-grow-1">
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
														<div class="flex-grow-1 flex-md-grow-0 align-self-end ">
															<button class="btn btn-primary w-100 w-md-auto">Speichern</button>
														</div>
													</div>
												</div>
												 <!--Infobox -->
												<div class="col-12 col-lg-5 order-2 mt-3">
													<div class="bg-light border rounded p-3 h-100">
														<Infobox 
															collapseBreakpoint="all" 
															:text="infoGesamtLv"
														>
														</Infobox>
													</div>
												</div><!--.end Infobox cols -->
											</div><!--.end row -->
										</div><!--.end card-body -->
										<!-- Studierendenlinks versenden -->
										<div class="card-footer bg-white">
<!--												<h5 class="card-title mt-2">Studierendenlinks versenden</h5>-->
											<div class="row gx-5">
												<!-- Button -->
												<div class="col-12 col-lg-7 order-1">
													<div class="d-grid d-md-block">
														<button class="btn btn-success mt-3">Studierendenlinks versenden</button>
													</div>
												</div>
												<!-- Info Box -->
												<div class="col-12 col-lg-5 order-2 mt-3">
													<div class="bg-light border rounded p-3 h-100">
														<Infobox :text="infoStudierendenlink"></Infobox>
													</div>
												</div>
											</div><!--.end row -->
										</div><!--.end card-footer -->
									</div><!--.end card-->
								</template>
							</form-form><!--.end form -->
						</div><!--.end accordion-collapse -->
					  </div><!--.end accordion-item -->
				</template><!--.end template v-for -->
			</div><!--.end accordion -->
		</div><!--.end row -->
	</div><!--.end div -->
	`
}