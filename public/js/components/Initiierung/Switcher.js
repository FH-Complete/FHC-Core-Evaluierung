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
	emits: ['onUpdateLvAufgeteilt'],
	components: {
		FormForm,
		FormInput
	},
	data(){
		return {
			infoEvaluierungByLv:  `
				Die gesamte LV wird evaluiert.<br><br>
				Sie können die Voreinstellungen zum Start der Evaluierung und der Dauer der Evaluierung aktiv verändern/anpassen.<br><br>
				Der Zugriff auf die Evaluierung ist für Studierende nur in diesem Zeitfenster möglich. Sie können den Zeitraum jederzeit korrigieren, solange die Evaluierung noch nicht abgeschlossen wurde.
			`,
			infoEvaluierungByLe:  `
				Diese LV wird auf Gruppenbasis evaluiert.<br><br>
				Sie können die Voreinstellungen zum Start der Evaluierung und der Dauer der Evaluierung aktiv verändern/anpassen.<br><br>
				Der Zugriff auf die Evaluierung ist für Studierende nur in diesem Zeitfenster möglich. Sie können den Zeitraum jederzeit korrigieren, solange die Evaluierung noch nicht abgeschlossen wurde.
			`
		}
	},
	methods: {
		updateLvAufgeteilt(newVal) {
			if (!this.canSwitch) return;

			this.$api
				.call(ApiInitiierung.updateLvAufgeteilt(this.selLveLv.lvevaluierung_lehrveranstaltung_id, newVal))
				.then(() => this.$emit('onUpdateLvAufgeteilt', newVal))
				.catch(error => this.$fhcAlert.handleSystemError(error));
		},
		getLektorenInfoString(lektoren) {
			return lektoren.map(l => l.vorname + ' ' + l.nachname).join(', ');
		}
	},
	template: `
	<div class="switcher mt-4">
		<div class="mb-3">
			<!-- LV-Leitungen -->
			<div class="mb-3 pb-3 border-bottom" v-if="this.lvLeitungen">
		<!--		<i class="fa fa-star me-2"></i>-->
				<span class="me-2 fw-bolder">LV-Leitung:</span>
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
					<div class="flex-md-grow-0 ms-auto">
						<span v-if="canSwitchInfo.length > 0">
							<i 
								class="fa fa-ban fa-lg text-muted" 
								:title="canSwitchInfo.join(', ')"
								v-tooltip="canSwitchInfo.join(', ')"
								data-bs-html="true"
								data-bs-custom-class="tooltip-left">
							</i>
						</span>			
					<!--	<span v-if="canSwitchInfo.length > 0">{{canSwitchInfo.join(', ')}}</span>-->
						<span class="ms-2">
							<i 
								class="fa fa-info-circle text-primary fa-lg" 
								:title="selLveLv.lv_aufgeteilt ? infoEvaluierungByLe : infoEvaluierungByLv"
								v-tooltip="selLveLv.lv_aufgeteilt ? infoEvaluierungByLe : infoEvaluierungByLv"
								data-bs-html="true"
								data-bs-custom-class="tooltip-left">
							</i>
						</span>	
					</div>
				</div><!--.div Switch Radio Buttons-->
			</fieldset>
		</div><!--.card -->
	</div>
	`
}