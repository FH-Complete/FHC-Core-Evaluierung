import Studienbereich from "../../components/Evaluation/Studienbereich.js";
import Phrasen from "../../../../../js/plugins/Phrasen.js";
import highchartsPlugin from "../../../../../js/plugins/highchartsVue.js";
import tooltip from "../../../../../js/directives/tooltip.js";

const ciPath = FHC_JS_DATA_STORAGE_OBJECT.app_root.replace(/(https:|)(^|\/\/)(.*?\/)/g, '') + FHC_JS_DATA_STORAGE_OBJECT.ci_router;
const router = VueRouter.createRouter({
	history: VueRouter.createWebHistory(),
	routes: [
		{
			path: `/${ciPath}/extensions/FHC-Core-Evaluierung/Evaluation/Studienbereich`,
			name: 'Studienbereich',
			component: Studienbereich
		}
	]
});

const app = Vue.createApp({
	components: {
		Studienbereich
	},
	template: `<router-view></router-view>`
});

app
	.use(router)
	.use(primevue.config.default, {zIndex: {overlay: 9999}})
	.use(Phrasen)
	.use(highchartsPlugin, {tagName: 'highcharts'})
	.directive('tooltip', tooltip)
	.mount('#evaluation-studienbereich-main')