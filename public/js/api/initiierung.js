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
	getLveLvWithLesAndGruppenById(lvevaluierung_lehrveranstaltung_id)
	{
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Initiierung/getLveLvWithLesAndGruppenById',
			params: {
				lvevaluierung_lehrveranstaltung_id: lvevaluierung_lehrveranstaltung_id
			}
		}
	},
	getLvEvaluierungenByID(lvevaluierung_lehrveranstaltung_id)
	{
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Initiierung/getLvEvaluierungenByID',
			params: {
				lvevaluierung_lehrveranstaltung_id: lvevaluierung_lehrveranstaltung_id
			}
		}
	},
	updateLvAufgeteilt(lvevaluierung_lehrveranstaltung_id, lv_aufgeteilt) {
		return {
			method: 'post',
			url: '/extensions/FHC-Core-Evaluierung/api/Initiierung/updateLvAufgeteilt',
			params: {
				lvevaluierung_lehrveranstaltung_id: lvevaluierung_lehrveranstaltung_id,
				lv_aufgeteilt: lv_aufgeteilt
			}
		}
	},
	saveOrUpdateLvevaluierung(data) {
		return {
			method: 'post',
			url: '/extensions/FHC-Core-Evaluierung/api/Initiierung/saveOrUpdateLvevaluierung',
			params: {
				data: data
			}
		}
	},
	getLveLvPrestudenten(lvevaluierung_lehrveranstaltung_id){
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Initiierung/getLveLvPrestudenten',
			params: {
				lvevaluierung_lehrveranstaltung_id: lvevaluierung_lehrveranstaltung_id
			}
		}
	},
	generateCodesAndSendLinksToStudents(lvevaluierung_id){
		return {
			method: 'post',
			url: '/extensions/FHC-Core-Evaluierung/api/Initiierung/generateCodesAndSendLinksToStudents',
			params: {
				lvevaluierung_id: lvevaluierung_id
			}
		}
	}
}