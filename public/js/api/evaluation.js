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
	},
	getAuswertungDataByLve(lvevaluierung_id)
	{
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/getAuswertungDataByLve',
			params: {
				lvevaluierung_id: lvevaluierung_id
			}
		}
	},
	getAuswertungDataByLveLv(lvevaluierung_lehrveranstaltung_id)
	{
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/getAuswertungDataByLveLv',
			params: {
				lvevaluierung_lehrveranstaltung_id: lvevaluierung_lehrveranstaltung_id
			}
		}
	},
	getTextantwortenByLve(lvevaluierung_id)
	{
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/getTextantwortenByLve',
			params: {
				lvevaluierung_id: lvevaluierung_id
			}
		}
	},
	getTextantwortenByLveLv(lvevaluierung_lehrveranstaltung_id)
	{
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/getTextantwortenByLveLv',
			params: {
				lvevaluierung_lehrveranstaltung_id: lvevaluierung_lehrveranstaltung_id
			}
		}
	},

}