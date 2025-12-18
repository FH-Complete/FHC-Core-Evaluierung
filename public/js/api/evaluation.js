export default {
	getEvaluationDataByLve(lvevaluierung_id) {
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/getEvaluationDataByLve',
			params: {
				lvevaluierung_id: lvevaluierung_id
			}
		}
	},
	getEvaluationDataByLveLv(lvevaluierung_lehrveranstaltung_id) {
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/getEvaluationDataByLveLv',
			params: {
				lvevaluierung_lehrveranstaltung_id: lvevaluierung_lehrveranstaltung_id
			}
		}
	},
	getAuswertungDataByLve(lvevaluierung_id) {
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/getAuswertungDataByLve',
			params: {
				lvevaluierung_id: lvevaluierung_id
			}
		}
	},
	getAuswertungDataByLveLv(lvevaluierung_lehrveranstaltung_id) {
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/getAuswertungDataByLveLv',
			params: {
				lvevaluierung_lehrveranstaltung_id: lvevaluierung_lehrveranstaltung_id
			}
		}
	},
	getTextantwortenByLve(lvevaluierung_id) {
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/getTextantwortenByLve',
			params: {
				lvevaluierung_id: lvevaluierung_id
			}
		}
	},
	getTextantwortenByLveLv(lvevaluierung_lehrveranstaltung_id) {
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/getTextantwortenByLveLv',
			params: {
				lvevaluierung_lehrveranstaltung_id: lvevaluierung_lehrveranstaltung_id
			}
		}
	},
	getEntitledStgs(studiensemester_kurzbz){
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/getEntitledStgs',
			params: {
				studiensemester_kurzbz: studiensemester_kurzbz
			}
		}
	},
	getOrgformsByStg(studiengang_kz, studiensemester_kurzbz){
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/getOrgformsByStg',
			params: {
				studiengang_kz: studiengang_kz,
				studiensemester_kurzbz: studiensemester_kurzbz
			}
		}
	},
	getLvListByStg(studiensemester_kurzbz, studiengang_kz, orgform_kurzbz) {
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/getLvListByStg',
			params: {
				studiensemester_kurzbz: studiensemester_kurzbz,
				studiengang_kz: studiengang_kz,
				orgform_kurzbz: orgform_kurzbz
			}
		}
	},
	updateVerpflichtend(lvevaluierung_lehrveranstaltung_id, isVerpflichtend) {
		return {
			method: 'post',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/updateVerpflichtend',
			params: {
				lvevaluierung_lehrveranstaltung_id: lvevaluierung_lehrveranstaltung_id,
				isVerpflichtend: isVerpflichtend
			}
		}
	},
	updateReviewedLvInStg(lvevaluierung_lehrveranstaltung_id, isReviewed) {
		return {
			method: 'post',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/updateReviewedLvInStg',
			params: {
				lvevaluierung_lehrveranstaltung_id: lvevaluierung_lehrveranstaltung_id,
				isReviewed: isReviewed
			}
		}
	},
}