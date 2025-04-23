export default {

	getInitFragebogen(fragebogen_id) {
		const url = '/extensions/FHC-Core-Evaluierung/api/Evaluierung/getInitFragebogen';
		return this.$fhcApi.get(url, {fragebogen_id: fragebogen_id})
	}
}