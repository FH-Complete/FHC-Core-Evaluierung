import FormForm from "../../../../../js/components/Form/Form.js";
import FormInput from "../../../../../js/components/Form/Input.js";
import FormValidation from "../../../../../js/components/Form/Validation.js";
export default {
	components: {
		FormForm,
		FormInput,
		FormValidation
	},
	data(){
		return {
			data: []
		}
	},
	created() {
		console.log('Component created');
		console.log(this.$route.params.lvevaluierung_id);
	},
	methods: {
		onSubmit(){
			this.$router.push({name: 'Logout'});
		}
	},
	template: `
	<div class="lve-evaluierung d-lg-flex flex-column min-vh-100 py-5 ps-lg-2 overflow-auto">
		<form-form ref="form" class="lve-evaluierung" @submit.prevent="onSubmit">
			<!-- LV-Evaluierung-Body -->
			<div class="lve-evaluierung-body row flex-grow-1">
			
				<!-- LV-Evaluierung Fragen -->
				<div class="col-12 col-lg-8 order-2 order-lg-1 mb-3">
				
					<!-- Fragebogengruppe Card -->
					<div class="lve-fb-card card my-lg-3 border-0">
						<div class="card-body px-0">
							<!-- Fragenbogenfrage SingleChoice -->	
							<div class="card mb-4 text-center border-0">
								<div class="card-title fw-bold">
									Mit Radio Toggle Button Group
								</div>
								<div class="card-body">
									<div class="btn-group" role="group" aria-label="Basic radio toggle button group">
										<form-input
											type="radio"
											v-model="data"
											name="btnradio"
											container-class="px-0"
											class="btn-check"
											id="lveBtnradio1"
											autocomplete="off"
											>
										</form-input>									
										<label class="btn" for="lveBtnradio1"><i class="fa-regular fa-face-laugh fa-2x"></i></label>
									
										<form-input
											type="radio"
											v-model="data"
											name="btnradio"
											container-class="px-0"
											class="btn-check"
											id="lveBtnradio2"
											autocomplete="off"
											>
										</form-input>									
										<label class="btn" for="lveBtnradio2"><i class="fa-regular fa-face-smile fa-2x"></i></label>
									
										<form-input
											type="radio"
											v-model="data"
											name="btnradio"
											container-class="px-0"
											class="btn-check"
											id="lveBtnradio3"
											autocomplete="off"
											>
										</form-input>									
										<label class="btn" for="lveBtnradio3"><i class="fa-regular fa-face-meh fa-2x"></i></label>
										
										<form-input
											type="radio"
											v-model="data"
											name="btnradio"
											container-class="px-0"
											class="btn-check"
											id="lveBtnradio4"
											autocomplete="off"
											>
										</form-input>
										<label class="btn" for="lveBtnradio4"><i class="fa-regular fa-face-frown fa-2x"></i></label>
										
										<form-input
											type="radio"
											v-model="data"
											name="btnradio"
											container-class="px-0"
											class="btn-check"
											id="lveBtnradio5"
											autocomplete="off"
											>
										</form-input>									
										<label class="btn" for="lveBtnradio5"><i class="fa-regular fa-face-tired fa-2x"></i></label>					
									</div>
								</div>
							</div><!-- .card Fragebogenfrage SingleChoice -->
							
							<!-- Fragenbogenfrage Text -->
							<div class="card mb-4 px-0 px-md-1 d-flex text-center border-0">
								<div class="card-body">
									<label class="fw-bold mb-3">Offene Antworten</label>
									<form-input
										type="textarea"
										v-model="data"
										name="test"
										placeholder="Ihre Antwort hier..."
										style="height: 100px"
										>
									</form-input>
								</div>
							</div><!-- .card Fragebogenfrage Text -->
						</div>
					</div><!-- .card Fragebogengruppe Card-->
					
					<!-- Fragenbogengruppe Label -->
					<div class="lve-fb-label card my-lg-3 text-center border-0">
						<div class="card-title pt-3 fw-bold">
							Nur Label
						</div>
					</div><!-- .card Fragebogengruppe Label-->
					
					<!-- Fragenbogengruppe Group -->
					<div class="lve-fb-group card my-lg-3 border-0">
						<div class="card-body px-0 px-md-3">
							<div class="lve-fb-group-accordion accordion accordion-flush border rounded" id="accordionFlushExample">
								<div class="accordion-item">
									<h2 class="accordion-header" id="flush-headingOne">
									<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseOne" aria-expanded="false" aria-controls="flush-collapseOne">
										Accordion Item #1
									</button>
									</h2>
									<div id="flush-collapseOne" class="accordion-collapse collapse" aria-labelledby="flush-headingOne" data-bs-parent="#accordionFlushExample">
										<div class="accordion-body">
											Fragebogengruppen oder Fragebogenfragen
										</div>
									</div>
								</div>
								<div class="accordion-item">
									<h2 class="accordion-header" id="flush-headingTwo">
									<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseTwo" aria-expanded="false" aria-controls="flush-collapseTwo">
										Accordion Item #2
									</button>
									</h2>
									<div id="flush-collapseTwo" class="accordion-collapse collapse" aria-labelledby="flush-headingTwo" data-bs-parent="#accordionFlushExample">
										<div class="accordion-body">
											Fragebogengruppen oder Fragebogenfragen
										</div>
									</div>
								</div>
								<div class="accordion-item">
									<h2 class="accordion-header" id="flush-headingThree">
									<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseThree" aria-expanded="false" aria-controls="flush-collapseThree">
										Accordion Item #3
									</button>
									</h2>
									<div id="flush-collapseThree" class="accordion-collapse collapse" aria-labelledby="flush-headingThree" data-bs-parent="#accordionFlushExample">
										<div class="accordion-body">
											Fragebogengruppen oder Fragebogenfragen
										</div>
									</div>
								</div>
							</div><!-- .accordion Fragebogengruppe Group -->	
						</div>
					</div>
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