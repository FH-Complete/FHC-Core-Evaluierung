import ApiEvaluation from "../../api/evaluation.js";
import EvaluationAuswertung from "./EvaluationAuswertung.js";
import EvaluationReflexion from "./EvaluationReflexion.js";
import EvaluationEinmeldung from "./EvaluationEinmeldung.js";

export default {
	name: "Evaluation",
	components: {
		EvaluationAuswertung,
		EvaluationReflexion,
		EvaluationEinmeldung,
	},
	props: ['lvevaluierung_id', 'lvevaluierung_lehrveranstaltung_id', 'lehrveranstaltung_template_id', 'selected_view'],
	data() {
		return {
			evalData: {
				bezeichnung: '',
				verpflichtend: null,
				lv_aufgeteilt: null,
				lvLeitungen: [],
				lehrende: [],
				codes_ausgegeben: 0,
				countSubmitted: 0,
				ruecklaufquote: null,
				startzeit: null,
				endezeit: null,
			},
			selectedView: this.selected_view || 'auswertung',
			scrollPositions: {
				auswertung: 0,
				reflexion: 0,
				einmeldung: 0
			},
			evaluationView: {
				open: null,
				msg: ''
			}
		}
	},
	provide() {
		return { 
			evalData:Vue.computed(()=>this.evalData)
		}
	},
	created() {
		if (this.lvevaluierung_id || this.lvevaluierung_lehrveranstaltung_id) {
			const apiCall = this.lvevaluierung_id
				? ApiEvaluation.getEvaluationDataByLve(this.lvevaluierung_id)
				: ApiEvaluation.getEvaluationDataByLveLv(this.lvevaluierung_lehrveranstaltung_id);

			this.$api
				.call(apiCall)
				.then(result => {
					if (result.data?.data !== null)
					{
						this.evalData = result.data.data;
					}

					this.evaluationView.open = result.data.evaluationView.open;
					this.evaluationView.msg  = result.data.evaluationView.msg;
				})
				.catch(error => this.$fhcAlert.handleSystemError(error));
		}
		else {
			// If no Lve ID or LveLv ID
			this.evaluationView = {
				open: false,
				msg: 'Keine Daten vorhanden'
			}
		}
	},
	computed: {
		lvTemplateId() {
			if (this.lehrveranstaltung_template_id){
				return this.lehrveranstaltung_template_id;
			}
			else if (this.lvevaluierung_id || this.lvevaluierung_lehrveranstaltung_id) {
				return this.evalData.lehrveranstaltung_template_id;
			}
		},
		selectedComponent() {
			if (this.selectedView === 'auswertung') return 'Evaluation-Auswertung'
			if (this.selectedView === 'reflexion') return 'Evaluation-Reflexion'
			if (this.selectedView === 'einmeldung') return 'Evaluation-Einmeldung'
		},
		lehrveranstaltung() {
			if (!this.evalData.bezeichnung) return;

			const d = this.evalData;
			return `${d.stgKurzbz}-${d.semester}: ${d.bezeichnung} - ${d.orgform_kurzbz}`;
		},
		verpflichtend() {
			if (this.evalData.verpflichtend === null) return;
			return this.evalData.verpflichtend ? 'Ja' : 'Nein';
		},
		lvAufgeteilt() {
			if (this.evalData.lv_aufgeteilt === null) return;
			return this.evalData.lv_aufgeteilt ? 'Gruppenbasis' : 'Gesamt-LV';
		},
		lehrende() {
			return this.evalData?.lehrende?.map(l => `${l.vorname} ${l.nachname}`).join(', ');
		},
		lvLeitungen() {
			return this.evalData?.lvLeitungen?.map(l => `${l.vorname} ${l.nachname}`).join(', ');
		},
		formattedEvalPeriod() {
			if (!this.evalData.startzeit || !this.evalData.endezeit) return '-';

			return this.DateHelper.formatDate(this.evalData.startzeit) + ' - ' + this.DateHelper.formatDate(this.evalData.endezeit);
		},
		avgDuration() {
			return ((this.evalData?.minDuration + this.evalData?.maxDuration) / 2).toFixed(2);
		},
		ruecklaufWarning() {
			let msgCollector = [];

			if (this.evalData.ruecklaufquote !== null && this.evalData.ruecklaufquote < 30) {
				msgCollector.push({
					text: 'RL-Quote < 30%',
					type: 'text-danger',
					title: 'Repräsentativität könnte durch geringe Rücklaufquote eingeschränkt sein'
				})
			}

			if (this.evalData.codes_ausgegeben > 0)
			{
				if (this.evalData.countSubmitted <= 5) {
					msgCollector.push({
						text: 'abgeschlossen <= 5',
						type: 'text-danger',
						title: 'Vorsicht bei Interpretation: Anzahl der Evaluierungen ist sehr gering, Anonymität könnte beeinträchtigt sein.'
					})
				}

				if (this.evalData.countSubmitted > 5 && this.evalData.countSubmitted <= 10) {
					msgCollector.push({
						text: 'abgeschlossen <= 10',
						type: 'text-warning',
						title: 'Berücksichtigen bei Interpretation: Anzahl der Evaluierungen ist gering'
					})
				}
			}

			return msgCollector;
		},
		ruecklaufClass() {
			if (this.evalData?.ruecklaufquote === null) {
				return 'bg-white'
			}

			if (this.evalData.ruecklaufquote < 30) {
				return 'bg-danger-subtle'
			}

			if (this.evalData.ruecklaufquote >= 30) {
				return 'bg-success-subtle'
			}
		}
	},
	methods: {
		changeView(view) {
			this.saveScroll()
			this.selectedView = view
			this.$nextTick(() => this.restoreScroll())
		},
		saveScroll() {
			const el = this.$refs.scrollArea;
			if (el) this.scrollPositions[this.selectedView] = el.scrollTop
		},
		restoreScroll() {
			const el = this.$refs.scrollArea;
			if (el) el.scrollTop = this.scrollPositions[this.selectedView] || 0
		},
	},
	template: `
	<div class="evaluation-evaluation container-fluid d-flex flex-column vh-100 p-0">
		<!-- Fixed Header -->
		<div class="bg-white py-3 px-3 flex-shrink-0">
			<div class="d-flex justify-content-between align-items-center flex-wrap">
				<div>
					<h2 class="d-none d-lg-inline-block mb-0">
						LV-Evaluation <small>{{ evalData.bezeichnung }}</small>
					</h2>
					<h2 class="d-lg-none mb-0">LV-Evaluation</h2>
				</div>
				<div class="btn-group btn-group-lg mt-2 mt-lg-0" role="group">
					<input type="radio" class="btn-check" id="option1" 
						:checked="selectedView==='auswertung'" 
						@change="changeView('auswertung')">
					<label class="btn btn-outline-primary" for="option1" 
						v-tooltip
						title="Ergebnisse LV-Evaluierung">
							<i class="fa fa-chart-simple"></i>
					</label>

					<input type="radio" class="btn-check" id="option2" 
						:checked="selectedView==='reflexion'" 
						@change="changeView('reflexion')">
					<label class="btn btn-outline-primary" for="option2"
						v-tooltip
						title="LV-Reflexion">
							<i class="fa fa-list-check"></i>
					</label>
					<input type="radio" class="btn-check" id="option3" 
						:checked="selectedView==='einmeldung'" 
						@change="changeView('einmeldung')">
					<label class="btn btn-outline-primary" for="option3"
						v-tooltip
						title="Einmeldungen">
							<i class="fa fa-square-poll-horizontal"></i>
					</label>
				</div>
			</div>
		</div>
		<!-- Scrollable content -->
		<main ref="scrollArea" class="flex-grow-1 overflow-auto px-3 pt-3">
			<!-- Info tables -->
			<div class="row">
				<div class="col-12">
					<div class="d-flex flex-wrap gap-3 mb-5 mb-lg-3">
						<!-- Left table -->
						<div class="evaluation-data-table-flex">
							<table class="table table-bordered align-middle">
								<tbody>
									<tr>
										<th>Lehrveranstaltung</th>
										<td>{{ lehrveranstaltung }}</td>
									</tr>
									<tr>
										<th>Verbindlich ausgewählt</th>
										<td>{{ verpflichtend }}</td>
									</tr>
									<tr>
										<th>Evaluationsebene</th>
										<td>{{ lvAufgeteilt }}</td>
									</tr>
									<tr>
										<th>LV-Leitung</th>
										<td>{{ lvLeitungen }}</td>
									</tr>
									<tr>
										<th>Lehrende</th>
										<td>{{ lehrende }}</td>
									</tr>
									<tr>
										<th>Evaluierungszeitfenster</th>
										<td>{{ formattedEvalPeriod }}</td>
									</tr>
									<tr>
										<th>Bearbeitungszeit (in min)</th>
										<td>
											<div class="d-flex justify-content-between">
												<span>Ø {{ avgDuration }}</span>
												<span>Min {{ evalData.minDuration }}</span>
												<span>Max {{ evalData.maxDuration }} </span>
											</div>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<!-- Rücklaufquote -->
						<div class="evaluation-card-flex">
							<div class="card" :class="ruecklaufClass">
								<!-- Header -->
								<div class="card-header text-center">
						  			<span class="fw-bold">Rücklaufquote</span>
								</div>
								<!-- Body -->
								<div class="card-body d-flex flex-column justify-content-center align-items-center">
									<!-- Display RL-Quote -->
									<div class="fw-bold display-5 mb-3">
										{{ evalData.ruecklaufquote !== null ? evalData.ruecklaufquote + ' %' : '-' }}
									</div>
									<!-- abgeschlossen / versandt -->
									<div class="fw-bold">
										{{ evalData.countSubmitted }} abgeschlossen /
										{{ evalData.codes_ausgegeben }} versandt
									</div>
									<!-- Warning icon and Tooltip -->
									<div class="mt-3">
										<div
											v-for="(warning, index) in ruecklaufWarning"
											:key="index"
											v-tooltip="warning.title"
										>
											<i
												class="fa fa-triangle-exclamation fa-lg me-2"
												:class="warning.type"
											>
											</i>
											<small>{{ warning.text }}</small>
										</div>
									</div>
								</div><!--.card-body -->
								<!-- Footer -->
								<div class="card-footer text-center">
							  		<small>Abgeschlossene Evaluierungen / Einladungen versandt</small>
								</div>
							</div><!--.card -->		  
						</div><!--.Rücklaufquote -->
					</div><!--.d-flex -->
				</div><!--.col -->
			</div><!--.row -->
			
			<!-- Dynamic content -->
			<div v-if="evaluationView.open === true">
				<keep-alive>
					<component
						:evaluationView="evaluationView" 
						:is="selectedComponent" 
						:lvevaluierung_id="lvevaluierung_id"
						:lvevaluierung_lehrveranstaltung_id="lvevaluierung_lehrveranstaltung_id"
						:lvevaluierung_template_id="lvTemplateId"
						@change-view="changeView"
						class="d-block mt-5"
					></component>
				</keep-alive>
				
			</div>
		  	<!-- Alert if no existing data or evaluation period still running -->
		  	<div v-else class="card card-body py-5 d-flex mt-3" role="alert">
				<div class="fw-bold">
					<i class="fa fa-triangle-exclamation me-2 fa-lg text-warning"></i>{{ evaluationView.msg }}
				</div>
			</div><!--.end v-else-->
		</main>
	</div>
	`
}