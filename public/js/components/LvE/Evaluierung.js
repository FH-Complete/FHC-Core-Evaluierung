import FormForm from "../../../../../js/components/Form/Form.js";
import FragebogenFrage from "./FragebogenFrage";
export default {
	components: {
		FormForm,
		FragebogenFrage
	},
	data(){
		return {
			fbAntworten: {},
			fbGruppen: [
				{
					lvevaluierung_fragebogen_gruppe_id: 1,
					typ: "card",
					bezeichnung: '',
					sort: 1,
					style: '',
					fbFrage: [
						{
							lvevaluierung_frage_id: 1,
							typ: "singleresponse",
							bezeichnung: "Wie super war die LV?",
							sort: 1,
							fbFrageAntwort: [
								{
									lvevaluierung_frage_antwort_id: 1,
									bezeichnung: "Sehr gut",
									sort: 1,
									wert: 1
								},
								{
									lvevaluierung_frage_antwort_id: 2,
									bezeichnung: "",
									sort: 2,
									wert: 2
								},
								{
									lvevaluierung_frage_antwort_id: 3,
									bezeichnung: "",
									sort: 3,
									wert: 3
								},
								{
									lvevaluierung_frage_antwort_id: 4,
									bezeichnung: "",
									sort: 4,
									wert: 4
								},
								{
									lvevaluierung_frage_antwort_id: 5,
									bezeichnung: "Sehr schlecht",
									sort: 5,
									wert: 5
								}
							]
						},
						{
							lvevaluierung_frage_id: 2,
							typ: "singleresponse",
							bezeichnung: "Wie toll war die SW?",
							sort: 2,
							fbFrageAntwort: [
								{
									lvevaluierung_frage_antwort_id: 6,
									bezeichnung: "Sehr gut",
									sort: 1,
									wert: 1
								},
								{
									lvevaluierung_frage_antwort_id: 7,
									bezeichnung: "",
									sort: 2,
									wert: 2
								},
								{
									lvevaluierung_frage_antwort_id: 8,
									bezeichnung: "",
									sort: 3,
									wert: 3
								},
								{
									lvevaluierung_frage_antwort_id: 9,
									bezeichnung: "",
									sort: 4,
									wert: 4
								},
								{
									lvevaluierung_frage_antwort_id: 10,
									bezeichnung: "Sehr schlecht",
									sort: 5,
									wert: 5
								}
							]
						}
					]
				},
				{
					lvevaluierung_fragebogen_gruppe_id: 2,
					typ: "label",
					bezeichnung: "Möchten Sie konkretes Feedback zu folgenden Bereichen geben?",
					sort: 2,
					style: '',
					fbFrage: []
				},
				{
					lvevaluierung_fragebogen_gruppe_id: 3,
					typ: "group",
					bezeichnung: 'Organisation',
					sort: 3,
					style: 'background-color: lightgrey',
					fbFrage: [
						{
							lvevaluierung_frage_id: 3,
							typ: "singleresponse",
							bezeichnung: "Alles Organisation locker?",
							sort: 1,
							fbFrageAntwort: [
								{
									lvevaluierung_frage_antwort_id: 11,
									bezeichnung: "Sehr gut",
									sort: 1,
									wert: 1
								},
								{
									lvevaluierung_frage_antwort_id: 12,
									bezeichnung: "",
									sort: 2,
									wert: 2
								},
								{
									lvevaluierung_frage_antwort_id: 13,
									bezeichnung: "",
									sort: 3,
									wert: 3
								},
								{
									lvevaluierung_frage_antwort_id: 14,
									bezeichnung: "",
									sort: 4,
									wert: 4
								},
								{
									lvevaluierung_frage_antwort_id: 15,
									bezeichnung: "Sehr schlecht",
									sort: 5,
									wert: 5
								}
							]
						}
					]
				},
				{
					lvevaluierung_fragebogen_gruppe_id: 4,
					typ: "group",
					bezeichnung: 'Moodle Kurs',
					sort: 4,
					style: 'background-color: lightsteelblue;',
					fbFrage: [
						{
							lvevaluierung_frage_id: 4,
							typ: "singleresponse",
							bezeichnung: "Alles Moodle locker?",
							sort: 1,
							fbFrageAntwort: [
								{
									lvevaluierung_frage_antwort_id: 16,
									bezeichnung: "Sehr gut",
									sort: 1,
									wert: 1
								},
								{
									lvevaluierung_frage_antwort_id: 17,
									bezeichnung: "",
									sort: 2,
									wert: 2
								},
								{
									lvevaluierung_frage_antwort_id: 18,
									bezeichnung: "",
									sort: 3,
									wert: 3
								},
								{
									lvevaluierung_frage_antwort_id: 19,
									bezeichnung: "",
									sort: 4,
									wert: 4
								},
								{
									lvevaluierung_frage_antwort_id: 20,
									bezeichnung: "Sehr schlecht",
									sort: 5,
									wert: 5
								}
							]
						}
					]
				},
				{
					lvevaluierung_fragebogen_gruppe_id: 7,
					typ: "group",
					bezeichnung: 'Durchführung der LV',
					sort: 5,
					style: 'background-color: lightblue',
					fbFrage: [
						{
							lvevaluierung_frage_id: 7,
							typ: "singleresponse",
							bezeichnung: "Alles LV locker?",
							sort: 1,
							fbFrageAntwort: [
								{
									lvevaluierung_frage_antwort_id: 22,
									bezeichnung: "Sehr gut",
									sort: 1,
									wert: 1
								},
								{
									lvevaluierung_frage_antwort_id: 23,
									bezeichnung: "",
									sort: 2,
									wert: 2
								},
								{
									lvevaluierung_frage_antwort_id: 24,
									bezeichnung: "",
									sort: 3,
									wert: 3
								},
								{
									lvevaluierung_frage_antwort_id: 25,
									bezeichnung: "",
									sort: 4,
									wert: 4
								},
								{
									lvevaluierung_frage_antwort_id: 26,
									bezeichnung: "Sehr schlecht",
									sort: 5,
									wert: 5
								}
							]
						}
					],
				},
				{
					lvevaluierung_fragebogen_gruppe_id: 8,
					typ: "group",
					bezeichnung: 'Infrastruktur (Ausstattung)',
					sort: 6,
					style: 'background-color: lightyellow',
					fbFrage: [
						{
							lvevaluierung_frage_id: 8,
							typ: "singleresponse",
							bezeichnung: "Alles Infrastruktur locker?",
							sort: 1,
							fbFrageAntwort: [
								{
									lvevaluierung_frage_antwort_id: 27,
									bezeichnung: "Sehr gut",
									sort: 1,
									wert: 1
								},
								{
									lvevaluierung_frage_antwort_id: 28,
									bezeichnung: "",
									sort: 2,
									wert: 2
								},
								{
									lvevaluierung_frage_antwort_id: 29,
									bezeichnung: "",
									sort: 3,
									wert: 3
								},
								{
									lvevaluierung_frage_antwort_id: 30,
									bezeichnung: "",
									sort: 4,
									wert: 4
								},
								{
									lvevaluierung_frage_antwort_id: 31,
									bezeichnung: "Sehr schlecht",
									sort: 5,
									wert: 5
								}
							]
						}
					]
				},
				{
					lvevaluierung_fragebogen_gruppe_id: 5,
					typ: "card",
					bezeichnung: '',
					sort: 7,
					style: '',
					fbFrage: [
						{
							lvevaluierung_frage_id: 5,
							typ: "text",
							bezeichnung: "Meine offene Frage 1",
							sort: 1,
							fbFrageAntwort: [

							]
						},
						{
							lvevaluierung_frage_id: 6,
							typ: "text",
							bezeichnung: "Meine offene Frage 2",
							sort: 2,
							fbFrageAntwort: [
								{
									lvevaluierung_frage_antwort_id: 21,
									bezeichnung: "",
									sort: 1,
									wert: 0
								}
							]
						}
					]
				}
			]
		}
	},
	created() {
		console.log('Component created');
		console.log(this.$route.params.lvevaluierung_id);
	},
	methods: {
		onSubmit(){
			console.table(this.fbAntworten);
			//console.log(this.$refs.form);
			//this.$router.push({name: 'Logout'}); // todo umcomment after testing
		},
		getClusteredGruppen(startIndex) {
			const arr = this.fbGruppen;
			const clusteredGruppe = [];

			// Sort Fragebogengruppen by their sort number
			arr.sort((a, b) => a.sort - b.sort);

			// Return aufeinanderfolgende Fragebogengruppen of typ 'group' to the actual Accordion
			let i = startIndex;
			while (i < arr.length && arr[i].typ === 'group') {
				clusteredGruppe.push(arr[i]);
				i++;
			}
			return clusteredGruppe;
		}
	},
	template: `
	<div class="lve-evaluierung d-lg-flex flex-column min-vh-100 py-5 ps-lg-2 overflow-auto">
		<form-form ref="form" class="lve-evaluierung" @submit.prevent="onSubmit">
			<!-- LV-Evaluierung-Body -->
			<div class="lve-evaluierung-body row flex-grow-1">
			
				<!-- LV-Evaluierung Fragen -->
				<div class="col-12 col-lg-8 order-2 order-lg-1 mb-3">
				
					<template v-for="(fbGruppe, index) in fbGruppen" :key="index">
					
						<!-- Fragebogengruppe Card -->
						<div v-if="fbGruppe.typ === 'card'">
							<div class="lve-fb-card card my-lg-3 border-0">
								<div class="card-body px-0">
								
									<!-- Loop Fragebogen Fragen-->
									<template v-for="(frage, fIndex) in fbGruppe.fbFrage" :key="fIndex">
										<fragebogen-frage 
											:frage="frage"
											v-model="fbAntworten[frage.lvevaluierung_frage_id]"
										>
										</fragebogen-frage>
									</template>
								</div>
							</div><!-- .card Fragebogengruppe Card-->
						</div><!-- .endif Fragebogengruppe Card-->
						
						<!-- Fragenbogengruppe Label -->
						<div v-if="fbGruppe.typ === 'label'">
							<div class="lve-fb-label card my-lg-3 text-center border-0">
								<div class="card-title pt-3 fw-bold">
									{{ fbGruppe.bezeichnung }}
								</div>
							</div><!-- .card Fragebogengruppe Label-->
						</div><!-- .endif Fragebogengruppe Label-->
						
						<!-- Fragenbogengruppe Group. Bilde mehrere Accordions, wenn typ 'group' nicht aufeinander folgen -->
						<div v-if="fbGruppe.typ === 'group' && (index == 0 || fbGruppen[index-1].typ !== 'group')">
							<div class="lve-fb-group card my-lg-3 border-0">
								<div class="card-body px-0 px-md-3">
									<div class="lve-fb-group-accordion accordion accordion-flush border rounded" 
										:id="'accordionFlush' + fbGruppe.lvevaluierung_fragebogen_gruppe_id">
										
										<!-- Loop zusammenhaengende Gruppen vom typ group -->
										<template v-for="(cGruppe, cIndex) in getClusteredGruppen(index)" :key="cIndex">
											<div class="accordion-item">
												<h2 class="accordion-header" :id="'flush-heading' + cGruppe.lvevaluierung_fragebogen_gruppe_id">
												<button 
													class="accordion-button collapsed fw-bold" 
													type="button" 
													data-bs-toggle="collapse" 
													:data-bs-target="'#flush-collapse' + cGruppe.lvevaluierung_fragebogen_gruppe_id" 
													aria-expanded="false" 
													:aria-controls="'flush-collapse' + cGruppe.lvevaluierung_fragebogen_gruppe_id"
													:style="cGruppe.style"
												>
													{{ cGruppe.bezeichnung }}
												</button>
												</h2>
												<div :id="'flush-collapse' + cGruppe.lvevaluierung_fragebogen_gruppe_id" 
													class="accordion-collapse collapse" 
													:aria-labelledby="'flush-heading' + cGruppe.lvevaluierung_fragebogen_gruppe_id" 
													:data-bs-parent="'#accordionFlush' + fbGruppe.lvevaluierung_fragebogen_gruppe_id">
													<div class="accordion-body">
													
														<!-- Loop Fragebogen Fragen -->
														<template v-for="(frage, fbIndex) in cGruppe.fbFrage" :key="fbIndex">
															<fragebogen-frage 
																:frage="frage" 
																v-model="fbAntworten[frage.lvevaluierung_frage_id]"
															>
															</fragebogen-frage>
														</template>
													</div>
												</div>
											</div>
										</template>
									</div>	
								</div>
							</div><!-- .card Fragebogengruppe Group -->
						</div><!-- .endif Fragebogengruppe Group-->
					</template>
				</div><!-- . col-12 LV Fragen -->
		
				<!-- LV Infos + Countdown (lg only) -->
				<div class="col-12 col-lg-4 order-1 order-lg-2 d-flex flex-column">
					<!-- LV Infos -->
					<div class="card mb-3 mt-md-3">
						<div class="card-body">LV Infos</div>
					</div>
			
					<!-- Countdown for lg+ only -->
					<div class="card w-100 text-center d-none d-lg-flex flex-grow-1">
						<div class="card-body d-flex flex-column align-items-center justify-content-center">
							<i class="fa-regular fa-clock fa-8x mb-3"></i>Countdown lg-xl
						</div>
					</div>
				</div><!-- .col LV Infos + Countdown (lg only) -->
			</div><!-- .row lv-evaluierung-body -->
			
			<!-- LV-Evaluierung-Footer -->
			<div class="lve-evaluierung-footer row fixed-bottom px-3 py-2 bg-light">
		
			<!-- Countdown for sm/md only -->
			<div class="col-8 d-lg-none d-flex align-items-center">
				<i class="fa-regular fa-clock fa-2x me-2"></i>Countdown sm-md
			</div>
			
			<!-- Submit button -->
			<div class="col-4 col-lg-12 text-end">
				<button class="btn btn-primary" type="submit">Submit</button>
			</div>	
		</div><!-- .row lv-evaluierung-footer-->
	</form-form>
	</div>`
}