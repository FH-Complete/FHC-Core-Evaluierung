import BsModal from "../../../../../js/components/Bootstrap/Modal.js";
import FormForm from "../../../../../js/components/Form/Form.js";
import FormInput from "../../../../../js/components/Form/Input.js";
import ApiAdminFragebogen from "../../api/admin/fragebogen.js";
import FragebogenHelper from "../../helpers/FragebogenHelper.js";

export default {
	name: "FragebogenGruppeModal",
	components: {
		BsModal,
		FormForm,
		FormInput
	},
	props: {
		fragebogenId: {required: true}
	},
	emits: ['saved'],
	data() {
		return {
			GRUPPE_TYP: FragebogenHelper.GRUPPE_TYP,
			gruppe: {},
			hasFragen: false,		// label nur ohne Fragen möglich
			nurLesenGrund: null,	// Gesetzt: nur ansehen, Speichern gesperrt
			isSaving: false
		}
	},
	methods: {
		// Gruppe aus der Tabelle oder null für neu
		open(gruppe, nurLesenGrund) {
			this.gruppe = gruppe
				? {
					lvevaluierung_fragebogen_gruppe_id: gruppe.lvevaluierung_fragebogen_gruppe_id,
					typ: gruppe.typ,
					bezeichnung_de: gruppe.bezeichnung_de,
					bezeichnung_en: gruppe.bezeichnung_en
				}
				: {lvevaluierung_fragebogen_gruppe_id: null, typ: this.GRUPPE_TYP.GROUP, bezeichnung_de: '', bezeichnung_en: ''};
			this.hasFragen = !!gruppe && gruppe.anzahlFragen > 0;
			this.nurLesenGrund = nurLesenGrund;

			this.$refs.form.clearValidation();
			this.$refs.modal.show();
		},
		save() {
			const gruppe = {...this.gruppe, fragebogen_id: this.fragebogenId};
			const apiCall = gruppe.lvevaluierung_fragebogen_gruppe_id
				? ApiAdminFragebogen.updateGruppe(gruppe)
				: ApiAdminFragebogen.insertGruppe(gruppe);

			this.isSaving = true;
			this.$refs.form
				.call(apiCall)
				.then(result => {
					this.$fhcAlert.alertSuccess(this.$p.t('ui', 'gespeichert'));
					this.$refs.modal.hide();
					this.$emit('saved', result.data);
				})
				.catch(error => this.$fhcAlert.handleSystemError(error))
				.finally(() => this.isSaving = false);
		}
	},
	template: `
	<bs-modal ref="modal" dialog-class="modal-lg">
		<template #title>{{ gruppe.lvevaluierung_fragebogen_gruppe_id ? 'Gruppe bearbeiten' : 'Neue Gruppe' }}</template>

		<div v-if="nurLesenGrund" class="alert alert-warning">{{ nurLesenGrund }}</div>

		<form-form ref="form" @submit.prevent="save">
			<fieldset class="row g-3" :disabled="!!nurLesenGrund">
				<div class="col-md-6">
					<form-input label="Bezeichnung (DE) *" name="bezeichnung_de" v-model.trim="gruppe.bezeichnung_de" bs-feedback></form-input>
				</div>
				<div class="col-md-6">
					<form-input label="Bezeichnung (EN) *" name="bezeichnung_en" v-model.trim="gruppe.bezeichnung_en" bs-feedback></form-input>
				</div>
				<div class="col-12">
					<span class="form-label d-block">
						Typ
						<i
							class="fa-solid fa-circle-info text-secondary ms-1"
							v-tooltip
							title="group: Fragen in einer Card, einklappbar. card: Fragen in einer Card, immer angezeigt. label: nur Überschrift, ohne Fragen."
						></i>
					</span>
					<form-input type="select" name="typ" aria-label="Typ" class="w-auto" v-model="gruppe.typ" bs-feedback>
						<option
							v-for="typ in GRUPPE_TYP"
							:key="typ"
							:value="typ"
							:disabled="typ === GRUPPE_TYP.LABEL && hasFragen"
						>{{ typ }}</option>
					</form-input>
				</div>
			</fieldset>
		</form-form>

		<template #footer>
			<span :title="nurLesenGrund">
				<button type="button" class="btn btn-primary" :disabled="!!nurLesenGrund || isSaving" @click="save">
					<i class="fa-solid fa-floppy-disk me-2"></i>Speichern
				</button>
			</span>
		</template>
	</bs-modal>
	`
}