export default {
	created() {
		console.log('Component created');
		console.log(this.$route.params.lvevaluierung_id);
	},
	methods: {
		onClickSubmit(){
			this.$router.push({name: 'Logout'});
		}
	},
	template: `
	<div class="lve-evaluierung d-lg-flex flex-column min-vh-100 py-5 ps-lg-2 overflow-auto">
		<div class="lv-evaluierung-body row flex-grow-1">
			<!-- LV Fragen -->
			<div class="col-12 col-lg-8 order-2 order-lg-1 mb-3">
			  	<div class="card my-lg-3">
					<div class="card-body">LV Fragen</div>
			  	</div>	
			</div>
			
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
			</div>
	  	</div>
	  	
  		<!-- Footer -->
		<div class="lv-evaluierung-footer row fixed-bottom px-3 py-2 bg-light">
			<!-- Countdown for sm/md only -->
			<div class="col-8 d-lg-none d-flex align-items-center">
				<i class="fa-regular fa-clock fa-2x me-2"></i>Countdown sm-md
			</div>
			
			<!-- Submit button -->
			<div class="col-4 col-lg-12 text-end">
				<button class="btn btn-primary" type="button" @click="onClickSubmit">Submit</button>
			</div>	
		</div>
	</div>`
}