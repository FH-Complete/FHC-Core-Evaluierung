import BsModal from "../../../../../js/components/Bootstrap/Modal.js";
import FormForm from "../../../../../js/components/Form/Form.js";
import FormInput from "../../../../../js/components/Form/Input.js";
import ApiAdminFragebogen from "../../api/admin/fragebogen.js";
import FragebogenHelper from "../../helpers/FragebogenHelper.js";

export default {
	name: "FragebogenFrageModal",
	components: {
		BsModal,
		FormForm,
		FormInput
	},
	props: {
		fragebogenId: {default: null},
		gruppeId: {default: null}	// Gruppe, der eine neue Frage zugeordnet wird
	},
	emits: ['saved'],
	data() {
		return {
			FRAGE_TYP: FragebogenHelper.FRAGE_TYP,
			frage: {antworten: []},
			nurLesenGrund: null,	// Gesetzt: nur ansehen, Speichern gesperrt
			isSaving: false
		}
	},
	methods: {
		// Frage-ID aus der Tabelle oder null für neu
		open(frage_id, nurLesenGrund) {
			this.nurLesenGrund = nurLesenGrund;
			this.$refs.form.clearValidation();

			if (!frage_id) {
				this.frage = {
					lvevaluierung_frage_id: null,
					typ: this.FRAGE_TYP.SINGLERESPONSE,
					bezeichnung_de: '',
					bezeichnung_en: '',
					placeholder_de: '',
					placeholder_en: '',
					verpflichtend: false,
					antworten: FragebogenHelper.defaultAntworten()
				};
				this.$refs.modal.show();
				return;
			}

			this.$api
				.call(ApiAdminFragebogen.getFrage(frage_id))
				.then(result => {
					this.frage = result.data;
					this.$refs.modal.show();
				})
				.catch(error => this.$fhcAlert.handleSystemError(error));
		},
		changeTyp(typ) {
			this.frage.typ = typ;
			// text ohne Antworten, singleresponse mit Standard-Antworten
			this.frage.antworten = typ === this.FRAGE_TYP.TEXT
				? []
				: FragebogenHelper.defaultAntworten();
		},
		save() {
			const frage = {
				...this.frage,
				fragebogen_id: this.fragebogenId,
				lvevaluierung_fragebogen_gruppe_id: this.gruppeId
			};
			const apiCall = frage.lvevaluierung_frage_id
				? ApiAdminFragebogen.updateFrage(frage)
				: ApiAdminFragebogen.insertFrage(frage);

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
	<bs-modal ref="modal" dialog-class="modal-lg modal-dialog-scrollable">
		<template #title>{{ frage.lvevaluierung_frage_id ? 'Frage bearbeiten' : 'Neue Frage' }}</template>

		<div v-if="nurLesenGrund" class="alert alert-warning">{{ nurLesenGrund }}</div>

		<form-form ref="form" @submit.prevent="save">
			<fieldset class="d-flex flex-column gap-3" :disabled="!!nurLesenGrund">
				<div>
					<span class="form-label fw-semibold d-block">Fragetyp</span>
					<div v-for="typ in FRAGE_TYP" :key="typ" class="form-check form-check-inline">
						<input type="radio" class="form-check-input" name="frageTyp" :id="'frageTyp-' + typ" :checked="frage.typ === typ" @change="changeTyp(typ)">
						<label class="form-check-label" :for="'frageTyp-' + typ">{{ typ }}</label>
					</div>
				</div>

				<div class="row g-3">
					<div class="col-md-6">
						<form-input type="textarea" rows="2" label="Fragetext (DE) *" name="bezeichnung_de" v-model.trim="frage.bezeichnung_de" bs-feedback></form-input>
					</div>
					<div class="col-md-6">
						<form-input type="textarea" rows="2" label="Fragetext (EN) *" name="bezeichnung_en" v-model.trim="frage.bezeichnung_en" bs-feedback></form-input>
					</div>
				</div>

				<form-input type="checkbox" label="Pflichtfrage" name="verpflichtend" v-model="frage.verpflichtend"></form-input>

				<!-- text: Placeholder -->
				<div v-if="frage.typ === FRAGE_TYP.TEXT" class="row g-3">
					<div class="col-md-6">
						<form-input label="Placeholder (DE)" name="placeholder_de" v-model.trim="frage.placeholder_de" bs-feedback></form-input>
					</div>
					<div class="col-md-6">
						<form-input label="Placeholder (EN)" name="placeholder_en" v-model.trim="frage.placeholder_en" bs-feedback></form-input>
					</div>
				</div>

				<!-- singleresponse: fünf Antworten, Wert 1-5 fest -->
				<div v-else>
					<span class="form-label fw-semibold d-block">Antwortoptionen</span>
					<table class="table table-sm align-middle mb-0">
						<thead>
							<tr><th>DE</th><th>EN</th><th>Wert</th></tr>
						</thead>
						<tbody>
							<tr v-for="(antwort, index) in frage.antworten" :key="index">
								<td><input class="form-control form-control-sm" v-model.trim="antwort.bezeichnung_de" aria-label="Antwort DE"></td>
								<td><input class="form-control form-control-sm" v-model.trim="antwort.bezeichnung_en" aria-label="Antwort EN"></td>
								<td>{{ antwort.wert }}</td>
							</tr>
						</tbody>
					</table>
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