export default {

	getLvEvaluierungCode(code) {
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluierung/getLvEvaluierungCode',
			params: {code: code}
		}
	},
	getLvEvaluierung(lvevaluierung_id) {
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluierung/getLvEvaluierung',
			params: {lvevaluierung_id: lvevaluierung_id}
		}
	},
	getLvInfo(lvevaluierung_lehrveranstaltung_id) {
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluierung/getLvInfo',
			params: {lvevaluierung_lehrveranstaltung_id: lvevaluierung_lehrveranstaltung_id}
		}

	},
	setStartzeit(lvevaluierung_code_id) {
		return {
			method: 'post',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluierung/setStartzeit',
			params: {lvevaluierung_code_id: lvevaluierung_code_id}
		}
	},
	setEndezeit(lvevaluierung_code_id) {
		return {
			method: 'post',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluierung/setEndezeit',
			params: {lvevaluierung_code_id: lvevaluierung_code_id}
		}
	},
	saveAntwortenAndSetEndezeit(lvevaluierung_code_id, data) {
		return {
			method: 'post',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluierung/saveAntwortenAndSetEndezeit',
			params: {
				lvevaluierung_code_id: lvevaluierung_code_id,
				data: data
			}
		}
	}
}