const URL = 'extensions/FHC-Core-Evaluierung/api/admin/Fragebogen/';

export default {
	getFrageboegen() {
		return {
			method: 'get',
			url: URL + 'getFrageboegen'
		};
	},
	getZuordnungen(fragebogen_id) {
		return {
			method: 'get',
			url: URL + 'getZuordnungen',
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
	insertFragebogen(fragebogen) {
		return {
			method: 'post',
			url: URL + 'insertFragebogen',
			params: fragebogen
		};
	},
	updateFragebogen(fragebogen) {
		return {
			method: 'post',
			url: URL + 'updateFragebogen',
			params: fragebogen
		};
	},
	deleteFragebogen(fragebogen_id) {
		return {
			method: 'post',
			url: URL + 'deleteFragebogen',
			params: {fragebogen_id}
		};
	},
	insertZuordnung(zuordnung) {
		return {
			method: 'post',
			url: URL + 'insertZuordnung',
			params: zuordnung
		};
	},
	deleteZuordnung(lvevaluierung_fragebogen_zuordnung_id) {
		return {
			method: 'post',
			url: URL + 'deleteZuordnung',
			params: {lvevaluierung_fragebogen_zuordnung_id}
		};
	},
	getGruppen(fragebogen_id) {
		return {
			method: 'get',
			url: URL + 'getGruppen',
			params: {fragebogen_id}
		};
	},
	uebernehmenGruppe(fragebogen_id, lvevaluierung_fragebogen_gruppe_id) {
		return {
			method: 'post',
			url: URL + 'uebernehmenGruppe',
			params: {fragebogen_id, lvevaluierung_fragebogen_gruppe_id}
		};
	},
	insertGruppe(gruppe) {
		return {
			method: 'post',
			url: URL + 'insertGruppe',
			params: gruppe
		};
	},
	updateGruppe(gruppe) {
		return {
			method: 'post',
			url: URL + 'updateGruppe',
			params: gruppe
		};
	},
	deleteGruppe(lvevaluierung_fragebogen_gruppe_id) {
		return {
			method: 'post',
			url: URL + 'deleteGruppe',
			params: {lvevaluierung_fragebogen_gruppe_id}
		};
	},
	sortGruppen(fragebogen_id, gruppeIds) {
		return {
			method: 'post',
			url: URL + 'sortGruppen',
			params: {fragebogen_id, gruppeIds}
		};
	},
	getFragen(lvevaluierung_fragebogen_gruppe_id, fragebogen_id) {
		return {
			method: 'get',
			url: URL + 'getFragen',
			params: {lvevaluierung_fragebogen_gruppe_id, fragebogen_id}
		};
	},
	getFrage(lvevaluierung_frage_id) {
		return {
			method: 'get',
			url: URL + 'getFrage',
			params: {lvevaluierung_frage_id}
		};
	},
	insertGruppeFrage(lvevaluierung_fragebogen_gruppe_id, lvevaluierung_frage_id) {
		return {
			method: 'post',
			url: URL + 'insertGruppeFrage',
			params: {lvevaluierung_fragebogen_gruppe_id, lvevaluierung_frage_id}
		};
	},
	deleteGruppeFrage(lvevaluierung_fragebogen_gruppe_id, lvevaluierung_frage_id) {
		return {
			method: 'post',
			url: URL + 'deleteGruppeFrage',
			params: {lvevaluierung_fragebogen_gruppe_id, lvevaluierung_frage_id}
		};
	},
	insertFrage(frage) {
		return {
			method: 'post',
			url: URL + 'insertFrage',
			params: frage
		};
	},
	updateFrage(frage) {
		return {
			method: 'post',
			url: URL + 'updateFrage',
			params: frage
		};
	},
	deleteFrage(lvevaluierung_frage_id, fragebogen_id) {
		return {
			method: 'post',
			url: URL + 'deleteFrage',
			params: {lvevaluierung_frage_id, fragebogen_id}
		};
	},
	sortFragen(lvevaluierung_fragebogen_gruppe_id, frageIds) {
		return {
			method: 'post',
			url: URL + 'sortFragen',
			params: {lvevaluierung_fragebogen_gruppe_id, frageIds}
		};
	},
};