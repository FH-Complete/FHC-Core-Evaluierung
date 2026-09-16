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
	emits: ['onPreviewLvAufgeteilt'],
	components: {
		FormForm,
		FormInput
	},
	data(){
		return {
			previewLvAufgeteilt: this.selLveLv.lv_aufgeteilt,
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
			// Voranzeige erstellecn
			this.$emit('onPreviewLvAufgeteilt', this.previewLvAufgeteilt);
		},
		cancelPreview() {
			this.previewLvAufgeteilt = this.selLveLv.lv_aufgeteilt;
			this.$emit('onPreviewLvAufgeteilt', this.previewLvAufgeteilt);
		},
		updateLvAufgeteilt() {
			if (!this.canSwitch) return;

			this.$api
				.call(ApiInitiierung.updateLvAufgeteilt(this.selLveLv.lvevaluierung_lehrveranstaltung_id, this.previewLvAufgeteilt))
				.then(() => {
					this.selLveLv.lv_aufgeteilt =  this.previewLvAufgeteilt;
					this.$fhcAlert.alertSuccess(this.$p.t('ui', 'gespeichert'))
				})
				.catch(error => this.$fhcAlert.handleSystemError(error));
		},
		getLektorenInfoString(lektoren) {
			return lektoren.map(l => l.vorname + ' ' + l.nachname).join(', ');
		}
	},
	template: ` 	
	<div class="switcher mt-4">
		<div class="border border-secondary-subtle rounded-3 p-3 mb-3">
			<!-- LV-Leitungen -->
			<div class="mb-3 pb-3 border-bottom" v-if="this.lvLeitungen">
				<span class="me-2 fw-bolder">LV-Leitung:</span>
				<span v-html="getLektorenInfoString(lvLeitungen)"></span>
			</div>	
			<!-- Evaluierungsebene -->
			<div class="mb-3">
				<span>Evaluierungsebene: <strong class="text-body">{{ selLveLv.lv_aufgeteilt ? 'Gruppenbasis' : 'Gesamt-LV' }}</strong></span>
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
			<div class="rounded-3" :class="{ 'alert alert-secondary p-3 mb-0': !canSwitch }">
				<div 
					v-if="!canSwitch && canSwitchInfo.length > 0"
					class="d-flex flex-wrap align-items-center gap-2 mb-3"
				>
				   <!-- <i class="fa fa-ban text-muted fa-lg"></i>-->
					<span v-html="canSwitchInfo.join('<br>')"></span>
				</div>
				<!-- Evaluierungsebene wechseln -->
				<fieldset :disabled="!canSwitch">
					<!-- Radiobuttons -->
					<div class="d-flex flex-wrap flex-md-nowrap gap-2 align-items-start">
						<div class="flex-grow-1 flex-md-grow-0 d-flex flex-wrap gap-2 align-items-center">
							<div class="form-check form-check-inline ps-0">
								<form-input
									label="Gesamt-LV evaluieren"
									class="form-check-input"
									type="radio"
									:value="false"
									v-model="previewLvAufgeteilt"
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
									v-model="previewLvAufgeteilt"
									 @change="onSwitch"
								>
								</form-input>
							</div>
						</div>
					</div><!--.div Radiobuttons-->
					<!-- Voranzeige Alert-->
					<div 
						v-if="canSwitch && previewLvAufgeteilt !== selLveLv.lv_aufgeteilt"
						class="alert alert-primary d-flex flex-wrap align-items-center gap-2 mt-3 mb-0 text-primary fw-bold"
					>
						<span>Voranzeige: {{ previewLvAufgeteilt ? 'Gruppenbasis' : 'Gesamt-LV' }}
							<i 
								class="ms-2 fa fa-info-circle text-primary fa-lg" 
								:title="selLveLv.lv_aufgeteilt ? infoEvaluierungByLe : infoEvaluierungByLv"
								v-tooltip="previewLvAufgeteilt ? infoEvaluierungByLe : infoEvaluierungByLv"
								data-bs-html="true"
								data-bs-custom-class="tooltip-left">
							</i>
							Jetzt übernehmen und speichern?
						</span>
						   <div class="d-flex gap-2 ms-2">
							  
							  <button type="button" class="btn btn-primary" @click="updateLvAufgeteilt()">Evaluierungsebene speichern</button>
							  <button type="button" class="btn btn-outline-primary" @click="cancelPreview()">Abbrechen</button>
						   </div>
					</div><!--.div Voranzeige Alert-->
				</fieldset>	
			</div>
		</div><!--.card -->
	</div>
	`
}