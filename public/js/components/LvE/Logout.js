export default {
	name: 'Logout',
	props: {
		title: String,
		content: String
	},
	computed: {
		computedTitle() {
			// If prop is given, return title. Else return default logout title.
			return this.title || this.$p.t('fragebogen/logoutTitle');
		},
		computedContent() {
			// If prop is given, return content
			if (this.content) return this.content;

			// Else return default
			const text = this.$p.t('fragebogen/logoutText');
			const link = `<a href="" target="_blank">Whitepaper</a>`;
			return `${text} ${link}`;
		}
	},
	methods: {
		onClickBackToStart(){
			this.$router.push({name: 'Login'});
		}
	},
	template: `
	<div class="lve-logout overflow-hidden">
		<div class="row justify-content-center vh-100">
			<div class="col-10 col-md-8 col-lg-6 text-center align-content-center">
				<h1 class="h3 mb-3" v-html="computedTitle"></h1>
				
				<div class="text-center mb-5" v-html="computedContent"></div>
				
			  	<div class="text-center">
			  		<button class="btn btn-primary" type="button" @click="onClickBackToStart">{{ $p.t('global/zurueckZumStart')}}</button>
				</div>
			</div>
		</div>
	</div>`
}