import BsModal from "../../../../../js/components/Bootstrap/Modal.js";
import FormInput from "../../../../../js/components/Form/Input.js";
import FragebogenHelper from "../../helpers/FragebogenHelper.js";

export default {
	name: "FragebogenFrageModal",
	components: {
		BsModal,
		FormInput,
	},
	props: {
		isLocked: Boolean
	},
	data() {
		return {
			FRAGE_TYP: FragebogenHelper.FRAGE_TYP,
			frage: null,	// Referenz auf bearbeitete Frage
			typen: [
				{value: FragebogenHelper.FRAGE_TYP.SINGLERESPONSE, label: 'Single Response'},
				{value: FragebogenHelper.FRAGE_TYP.TEXT, label: 'Freitext'}
			]
		}
	},
	computed: {
		isReadonly() {
			return this.isLocked || !!this.frage?.gesperrt;
		},
	},
	methods: {
		open(frage) {
			this.frage = frage;
			this.$refs.modal.show();
		},
		changeTyp(typ) {
			this.frage.typ = typ;
			// Freitext ohne Antworten, Single Response mit Standard-Antworten
			this.frage.antworten = typ === this.FRAGE_TYP.TEXT
					? []
					: FragebogenHelper.defaultAntworten();
		},
	},
	template: `
	<bs-modal ref="modal" dialog-class="modal-lg modal-dialog-scrollable">
		<template #title>{{ isReadonly ? 'Frage ansehen' : 'Frage bearbeiten' }}</template>

		<template v-if="frage">
			<div v-if="frage.gesperrt && !isLocked" class="alert alert-warning">
				Diese Frage wurde bereits beantwortet und kann nicht geändert werden.
				Für Änderungen bitte eine neue Frage erstellen.
			</div>
			<div v-else-if="frage.anzahlAndere > 0 && !isLocked" class="alert alert-info">
				Diese Frage wurde noch nicht beantwortet und ist daher änderbar.
				Sie wird aber auch in anderen Fragebögen verwendet, Änderungen gelten dort mit.
				Soll sie nur hier anders lauten, bitte eine neue Frage erstellen.
			</div>
			<fieldset class="d-flex flex-column gap-3" :disabled="isReadonly">
				<div>
					<span class="form-label fw-semibold d-block">Fragetyp</span>
					<div class="btn-group" role="group" aria-label="Fragetyp">
						<template v-for="t in typen" :key="t.value">
							<input type="radio" class="btn-check" name="frageTyp" :id="'frageTyp-' + t.value" :checked="frage.typ === t.value" @change="changeTyp(t.value)">
							<label class="btn btn-outline-primary" :for="'frageTyp-' + t.value">{{ t.label }}</label>
						</template>
					</div>
				</div>

				<div class="row g-3">
					<div class="col-md-6">
						<form-input type="textarea" rows="2" label="Fragetext (DE) *" name="frage_bezeichnung_de" v-model.trim="frage.bezeichnung_de" bs-feedback></form-input>
					</div>
					<div class="col-md-6">
						<form-input type="textarea" rows="2" label="Fragetext (EN) *" name="frage_bezeichnung_en" v-model.trim="frage.bezeichnung_en" bs-feedback></form-input>
					</div>
				</div>

				<form-input type="checkbox" label="Pflichtfrage" name="frage_verpflichtend" v-model="frage.verpflichtend"></form-input>

				<!-- Freitext: Placeholder -->
				<div v-if="frage.typ === FRAGE_TYP.TEXT" class="row g-3">
					<div class="col-md-6">
						<form-input label="Placeholder (DE)" name="frage_placeholder_de" v-model.trim="frage.placeholder_de" bs-feedback></form-input>
					</div>
					<div class="col-md-6">
						<form-input label="Placeholder (EN)" name="frage_placeholder_en" v-model.trim="frage.placeholder_en" bs-feedback></form-input>
					</div>
				</div>

				<!-- Single Response: Antworten -->
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
								<td><input type="number" class="form-control form-control-sm" v-model.number="antwort.wert" aria-label="Wert"></td>
							</tr>
						</tbody>
					</table>
				</div>
			</fieldset>
		</template>

		<template #footer>
			<button type="button" class="btn btn-primary" data-bs-dismiss="modal">Fertig</button>
		</template>
	</bs-modal>
	`
}