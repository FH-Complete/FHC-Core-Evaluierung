import Evaluation from "../../components/Evaluation/Evaluation.js";
import Phrasen from "../../../../../js/plugins/Phrasen.js";
import highchartsPlugin from "../../../../../js/plugins/highchartsVue.js"
import tooltip from "../../../../../js/directives/tooltip.js";
import DateHelper from "../../helpers/DateHelper";

const ciPath = FHC_JS_DATA_STORAGE_OBJECT.app_root.replace(/(https:|)(^|\/\/)(.*?\/)/g, '') + FHC_JS_DATA_STORAGE_OBJECT.ci_router;
const router = VueRouter.createRouter({
	history: VueRouter.createWebHistory(),
	routes: [
		{
			path: `/${ciPath}/extensions/FHC-Core-Evaluierung/Evaluation/Evaluation`,
			name: 'Evaluation',
			component: Evaluation,
			props: route => ({
				lvevaluierung_id: route.query.lvevaluierung_id && route.query.lvevaluierung_id !== 'null'
						? Number(route.query.lvevaluierung_id)
						: null,
				lvevaluierung_lehrveranstaltung_id: route.query.lvevaluierung_lehrveranstaltung_id && route.query.lvevaluierung_lehrveranstaltung_id !== 'null'
						? Number(route.query.lvevaluierung_lehrveranstaltung_id)
						: null
			})
		}
	]
});

const app = Vue.createApp({
	components: {
		Evaluation
	},
	template: `<router-view></router-view>`
});

app.config.globalProperties.DateHelper = DateHelper;

app
	.use(router)
	.use(primevue.config.default, {zIndex: {overlay: 9999}})
	.use(Phrasen)
	.use(highchartsPlugin, {tagName: 'highcharts'})
	.directive('tooltip', tooltip)
	.mount('#evaluation-evaluation-main')