import LveLogin from '../components/LvE/Login.js';
import LveEvaluierung from '../components/LvE/Evaluierung.js';
import LveLogout from '../components/LvE/Logout.js';
import SpracheDropdown from "../widgets/SpracheDropdown.js";
import DateHelper from '../helpers/DateHelper';
import Phrasen from "../../../../js/plugins/Phrasen.js";

const selectedLanguage = Vue.ref(FHC_JS_DATA_STORAGE_OBJECT.user_language);
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
				reason: route.query.reason,
				date: route.query.date
			})
		},
	]
});

const app = Vue.createApp({
	components: {
		SpracheDropdown
	},
	data() {
		return {
			selectedLanguage
		};
	},
	provide() {
		return {
			selectedLanguage
		};
	},
	methods: {
		onLanguageChanged(language) {
			this.selectedLanguage = language;
		}
	},
	template: `
		<header id="lve-evaluierung-header" class="fixed-top" role="banner">
			<div class="row fhc-bgc-blue py-2 px-3 align-items-center">
				<div class="col text-start text-light">{{ $p.t('global/lvevaluierung') }}</div>
				<div class="col text-end">
					<sprache-dropdown @language-changed="onLanguageChanged"></sprache-dropdown>
				</div>
			</div>
		</header>
		<main id="lve-evaluierung-body" class="container-fluid" role="main">
			<router-view></router-view>
		</main>
	`
});

app
	.use(router)
	.use(primevue.config.default, {zIndex: {overlay: 9999}})
	.use(Phrasen)
	.use(DateHelper)
	.mount('#lve-evaluierung-main')