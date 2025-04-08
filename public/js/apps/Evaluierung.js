import LveLogin from '../components/LvE/Login.js';
import LveEvaluierung from '../components/LvE/Evaluierung.js';
import LveLogout from '../components/LvE/Logout.js';
import FhcAlert from '../../../../js/plugin/FhcAlert.js';
import FhcApi from "../../../../js/plugin/FhcApi.js";
import Phrasen from "../../../../js/plugin/Phrasen.js";

const ciPath = FHC_JS_DATA_STORAGE_OBJECT.app_root.replace(/(https:|)(^|\/\/)(.*?\/)/g, '') + FHC_JS_DATA_STORAGE_OBJECT.ci_router;

const router = VueRouter.createRouter({
	history: VueRouter.createWebHistory(),
	routes: [
		{ path: `/${ciPath}/extensions/FHC-Core-Evaluierung/Evaluierung`, name: 'Login', component: LveLogin },
		{ path: `/${ciPath}/extensions/FHC-Core-Evaluierung/Evaluierung/:lvevaluierung_id`, name: 'Evaluierung', component: LveEvaluierung },
		{ path: `/${ciPath}/extensions/FHC-Core-Evaluierung/Evaluierung`, name: 'Logout', component: LveLogout }
	]
});

const app = Vue.createApp();
app
	.use(router)
	.use(primevue.config.default, {zIndex: {overlay: 9999}})
	.use(FhcAlert)
	.use(FhcApi)
	.use(Phrasen)
	.mount('#main')