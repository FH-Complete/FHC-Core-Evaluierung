import LveStarten from '../components/Initiierung/LveStarten.js';
import Phrasen from "../../../../js/plugins/Phrasen.js";
import tooltip from "../../../../js/directives/tooltip.js";

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
	.use(Phrasen)
	.directive('tooltip', tooltip)
	.mount('#lve-initiierung-main')