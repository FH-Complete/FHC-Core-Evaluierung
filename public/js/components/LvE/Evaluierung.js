import FormForm from "../../../../../js/components/Form/Form.js";
import FragebogenFrage from "./FragebogenFrage";
export default {
	components: {
		FormForm,
		FragebogenFrage
	},
	data(){
		return {
			lvEvaluierungCode: {},
			lvEvaluierung: {},
			fbGruppen: [],
			fbAntworten: [],
			lvInfo: {},
			lvInfoExpanded: true
		}
	},
	created() {
		const code = this.$route.params.code;

		// Get EvaluierungCode
		this.$fhcApi.factory.evaluierung.getLvEvaluierungCode(code)
			.then(result => {
				this.lvEvaluierungCode = result.data;

				// Get Evaluierung
				return this.$fhcApi.factory.evaluierung.getLvEvaluierung(this.lvEvaluierungCode.lvevaluierung_id)
			})
			.then(result => {
				this.lvEvaluierung = result.data;

				return Promise.all([
					// Get LV Infos
					this.$fhcApi.factory.evaluierung.getLvInfo(this.lvEvaluierung.lvevaluierung_lehrveranstaltung_id),

					// Get full initial Fragebogen
					this.$fhcApi.factory.fragebogen.getInitFragebogen(this.lvEvaluierung.fragebogen_id),
				])
			})
			.then(([resultLvInfo, resultInitFragebogen]) => {
				this.lvInfo = resultLvInfo.data;
				this.fbGruppen = resultInitFragebogen.data;

				// Build initital fbAntworten antwort objects
				resultInitFragebogen.data.forEach(gruppe => {
					gruppe.fbFrage.forEach(frage => {
						this.fbAntworten.push({
							lvevaluierung_code_id: this.lvEvaluierungCode.lvevaluierung_code_id,
							lvevaluierung_frage_id: frage.lvevaluierung_frage_id,
							lvevaluierung_frage_antwort_id: null,
							antwort: null
						});
					});
				});

				// Start Evaluierung
				return this.$fhcApi.factory.evaluierung.setStartzeit(this.lvEvaluierungCode.lvevaluierung_code_id)
			})
			.catch(error => this.$fhcAlert.handleSystemError(error));
	},
	mounted() {
		this.updateLvInfoExpanded();
		window.addEventListener('resize', this.updateLvInfoExpanded);
	},
	beforeUnmount() {
		window.removeEventListener('resize', this.updateLvInfoExpanded);
	},
	methods: {
		findAntwortObj(lvevaluierung_frage_id) {
			return this.fbAntworten.find(a => a.lvevaluierung_frage_id === lvevaluierung_frage_id);
		},
		onSubmit(){

			this.$fhcApi.factory.evaluierung.saveAntworten(this.fbAntworten)
				.then(result => {
					// TODO....hier weitermache. ein timeout nach saved, dann beim starten check, ob es zu dem code bereits schon ausgefüllt.
					// todo ggf. noch clientseitig check required.
					// TODO jedenfalls noch setEndeZeit!! und Dauer bzw Timer
					if (result.data.length > 0) {
						this.$fhcAlert.alertSuccess('Saved!')
						//this.$router.push({name: 'Logout'}); // todo umcomment after testing
					}
					else {
						this.$fhcAlert.alertInfo('No data was saved. Did you answered the questions?')
					}
				})
				.catch(error => this.$fhcAlert.handleSystemError(error));
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
		},
		updateLvInfoExpanded() {
			this.lvInfoExpanded = window.innerWidth > 992;
		},
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
											v-model:lvevaluierung_frage_id="findAntwortObj(frage.lvevaluierung_frage_id).lvevaluierung_frage_id"
											v-model:lvevaluierung_frage_antwort_id="findAntwortObj(frage.lvevaluierung_frage_id).lvevaluierung_frage_antwort_id"
											v-model:antwort="findAntwortObj(frage.lvevaluierung_frage_id).antwort"
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
									{{ fbGruppe.bezeichnung_by_language }}
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
													{{ cGruppe.bezeichnung_by_language }}
												</button>
												</h2>
												<div :id="'flush-collapse' + cGruppe.lvevaluierung_fragebogen_gruppe_id" 
													class="accordion-collapse collapse" 
													:aria-labelledby="'flush-heading' + cGruppe.lvevaluierung_fragebogen_gruppe_id" 
													:data-bs-parent="'#accordionFlush' + fbGruppe.lvevaluierung_fragebogen_gruppe_id">
													<div class="accordion-body">
													
														<!-- Loop Fragebogen Fragen -->
														<template v-for="(frage, fIndex) in cGruppe.fbFrage" :key="fIndex">
															<fragebogen-frage 
																:frage="frage" 
																v-model:lvevaluierung_frage_id="findAntwortObj(frage.lvevaluierung_frage_id).lvevaluierung_frage_id"
																v-model:lvevaluierung_frage_antwort_id="findAntwortObj(frage.lvevaluierung_frage_id).lvevaluierung_frage_antwort_id"
																v-model:antwort="findAntwortObj(frage.lvevaluierung_frage_id).antwort"
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
					<div class="lvinfo-accordion accordion mb-3 mt-md-3" id="accordionLvinfo">
					  	<div class="accordion-item">
							<h2 class="accordion-header" id="headingOne">
								<button 
									class="accordion-button bg-white fw-bold text-dark fs-5" 
									:class="{collapsed: !lvInfoExpanded}"
									type="button" 
									data-bs-toggle="collapse" 
									data-bs-target="#collapseLvinfo" 
									:aria-expanded="lvInfoExpanded" 
									aria-controls="collapseLvinfo"
								>
									{{lvInfo.bezeichnung_by_language}}
								</button>
							</h2>
							<div 
								id="collapseLvinfo" 
								class="accordion-collapse collapse"
								:class="{show: lvInfoExpanded}"
								aria-labelledby="headingOne" 
								data-bs-parent="#accordionLvinfo"
							>
								<div class="accordion-body">
									<div class="d-flex">
										<div class="flex-shrink-0 me-3 fw-bold">LektorInnen:</div>
										<div class="flex-fill text-start">
											<div v-for="(lehrende, lIndex) in lvInfo.lehrende" :key="lIndex">
												{{ lehrende.vorname }} {{lehrende.nachname}}
											</div>
										</div>
									</div>
									<div class="d-flex">
										<div class="flex-shrink-0 me-3 fw-bold">ECTS:</div>
										<div class="flex-fill text-start">{{lvInfo.ects}}</div>
									</div>
								</div>
							</div>
					  </div>
				  </div><!-- .accordion LV Infos -->
			
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