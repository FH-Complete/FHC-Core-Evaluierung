import Phrasen from "../../../../js/plugins/Phrasen.js";
import ExportComponent from "../components/Export/ExportComponent.js";
import {capitalize} from "../../../../js/helpers/StringHelpers.js";

const app = Vue.createApp({
	name: 'ExportApp',
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

app.config.globalProperties.$capitalize = capitalize;

app
	.use(primevue.config.default, {zIndex: {overlay: 9999}})
	.use(Phrasen)
	.mount('#evaluation-export-main')