export default {
	created() {
		console.log('Component created');
		console.log(this.$route.params.lvevaluierung_id);
	},
	methods: {
		onClickSubmit(){
			this.$router.push({name: 'Logout'});
		}
	},
	template: `
	<div class="lve-evaluierung">
		Evaluierung Fragebogen<br>
	  	<button class="btn btn-primary" type="button" @click="onClickSubmit">Submit</button>
	</div>`
}