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
				Die Evaluierung der LV erfolgt auf Gesamt-Ebene.<br><br>
				Das Start- und Enddatum der LV-Evaluierung kann geändert bzw. angepasst werden, solange die Studierenden noch nicht eingeladen wurden.<br><br>
				Der Zugriff für Studierende ist auf dieses Evaluierungszeitfenster beschränkt.
			`,
			infoEvaluierungByLe:  `
				Die Evaluierung der LV erfolgt auf Gruppen-Ebene. <br><br>
				Das Start- und Enddatum der LV-Evaluierung kann geändert bzw. angepasst werden, solange die Studierenden noch nicht eingeladen wurden.<br><br>
				Der Zugriff für Studierende ist auf dieses Evaluierungszeitfenster beschränkt.
			`
		}
	},
	methods: {
		onSwitch() {
			this.$emit('onUpdateLvAufgeteilt', this.selLveLv.lv_aufgeteilt);
		},
		updateLvAufgeteilt() {
			if (!this.canSwitch) return;

			this.$api
				.call(ApiInitiierung.updateLvAufgeteilt(this.selLveLv.lvevaluierung_lehrveranstaltung_id, this.selLveLv.lv_aufgeteilt))
				.then(() => this.$fhcAlert.alertSuccess(this.$p.t('ui', 'gespeichert')))
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
				<div class="d-flex flex-wrap flex-md-nowrap gap-2 align-items-start">
					<div class="flex-grow-1 flex-md-grow-0 d-flex flex-wrap gap-2 align-items-center">
						<div class="form-check form-check-inline ps-0">
							<form-input
								label="Gesamt-LV evaluieren"
								class="form-check-input"
								type="radio"
								:value="false"
								v-model="selLveLv.lv_aufgeteilt"
								 @change="onSwitch"
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
								 @change="onSwitch"
							>
							</form-input>
						</div>
						<div class="flex-grow-1 flex-md-grow-0 align-self-end">
							<button 
							  type="button" 
							  class="btn btn-primary mt-2 mt-md-0 ms-md-2 w-100 w-md-auto"
							  @click="updateLvAufgeteilt()"
							>
							  Speichern
							</button>
						</div>
					</div>
					<div class="flex-md-grow-0 ms-auto mt-2 mt-md-0 d-flex align-items-center">
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