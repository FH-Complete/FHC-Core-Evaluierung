import BsModal from "../../../../../js/components/Bootstrap/Modal.js";
import FormForm from "../../../../../js/components/Form/Form.js";
import FormInput from "../../../../../js/components/Form/Input.js";
import ApiAdminFragebogen from "../../api/admin/fragebogen.js";

export default {
	name: "FragebogenModal",
	components: {
		BsModal,
		FormForm,
		FormInput
	},
	emits: ['saved'],
	data() {
		return {
			fragebogen: {},
			hasAntworten: false,	// Bereits beantwortet: nur Gültig bis änderbar
			isSaving: false
		}
	},
	methods: {
		// Fragebogen aus der Tabelle oder null für neu
		open(fb) {
			this.fragebogen = fb
				? {
					fragebogen_id: fb.fragebogen_id,
					bezeichnung_de: fb.bezeichnung_de,
					bezeichnung_en: fb.bezeichnung_en,
					gueltig_von: fb.gueltig_von,
					gueltig_bis: fb.gueltig_bis
				}
				: {fragebogen_id: null, bezeichnung_de: '', bezeichnung_en: '', gueltig_von: null, gueltig_bis: null};
			this.hasAntworten = !!fb && fb.hasAntworten;

			this.$refs.form.clearValidation();
			this.$refs.modal.show();
		},
		save() {
			const apiCall = this.fragebogen.fragebogen_id
				? ApiAdminFragebogen.updateFragebogen(this.fragebogen)
				: ApiAdminFragebogen.insertFragebogen(this.fragebogen);

			this.isSaving = true;
			this.$refs.form
				.call(apiCall)
				.then(result => {
					this.$fhcAlert.alertSuccess(this.$p.t('ui', 'gespeichert'));
					this.$refs.modal.hide();
					this.$emit('saved', this.fragebogen.fragebogen_id || result.data);
				})
				.catch(error => this.$fhcAlert.handleSystemError(error))
				.finally(() => this.isSaving = false);
		}
	},
	template: `
	<bs-modal ref="modal" dialog-class="modal-lg">
		<template #title>{{ fragebogen.fragebogen_id ? 'Fragebogen bearbeiten' : 'Neuer Fragebogen' }}</template>

		<div v-if="hasAntworten" class="alert alert-warning">Bereits beantwortet – nur Gültig bis änderbar.</div>

		<form-form ref="form" class="row g-3" @submit.prevent="save">
			<div class="col-md-6">
				<form-input label="Bezeichnung (DE) *" name="bezeichnung_de" v-model.trim="fragebogen.bezeichnung_de" :disabled="hasAntworten" bs-feedback></form-input>
			</div>
			<div class="col-md-6">
				<form-input label="Bezeichnung (EN) *" name="bezeichnung_en" v-model.trim="fragebogen.bezeichnung_en" :disabled="hasAntworten" bs-feedback></form-input>
			</div>
			<div class="col-md-6">
				<form-input
					type="datepicker"
					label="Gültig von *"
					name="gueltig_von"
					v-model="fragebogen.gueltig_von"
					locale="de"
					text-input
					format="dd.MM.yyyy"
					model-type="yyyy-MM-dd"
					:enable-time-picker="false"
					:auto-apply="true"
					:disabled="hasAntworten"
					bs-feedback
				></form-input>
			</div>
			<div class="col-md-6">
				<form-input
					type="datepicker"
					label="Gültig bis"
					name="gueltig_bis"
					v-model="fragebogen.gueltig_bis"
					locale="de"
					text-input
					format="dd.MM.yyyy"
					model-type="yyyy-MM-dd"
					:enable-time-picker="false"
					:auto-apply="true"
					bs-feedback
				></form-input>
				<div class="form-text">Leer = unbefristet</div>
			</div>
		</form-form>

		<template #footer>
			<button type="button" class="btn btn-primary" :disabled="isSaving" @click="save">
				<i class="fa-solid fa-floppy-disk me-2"></i>Speichern
			</button>
		</template>
	</bs-modal>
	`
}