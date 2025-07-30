export default {
	getLveLvs(studiensemester_kurzbz, lehrveranstaltung_id = null) {
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Initiierung/getLveLvs',
			params: {
				studiensemester_kurzbz: studiensemester_kurzbz,
				lehrveranstaltung_id: lehrveranstaltung_id
			}
		}
	},
	getLveLvsWithLes(studiensemester_kurzbz, lehrveranstaltung_id = null) {
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Initiierung/getLveLvsWithLes',
			params: {
				studiensemester_kurzbz: studiensemester_kurzbz,
				lehrveranstaltung_id: lehrveranstaltung_id
			}
		}
	},
}