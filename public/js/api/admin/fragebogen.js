const URL = 'extensions/FHC-Core-Evaluierung/api/admin/Fragebogen/';

export default {
	getFrageboegen() {
		return {
			method: 'get',
			url: URL + 'getFrageboegen'
		};
	},
	getFragebogen(fragebogen_id) {
		return {
			method: 'get',
			url: URL + 'getFragebogen',
			params: {fragebogen_id}
		};
	}
};