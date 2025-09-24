import LveStarten from '../components/Initiierung/LveStarten.js';
import DateHelper from '../helpers/DateHelper';
import FhcAlert from '../../../../js/plugins/FhcAlert.js';
import FhcApi from "../../../../js/plugins/Api.js";
import Phrasen from "../../../../js/plugins/Phrasen.js";


const app = Vue.createApp({
	components: {
		LveStarten
	},
	template: `<lve-starten></lve-starten>`
});

app
	.use(primevue.config.default, {zIndex: {overlay: 9999}})
	.use(FhcAlert)
	.use(FhcApi)
	.use(Phrasen)
	.use(DateHelper)
	.mount('#lve-initiierung-main')