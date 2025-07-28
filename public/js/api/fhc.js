export default {
	Studiensemester: {
		getAll(){
			return {
				method: 'get',
				url: 'api/frontend/v1/organisation/Studiensemester/getAll',
				params: {start: '2025-08-01'}	// todo config
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