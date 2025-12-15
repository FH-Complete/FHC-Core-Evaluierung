import FormForm from "../../../../../js/components/Form/Form.js";
import FormInput from "../../../../../js/components/Form/Input.js";
import FhcChart from "../../../../../js/components/Chart/FhcChart.js";
import ChartHelper from "../../helpers/ChartHelper.js";
import ApiEvaluation from "../../api/evaluation";

export default {
	name: "EvaluationAuswertung",
	components: {
		FormForm,
		FormInput,
		FhcChart,
	},
	props:  {
		lvevaluierung_id: {
			type: [String, Number],
			default: null
		},
		lvevaluierung_lehrveranstaltung_id: {
			type: [String, Number],
			default: null
		}
	},
	data() {
		return {
			auswertungData: [],
			textantworten: []
		}
	},
	created() {
		if (this.lvevaluierung_id || this.lvevaluierung_lehrveranstaltung_id) {
			const apiCallAuswertungData = this.lvevaluierung_id
					? ApiEvaluation.getAuswertungDataByLve(this.lvevaluierung_id)
					: ApiEvaluation.getAuswertungDataByLveLv(this.lvevaluierung_lehrveranstaltung_id);
			const apiCallTextantworten = this.lvevaluierung_id
					? ApiEvaluation.getTextantwortenByLve(this.lvevaluierung_id)
					: ApiEvaluation.getTextantwortenByLveLv(this.lvevaluierung_lehrveranstaltung_id);

			this.$api
				.call(apiCallAuswertungData)
				.then(result => {
					this.auswertungData = result.data;
					return this.$api.call(apiCallTextantworten)
				})
				.then(result => {
					this.textantworten = result.data
				})
				.catch(error => this.$fhcAlert.handleSystemError(error));
		}
	},
	computed: {
		chartOptionsByFrageId() {
			const result = {};
			this.auswertungData.forEach(gruppe => {
				gruppe.fbFragen.forEach(frage => {
					result[frage.lvevaluierung_frage_id] = this.createEinzelfrageChart(frage);
				});
			});
			return result;
		},
		chartOptionsLvImZeitverlauf() {
			return this.createTimelineChart(this.auswertungData);
		}
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
						{ value: fbFragen.antworten.iMedian.actYear, color: "orange", width: 2, zIndex: 10, dashStyle: "Dot", label: { text: `Interp. Median ${fbFragen.antworten.iMedian.actYear}` } }
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
			const yearKeys = ["actYear", "actYearMin1", "actYearMin2"];
			const yearNames = ["Aktuelles Jahr", "Letztes Jahr", "Vor 2 Jahren"];
			return {
				chart: { type: 'line', height: 600, inverted: true },// Fragen left, Bewertungen below
				title: { text: 'LV im Zeitverlauf' },
				subtitle: { text: 'IM - Interpolierter Median der letzten 3 Jahre' },
				series: yearKeys.map((key, i) => ({
					name: yearNames[i],
					data: fbGruppen.flatMap(g => g.fbFragen.map(f => f.antworten.iMedian[key])),
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
			<div v-if="auswertungData.length > 0" v-for="(gruppe, index) in auswertungData" :key="gruppe.lvevaluierung_fragebogen_gruppe_id" 
				:class="['row py-4 mb-3 gy-3', {'bg-light': index % 2 === 0 }]">
				
				<h5 class="mb-3">{{ gruppe.bezeichnung }}</h5>
 
				<div v-for="frage in gruppe.fbFragen" :key="frage.lvevaluierung_frage_id"
					class="col-md-6 col-lg-4 col-xl-3">
					<div class="card h-100">
						<div class="card-body d-flex justify-content-center align-items-center">
							<fhc-chart :chartOptions="chartOptionsByFrageId[frage.lvevaluierung_frage_id]"></fhc-chart>
						</div>
					</div>
				</div>
			</div>
			<div v-else class="card"><div class="card-body">Keine Daten vorhanden</div></div>
		</div>
		<div class="evaluation-evaluation-auswertung-textantworten mb-3">
			<h4 class="my-5">Textantworten</h4>
			<div v-if="textantworten.length > 0" v-for="(frage, index) in textantworten" :key="frage.lvevaluierung_frage_id"
				class="row-col mb-5">
			
				<h5 class="mb-3">{{ frage.bezeichnung }}</h5>
				
				<div class="row">
					<div class="col-12">
						<div class="columns-1 columns-md-2 gap-3">
							<div v-for="antwort in frage.antworten" :key="antwort.lvevaluierung_antwort_id" class="card mb-3">
								<div class="card-body">{{ antwort.antwort }}</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div v-else class="card"><div class="card-body">Keine Daten vorhanden</div></div>
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