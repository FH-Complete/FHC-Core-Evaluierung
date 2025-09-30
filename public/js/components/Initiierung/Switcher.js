import FormForm from "../../../../../js/components/Form/Form.js";
import FormInput from "../../../../../js/components/Form/Input.js";
import ApiInitiierung from "../../api/initiierung.js";

export default {
	name: 'Switcher',
	props: {
		canSwitch: {type: Boolean, default: false},
		canSwitchInfo: {type: Array, default: () => []},
		selLveLv: { type: Object, required: true },
		lvLeitungen: { type: Array, default: () => [] }
	},
	components: {
		FormForm,
		FormInput
	},
	methods: {
		updateLvAufgeteilt(newVal) {
			if (!this.canSwitch) return;

			this.$api
				.call(ApiInitiierung.updateLvAufgeteilt(this.selLveLv.lvevaluierung_lehrveranstaltung_id, newVal))
				.catch(error => this.$fhcAlert.handleSystemError(error));
		},
		getLektorenInfoString(lektoren) {
			return lektoren.map(l => l.vorname + ' ' + l.nachname).join(', ');
		}
	},
	template: `
	<div class="switcher mt-3">
		<!-- Radio Buttons -->
		<div class="card mb-3">
			<div class="card-body">
				<!-- LV-Leitungen -->
				<div class="mb-3 pb-3 border-bottom" v-if="this.lvLeitungen">
					<i class="fa fa-star me-2"></i>
					<span class="d-none d-md-inline me-2">LV-Leitung:</span>
					<span v-html="getLektorenInfoString(lvLeitungen)"></span>
				</div>
				<!-- Switch Radio Buttons -->	
				<fieldset :disabled="!canSwitch">
					<div class="d-flex flex-wrap justify-content-md-between align-items-center">
						<div class="flex-grow-1 flex-md-grow-0">
							<div class="form-check form-check-inline ps-0">
								<form-input
									label="Gesamt-LV evaluieren"
									class="form-check-input"
									type="radio"
									:value="false"
									v-model="selLveLv.lv_aufgeteilt"
									 @update:modelValue="updateLvAufgeteilt"
								>
								</form-input>
							</div>
							<div class="form-check form-check-inline ps-0">
								<form-input
									label="LV auf Gruppenbasis evaluieren"
									class="form-check-input"
									type="radio"
									:value="true"
									v-model="selLveLv.lv_aufgeteilt"
									@update:modelValue="updateLvAufgeteilt"
								>
								</form-input>
							</div>
						</div>
						<div class="flex-grow-1 flex-md-grow-0 ms-auto">
							<span v-if="canSwitchInfo.length > 0">
								<i 
									class="fa fa-ban fa-xl text-danger" 
									:title="canSwitchInfo.join(', ')"
									data-bs-toggle="tooltip"
									data-bs-html="true">
								</i>
							</span>			
						<!--	<span v-if="canSwitchInfo.length > 0">{{canSwitchInfo.join(', ')}}</span>-->
						</div>
					</div><!--.div Switch Radio Buttons-->
				</fieldset>
			</div><!--.card-body -->
		</div><!--.card -->
	</div>
	`
}