import FormForm from "../../../../../js/components/Form/Form.js";
import FormInput from "../../../../../js/components/Form/Input.js";
import ApiAdminFragebogen from "../../api/admin/fragebogen.js";

export default {
	name: "FragebogenBasis",
	components: {
		FormForm,
		FormInput
	},
	props: {
		selFragebogen: {default: null}	// Fragebogen aus der Fragebogentabelle
	},
	emits: ['saved'],
	data() {
		return {
			fragebogen: {},
			isSaving: false
		}
	},
	created() {
		this.setFragebogen();
	},
	computed: {
		// Bereits beantwortet: nur Gültig bis änderbar
		hasAntworten() {
			return !!this.selFragebogen && this.selFragebogen.hasAntworten;
		},
		infos() {
			return [
				{
					label: 'Zuordnungen',
					value: this.selFragebogen.anzahlZuordnungen,
					tooltip: 'Anzahl Zuordnungen zu OE, Studienplan oder LV'
				},
				{
					label: 'Verwendet',
					value: this.selFragebogen.isVerwendet ? 'Ja' : 'Nein',
					tooltip: 'In mind. einer LV-Evaluierung eingesetzt'
				},
				{
					label: 'Beantwortet',
					value: this.selFragebogen.hasAntworten ? 'Ja' : 'Nein',
					tooltip: 'Fragebogen von mind. 1 Studierenden beantwortet'
				}
			];
		}
	},
	watch: {
		selFragebogen() {
			this.setFragebogen();
		},
	},
	methods: {
		setFragebogen() {
			this.fragebogen = this.selFragebogen
				? {
					fragebogen_id: this.selFragebogen.fragebogen_id,
					bezeichnung_de: this.selFragebogen.bezeichnung_de,
					bezeichnung_en: this.selFragebogen.bezeichnung_en,
					gueltig_von: this.selFragebogen.gueltig_von,
					gueltig_bis: this.selFragebogen.gueltig_bis
				}
				: {
					fragebogen_id: null,
					bezeichnung_de: '',
					bezeichnung_en: '',
					gueltig_von: null,
					gueltig_bis: null
				};

			if (this.$refs.form) this.$refs.form.clearValidation();
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
					this.$emit('saved', this.fragebogen.fragebogen_id || result.data);
				})
				.catch(error => this.$fhcAlert.handleSystemError(error))
				.finally(() => this.isSaving = false);
		}
	},
	template: `
	<div class="row g-3">
		<div class="col-xl-8 col-xxl-6 d-flex flex-column gap-4">
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
			<!-- Nur Info, nicht änderbar -->
			<div v-if="selFragebogen" class="d-flex flex-wrap gap-5">
				<div v-for="info in infos" :key="info.label">
					<div class="form-label">
						{{ info.label }}
						<i class="fa-solid fa-circle-info text-body-secondary ms-1" :title="info.tooltip" v-tooltip="info.tooltip"></i>
					</div>
					{{ info.value }}
				</div>
			</div>
		</div>
		<div class="col text-end">
			<button type="button" class="btn btn-primary" :disabled="isSaving" @click="save">Speichern</button>
		</div>
	</div>
	`
}