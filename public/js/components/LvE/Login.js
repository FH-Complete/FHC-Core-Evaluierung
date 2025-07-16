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
			<div class="col-10 col-md-8 col-lg-4 text-center align-content-center">
				<img :src="logo" :alt="$p.t('fragebogen/fhtwLogo')" class="img-fluid mb-4 mb-lg-5" style="max-height: 80px;">
				
				<h1 class="text-start mb-3 fw-normal fs-6">
					<p>{{ $p.t('fragebogen/loginTextCodeEingeben') }}</p>
			  	</h1>
				<div class="input-group mb-3 mb-lg-5">
					<label for="evaluation-code-input" class="visually-hidden">
						{{ $p.t('fragebogen/evaluierungscodeEingeben')}}
					</label>
				  	<input 
						id="evaluation-code-input"
						type="text" 
						v-model="code"
						class="form-control" 
						:placeholder="$p.t('fragebogen/loginCodeEingeben')" 
						@keyup.enter="onClickStart"
				  	>
				  <button class="btn btn-primary" type="button" @click="onClickStart">Start</button>
				</div>
				
				<div v-html="$p.t('fragebogen/loginTextLvevaluierung')"></div>
				
				<div v-html="$p.t('fragebogen/loginTextAntwortoptionen')"></div>
			
				
			</div>
		</div>
	</div>`
}