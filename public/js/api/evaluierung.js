export default {

	getLvEvaluierungCode(code) {
		const url = '/extensions/FHC-Core-Evaluierung/api/Evaluierung/getLvEvaluierungCode';
		return this.$fhcApi.get(url, {code: code})
	},
	getLvEvaluierung(lvevaluierung_id) {
		const url = '/extensions/FHC-Core-Evaluierung/api/Evaluierung/getLvEvaluierung';
		return this.$fhcApi.get(url, {lvevaluierung_id: lvevaluierung_id})
	},
	getLvInfo(lvevaluierung_lehrveranstaltung_id) {
		const url = '/extensions/FHC-Core-Evaluierung/api/Evaluierung/getLvInfo';
		return this.$fhcApi.get(url, {lvevaluierung_lehrveranstaltung_id: lvevaluierung_lehrveranstaltung_id})
	},
	setStartzeit(lvevaluierung_code_id) {
		const url = '/extensions/FHC-Core-Evaluierung/api/Evaluierung/setStartzeit';
		return this.$fhcApi.post(url, {lvevaluierung_code_id: lvevaluierung_code_id})
	},
	setEndezeit(lvevaluierung_code_id) {
		const url = '/extensions/FHC-Core-Evaluierung/api/Evaluierung/setEndezeit';
		return this.$fhcApi.post(url, {lvevaluierung_code_id: lvevaluierung_code_id})
	},
	saveAntworten(data) {
		const url = '/extensions/FHC-Core-Evaluierung/api/Evaluierung/saveAntworten';
		return this.$fhcApi.post(url, {data: data})
	}
}