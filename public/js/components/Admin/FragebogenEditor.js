import FormForm from "../../../../../js/components/Form/Form.js";
import FormValidation from "../../../../../js/components/Form/Validation.js";
import ApiAdminFragebogen from "../../api/admin/fragebogen.js";
import FragebogenHelper from "../../helpers/FragebogenHelper.js";
import FragebogenBasis from "./FragebogenBasis.js";
import FragebogenVorschau from "./FragebogenVorschau.js";
import FragebogenZuordnung from "./FragebogenZuordnung.js";

export default {
	name: "FragebogenEditor",
	components: {
		FormForm,
		FormValidation,
		FragebogenBasis,
		FragebogenVorschau,
		FragebogenZuordnung
	},
	data() {
		return {
			steps: ['Basisdaten', 'Zuordnung'],
			step: 0,
			mode: 'neu',				// neu | edit | copy
			basisFragebogenId: null,	// Gewählter Fragebogen bei edit/copy
			frageboegen: [],			// Dropdown bestehende Fragebögen
			fragebogen: FragebogenHelper.newFragebogen(),
			selGruppeKey: null			// Ausgewählte Gruppe (Aufbau und Vorschau)
		}
	},
	created() {
		this.selGruppeKey = this.fragebogen.gruppen[0]._key;
		this.loadFrageboegen();
	},
	computed: {
		isLastStep() {
			return this.step === this.steps.length - 1;
		},
		// Bereits beantwortet: Struktur nur lesbar
		isLocked() {
			return this.mode === 'edit' && this.fragebogen.hasAntworten;
		},
		anzahlFragen() {
			return this.fragebogen.gruppen.reduce((sum, gruppe) => sum + gruppe.fragen.length, 0);
		}
	},
	methods: {
		loadFrageboegen() {
			return this.$api
				.call(ApiAdminFragebogen.getFrageboegen())
				.then(result => this.frageboegen = result.data)
				.catch(error => this.$fhcAlert.handleSystemError(error));
		},
		loadFragebogen(fragebogen_id) {
			this.$api
				.call(ApiAdminFragebogen.getFragebogen(fragebogen_id))
				.then(result => this.setFragebogen(FragebogenHelper.prepareLoaded(result.data, this.mode === 'copy')))
				.catch(error => this.$fhcAlert.handleSystemError(error));
		},
		setFragebogen(fragebogen) {
			this.fragebogen = fragebogen;
			this.selGruppeKey = fragebogen.gruppen.length ? fragebogen.gruppen[0]._key : null;
		},
		onChangeMode(mode) {
			this.mode = mode;

			if (mode === 'neu') {
				this.basisFragebogenId = null;
				this.setFragebogen(FragebogenHelper.newFragebogen());
				return;
			}

			// Bestehenden laden, sonst leeren bis Auswahl getroffen
			if (this.basisFragebogenId) {
				this.loadFragebogen(this.basisFragebogenId);
			}
			else {
				this.setFragebogen(FragebogenHelper.emptyFragebogen());
			}
		},
		onChangeBasis(fragebogen_id) {
			this.basisFragebogenId = fragebogen_id;
			if (fragebogen_id) this.loadFragebogen(fragebogen_id);
		},
		stepBadgeClass(index) {
			if (index === this.step) return 'text-bg-primary';
			if (index < this.step) return 'text-bg-success';
			return 'text-bg-light border';
		}
	},
	template: `
	<div class="fragebogen-editor container-fluid d-flex flex-column vh-100 p-0">
		<h1 class="h2 px-3 mb-0 fhc-page-header mb-3">LV-Evaluierung<small class="fw-normal"> | Fragebögen verwalten</small></h1>

		<!-- Schritte als Reiter -->
		<nav class="d-flex flex-wrap align-items-center gap-3 px-3 py-2 bg-white border-bottom flex-shrink-0" aria-label="Schritte">
			<button
				v-for="(label, index) in steps"
				:key="index"
				type="button"
				class="btn btn-link text-decoration-none d-flex align-items-center gap-2 px-1"
				:class="index === step ? 'fw-semibold text-body' : 'text-secondary'"
				:aria-current="index === step ? 'step' : null"
				@click="step = index"
			>
				<span class="badge rounded-pill" :class="stepBadgeClass(index)">{{ index + 1 }}</span>
				{{ label }}
			</button>
			<!--<div class="ms-auto small text-secondary d-flex gap-3">
				<span>{{ fragebogen.gruppen.length }} Gruppen</span>
				<span>{{ anzahlFragen }} Fragen</span>
				<span>{{ fragebogen.zuordnungen.length }} Zuordnungen</span>
			</div>-->
		</nav>

		<div class="row g-0 flex-grow-1 overflow-hidden">
			<!-- Editor: Formular sammelt alle Eingaben der einzelnen steps -->
			<form-form ref="form" class="col-lg-7 h-100 overflow-auto p-4 d-flex flex-column gap-4">
				<keep-alive>
					<fragebogen-basis
						v-if="step === 0"
						:fragebogen="fragebogen"
						:mode="mode"
						:frageboegen="frageboegen"
						:basis-fragebogen-id="basisFragebogenId"
						:is-locked="isLocked"
						@change-mode="onChangeMode"
						@change-basis="onChangeBasis"
					></fragebogen-basis>
					<fragebogen-zuordnung
						v-else-if="step === 1"
						:zuordnungen="fragebogen.zuordnungen"
					></fragebogen-zuordnung>
				</keep-alive>
				<!-- Buttons: Weiter und Zurück -->
				<div class="d-flex justify-content-between mt-auto">
					<button type="button" class="btn btn-outline-secondary" :disabled="step === 0" @click="step--">
						<i class="fa-solid fa-arrow-left me-2"></i>Zurück
					</button>
					<button v-if="!isLastStep" type="button" class="btn btn-primary" @click="step++">
						Weiter<i class="fa-solid fa-arrow-right ms-2"></i>
					</button>
				</div>
			</form-form>
			
				<!-- Live Vorschau -->
			<aside class="col-lg-5 h-100 border-start bg-body-tertiary">
				<fragebogen-vorschau :fragebogen="fragebogen" :sel-gruppe-key="selGruppeKey"></fragebogen-vorschau>
			</aside>
		</div>
	</div>
	`
}