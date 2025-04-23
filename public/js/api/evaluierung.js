export default {

	getLvInfo(lehrveranstaltung_id, studiensemester_kurzbz) {
		const url = '/extensions/FHC-Core-Evaluierung/api/Evaluierung/getLvInfo';
		return this.$fhcApi.get(url, {
			lehrveranstaltung_id: lehrveranstaltung_id,
			studiensemester_kurzbz: studiensemester_kurzbz
		})
	}
}