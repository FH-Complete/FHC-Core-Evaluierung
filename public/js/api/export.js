export default {

	getExportRowCount(studiensemester, von, bis) {
		return {
			method: 'post',
			url: '/extensions/FHC-Core-Evaluierung/api/Export/getExportRowCount',
			params: { studiensemester, von, bis }
		}
	}
}