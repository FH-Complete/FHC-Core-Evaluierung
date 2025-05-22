export default {

	getInitFragebogen(fragebogen_id) {
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluierung/getInitFragebogen',
			params: { fragebogen_id: fragebogen_id }
		}
	}
}