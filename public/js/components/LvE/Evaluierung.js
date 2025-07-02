import FormForm from "../../../../../js/components/Form/Form.js";
import FragebogenFrage from "./FragebogenFrage";
import Countdown from "./Countdown";
import DateHelper from "../../helpers/DateHelper";
import ApiEvaluierung from "../../api/evaluierung.js";
import ApiFragebogen from "../../api/fragebogen.js";

export default {
	components: {
		FormForm,
		FragebogenFrage,
		Countdown
	},
	inject: ['selectedLanguage'],
	data(){
		return {
			lvEvaluierungCode: {},
			lvEvaluierung: {},
			fbGruppen: [],
			fbAntworten: [],
			lvInfo: {},
			showCountdown: false,
			windowWidth: window.innerWidth
		}
	},
	computed: {
		isScreenLg() { return this.windowWidth > 992 },
		isScreenXl() { return this.windowWidth > 1800 }
	},
	watch: {
		selectedLanguage(newVal) {
			// If selected Language changed, re-fetch  LV-Info, Fragebogen and Antworten to get in new User Language
			if (newVal) {
				// Get full initial Fragebogen
				this.$api
					.call(ApiEvaluierung.getLvInfo(this.lvEvaluierung.lvevaluierung_lehrveranstaltung_id))
					.then(resultLvInfo => {
						this.lvInfo = resultLvInfo.data;

						return this.$api.call(ApiFragebogen.getInitFragebogen(this.lvEvaluierung.fragebogen_id))
					})
					.then(resultInitFragebogen => {
						this.fbGruppen = resultInitFragebogen.data;
						this.initializeAntworten(this.fbGruppen);
					});
			}
		}
	},
	created() {
		const code = this.$route.params.code;

		// Get EvaluierungCode
		this.$api
			.call(ApiEvaluierung.getLvEvaluierungCode(code))
			.then(result => this.logoutIfInvalidCode(result))
			.then(result => {
				this.lvEvaluierungCode = result.data;

				// Get Evaluierung
				return this.$api.call(ApiEvaluierung.getLvEvaluierung(this.lvEvaluierungCode.lvevaluierung_id))
			})
			.then(result => this.lvEvaluierung = result.data)
			.then(() =>
				Promise.all([
					this.logoutIfEvaluierungPeriodClosed(),
					this.logoutIfEvaluierungSubmitted()
				])
			)
			.then(() =>
				Promise.all([
					// Get LV Infos
					this.$api.call(ApiEvaluierung.getLvInfo(this.lvEvaluierung.lvevaluierung_lehrveranstaltung_id)),

					// Get full initial Fragebogen
					this.$api.call(ApiFragebogen.getInitFragebogen(this.lvEvaluierung.fragebogen_id)),
				])
			)
			.then(([resultLvInfo, resultInitFragebogen]) => {
				this.lvInfo = resultLvInfo.data;
				this.fbGruppen = resultInitFragebogen.data;
				this.initializeAntworten(this.fbGruppen);

				// Start Evaluierung
				return this.$api.call(ApiEvaluierung.setStartzeit(this.lvEvaluierungCode.lvevaluierung_code_id))
			})
			.then(() => {
				// Show Countdown, if Evaluierung Dauer is set
				if (this.lvEvaluierung.dauer && this.lvEvaluierung.dauer > "00:00:00")
				{
					this.showCountdown = true;
				}
			})
			.catch(error => console.warn(error));
	},
	mounted() {
		this.handleResize();
		window.addEventListener('resize', this.handleResize);
	},
	beforeUnmount() {
		window.removeEventListener('resize', this.handleResize);
	},
	methods: {
		onSubmit(){
			// SaveAntworten
			this.$api.call(ApiEvaluierung.saveAntwortenAndSetEndezeit(
				this.lvEvaluierungCode.lvevaluierung_code_id,
				this.fbAntworten
			))
			.then(result => {
				// On Success
				if (result.data && result.data.length > 0) {
					// Change to Logout Site
					this.$router.push({name: 'Logout'});
				}
				else {
					this.$fhcAlert.alertInfo(this.$p.t('fragebogen/evaluierungAntwortenNichtUebermittelt'))
				}
			})
			.catch(error => this.$fhcAlert.handleSystemError(error));
		},
		initializeAntworten(fbGruppen) {
			// Build initital fbAntworten antwort objects
			fbGruppen.forEach(gruppe => {
				gruppe.fbFrage.forEach(frage => {
					this.fbAntworten.push({
						lvevaluierung_code_id: this.lvEvaluierungCode.lvevaluierung_code_id,
						lvevaluierung_frage_id: frage.lvevaluierung_frage_id,
						lvevaluierung_frage_antwort_id: null,
						antwort: null
					});
				});
			});
		},
		handleResize() {
			this.windowWidth = window.innerWidth;
		},
		logoutIfInvalidCode(result) {
			if (result.data === false) {
				this.$router.push({
					name: 'Logout',
					query: {
						reason: 'evaulierungCodeExistiertNicht'
					}
				});
				return Promise.reject('EvaluierungCode does not exist');
			}
			return result;
		},
		logoutIfEvaluierungPeriodClosed(){
			const startzeit = new Date(this.lvEvaluierung.startzeit);
			const endezeit = new Date(this.lvEvaluierung.endezeit);
			const now = new Date();

			// Redirect if Evaluation period is over
			if (now > endezeit) {
				this.$router.push({
					name: 'Logout',
					query: {
						reason: 'evaluierungPeriodeBeendet',
						date: DateHelper.formatDate(this.lvEvaluierung.endezeit)
					}
				});

				return Promise.reject('Evaluierung period is over');
			}

			// Redirect if Evaluation period is over
			if (now < startzeit) {
				this.$router.push({
					name: 'Logout',
					query: {
						reason: 'evaluierungPeriodeStartetErst',
						date: DateHelper.formatDate(this.lvEvaluierung.startzeit)
					}
				});

				return Promise.reject('Evaluierung period not started yet');
			}

			return Promise.resolve();
		},
		logoutIfEvaluierungSubmitted(){
			// Redirect if Evaluation was already submitted
			if (this.lvEvaluierungCode.endezeit !== null) {
				this.$router.push({
					name: 'Logout',
					query: {
						reason: 'evaluierungEingereicht',
						date: DateHelper.formatDateTime(this.lvEvaluierungCode.endezeit)
					}
				});

				return Promise.reject('Evaluierung already submitted');
			}

			return Promise.resolve();
		},
		onCountdownEndSoon(){
			this.$fhcAlert.alertWarning(this.$p.t('fragebogen/zeitLaeuftAb'))
		},
		onCountdownEnd(){
			// Set Endezeit
			this.$api
				.call(ApiEvaluierung.setEndezeit(this.lvEvaluierungCode.lvevaluierung_code_id))
				.then(result => {
					if (result.data === true) {
						// Change to Logout Site
						this.$router.push({
							name: 'Logout',
							query: {
								reason: 'evaluierungZeitAbgelaufen'
							}
						});
					}
				})
		},
		parseDauerToSeconds(dauer) {
			const [h, m, s] = dauer.split(':').map(Number);
			return h * 3600 + m * 60 + s;
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
		findAntwortObj(lvevaluierung_frage_id) {
			return this.fbAntworten.find(a => a.lvevaluierung_frage_id === lvevaluierung_frage_id);
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
													<div class="accordion-body px-0 px-sm-3">
													
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
									:class="{collapsed: !isScreenXl}"
									type="button" 
									data-bs-toggle="collapse" 
									data-bs-target="#collapseLvinfo" 
									:aria-expanded="isScreenXl" 
									aria-controls="collapseLvinfo"
								>
									{{lvInfo.bezeichnung_by_language}}
								</button>
							</h2>
							<div 
								id="collapseLvinfo" 
								class="accordion-collapse collapse"
								:class="{show: isScreenXl}"
								aria-labelledby="headingOne" 
								data-bs-parent="#accordionLvinfo"
							>
								<div class="accordion-body">
									<div class="d-flex">
										<div class="flex-shrink-0 me-3 fw-bold">{{ $p.t('lehre/lektorInnen') }}:</div>
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
			
					<!-- Countdown DOM container for lg+ only 
						The Countdown Component is teleported here when showCountdown and isScreenLg is true -->
					<div class="card w-100 d-none d-lg-flex flex-grow-1">
						<div 
							id="countdown-lg" 
							class="card-body d-flex flex-column align-items-center justify-content-center">
						</div>
					</div>
				</div><!-- .col LV Infos + Countdown (lg only) -->
			</div><!-- .row lv-evaluierung-body -->
			
			<!-- LV-Evaluierung-Footer -->
			<div class="lve-evaluierung-footer row fixed-bottom px-3 py-2 bg-light">
		
			<!-- Countdown DOM container for sm/md only 
				The Countdown Component is teleported here when showCountdown is true and isScreenLg is false -->
			<div id="countdown-sm" class="col-4 d-lg-none d-flex align-items-center"></div>
			
			<!-- Submit button -->
			<div class="col-8 col-lg-12 text-end">
				<button class="btn btn-primary" type="submit"> {{ $p.t('global/abschicken') }}</button>
			</div>	
		</div><!-- .row lv-evaluierung-footer-->
	</form-form>
	</div>
	
	 <!-- Teleporting the countdown to the correct DOM node -->
	<template>
  		<teleport v-if="showCountdown" :to="isScreenLg ? '#countdown-lg' : '#countdown-sm'">
			<Countdown 
				:duration="parseDauerToSeconds(lvEvaluierung.dauer)"
				:type="isScreenLg ? 'circle' : 'icon'"
				@countdown-end-soon="onCountdownEndSoon" 
				@countdown-ended="onCountdownEnd"
			/>
		</teleport>
	</template>
`
}