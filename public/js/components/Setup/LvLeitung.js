import FormForm from "../../../../../js/components/Form/Form.js";
import FormInput from "../../../../../js/components/Form/Input.js";
import Infobox from "../../widgets/Infobox";
import DateHelper from "../../helpers/DateHelper";

export default {
	components: {
		FormForm,
		FormInput,
		Infobox
	},
	created() {
		console.log('Component created');
	},
	data() {
		return {
			lvs: [
				{
					bezeichnung: 'Lehrveranstaltung',
					startzeit: null,
					endezeit: null,
					dauer: '00:00:00',
					lv_aufgeteilt: false
				},
				{
					bezeichnung: 'Lehrveranstaltung',
					startzeit: null,
					endezeit: null,
					dauer: '00:00:00',
					lv_aufgeteilt: false
				},
				{
					bezeichnung: 'Lehrveranstaltung',
					startzeit: null,
					endezeit: null,
					dauer: '00:00:00',
					lv_aufgeteilt: false
				},
				{
					bezeichnung: 'Lehrveranstaltung',
					startzeit: null,
					endezeit: null,
					dauer: '00:00:00',
					lv_aufgeteilt: false
				},
				{
					bezeichnung: 'Lehrveranstaltung',
					startzeit: null,
					endezeit: null,
					dauer: '00:00:00',
					lv_aufgeteilt: false
				}
			],
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
	mounted() {
		// todo: tbd in API call
		this.lvs.forEach((lv) => {
			if (!lv.startzeit) {
				lv.startzeit = DateHelper.formatToSqlTimestamp(new Date());  // Sets current date/time
			}
		})
	},
	methods: {},
	template: `
	<div class="lve-starten-body container-fluid">
		<h1>LV-Evaluierung starten</h1>
		<div class="row">
			<div class="accordion" id="accordionFlushExample">
				<template v-for="(lv, index) in lvs" :key="index">	
					<div class="accordion-item">
						<h2 class="accordion-header" :id="'flush-heading' + index">
						  	<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" :data-bs-target="'#flush-collapse' + index" aria-expanded="false" aria-controls="flush-collapseOne">
							{{ lv.bezeichnung + index }}
						  </button>
						</h2>
						<div :id="'flush-collapse' + index" class="accordion-collapse collapse" :aria-labelledby="'flush-heading' + index" data-bs-parent="#accordionFlushExample">
						  	<div class="p-md-3">
								<form-form ref="form" class="lve-starten">
									<div class="card">
										<div class="card-header bg-white py-3">
											<div class="form-check form-check-inline ps-0">
												<form-input
													label="Gesamt-LV evaluieren"
													class="form-check-input"
													type="radio"
													:value="false"
													v-model="lv.lv_aufgeteilt"
												>
												</form-input>
											</div>
											<div class="form-check form-check-inline ps-0">
												<form-input
													label="LV auf Gruppenbasis evaluieren"
													class="form-check-input"
													type="radio"
													:value="true"
													v-model="lv.lv_aufgeteilt"
												>
												</form-input>
											</div>
										</div>
										<!-- Wenn Gesamt-LV evaluiert wird -->
										<div v-if="lv.lv_aufgeteilt === false" >
											<!-- Evaluierungskriterien festlegen -->
											<div class="card-body">
												<h5 class="card-title">Evaluierungskriterien festlegen</h5>
												<div class="row gx-5">
													<!-- Form Inputs + Button -->
													<div class="col-12 col-lg-6 order-1">
														<div class="d-flex flex-wrap flex-md-nowrap gap-3">
															<div class="flex-grow-1">
																<form-input 
																	label="Startdatum" 
																	type="datepicker"
																	v-model="lv.startzeit"
																	name="lv.startzeit"
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
																	v-model="lv.endezeit"
																	name="lv.endezeit"
																	locale="de"
																	format="dd.MM.yyyy HH:mm"
																	model-type="yyyy-MM-dd HH:mm:ss"
																	:minutes-increment="5"
																	:auto-apply="true"
																	:start-time="{hours: 0, minutes: 0}"
																>
																</form-input>
															</div>
															<div class="flex-grow-1">
																<form-input
																	label="Dauer (HH:MM)" 
																	type="datepicker"
																	v-model="lv.dauer"
																	name="lv.dauer"
																	locale="de"								
																	:time-picker="true"
  																	:is-24="true"
  																	:hide-input-icon="true"
  																	:minutes-increment="5"
																	format="HH:mm"
																	model-type="HH:mm:ss"
																	:text-input="true"
																	:auto-apply="true"
																	:disabled="true"
																>
																</form-input>
															</div>
														</div>
														<div class="d-grid d-md-block">
															<button class="btn btn-primary mt-3 me-2">Speichern</button>
<!--															<button class="btn btn-primary mt-3">Studierendenlinks versenden</button>-->
														</div>
													</div>
													<!-- Infobox -->
													<div class="col-12 col-lg-6 order-2 mt-3 mt-lg-0">
														<div class="bg-light border rounded p-3 h-100">
															<Infobox 
																collapseBreakpoint="lg" 
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
													<div class="col-12 col-lg-6 order-1">
														<div class="d-grid d-md-block">
															<button class="btn btn-success mt-3">Studierendenlinks versenden</button>
														</div>
													</div>
													<!-- Info Box -->
													<div class="col-12 col-lg-6 order-2 mt-3 mt-lg-0">
														<div class="bg-light border rounded p-3 h-100">
															<Infobox :text="infoStudierendenlink"></Infobox>
														</div>
													</div>
												</div><!--.end row -->
											</div><!--.end card-footer -->
										</div><!--.end v-if -->
										<!-- Wenn LV auf Gruppenbasis evaluiert wird -->
										<div v-if="lv.lv_aufgeteilt === true" >
											<div class="card-body">
<!--												<h5 class="card-title">Evaluierungskriterien durch Lektor*innen festlegen lassen</h5>-->
												<div class="row">
													<!-- Button -->
													<div class="col-12 col-md-6 order-1">
														<div class="d-grid d-lg-block">
															<button class="btn btn-success mt-3">Mail an Lektor*Innen versenden</button>
													  	</div>
													</div>
													<!-- Info Box -->
													<div class="col-12 col-lg-6 order-2 mt-3 mt-lg-0">
														<div class="bg-light border rounded p-3 h-100">
															<Infobox 
																collapseBreakpoint="lg" 
																:text="infoGruppenbasis" 
															>
															</Infobox>
														</div>
													</div><!--.end Infobox cols -->
												</div><!--.end row -->
											</div><!--.end card-body -->
										</div><!--.end v-if -->
									</div><!--.end card -->
								</form-form><!--.end form -->
							</div><!--.end p-md-3 -->
						</div><!--.end accordion-collapse -->
					  </div><!--.end accordion-item -->
				</template><!--.end template v-for -->
			</div><!--.end accordion -->
		</div><!--.end row -->
	</div><!--.end div -->
	`
}