import FormForm from "../../../../../js/components/Form/Form.js";
import FormInput from "../../../../../js/components/Form/Input.js";

export default {
	name: "EvaluationReflexion",
	components: {
		FormForm,
		FormInput,
	},
	data() {
		return {
		}
	},
	methods: {
	},
	template: `
	<div class="evaluation-evaluation-reflexion">
		<h3 class="mb-4">LV-Reflexion</h3>
		<div class="row mb-3">
			<div class="col-12 mb-3">
				<div class="card">
					<div class="card-body d-flex justify-content-center align-items-center">
						<span><i class="fa fa-table-cells fa-8x"></i></span>
					</div>
				</div>
			</div>
			<div class="col-12 d-flex justify-content-end">
				<button class="btn btn-primary me-2">Speichern</button>
			</div>
		</div>
	</div>	
	`
}