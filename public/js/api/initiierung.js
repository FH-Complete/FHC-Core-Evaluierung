export default {
	getLveLvsByUser(studiensemester_kurzbz, lehrveranstaltung_id = null) {
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Initiierung/getLveLvsByUser',
			params: {
				studiensemester_kurzbz: studiensemester_kurzbz,
				lehrveranstaltung_id: lehrveranstaltung_id
			}
		}
	},
	getDataForEvaluierungByLe(lvevaluierung_lehrveranstaltung_id)
	{
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Initiierung/getDataForEvaluierungByLe',
			params: {
				lvevaluierung_lehrveranstaltung_id: lvevaluierung_lehrveranstaltung_id
			}
		}
	},
	getDataForEvaluierungByLv(lvevaluierung_lehrveranstaltung_id)
	{
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Initiierung/getDataForEvaluierungByLv',
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
	generateCodesAndSendLinksToStudent(lvevaluierung_id){
		return {
			method: 'post',
			url: '/extensions/FHC-Core-Evaluierung/api/Initiierung/generateCodesAndSendLinksToStudent',
			params: {
				lvevaluierung_id: lvevaluierung_id
			}
		}
	}
}