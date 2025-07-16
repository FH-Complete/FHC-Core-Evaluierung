export default {
	emits: ['languageChanged'],
	data(){
		return {
			serverLanguages: FHC_JS_DATA_STORAGE_OBJECT.server_languages,
			selectedLanguage: this.$p.user_language.value
		}
	},
	methods:{
		changeLanguage(){
			const language = this.selectedLanguage;

			if (this.serverLanguages.some(l => l.sprache === language))
			{
				this.$p
					.setLanguage(language, this.$fhcApi)
					.then(() =>
					{
						this.$emit('languageChanged', language)
					});
			}
		},
	},
	template:`
		<div class="sprache-dropdown d-inline-block">
			<label for="language-select" class="visually-hidden text-white bg-primary ">{{ $p.t('fragebogen/spracheAuswaehlen')}}</label>
			<select v-model="selectedLanguage" @change="changeLanguage" id="language-select" class="form-select form-select-sm">
				<option v-for="language in serverLanguages" :key="language.sprache" :value="language.sprache">
					{{language.bezeichnung}}
				</option>
			</select>
		</div>
	`,
};