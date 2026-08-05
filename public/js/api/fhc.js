export default {
	Studiensemester: {
		getAll(){
			return {
				method: 'get',
				url: 'api/frontend/v1/organisation/Studiensemester/getAll',
				params: {start: '2026-02-01'}
			}
		},
		getAktNext(){
			return {
				method: 'get',
				url: 'api/frontend/v1/organisation/Studiensemester/getAktNext',
			}
		}
	}
}