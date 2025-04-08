export default {
	methods: {
		onClickBackToStart(){
			this.$router.push({name: 'Login'});
		}
	},
	template: `
	<div class="lve-logout">
		Logout - Danke!<br>
	  	<button class="btn btn-primary" type="button" @click="onClickBackToStart">Back to Start</button>
	</div>`
}