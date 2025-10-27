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
		<div class="row justify-content-center vh-100 mt-5">
			<div class="col-10 col-md-8 col-lg-4 text-center d-flex flex-column justify-content-center">
				<!-- Image Logo -->
				<h1 class="mb-4 mb-lg-5 order-1 align-self-center" tabindex="0">
					<img :src="logo" :alt="$p.t('fragebogen/fhtwLogo')" class="img-fluid" style="max-height: 80px;">
					<span class="visually-hidden">
						Startseite der Lehrveranstaltungsevaluierung
					</span>
			  	</h1>
				<!-- Text Code eingeben -->
				<div class="text-start mb-3 fw-normal fs-6 order-2">
					<p>{{ $p.t('fragebogen/loginTextCodeEingeben') }}</p>
			  	</div>
			  	<!-- Text Evaluierung Info -->
			  	<div v-html="$p.t('fragebogen/loginTextLvevaluierung')" tabindex="0" class="order-4"></div>
				<!-- Text Antwortoptionen-->
				<div v-html="$p.t('fragebogen/loginTextAntwortoptionen')" tabindex="0" class="order-5"></div>
				<!-- Input Codeeingabe und Submit button -->
				<div class="input-group mb-3 mb-lg-5 order-3">
					<label for="evaluation-code-input" class="visually-hidden">Code-Input</label>
				  	<input 
						id="evaluation-code-input"
						type="text" 
						v-model="code"
						class="form-control" 
						:placeholder="$p.t('fragebogen/loginCodeEingeben')" 
				  	>
				  	<button 
				  		class="btn btn-primary" 
				  		type="button" 
				  		@click="onClickStart" 
				  		:aria-label="'Evaluierung starten'"
					>
						Start
					</button>
				</div>	
			</div>
		</div>
	</div>`
}