import Studiengaenge from "../../components/Evaluation/Studiengaenge.js";
import Phrasen from "../../../../../js/plugins/Phrasen.js";
import highchartsPlugin from "../../../../../js/plugins/highchartsVue.js"

const ciPath = FHC_JS_DATA_STORAGE_OBJECT.app_root.replace(/(https:|)(^|\/\/)(.*?\/)/g, '') + FHC_JS_DATA_STORAGE_OBJECT.ci_router;
const router = VueRouter.createRouter({
	history: VueRouter.createWebHistory(),
	routes: [
		{
			path: `/${ciPath}/extensions/FHC-Core-Evaluierung/Evaluation/Studiengaenge`,
			name: 'Studiengaenge',
			component: Studiengaenge
		}
	]
});

const app = Vue.createApp({
	components: {
		Studiengaenge
	},
	template: `<router-view></router-view>`
});

app
	.use(router)
	.use(primevue.config.default, {zIndex: {overlay: 9999}})
	.use(Phrasen)
	.use(highchartsPlugin, {tagName: 'highcharts'})
	.mount('#evaluation-studiengaenge-main')