export default {
	methods: {
		onClickBackToStart(){
			this.$router.push({name: 'Login'});
		}
	},
	template: `
	<div class="lve-logout overflow-hidden">
		<div class="row justify-content-center vh-100">
			<div class="col-10 col-md-8 col-lg-4 text-center align-content-center">
				<h1 class="h3 mb-3">Danke!<br>Was passiert nun mit Ihrem Feedback?</h1>
				
				<div class="text-start mb-5">
					<p>Ihr Feedback dient den Lehrenden zur Selbstevaluierung und Ihre Jahrgangsvertretungen werden von der Studiengangsleitung zum Jour-Fixe eingeladen, um die Ergebnisse zu besprechen.</p>
					<p>Ihr Feedback dient der kontinuierlichen Weiterentwicklung der Lehrveranstaltung. Mehr Details dazu finden Sie hier: <a href="">Auszug zu Whitepaper</a></p>
			  	</div>
			  	
			  	<div class="text-center">
			  		<button class="btn btn-primary" type="button" @click="onClickBackToStart">Back to Start</button>
				</div>
			</div>
		</div>
	</div>`
}