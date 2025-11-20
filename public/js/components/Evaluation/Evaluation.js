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
		}
	},
	created() {
		console.log("Eval ID:", this.lvevaluierung_id);
		console.log("LVE-LV ID:", this.lvevaluierung_lehrveranstaltung_id);
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
						LV-Reflexion <small>Grundlagen der Programmierung</small>
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
					<label class="btn btn-outline-primary" for="option3">
						<i class="fa fa-medal"></i><span class="d-none d-lg-inline-block ms-2" disabled="'true'">LV Weiterentwicklung</span>
					</label>
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
								<td>BBE - 1: Grundlagen der Programmierung - VZ</td>
							</tr>
							<tr>
								<th>Verpflichtende Evaluation</th>
								<td>Ja</td>
							</tr>
							<tr>
								<th>Evaluationseinheit</th>
								<td>Gesamt-LV</td>
							</tr>
							<tr>
								<th>LV-Leitung</th>
								<td>Cristina Hainberger</td>
							</tr>
							<tr>
								<th>Lehrende</th>
								<td>Cristina Hainberger, Andreas Österreicher</td>
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
								<td>20</td>
							</tr>
							<tr>
								<th>Abgeschlossene Evaluierungen</th>
								<td>
									<div class="d-flex justify-content-between">
										<span>15</span>
										<span 
											v-if="true"
											data-bs-toggle="tooltip" 
											data-bs-placement="top" 
											title="Sehr wenig abgeschlossene Evaluierungen. Anonymität beachten."
										>
										&lt; 5<i class="fa fa-triangle-exclamation text-danger ms-2"></i>
										</span>
										<span 
											v-if="true"
											data-bs-toggle="tooltip" 
											data-bs-placement="top" 
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
										<span>75%</span>
										<span 
											v-if="true"
											data-bs-toggle="tooltip" 
											data-bs-placement="top" 
											title="Sehr geringe Rücklaufquote"
										>
										&lt; 30%<i class="fa fa-triangle-exclamation text-danger ms-2"></i>
										</span>
									</div>
								</td>
							</tr>
							<tr>
								<th>Evaluierungszeitraum</th>
								<td>01.11.2025 – 03.11.2025</td>
							</tr>
							<tr>
								<th>Durchführungszeitraum (in min)</th>
								<td>
									<div class="d-flex justify-content-between">
										<span>Ø 7,5</span>
										<span>Min 5</span>
										<span>Max 10</span>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		  	<!-- Dynamic content -->
			<keep-alive>
				<component :is="selectedComponent" class="d-block"></component>
			</keep-alive>
		</main>
	</div>
	`
}