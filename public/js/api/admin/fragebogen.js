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
	},
	getOrganisationseinheiten() {
		return {
			method: 'get',
			url: URL + 'getOrganisationseinheiten'
		};
	},
	getStudienplaene(studiensemester_kurzbz) {
		return {
			method: 'get',
			url: URL + 'getStudienplaene',
			params: {studiensemester_kurzbz}
		};
	},
	searchLehrveranstaltungen(query, studiensemester_kurzbz) {
		return {
			method: 'get',
			url: URL + 'searchLehrveranstaltungen',
			params: {query, studiensemester_kurzbz}
		};
	},
};