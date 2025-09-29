import LveStarten from '../components/Initiierung/LveStarten.js';
import DateHelper from '../helpers/DateHelper';
import FhcAlert from '../../../../js/plugins/FhcAlert.js';
import FhcApi from "../../../../js/plugins/Api.js";
import Phrasen from "../../../../js/plugins/Phrasen.js";

const ciPath = FHC_JS_DATA_STORAGE_OBJECT.app_root.replace(/(https:|)(^|\/\/)(.*?\/)/g, '') + FHC_JS_DATA_STORAGE_OBJECT.ci_router;
const router = VueRouter.createRouter({
	history: VueRouter.createWebHistory(),
	routes: [
		{
			path: `/${ciPath}/extensions/FHC-Core-Evaluierung/Initiierung`,
			name: 'LveStarten',
			component: LveStarten
		}
	]
});

const app = Vue.createApp({
	components: {
		LveStarten
	},
	template: `<router-view></router-view>`
});

app
	.use(router)
	.use(primevue.config.default, {zIndex: {overlay: 9999}})
	.use(FhcAlert)
	.use(FhcApi)
	.use(Phrasen)
	.use(DateHelper)
	.mount('#lve-initiierung-main')