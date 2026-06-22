export default {
	getEvaluationDataByLve(lvevaluierung_id, role) {
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/getEvaluationDataByLve',
			params: {
				lvevaluierung_id: lvevaluierung_id,
				role: role,
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
	getEvaluationDataByLvTemplate(lehrveranstaltung_template_id, studiensemester_kurzbz) {
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/getEvaluationDataByLvTemplate',
			params: {
				lehrveranstaltung_template_id: lehrveranstaltung_template_id,
				studiensemester_kurzbz: studiensemester_kurzbz
			}
		}
	},
	getAuswertungDataByLve(lvevaluierung_id, role) {
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/getAuswertungDataByLve',
			params: {
				lvevaluierung_id: lvevaluierung_id,
				role: role
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
	getAuswertungDataByLvTemplate(lehrveranstaltung_template_id, studiensemester_kurzbz) {
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/getAuswertungDataByLvTemplate',
			params: {
				lehrveranstaltung_template_id: lehrveranstaltung_template_id,
				studiensemester_kurzbz: studiensemester_kurzbz
			}
		}
	},
	getTextantwortenByLve(lvevaluierung_id, role) {
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/getTextantwortenByLve',
			params: {
				lvevaluierung_id: lvevaluierung_id,
				role: role
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
	getReflexionDataByLve(lvevaluierung_id, role) {
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/getReflexionDataByLve',
			params: {
				lvevaluierung_id: lvevaluierung_id,
				role: role
			}
		}
	},
	getReflexionDataByLveLv(lvevaluierung_lehrveranstaltung_id) {
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/getReflexionDataByLveLv',
			params: {
				lvevaluierung_lehrveranstaltung_id: lvevaluierung_lehrveranstaltung_id
			}
		}
	},
	getReflexionDataByLvTemplate(lehrveranstaltung_template_id, studiensemester_kurzbz) {
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/getReflexionDataByLvTemplate',
			params: {
				lehrveranstaltung_template_id: lehrveranstaltung_template_id,
				studiensemester_kurzbz: studiensemester_kurzbz
			}
		}
	},
	saveOrUpdateReflexion(lvevaluierung_reflexion_id, lvevaluierung_id, mitarbeiter_uid, data) {
		return {
			method: 'post',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/saveOrUpdateReflexion',
			params: {
				lvevaluierung_reflexion_id: lvevaluierung_reflexion_id,
				lvevaluierung_id: lvevaluierung_id,
				mitarbeiter_uid: mitarbeiter_uid,
				data: data
			}
		}
	},
	getEntitledKfs() {
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/getEntitledKfs',
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
	getLvListByKf(studiensemester_kurzbz, oe_kurzbz) {
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/getLvListByKf',
			params: {
				studiensemester_kurzbz: studiensemester_kurzbz,
				oe_kurzbz: oe_kurzbz,
			}
		}
	},
	getLvTemplateListByKf(studiensemester_kurzbz, oe_kurzbz) {
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/getLvTemplateListByKf',
			params: {
				studiensemester_kurzbz: studiensemester_kurzbz,
				oe_kurzbz: oe_kurzbz,
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
	updateReviewedLvInKf(lvevaluierung_lehrveranstaltung_id, isReviewed) {
		return {
			method: 'post',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/updateReviewedLvInKf',
			params: {
				lvevaluierung_lehrveranstaltung_id: lvevaluierung_lehrveranstaltung_id,
				isReviewed: isReviewed
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
	getAuswertungHelpUrl(){
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/getAuswertungHelpUrl',
		}
	},
	getMalveByKf(oe_kurzbz, studiensemester_kurzbz) {
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/getMalveByKf',
			params: {
				oe_kurzbz: oe_kurzbz,
				studiensemester_kurzbz: studiensemester_kurzbz
			}
		}
	},
	getMalveByStg(studiengang_kz, studiensemester_kurzbz){
		return {
			method: 'get',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/getMalveByStg',
			params: {
				studiengang_kz: studiengang_kz,
				studiensemester_kurzbz: studiensemester_kurzbz
			}
		}
	},
	saveMalveByKf(oe_kurzbz, studiensemester_kurzbz) {
		return {
			method: 'post',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/saveMalveByKf',
			params: {
				oe_kurzbz: oe_kurzbz,
				studiensemester_kurzbz: studiensemester_kurzbz
			}
		}
	},
	saveMalveByStg(studiengang_kz, studiensemester_kurzbz){
		return {
			method: 'post',
			url: '/extensions/FHC-Core-Evaluierung/api/Evaluation/saveMalveByStg',
			params: {
				studiengang_kz: studiengang_kz,
				studiensemester_kurzbz: studiensemester_kurzbz
			}
		}
	}
}