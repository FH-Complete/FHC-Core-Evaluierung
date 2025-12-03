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
	props: ['lvevaluierung_id', 'lvevaluierung_lehrveranstaltung_id'],
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
				ruecklaufquote: 0,
				startzeit: null,
				endezeit: null,
			},
			selectedView: 'auswertung',
			scrollPositions: {
				auswertung: 0,
				reflexion: 0,
				einmeldung: 0
			}
		}
	},
	computed: {
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
		verpflichtend(){
			if (this.evalData.verpflichtend === null) return;
			return this.evalData.verpflichtend ? 'Ja' : 'Nein';
		},
		lvAufgeteilt(){
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
			if (!this.evalData.startzeit || !this.evalData.endezeit) return;

			return this.DateHelper.formatDate(this.evalData.startzeit) + ' - ' + this.DateHelper.formatDate(this.evalData.endezeit);
		},
		avgDuration() {
			return ((this.evalData?.minDuration + this.evalData?.maxDuration) / 2).toFixed(2);
		}
	},
	created() {
		if (this.lvevaluierung_id || this.lvevaluierung_lehrveranstaltung_id) {
			const apiCall = this.lvevaluierung_id
				? ApiEvaluation.getEvaluationDataByLve(this.lvevaluierung_id)
				: ApiEvaluation.getEvaluationDataByLveLv(this.lvevaluierung_lehrveranstaltung_id);

			this.$api
				.call(apiCall)
				.then(result => this.evalData = result.data)
				.catch(error => this.$fhcAlert.handleSystemError(error));
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
						LV-Reflexion <small>{{ evalData.bezeichnung }}</small>
					</h2>
					<h2 class="d-lg-none mb-0">LV-Reflexion</h2>
				</div>
				<div class="btn-group mt-2 mt-lg-0" role="group">
					<input type="radio" class="btn-check" id="option1" 
						:checked="selectedView==='auswertung'" 
						@change="changeView('auswertung')">
					<label class="btn btn-outline-primary" for="option1"><i class="fa fa-chart-simple"></i></label>
					
					<input type="radio" class="btn-check" id="option2" 
						:checked="selectedView==='reflexion'" 
						@change="changeView('reflexion')">
					<label class="btn btn-outline-primary" for="option2"><i class="fa fa-square-poll-horizontal"></i></label>
					
					<input type="radio" class="btn-check" id="option3" 
						:checked="selectedView==='einmeldung'" 
						@change="changeView('einmeldung')">
					<label class="btn btn-outline-primary" for="option3"><i class="fa fa-medal"></i></label>
				</div>
			</div>
		</div>
		<!-- Scrollable content -->
		<main ref="scrollArea" class="flex-grow-1 overflow-auto px-3 pt-3">
			<!-- Info tables -->
			<div class="row mb-5">
				<!-- Left table -->
				<div class="col-md-6">
					<table class="table table-sm table-bordered align-middle">
						<tbody>
							<tr>
								<th>Lehrveranstaltung</th>
								<td>{{ lehrveranstaltung }}</td>
							</tr>
							<tr>
								<th>Verpflichtende Evaluation</th>
								<td>{{ verpflichtend }}</td>
							</tr>
							<tr>
								<th>Evaluationseinheit</th>
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
						</tbody>
					</table>
				</div>
				<!-- Right table -->
				<div class="col-md-6">
					<table class="table table-sm table-bordered align-middle">
						<tbody>
							<tr>
								<th>Einladungen versandt</th>
								<td>{{ evalData.codes_ausgegeben }}</td>
							</tr>
							<tr>
								<th>Abgeschlossene Evaluierungen</th>
								<td>
									<div class="d-flex justify-content-between">
										<span>{{ evalData.countSubmitted }}</span>
										<span 
											v-if="evalData.countSubmitted < 5"
											v-tooltip
											title="Sehr wenig abgeschlossene Evaluierungen. Anonymität beachten."
										>
										&lt; 5<i class="fa fa-triangle-exclamation text-danger ms-2"></i>
										</span>
										<span 
											v-else-if="evalData.countSubmitted < 10"
											v-tooltip
											title="Wenig abgeschlossene Evaluierungen"
										>
										&lt; 10<i class="fa fa-triangle-exclamation text-warning ms-2"></i>
										</span>
									</div>
								</td>
							</tr>
							<tr>
								<th>Rücklaufquote</th>
								<td>
									<div class="d-flex justify-content-between">
										<span>{{ evalData.ruecklaufquote }}%</span>
										<span 
											v-if="evalData.ruecklaufquote < 30"
											v-tooltip
											title="Sehr geringe Rücklaufquote"
										>
										&lt; 30%<i class="fa fa-triangle-exclamation text-danger ms-2"></i>
										</span>
									</div>
								</td>
							</tr>
							<tr>
								<th>Evaluierungszeitraum</th>
								<td>{{ formattedEvalPeriod }}</td>
							</tr>
							<tr>
								<th>Durchführungszeitraum (in min)</th>
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
			</div>
		  	<!-- Dynamic content -->
			<keep-alive>
				<component 
					:is="selectedComponent" 
					:lvevaluierung_id="lvevaluierung_id"
    				:lvevaluierung_lehrveranstaltung_id="lvevaluierung_lehrveranstaltung_id"
					class="d-block"
				></component>
			</keep-alive>
		</main>
	</div>
	`
}