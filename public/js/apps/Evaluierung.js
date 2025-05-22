import LveLogin from '../components/LvE/Login.js';
import LveEvaluierung from '../components/LvE/Evaluierung.js';
import LveLogout from '../components/LvE/Logout.js';
import DateHelper from '../helpers/DateHelper';
import FhcAlert from '../../../../js/plugins/FhcAlert.js';
import FhcApi from "../../../../js/plugins/Api.js";
import Phrasen from "../../../../js/plugins/Phrasen.js";

const ciPath = FHC_JS_DATA_STORAGE_OBJECT.app_root.replace(/(https:|)(^|\/\/)(.*?\/)/g, '') + FHC_JS_DATA_STORAGE_OBJECT.ci_router;

const router = VueRouter.createRouter({
	history: VueRouter.createWebHistory(),
	routes: [
		{ path: `/${ciPath}/extensions/FHC-Core-Evaluierung/Evaluierung`, name: 'Login', component: LveLogin },
		{ path: `/${ciPath}/extensions/FHC-Core-Evaluierung/Evaluierung/:code`, name: 'Evaluierung', component: LveEvaluierung },
		{
			path: `/${ciPath}/extensions/FHC-Core-Evaluierung/Evaluierung`,
			name: 'Logout',
			component: LveLogout,
			props: route => ({
				title: route.query.title,
				content: route.query.content
			})
		},
	]
});

const app = Vue.createApp();
app
	.use(router)
	.use(primevue.config.default, {zIndex: {overlay: 9999}})
	.use(FhcAlert)
	.use(FhcApi)
	.use(Phrasen)
	.use(DateHelper)
	.mount('#main')