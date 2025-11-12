import Evaluation from "../../components/Evaluation/Evaluation.js";
import DateHelper from '../../helpers/DateHelper';
import FhcAlert from '../../../../../js/plugins/FhcAlert.js';
import FhcApi from "../../../../../js/plugins/Api.js";
import Phrasen from "../../../../../js/plugins/Phrasen.js";
import highchartsPlugin from "../../../../../js/plugins/highchartsVue.js"

const ciPath = FHC_JS_DATA_STORAGE_OBJECT.app_root.replace(/(https:|)(^|\/\/)(.*?\/)/g, '') + FHC_JS_DATA_STORAGE_OBJECT.ci_router;
const router = VueRouter.createRouter({
	history: VueRouter.createWebHistory(),
	routes: [
		{
			path: `/${ciPath}/extensions/FHC-Core-Evaluierung/Evaluation/Evaluation`,
			name: 'Evaluation',
			component: Evaluation
		}
	]
});

const app = Vue.createApp({
	components: {
		Evaluation
	},
	template: `<router-view></router-view>`
});

app
	.use(router)
	.use(primevue.config.default, {zIndex: {overlay: 9999}})
	.use(FhcApi)
	.use(FhcAlert)
	.use(Phrasen)
	.use(DateHelper)
	.use(highchartsPlugin, {tagName: 'highcharts'})
	.mount('#evaluation-evaluation-main')