import LvLeitung from '../components/Setup/LvLeitung.js';
import DateHelper from '../helpers/DateHelper';
import FhcAlert from '../../../../js/plugins/FhcAlert.js';
import FhcApi from "../../../../js/plugins/Api.js";
import Phrasen from "../../../../js/plugins/Phrasen.js";


const app = Vue.createApp({
	components: {
		LvLeitung
	},
	template: `<lv-leitung></lv-leitung>`
});

app
	.use(primevue.config.default, {zIndex: {overlay: 9999}})
	.use(FhcAlert)
	.use(FhcApi)
	.use(Phrasen)
	.use(DateHelper)
	.mount('#lve-starten-main')