import FormInput from "../../../../../js/components/Form/Input.js";

export default {
	name: "FragebogenBasis",
	components: {
		FormInput,
	},
	props: {
		fragebogen: {type: Object, required: true},
		mode: {type: String, required: true},
		frageboegen: {type: Array, default: () => []},
		basisFragebogenId: {default: null},
		isLocked: Boolean
	},
	emits: ['changeMode', 'changeBasis'],
	data() {
		return {
			modes: [
				{value: 'neu', label: 'Neuer Fragebogen', icon: 'fa-plus'},
				{value: 'edit', label: 'Fragebogen bearbeiten', icon: 'fa-edit'},
				{value: 'copy', label: 'Fragebogen kopieren', icon: 'fa-copy'}
			]
		}
	},
	computed: {
		selMode: {
			get() {
				return this.mode;
			},
			set(value) {
				this.$emit('changeMode', value);
			}
		},
		selBasis: {
			get() {
				return this.basisFragebogenId;
			},
			set(value) {
				this.$emit('changeBasis', value);
			}
		},
		labelBezeichnungNeu() {
			return this.mode === 'copy' ? ' NEU' : '';
		}
	},
	methods: {
		getFragebogenLabel(fb) {
			return '#' + fb.fragebogen_id + ' · ' + fb.bezeichnung_by_language +
				' · ab ' + this.DateHelper.formatDate(fb.gueltig_von);
		}
	},
	template: `
	<section class="d-flex flex-column gap-4">
		<h2 class="h4 mb-0">Basisdaten</h2>

		<div class="d-flex flex-wrap gap-2">
			<template v-for="m in modes" :key="m.value">
				<input type="radio" class="btn-check" name="fbMode" :id="'fbMode-' + m.value" :value="m.value" v-model="selMode">
				<label class="btn btn-outline-primary" :for="'fbMode-' + m.value">
					<i class="fa-solid me-2" :class="m.icon"></i>{{ m.label }}
				</label>
			</template>
		</div>

		<div v-if="mode !== 'neu'">
			<div v-if="mode !== 'neu'">
				<form-input
					type="select"
					label="Bestehender Fragebogen"
					name="basis_fragebogen_id"
					v-model="selBasis"
					bs-feedback
				>
					<option :value="null">Fragebogen wählen</option>
					<option v-for="fb in frageboegen" :key="fb.fragebogen_id" :value="fb.fragebogen_id">
						{{ getFragebogenLabel(fb) }}
					</option>
				</form-input>
			</div>
		</div>

		<div v-if="isLocked" class="alert alert-warning mb-0">
			Bereits beantwortet – nur noch <strong>Gültig bis</strong> änderbar. Für Änderungen am Inhalt <strong>Bestehenden kopieren</strong>.
		</div>

		<div class="card">
			<div class="card-body row g-3">
				<div class="col-md-6">
					<form-input
						:label="'Bezeichnung' + labelBezeichnungNeu + ' (DE) *'"
						name="bezeichnung_de"
						v-model.trim="fragebogen.bezeichnung_de"
						:disabled="isLocked"
						bs-feedback
					></form-input>
				</div>
				<div class="col-md-6">
					<form-input
						:label="'Bezeichnung' + labelBezeichnungNeu + ' (EN) *'"
						name="bezeichnung_en"
						v-model.trim="fragebogen.bezeichnung_en"
						:disabled="isLocked"
						bs-feedback
					></form-input>
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
						:disabled="isLocked"
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
			</div>
		</div>
	</section>
	`
}