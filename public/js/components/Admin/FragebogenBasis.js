import FormForm from "../../../../../js/components/Form/Form.js";
import FormInput from "../../../../../js/components/Form/Input.js";

export default {
	name: "FragebogenBasis",
	components: {
		FormForm,
		FormInput,
	},
	props: {
		fragebogen: {type: Object, default: null},	// Geladener Fragebogen, bei Neu leer
		mode: {type: String, required: true},		// edit | copy | neu
		isLocked: Boolean
	},
	emits: ['save'],
	data() {
		return {
			form: {}	// Basisdaten im Formular
		}
	},
	computed: {
		// Beantworteter Fragebogen: beim Bearbeiten nur Gültig bis änderbar
		isDisabled() {
			return this.mode === 'edit' && this.isLocked;
		}
	},
	watch: {
		// Formular bei Moduswechsel oder anderem Fragebogen neu befüllen
		mode: {
			handler() {
				this.resetForm();
			},
			immediate: true
		},
		fragebogen() {
			this.resetForm();
		}
	},
	methods: {
		resetForm() {
			const fb = this.fragebogen;

			// Neu: leeres Formular
			if (this.mode === 'neu' || !fb) {
				this.form = {fragebogen_id: null, bezeichnung_de: '', bezeichnung_en: '', gueltig_von: null, gueltig_bis: null};
				return;
			}

			// Kopieren: ID der Vorlage, Gültigkeit neu setzen
			if (this.mode === 'copy') {
				this.form = {
					fragebogen_id: fb.fragebogen_id,
					bezeichnung_de: fb.bezeichnung_de + ' (Kopie)',
					bezeichnung_en: fb.bezeichnung_en + ' (Copy)',
					gueltig_von: null,
					gueltig_bis: null
				};
				return;
			}

			// Bearbeiten: gespeicherte Basisdaten
			this.form = {
				fragebogen_id: fb.fragebogen_id,
				bezeichnung_de: fb.bezeichnung_de,
				bezeichnung_en: fb.bezeichnung_en,
				gueltig_von: fb.gueltig_von,
				gueltig_bis: fb.gueltig_bis
			};
		}
	},
	template: `
	<form-form ref="form" class="d-flex flex-column gap-3" @submit.prevent>
	<!--	<div v-if="isDisabled" class="alert alert-warning mb-0">
			Bereits beantwortet – nur noch <strong>Gültig bis</strong> änderbar. Für Änderungen am Inhalt <strong>Kopieren</strong>.
		</div>-->
<!--
		<div v-if="mode === 'copy'" class="alert alert-info mb-0">
			Gruppen und Fragen werden übernommen, Zuordnungen nicht.
		</div>
-->

		<div class="row g-3">
			<div class="col-md-6">
				<form-input
					label="Bezeichnung (DE) *"
					name="bezeichnung_de"
					v-model.trim="form.bezeichnung_de"
					:disabled="isDisabled"
					bs-feedback
				></form-input>
			</div>
			<div class="col-md-6">
				<form-input
					label="Bezeichnung (EN) *"
					name="bezeichnung_en"
					v-model.trim="form.bezeichnung_en"
					:disabled="isDisabled"
					bs-feedback
				></form-input>
			</div>
			<div class="col-md-6">
				<form-input
					type="datepicker"
					label="Gültig von *"
					name="gueltig_von"
					v-model="form.gueltig_von"
					locale="de"
					text-input
					format="dd.MM.yyyy"
					model-type="yyyy-MM-dd"
					:enable-time-picker="false"
					:auto-apply="true"
					:disabled="isDisabled"
					bs-feedback
				></form-input>
			</div>
			<div class="col-md-6">
				<form-input
					type="datepicker"
					label="Gültig bis"
					name="gueltig_bis"
					v-model="form.gueltig_bis"
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
		</div>

		<div class="d-flex justify-content-end gap-2">
			<button type="button" class="btn btn-primary" @click="$emit('save', form)">
				<i class="fa-solid fa-floppy-disk me-2"></i>Speichern
			</button>
		</div>
	</form-form>
	`
}