export default {
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
				this.$p.setLanguage(language, this.$fhcApi);
			}
		},
	},
	template:`
		<div class="sprache-dropdown d-inline-block">
			<select v-model="selectedLanguage" @change="changeLanguage" class="form-select form-select-sm" aria-label="Sprache Auswahl">
				<option v-for="language in serverLanguages" :key="language.sprache" :value="language.sprache">
					{{language.bezeichnung}}
				</option>
			</select>
		</div>
	`,
};