export default {
	name: "EvaluationReflexionUebersicht",
	props: {
		uebersichtData: {}
	},
	computed: {
		ausfuellquoteClass() {
			const aqp = this.uebersichtData.meta.ausfuellquoteProzent

			if (aqp === 100) return 'bg-success-subtle'
			if (aqp === 0) return 'bg-danger-subtle'

			return ''
		}
	},
	template: `
	<!-- Abschnitt Übersicht -->
	<div v-if="uebersichtData.meta" class="evaluation-evaluation-reflexion-uebersicht mb-3">
		<h4 class="mt-5 mb-3">Übersicht</h4>
		<div class="row mt-4">
			<div class="col-12">
				<div class="d-flex flex-wrap gap-3">
					<!-- Antwort Übersicht -->
					<div v-for="(row, key) in uebersichtData.verpflichtend"
						:key="key"
						class="evaluation-card-flex" 
					>
						<div class="card h-100">
							<!-- Header -->
							<div class="card-header bg-white">
								<span class="fw-bold">{{ row.label }}</span>
							</div>
							<!-- Body -->
							<div class="card-body">
								<!-- Table -->
								<table class="table mb-3">
									<thead>
										<tr>
											<th>Antworten kumuliert</th>
											<th class="text-end">Σ</th>
											<th class="text-end fw-normal" v-if="uebersichtData.meta.showUebersichtOptionale">
												Σ (optional)
											</th>
										</tr>
									</thead>
									<tbody>
										<tr v-for="(antwort, valKey) in row.values" :key="valKey">
											<td class="text-start">{{ antwort.label }}</td>
											<td class="fw-bold text-end">{{ antwort.anzahl }}</td>
											<td v-if="uebersichtData.meta.showUebersichtOptionale" class="text-end">
												{{
													uebersichtData.optional[key].values[valKey]
													? uebersichtData.optional[key].values[valKey].anzahl
													: 0
												}}
											</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div><!--.card -->
					</div><!--.div antwort übersicht  -->
					<!-- Ausfüllquote -->
					<div class="evaluation-card-flex">
						<div class="card h-100" :class="ausfuellquoteClass">
							<!-- Header -->
							<div class="card-header text-center">
								<span class="fw-bold">Ausfüllquote</span>
							</div>
							<!-- Body -->
							<div class="card-body d-flex flex-column justify-content-center align-items-center text-center">
								<div class="fw-bold display-5 mb-3">
									{{ uebersichtData.meta.ausfuellquoteProzent }} %
								</div>
								<div class="fw-bold">
									{{ uebersichtData.meta.ausgefuellteVerpflichtendeReflexionen }} ausgefüllt /
									{{ uebersichtData.meta.gesamtVerpflichtendeReflexionen }} total
								</div>
							</div>
							<!-- Footer -->
							<div class="card-footer text-center">
								<small>Ausgefüllte LV-Reflexionen / Gesamt-LV-Reflexionen (verpflichtend)</small>
							</div>
						</div><!--.card -->
					</div><!--.div ausfüllquote -->
				</div><!--.d-flex-->
		 	</div><!--.col -->
		</div><!--.row -->	
	</div><!--.end Abschnitt Übersicht-->
	`
}