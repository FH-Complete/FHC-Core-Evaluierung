export default {
	getEvaluationDataByLve(lvevaluierung_id)
	{
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/getEvaluationDataByLve',
			params: {
				lvevaluierung_id: lvevaluierung_id
			}
		}
	},
	getEvaluationDataByLveLv(lvevaluierung_lehrveranstaltung_id)
	{
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/getEvaluationDataByLveLv',
			params: {
				lvevaluierung_lehrveranstaltung_id: lvevaluierung_lehrveranstaltung_id
			}
		}
	}
}