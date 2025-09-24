import FormForm from "../../../../../js/components/Form/Form.js";
import FormInput from "../../../../../js/components/Form/Input.js";
import ApiInitiierung from "../../api/initiierung.js";

export default {
	name: 'Switcher',
	props: {
		canSwitch: {type: Boolean, default: false},
		lveLv: { type: Object, required: true },
		lvevaluierungen: { type: Array, default: () => [] },
		lvLeitungen: { type: Array, default: () => [] },
		selLveLvDataGroupedByLeUnique: { type: Array, default: () => [] }, // todo ÄNDERN
	},
	components: {
		FormForm,
		FormInput
	},
	methods: {
		updateLvAufgeteilt(newVal) {
			if (this.lvevaluierungen.length > 0) return;

			this.$api
				.call(ApiInitiierung.updateLvAufgeteilt(this.lveLv.lvevaluierung_lehrveranstaltung_id, newVal))
				.catch(error => this.$fhcAlert.handleSystemError(error));
		},
		getLektorenInfoString(lektoren) {
			return lektoren.map(l => l.vorname + ' ' + l.nachname).join(', ');
		}
	},
	template: `
	<div class="switcher">
		<fieldset :disabled="!canSwitch">
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
					<div class="d-flex flex-wrap justify-content-md-between align-items-center">
						<div class="flex-grow-1 flex-md-grow-0">
							<div class="form-check form-check-inline ps-0">
								<form-input
									label="Gesamt-LV evaluieren"
									class="form-check-input"
									type="radio"
									:value="false"
									v-model="lveLv.lv_aufgeteilt"
									:disabled="lvevaluierungen.length > 0"
									 @update:modelValue="updateLvAufgeteilt"
								>
								</form-input>
							</div>
							<div 
								class="form-check form-check-inline ps-0" 													
								:title="selLveLvDataGroupedByLeUnique.length === 0 
									? 'Nur verfügbar, wenn Gruppen eindeutig Lehrenden zugeordnet sind.' 
									: ''"
								data-bs-toggle="tooltip"
								data-bs-placement="top"
							>
								<form-input
									label="LV auf Gruppenbasis evaluieren"
									class="form-check-input"
									type="radio"
									:value="true"
									v-model="lveLv.lv_aufgeteilt"
									:disabled="lvevaluierungen.length > 0 || selLveLvDataGroupedByLeUnique.length === 0"
									 @update:modelValue="updateLvAufgeteilt"
								>
								</form-input>
							</div>
						</div>
					</div>
				</div><!--.card-body -->
			</div><!--.card -->
		</fieldset>
	</div>
	`
}