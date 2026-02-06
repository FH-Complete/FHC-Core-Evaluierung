import FormForm from "../../../../../js/components/Form/Form.js";
import FormInput from "../../../../../js/components/Form/Input.js";
import EinmeldungCreate from '../../../../FHC-Core-LVKVP/js/components/einmeldung/create.js';
import EinmeldungList from '../../../../FHC-Core-LVKVP/js/components/einmeldung/list.js';

export default {
	name: "EvaluationEinmeldung",
	components: {
		FormForm,
		FormInput,
		create:EinmeldungCreate,
		list:EinmeldungList
	},
	inject: [
		'evalData'
	],
	data() {
		return {

		}
	},
	computed: {
	},
	methods: {
	},
	template: `
	<div class="evaluation-evaluation-einmeldung">
		<h3 class="mb-4">Einmeldung LV Weiterentwicklung</h3>
		<div class="row mb-3">
			<div class="col-12 mb-3">
				<div class="card">
						<div class="card-body d-flex">
							<div class="col-8">
								<create
									:templateid="evalData.lehrveranstaltung_template_id"
									:sprache="evalData.sprache"
									source="lvevaluierung"
									lvevalsem="evalData.studiensemester_kurzbz"
									@op_workpackage_created="$refs.opwplist.fetchWorkpackages()"
								>
								</create>
							</div>
							<div class="col-4">
								<list 
									ref="opwplist"
									:templateid="evalData.lehrveranstaltung_template_id"
									:sprache="evalData.sprache"
								></list>
							</div>
						</div>
				</div>
			</div>
		</div>
	</div>	
	`
}