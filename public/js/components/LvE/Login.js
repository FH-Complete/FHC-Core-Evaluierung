export default {
	methods: {
		onClickStart(e){
			let lvevaluierung_code_id = e.target.previousElementSibling.value;
			// todo Get lvevaluierung_id from lvevaluierung_code_id. for test use now lvecodeid
			this.$router.push({name: 'Evaluierung', params: {lvevaluierung_id: lvevaluierung_code_id}});
		}
	},
	template: `
	<div class="lve-login">
		<div class="input-group mb-3">
		  <input type="text" class="form-control" placeholder="Evaluierung-Code eingeben" aria-label="Evaluierung Code eingeben">
		  <button class="btn btn-primary" type="button" @click="onClickStart">Start</button>
		</div>
	</div>`
}