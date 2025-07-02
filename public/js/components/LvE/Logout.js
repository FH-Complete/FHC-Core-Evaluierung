export default {
	name: 'Logout',
	props: {
		reason: String,
		date: String
	},
	computed: {
		computedTitle() {
			return this.reason
				? this.getTitleByReason(this.reason)
				: this.$p.t('fragebogen/logoutTitle');	// default
		},
		computedContent() {
			if (this.reason) {
				return this.getContentByReason(this.reason)
			}
			else {
				const text = this.$p.t('fragebogen/logoutText');
				const link = `<a href="" target="_blank">Whitepaper</a>`;
				return `${text} ${link}`;	// default
			}
		}
	},
	methods: {
		getTitleByReason(reason) {
			switch (reason) {
				case 'evaulierungCodeExistiertNicht':
					return this.$p.t('fragebogen/evaluierungCodeExistiertNicht');
				case 'evaluierungPeriodeBeendet':
					return this.$p.t('fragebogen/evaluierungPeriodeBeendet', {date: this.date});
				case 'evaluierungPeriodeStartetErst':
					return this.$p.t('fragebogen/evaluierungPeriodeStartetErst', {date: this.date});
				case 'evaluierungEingereicht':
					return this.$p.t('fragebogen/evaluierungEingereicht', {date: this.date});
				case 'evaluierungZeitAbgelaufen':
					return this.$p.t('fragebogen/evaluierungZeitAbgelaufen');
				default:
					break;
			}
		},
		getContentByReason(reason) {
			switch (reason) {
				case 'evaulierungCodeExistiertNicht':
					return this.$p.t('fragebogen/evaluierungNichtVerfuegbar');
				case 'evaluierungPeriodeBeendet':
					return this.$p.t('fragebogen/evaluierungNichtMehrVerfuegbar');
				case 'evaluierungPeriodeStartetErst':
					return this.$p.t('fragebogen/evaluierungNichtVerfuegbar');
				case 'evaluierungEingereicht':
					return this.$p.t('fragebogen/evaluierungNichtMehrVerfuegbar');
				case 'evaluierungZeitAbgelaufen':
					return this.$p.t('fragebogen/evaluierungAntwortenNichtUebermittelt');
				default:
					break;
			}
		},
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