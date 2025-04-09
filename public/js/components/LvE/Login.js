export default {
	computed: {
		logo() {
			return FHC_JS_DATA_STORAGE_OBJECT.app_root + 'skin/images/fh_logo.png';
		}
	},
	methods: {
		onClickStart(e){
			let lvevaluierung_code_id = e.target.previousElementSibling.value;
			// todo Get lvevaluierung_id from lvevaluierung_code_id. for test use now lvecodeid
			this.$router.push({name: 'Evaluierung', params: {lvevaluierung_id: lvevaluierung_code_id}});
		}
	},
	template: `
	<div class="lve-login overflow-hidden">
		<div class="row justify-content-center vh-100">
			<div class="col-10 col-md-8 col-lg-4 text-center align-content-center">
				<img :src="logo" alt="Logo" class="img-fluid mb-4 mb-lg-5" style="max-height: 80px;">
				
				<div class="text-start mb-3">
					<p>Bitte geben Sie Ihren Code ein, um die Evaluierung zu starten:</p>
			  	</div>
				<div class="input-group mb-3 mb-lg-5">
				  <input type="text" class="form-control" placeholder="Evaluierung-Code eingeben" aria-label="Evaluierung Code eingeben">
				  <button class="btn btn-primary" type="button" @click="onClickStart">Start</button>
				</div>
				
				<div class="text-start mb-3">
					<p>Die folgende LV-Evaluierung umfasst</p>
				</div>
				<ul class="text-start small">
					<li>zwei (geschlossene) Pflichtfragen</li>
					<li>die Möglichkeit, optional zu einzelnen Bereichen konkreteres Feedback zu geben</li>
					<li>zwei optionale Freitextfragen</li>
				</ul>
				
				<div class="text-start mb-3">
					<p>Die Antwortoptionen umfassen 5 Stufen:</p>
				</div>
				<ul class="text-start small">
					<li>Sehr gut</li>
					<li>Gut</li>
					<li>Mittel</li>
					<li>Schlecht</li>
					<li>Sehr schlecht</li>
				</ul>
				
			</div>
		</div>
	</div>`
}