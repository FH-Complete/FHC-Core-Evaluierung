import Phrasen from "../../../../js/plugins/Phrasen.js";
import ExportComponent from "../components/Export/ExportComponent.js";


const app = Vue.createApp({
	components: {
		ExportComponent: ExportComponent
	},
	data() {
		return {
			
		};
	},
	provide() {
		return {
			
		};
	},
	methods: {

	},
	template: `
		<ExportComponent/>
	`
});

app
	.use(primevue.config.default, {zIndex: {overlay: 9999}})
	.use(Phrasen)
	.mount('#evaluation-export-main')