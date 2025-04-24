export default {
	data() {
		return {
			code: null
		}
	},
	created() {
		// Set CodeID if it is given by get parameter
		if (this.$route.query.code)
			this.code = this.$route.query.code;
	},
	computed: {
		logo() {
			return FHC_JS_DATA_STORAGE_OBJECT.app_root + 'skin/images/fh_logo.png';
		}
	},
	methods: {
		onClickStart(){
			this.$router.push({name: 'Evaluierung', params: {code: this.code}});
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
				  <input type="text" v-model="code" class="form-control" placeholder="Evaluierung-Code eingeben" aria-label="Evaluierung Code eingeben">
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