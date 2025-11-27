import FormForm from "../../../../../js/components/Form/Form.js";
import FormInput from "../../../../../js/components/Form/Input.js";
import FhcChart from "../../../../../js/components/Chart/FhcChart.js";
import ChartHelper from "../../helpers/ChartHelper";

export default {
	name: "EvaluationAuswertung",
	components: {
		FormForm,
		FormInput,
		FhcChart,
	},
	data() {
		return {
			chartOptionsEinzelfrage: null,
			chartOptionsLvImZeitverlauf: null
		}
	},
	created() {
		// Fetch data and create bar charts for Einzelfragen
		const fbFragen = {
				bezeichnung: 'Bitte bewerten Sie die LV',
				sort: 1,
				antworten: {
					werte: [1, 2, 3, 4, 5],
					frequencies: [2, 4, 9, 1, 3],
					iMedian: 2.6
				}
			};
		this.chartOptionsEinzelfrage = this.createEinzelfrageChart(fbFragen);

		// Fetch data and create timeline chart for LV im Zeitverlauf
		const fbGruppen = [
			{
				bezeichnung: '',
				fbFragen: [
					{ bezeichnung: 'Bitte bewerten Sie die LV', iMedian: { actY: 2.5, actYm1: 2.8, actYm2: 3.0 } },
					{ bezeichnung: 'Bitte bewerten Sie Ihren Kompetenzzuwachs durch die LV', iMedian: { actY: 3.1, actYm1: 3.4, actYm2: 3.6 } }
				]
			},
			{
				bezeichnung: 'Organisation',
				fbFragen: [
					{ bezeichnung: 'Bitte bewerten Sie die inhaltliche Abstimmung mit vorangegangenen LVs', iMedian: { actY: 3.7, actYm1: 3.9, actYm2: 4.0 } },
					{ bezeichnung: 'Bitte bewerten Sie die Workload-Verteilung in der LV', iMedian: { actY: 4.0, actYm1: 4.2, actYm2: 4.3 } }
				]
			},
			{
				bezeichnung: 'Moodle Kurs',
				fbFragen: [
					{ bezeichnung: 'Bitte bewerten Sie den inhaltlichen Aufbau und die Struktur', iMedian: { actY: 3.8, actYm1: 4.0, actYm2: 4.1 } },
					{ bezeichnung: 'Bitte bewerten Sie die Übungsmöglichkeiten/ Prüfungsvorbereitung', iMedian: { actY: 4.1, actYm1: 4.2, actYm2: 4.3 } },
					{ bezeichnung: 'Bitte bewerten Sie die Qualität der Unterlagen', iMedian: { actY: 3.5, actYm1: 4.0, actYm2: 4.6 } }
				]
			},
			{
				bezeichnung: 'Durchführung der LV',
				fbFragen: [
					{ bezeichnung: 'Bitte bewerten Inhaltsvermittlung und Verständlichkeit', iMedian: { actY: 4.0, actYm1: 4.3, actYm2: 4.3 } },
					{ bezeichnung: 'Bitte bewerten Sie die Qualität des Feedbacks durch Lehrende', iMedian: { actY: 3.9, actYm1: 3.9, actYm2: 4.0 } },
					{ bezeichnung: 'Bitte bewerten Sie die LV-Kommunikation mit Lehrenden', iMedian: { actY: 4.1, actYm1: 4.2, actYm2: 4.3 } }
				]
			},
			{
				bezeichnung: 'Infrastruktur',
				fbFragen: [
					{ bezeichnung: 'Bitte bewerten Sie die IT Infrastruktur (Hardware, Software) für die LV', iMedian: { actY: 3.5, actYm1: 3.5, actYm2: 3.5 } },
					{ bezeichnung: 'Bitte bewerten Sie die Raumausstattung für die LV', iMedian: { actY: 3.8, actYm1: 4.0, actYm2: 4.1 } },
					{ bezeichnung: 'Bitte bewerten Sie die Raumgröße für die LV', iMedian: { actY: 4.0, actYm1: 4.1, actYm2: 4.3 } }
				]
			}
		];
		this.chartOptionsLvImZeitverlauf = this.createTimelineChart(fbGruppen);
	},
	methods: {
		createEinzelfrageChart(fbFragen){
			return {
				chart: { type: 'column'},
				title: { text: fbFragen.bezeichnung},
				series: [{
					name: "Häufigkeit der Bewertungen",
					data: fbFragen.antworten.frequencies,
					pointStart: fbFragen.antworten.werte[0],
					color: "#6fcd98"
				}],
				xAxis: {
					title: { text: "Bewertung" },
					min: fbFragen.antworten.werte[0],
					max: fbFragen.antworten.werte[fbFragen.antworten.werte.length - 1],
					tickInterval: 1,
					labels: {
						useHTML: true,
						formatter: function() {
							const value = this.value; // 1..5
							const iconName = ChartHelper.getIcon(value);
							const iconClass = ChartHelper.getIconClass(value);
							return `<i class="fa fa-${iconName} fa- ${iconClass}"></i>`;
						},
						style: {
							fontSize: '14px' // icon size
						}
					},
					plotLines: [
						{ value: fbFragen.antworten.iMedian, color: "orange", width: 2, zIndex: 10, dashStyle: "Dot", label: { text: `Interp. Median ${fbFragen.antworten.iMedian.toFixed(2)}` } }
					]
				},
				yAxis: {
					min: 0,
					max: Math.max(10, Math.max(...fbFragen.antworten.frequencies)),	// default 10, only stretch up if needed
					title: { text: "Häufigkeit" },
					tickInterval: 2,
				},
				credits: { enabled: false } // remove 'Highcharts.com' label
			}
		},
		createTimelineChart(fbGruppen) {
			const yearKeys = ["actY", "actYm1", "actYm2"];
			const yearNames = ["Aktuelles Jahr", "Letztes Jahr", "Vor 2 Jahren"];
			return {
				chart: { type: 'line', height: 600, inverted: true },// Fragen left, Bewertungen below
				title: { text: 'LV im Zeitverlauf' },
				subtitle: { text: 'IM - Interpolierter Median der letzten 3 Jahre' },
				series: yearKeys.map((key, i) => ({
					name: yearNames[i],
					data: fbGruppen.flatMap(g => g.fbFragen.map(f => f.iMedian[key])),
					visible: i === 0 // only current year visible by default
				})),
				yAxis: {
					title: { text: 'Bewertung' },
					min: 1,
					max: 5,
					tickInterval: 1,
					opposite: true,
					labels: {
						useHTML: true,
						formatter: function() {
							const value = this.value; // 1..5
							const iconName = ChartHelper.getIcon(value);
							const iconClass = ChartHelper.getIconClass(value);
							return `<i class="fa fa-${iconName} fa- ${iconClass}"></i>`;
						},
						style: {
							fontSize: '16px' // icon size
						}
					},
				},
				xAxis: {
					categories: fbGruppen.flatMap(g => g.fbFragen.map((f, idx) => `Frage ${idx + 1}`)),
					plotLines: ChartHelper.getFbGruppenPlotlines(fbGruppen),
					labels: {
						useHTML: true,
						formatter: function () {
							const label = this.value;
							const index = this.pos;

							// Finde, zu welcher Gruppe die aktuelle Kategorie gehört
							let currentGroup = null;
							let runningIndex = 0;
							for (const g of fbGruppen) {
								if (index >= runningIndex && index < runningIndex + g.fbFragen.length) {
									currentGroup = g;
									break;
								}
								runningIndex += g.fbFragen.length;
							}

							// Prüfen, ob dies die erste Frage der Gruppe ist
							let isGroupStart = false;
							let checkIndex = 0;
							for (const g of fbGruppen) {
								if (index === checkIndex) {
									isGroupStart = true;
									break;
								}
								checkIndex += g.fbFragen.length;
							}

							// Gruppentitel über der ersten Frage anzeigen
							return `
						  <div class="d-flex self-align-end">
							${isGroupStart ? `<div class="text-muted me-2">${currentGroup.bezeichnung}</div>` : ''}
							<div>${label}</div>
						  </div>
						`;
						}
					}
				},
				tooltip: {
					shared: false, // Nur den Punktwert eines Jahres zeigen
					crosshairs: true,
					formatter: function () {
						// Flatten all questions from fbGruppen
						const allQuestions = fbGruppen.flatMap(g => g.fbFragen.map(f => f.bezeichnung));

						// Get the text for the current point
						const questionText = allQuestions[this.point.index] || this.key;

						return `
						<b>${questionText}</b><br/>
						${this.series.name}: <b>${Highcharts.numberFormat(this.y, 2)}</b>
					  `;
					}
				},
				legend: {
					align: 'center',
					verticalAlign: 'bottom',
					layout: 'vertical'
				},
				credits: { enabled: false }
			};
		}
	},
	template: `
	<div class="evaluation-evaluation-auswertung">
		<h3 class="mb-4">Auswertung</h3>
		<div class="evaluation-evaluation-auswertung-einzelfragen mb-3">
			<h4 class="my-4">Auswertung Einzelfragen</h4>
			<div class="row g-3 py-3 mb-3 bg-light">
				<div class="col-md-6 col-xl-4">
					<div class="card h-100">
						<div class="card-body d-flex justify-content-center align-items-center">
							<fhc-chart :chartOptions="chartOptionsEinzelfrage"></fhc-chart>
						</div>
					</div>
				</div>
				<div class="col-md-6 col-xl-4">
					<div class="card h-100">
						<div class="card-body d-flex justify-content-center align-items-center">
							<fhc-chart :chartOptions="chartOptionsEinzelfrage"></fhc-chart>
						</div>
					</div>
				</div>
			</div>
			<div class="row g-3 py-3 mb-3">
				<h5>Organisation</h5>
				<div class="col-md-6 col-xl-4">
					<div class="card h-100">
						<div class="card-body d-flex justify-content-center align-items-center">
							<fhc-chart :chartOptions="chartOptionsEinzelfrage"></fhc-chart>
						</div>
					</div>
				</div>
				<div class="col-md-6 col-xl-4">
					<div class="card h-100">
						<div class="card-body d-flex justify-content-center align-items-center">
							<fhc-chart :chartOptions="chartOptionsEinzelfrage"></fhc-chart>
						</div>
					</div>
				</div>
				<div class="col-md-6 col-xl-4">
					<div class="card h-100">
						<div class="card-body d-flex justify-content-center align-items-center">
							<fhc-chart :chartOptions="chartOptionsEinzelfrage"></fhc-chart>
						</div>
					</div>
				</div>
			</div>
			<div class="row g-3 py-3 mb-3 bg-light">
				<h5>Moodle Kurs</h5>
				<div class="col-md-6 col-xl-4">
					<div class="card h-100">
						<div class="card-body d-flex justify-content-center align-items-center">
							<fhc-chart :chartOptions="chartOptionsEinzelfrage"></fhc-chart>
						</div>
					</div>
				</div>
				<div class="col-md-6 col-xl-4">
					<div class="card h-100">
						<div class="card-body d-flex justify-content-center align-items-center">
							<fhc-chart :chartOptions="chartOptionsEinzelfrage"></fhc-chart>
						</div>
					</div>
				</div>
				<div class="col-md-6 col-xl-4">
					<div class="card h-100">
						<div class="card-body d-flex justify-content-center align-items-center">
							<fhc-chart :chartOptions="chartOptionsEinzelfrage"></fhc-chart>
						</div>
					</div>
				</div>
			</div>
			<div class="row g-3 py-3 mb-3">
				<h5>Durchführung der LV</h5>
				<div class="col-md-6 col-xl-4">
					<div class="card h-100">
						<div class="card-body d-flex justify-content-center align-items-center">
							<fhc-chart :chartOptions="chartOptionsEinzelfrage"></fhc-chart>
						</div>
					</div>
				</div>
				<div class="col-md-6 col-xl-4">
					<div class="card h-100">
						<div class="card-body d-flex justify-content-center align-items-center">
							<fhc-chart :chartOptions="chartOptionsEinzelfrage"></fhc-chart>
						</div>
					</div>
				</div>
				<div class="col-md-6 col-xl-4">
					<div class="card h-100">
						<div class="card-body d-flex justify-content-center align-items-center">
							<fhc-chart :chartOptions="chartOptionsEinzelfrage"></fhc-chart>
						</div>
					</div>
				</div>
			</div>
			<div class="row g-3 py-3 mb-3 bg-light">
			<h5>Infrastruktur</h5>
			<div class="col-md-6 col-xl-4">
				<div class="card h-100">
					<div class="card-body d-flex justify-content-center align-items-center">
						<fhc-chart :chartOptions="chartOptionsEinzelfrage"></fhc-chart>
					</div>
				</div>
			</div>
			<div class="col-md-6 col-xl-4">
				<div class="card h-100">
					<div class="card-body d-flex justify-content-center align-items-center">
						<fhc-chart :chartOptions="chartOptionsEinzelfrage"></fhc-chart>
					</div>
				</div>
			</div>
			<div class="col-md-6 col-xl-4">
				<div class="card h-100">
					<div class="card-body d-flex justify-content-center align-items-center">
						<fhc-chart :chartOptions="chartOptionsEinzelfrage"></fhc-chart>
					</div>
				</div>
			</div>
		</div>
		</div>
		<div class="evaluation-evaluation-auswertung-textantworten mb-3">
			<h4 class="my-4">Textantworten</h4>
			<div class="row row-cols-1 row-cols-lg-2 g-3">
				<div class="col">
					<div class="card card-body border-0 bg-light">Textantwort 1</div>
				</div>
				<div class="col">
					<div class="card card-body border-0 bg-light">Textantwort 2</div>
				</div>
				<div class="col">
					<div class="card card-body border-0 bg-light">Textantwort 3</div>
				</div>
			</div>
		</div>
		<div class="evaluation-evaluation-auswertung-profillinien mb-3">
			<h4 class="my-4">Profillinien</h4>
			<div class="row align-items-stretch g-3">
				<div class="col-lg-6">
					<div class="card h-100">
						<div class="card-body">
							<fhc-chart :chartOptions="chartOptionsLvImZeitverlauf"></fhc-chart>
						</div>
					</div>
				</div>
				<div class="col-lg-6">
					<div class="card h-100">
						<div class="card-body">
							<fhc-chart :chartOptions="chartOptionsLvImZeitverlauf"></fhc-chart>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>	
	`
}