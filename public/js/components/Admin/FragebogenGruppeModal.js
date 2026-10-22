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
			gruppe: {},			// Formulardaten, gespeichert erst mit Speichern
			hasFragen: false,	// Überschrift nur ohne Fragen möglich
			isSaving: false
		}
	},
	methods: {
		// Ohne Gruppe: neue Gruppe anlegen
		open(gruppe) {
			this.gruppe = {
				lvevaluierung_fragebogen_gruppe_id: gruppe ? gruppe.lvevaluierung_fragebogen_gruppe_id : null,
				typ: gruppe ? gruppe.typ : this.GRUPPE_TYP.GROUP,
				bezeichnung_de: gruppe ? gruppe.bezeichnung_de : '',
				bezeichnung_en: gruppe ? gruppe.bezeichnung_en : ''
			};
			this.hasFragen = !!gruppe && gruppe.fragen.length > 0;

			this.$refs.form.clearValidation();
			this.$refs.modal.show();
		},
		save() {
			const apiCall = this.gruppe.lvevaluierung_fragebogen_gruppe_id
				? ApiAdminFragebogen.updateGruppe(this.gruppe)
				: ApiAdminFragebogen.insertGruppe({...this.gruppe, fragebogen_id: this.fragebogenId});

			this.isSaving = true;
			this.$refs.form
				.call(apiCall)
				.then(result => {
					this.$fhcAlert.alertSuccess(this.$p.t('ui', 'gespeichert'));
					this.$refs.modal.hide();
					this.$emit('saved', {...this.gruppe, lvevaluierung_fragebogen_gruppe_id: result.data});
				})
				.catch(error => this.$fhcAlert.handleSystemError(error))
				.finally(() => this.isSaving = false);
		}
	},
	template: `
	<bs-modal ref="modal" dialog-class="modal-lg">
		<template #title>{{ gruppe.lvevaluierung_fragebogen_gruppe_id ? 'Gruppe bearbeiten' : 'Neue Gruppe' }}</template>

		<form-form ref="form" class="row g-3" @submit.prevent="save">
			<div class="col-md-6">
				<form-input label="Bezeichnung (DE) *" name="bezeichnung_de" v-model.trim="gruppe.bezeichnung_de" bs-feedback></form-input>
			</div>
			<div class="col-md-6">
				<form-input label="Bezeichnung (EN) *" name="bezeichnung_en" v-model.trim="gruppe.bezeichnung_en" bs-feedback></form-input>
			</div>
			<div class="col-12">
				<!-- Eigenes Label wegen Info-Icon -->
				<span class="form-label d-block">
					Typ
					<i
						class="fa-solid fa-circle-info text-secondary ms-1"
						v-tooltip
						title="Fragengruppe (einklappbar): Fragen sind in einer einklappbaren Fragengruppe. Fragengruppe (fix): Fragen dieser Gruppe werden immer angezeigt. Nur Überschrift: wird nur als Überschrift verwendet, ohne Fragen."
					></i>
				</span>
				<form-input type="select" class="w-auto" name="typ" aria-label="Typ" v-model="gruppe.typ" bs-feedback>
					<option :value="GRUPPE_TYP.GROUP">Fragengruppe (einklappbar)</option>
					<option :value="GRUPPE_TYP.CARD">Fragengruppe (fix)</option>
					<option :value="GRUPPE_TYP.LABEL" :disabled="hasFragen">Nur Überschrift</option>
				</form-input>
				<div v-if="hasFragen" class="form-text">Nur Überschrift ist erst ohne Fragen möglich.</div>
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