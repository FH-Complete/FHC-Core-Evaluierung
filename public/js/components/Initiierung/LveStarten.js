import FormForm from "../../../../../js/components/Form/Form.js";
import FormInput from "../../../../../js/components/Form/Input.js";
import ApiFhc from "../../api/fhc.js";
import ApiInitiierung from "../../api/initiierung.js";
import Switcher from "./Switcher.js";
import LveItem from "./LveItem.js";

export default {
	name: "LveStarten",
	components: {
		AutoComplete: primevue.autocomplete,
		FormForm,
		FormInput,
		Switcher,
		LveItem,
	},
	created() {
		const lehrveranstaltung_id = this.$route.query?.lehrveranstaltung_id;

		this.$api
			.call(ApiFhc.Studiensemester.getAll())
			.then(result => this.studiensemester = result.data)
			.then(() => this.$api.call(ApiFhc.Studiensemester.getAktNext()))
			.then(result => this.selStudiensemester = result.data[0].studiensemester_kurzbz)
			.then(() => this.$api.call(ApiInitiierung.getLveLvs(this.selStudiensemester)))
			.then(result => {
				this.lveLvs = result.data;

				// Give Accordion time to build item DOMs first
 				setTimeout(() => {
					 // Now look if $route.query LV-ID is found in lveLvs
					this.lookupLv(lehrveranstaltung_id);
			 	}, 10);
			})
			.catch(error => this.$fhcAlert.handleSystemError(error) );
	},
	data() {
		return {
			studiensemester: [],
			selStudiensemester: '',
			lveLvs: [],					// All Lvs to be evaluated, where user is assigned to at least one Le as a Lektor.
			selLveLvId: null,			// Lve-Lv-ID of selected Lv
			selLveLvDetails: [],		// Structured Lv (plus Les, if evaluation is done by LEs) data merged with lvevaluations
			groupedByLv: [],			// Basis data for selLveLvDetails, grouped for Gesamt-LV Evaluierung
			groupedByLe: [],			// Basis data for selLveLvDetails, grouped for Gruppenbasis Evaluierung
			lvLeitungen: null,
			canSwitch: null,
			canSwitchInfo: [],
			filteredLvs: [],			// Autocomplete Lehrveranstaltung suggestions
			selLv: null					// Autocomplete selected LV
		}
	},
	computed: {
		selLveLv() {
			return this.lveLvs.find(lv => lv.lvevaluierung_lehrveranstaltung_id === this.selLveLvId);
		},
		visibleLveLvs() {
			if (this.selLveLvId && this.selLv) {
				return this.lveLvs.filter(lv => lv.lvevaluierung_lehrveranstaltung_id === this.selLveLvId);
			}
			return this.lveLvs;
		}
	},
	watch: {
		selLv(newVal){
			const lveLvId = newVal?.lvevaluierung_lehrveranstaltung_id;
			if (typeof lveLvId === 'number') {
				this.selLveLvId = lveLvId;

				this.openAccordionItem();
			}
			else {
				this.selLveLvId = null;
			}
		},
		selLveLvId(newId) {
			if (!newId) return;

			this.$api
				.call(ApiInitiierung.getLveLvDataGroups(newId))
				.then(result => {
					let data = result.data;
					this.canSwitch = data.canSwitch;
					this.canSwitchInfo = data.canSwitchInfo;
					this.lvLeitungen = data.lvLeitungen;
					this.groupedByLv = data.groupedByLv;
					this.groupedByLe = data.groupedByLe;

					// Lvevaluierung data, depending on selected Evaluierungsart (Gesamt-LV or Gruppenbasis)
					this.selLveLvDetails = this.selLveLv.lv_aufgeteilt
						? this.groupedByLe
						: this.groupedByLv;
				})
				.catch(error => this.$fhcAlert.handleSystemError(error));
		},
		'selLveLv.lv_aufgeteilt'(newVal) {
			if (!this.selLveLvId) return;

			// Switch, depending on selected Evaluierungsart (Gesamt-LV or Gruppenbasis)
			this.selLveLvDetails = newVal === true
				? this.groupedByLe
				: this.groupedByLv;
		}
	},
	mounted() {
		// Add Event Listener to load evaluation data only when an accordion item is expanded
		const accordion = document.getElementById('accordionFlush');
		if (accordion) {
			accordion.addEventListener('shown.bs.collapse', this.handleAccordionShown);
		}
	},
	methods: {
		lookupLv(lehrveranstaltung_id) {
			if (!isNaN(lehrveranstaltung_id)) {
				const foundLv = this.lveLvs.find(lv => lv.lehrveranstaltung_id == lehrveranstaltung_id);
				if (foundLv) {
					this.selLv = foundLv;  // Triggers selLv watcher
				}
			}
		},
		onChangeStudiensemester(e) {
			this.$api
				.call(ApiInitiierung.getLveLvs(this.selStudiensemester))
				.then(result => {
					this.lveLvs = result.data;
					this.selLv = null;	// Reset Autocomplete field
				})
				.catch(error => this.$fhcAlert.handleSystemError(error));
		},
		openAccordionItem() {
			const collapseEl = document.getElementById('flush-collapse' + this.selLveLvId);
			if (collapseEl) {
				// Get Bootstrap Collapse-Instance oder erstelle neue (toggle: false = nicht automatisch umschalten)
				const bsCollapse = bootstrap.Collapse.getInstance(collapseEl) || new bootstrap.Collapse(collapseEl, { toggle: false });
				bsCollapse.show();
			}
		},
		handleAccordionShown(e) {
			const accBtn = e.target;

			// Get ID from selected item
			if (accBtn){
				this.selLveLvId = Number(accBtn.dataset.lveLvId);
			}
		},
		updateEditableChecks(isAllSent){
			this.$api
				.call(ApiInitiierung.getLveLvDataGroups(this.selLveLvId))
				.then(result => {
					let data = result.data;
					this.canSwitch = data.canSwitch;
					this.canSwitchInfo = data.canSwitchInfo;
					this.lvLeitungen = data.lvLeitungen;
					this.groupedByLv = data.groupedByLv;
					this.groupedByLe = data.groupedByLe;

					// Lvevaluierung data, depending on selected Evaluierungsart (Gesamt-LV or Gruppenbasis)
					this.selLveLvDetails = this.selLveLv.lv_aufgeteilt
						? this.groupedByLe
						: this.groupedByLv;
				})

			// Update icon displaying if all students received mail
			this.selLveLv.isAllSent = isAllSent;
		},
		getLvInfoString(lv){
			//return lv.kurzbzlang + ' - ' + lv.semester + ': '+ lv.bezeichnung + ' - ' + lv.orgform_kurzbz + '  | LV-ID: ' + lv.lehrveranstaltung_id + ' LVE-LV-ID: ' + lv.lvevaluierung_lehrveranstaltung_id; // todo delete after testing.
			return lv.kurzbzlang + ' - ' + lv.semester + ': '+ lv.bezeichnung + ' - ' + lv.orgform_kurzbz ;
		},
		searchLv(event) {
			const query = event.query.toLowerCase();
			this.filteredLvs = this.lveLvs.filter(lv =>
					lv.bezeichnung.toLowerCase().includes(query) ||
					lv.kurzbzlang.toLowerCase().includes(query) ||
					lv.lehrveranstaltung_id.toString().includes(query)
			);
		}
	},
	template: `
	<div class="lve-initiierung-body container-fluid d-flex flex-column min-vh-100">
		<h1 class="mb-5">LV-Evaluierung starten<small class="fs-5 fw-normal text-muted"> | Evalueriungskriterien festlegen und Codes an Studierende mailen</small></h1>
		
		<!-- Dropdowns -->
		<div class="row">
			<div class="col-sm-10 col-lg-4 offset-lg-6 mb-3">
				<form-input
					type="autocomplete"
					v-model="selLv"
					name="selLv"
					:label="$p.t('lehre/lehrveranstaltung')"
					option-label="bezeichnung"
					:suggestions="filteredLvs"
					@complete="searchLv"
					@item-select="openAccordionItem"
					dropdown
					dropdown-current
					forceSelection
				>
				<template #option="slotProps">
					{{ getLvInfoString(slotProps.option) }}
				</template>
				<template #header>
					<div class="d-grid">
						<button type="button" class="btn btn-secondary btn-light" @click="this.selLv = null">Alle anzeigen</button>
					</div>
				</template>
				</form-input>
			</div>
			<div class="col-sm-2 mb-3">
				<form-input
					type="select"
					v-model="selStudiensemester"
					name="studiensemester"
					:label="$p.t('lehre/studiensemester')"
					@change="onChangeStudiensemester">
					<option 
						v-for="studSem in studiensemester"
						:key="studSem.studiensemester_kurzbz" 
						:value="studSem.studiensemester_kurzbz">
						{{ studSem.studiensemester_kurzbz }}
					</option>
				</form-input>
			</div>
		</div><!--.end row -->
		<!-- LV Accordion List -->
		<div class="accordion" id="accordionFlush">
			<template v-for="lveLv in visibleLveLvs" :key="lveLv.lvevaluierung_lehrveranstaltung_id">	
				<div class="accordion-item">
					<h2 class="accordion-header" :id="'flush-heading' + lveLv.lvevaluierung_lehrveranstaltung_id">
						<button 
							class="accordion-button collapsed" 
							type="button" 
							data-bs-toggle="collapse" 
							:data-bs-target="'#flush-collapse' + lveLv.lvevaluierung_lehrveranstaltung_id" 
							aria-expanded="false" 
							aria-controls="flush-collapse' + lveLv.lvevaluierung_lehrveranstaltung_id"
						>
							<span>
								<i
									class="fa-solid text-dark me-2" 
									:class="lveLv.lv_aufgeteilt ? 'fa-expand' : 'fa-square-full'"
									:title="lveLv.lv_aufgeteilt ? 'LV wird auf Gruppenbasis evaluiert' : 'Gesamt-LV wird evaluiert'"
									data-bs-toggle="tooltip"
								>								
								</i>
								<i 
									class="fa-solid me-2"
									:class="lveLv.verpflichtend ? 'fa-asterisk text-dark' : 'fa-asterisk text-light'"
									:title="lveLv.verpflichtend  ? 'Evaluierung muss durchgeführt werden (verpflichtend)' : 'Evaluierung kann durchgeführt werden (nicht verpflichtend)'"
									data-bs-toggle="tooltip"
								>
								</i>
								<i 
									class="fa-solid me-2"
									:class="lveLv.isAllSent ? 'fa-envelope-circle-check text-success' : 'fa-envelope text-secondary'"
									:title="lveLv.isAllSent  ? 'Alle Studierende benachrichtigt' : 'Studierende müssen noch benachrichtigt werden'"
									data-bs-toggle="tooltip"
								>
								</i> 
							  	{{ getLvInfoString(lveLv)}}
							</span>
						</button>
					</h2>
					<div 
						:id="'flush-collapse' + lveLv.lvevaluierung_lehrveranstaltung_id" 
						class="accordion-collapse collapse md-mx-3 px-3" 
						:aria-labelledby="'flush-heading' + lveLv.lvevaluierung_lehrveranstaltung_id" 
						data-bs-parent="#accordionFlush"
						:data-lve-lv-id="lveLv.lvevaluierung_lehrveranstaltung_id"
					>
						<Switcher 
  							v-if="lveLv.lvevaluierung_lehrveranstaltung_id === selLveLvId"
  							:can-switch="canSwitch"
  							:can-switch-info="canSwitchInfo"
  							:sel-lve-lv="selLveLv"
							:lv-leitungen="lvLeitungen"
						>
						</Switcher>
						<!-- LV-Evaluierungen -->
						<template v-if="lveLv.lvevaluierung_lehrveranstaltung_id === selLveLvId">
							<Lve-Item 
								:sel-lve-lv-id="lveLv.selLveLvId"
								:sel-lve-lv-details="selLveLvDetails"
								@update-editable-checks="updateEditableChecks"
							>								
							</Lve-Item>
						</template>
					</div><!--.end accordion-collapse -->
				  </div><!--.end accordion-item -->
			</template><!--.end template v-for -->
		</div><!--.end accordion -->
		<!-- Placeholder Card: If no LV for Evaluation found -->
		<div v-if="lveLvs.length == 0"  class="card card flex-grow-1 mb-3">
			<div class="card-body d-flex justify-content-center align-items-center text-center">
				<span class="h5 text-muted">
					Keine Lehrveranstaltungen zur Evaluierung freigegeben in {{ selStudiensemester}}
				</span>
			</div>
		</div>	
	</div><!--.end div -->
	`
}