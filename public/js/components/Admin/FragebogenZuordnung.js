import FormInput from "../../../../../js/components/Form/Input.js";
import ApiFragebogen from "../../api/admin/fragebogen.js";
import ApiFhc from "../../api/fhc.js";


export default {
	name: "FragebogenZuordnung",
	components: {
		FormInput
	},
	props: {
		fragebogenId: {required: true},
		isVerwendet: Boolean	// FB ist min. einer Evaluierung zugeordnet: Zuordnungen nur lesbar
	},
	emits: ['changed'],
	data() {
		return {
			ebenen: [
				{
					value: 'oe',
					label: 'Organisationseinheit',
				},
				{
					value: 'stpl',
					label: 'Studienplan',
				},
				{
					value: 'lv',
					label: 'Lehrveranstaltung',
				}
			],
			selEbene: 'oe',
			selStudiensemester: null,	// Filter für Studienplan/LV
			selItem: null,		// Autocomplete Auswahl
			suggestions: [],
			lists: {
				studiensemester: [],
				oe: [],
				stpl: []
			},
			zuordnungen: [],
		}
	},
	created() {
		this.loadZuordnungen();

		this.$api
			.call(ApiFragebogen.getOrganisationseinheiten())
			.then(result => {
				this.lists.oe = result.data.map(oe => ({
					id: oe.oe_kurzbz,
					label: oe.organisationseinheittyp_kurzbz + ' ' + oe.bezeichnung + ' (' + oe.oe_kurzbz + ')'
				}));
				return this.$api.call(ApiFhc.Studiensemester.getAll());
			})
			.then(result => {
				this.lists.studiensemester = result.data;
				return this.$api.call(ApiFhc.Studiensemester.getAktNext());
			})
			// Default: aktuelles Studiensemester (lädt Studienpläne über watcher)
			.then(result => this.selStudiensemester = result.data[0].studiensemester_kurzbz)
			.catch(error => this.$fhcAlert.handleSystemError(error));
	},
	computed: {
		selEbeneObj() {
			return this.ebenen.find(e => e.value === this.selEbene);
		},
		// OE ohne Studiensemester-Dropdown
		showStudiensemester() {
			return this.selEbene !== 'oe';
		},
		sortedZuordnungen() {
			const order = this.ebenen.map(e => e.value);
			return [...this.zuordnungen].sort((a, b) => order.indexOf(a.ebene) - order.indexOf(b.ebene));
		}
	},
	watch: {
		selEbene() {
			this.selItem = null;
		},
		selStudiensemester() {
			this.selItem = null;
			this.loadStudienplaene();
		},
		fragebogenId() {
			this.loadZuordnungen();
		},
	},
	methods: {
		loadStudienplaene() {
			this.$api
				.call(ApiFragebogen.getStudienplaene(this.selStudiensemester))
				.then(result => this.lists.stpl = result.data.map(stpl => ({
					id: stpl.studienplan_id,
					label: stpl.bezeichnung + ' - ' + stpl.semester + '. Semester',
				})))
				.catch(error => this.$fhcAlert.handleSystemError(error));
		},
		loadZuordnungen() {
			this.$api
					.call(ApiFragebogen.getZuordnungen(this.fragebogenId))
					.then(result => this.zuordnungen = result.data)
					.catch(error => this.$fhcAlert.handleSystemError(error));
		},
		search(event) {
			// LVs serverseitig suchen, OE/Studienplan lokal filtern
			if (this.selEbene === 'lv') {
				this.$api
					.call(ApiFragebogen.searchLehrveranstaltungen(event.query, this.selStudiensemester))
					.then(result => this.suggestions = result.data.map(lv => ({
						id: lv.lehrveranstaltung_id,
						label: this.getLvLabel(lv)
					})))
					.catch(error => this.$fhcAlert.handleSystemError(error));
				return;
			}

			const query = event.query.toLowerCase();
			this.suggestions = this.lists[this.selEbene].filter(item => item.label.toLowerCase().includes(query));
		},
		addZuordnung() {
			const exists = this.zuordnungen.some(z =>
					z.ebene === this.selEbene && this.getZuordnungId(z) === this.selItem.id
			);

			if (exists) {
				this.selItem = null;
				return;
			}

			const zuordnung = {
				ebene: this.selEbene,
				oe_kurzbz: this.selEbene === 'oe' ? this.selItem.id : null,
				studienplan_id: this.selEbene === 'stpl' ? this.selItem.id : null,
				lehrveranstaltung_id: this.selEbene === 'lv' ? this.selItem.id : null,
				label: this.selItem.label
			};

			this.$api
				.call(ApiFragebogen.insertZuordnung({...zuordnung, fragebogen_id: this.fragebogenId}))
				.then(result => {
					zuordnung.lvevaluierung_fragebogen_zuordnung_id = result.data;
					this.zuordnungen.push(zuordnung);
					this.selItem = null;
					this.$fhcAlert.alertSuccess(this.$p.t('ui', 'gespeichert'));
					this.$emit('changed');
				})
				.catch(error => this.$fhcAlert.handleSystemError(error));
		},
		async deleteZuordnung(zuordnung) {
			if (!await this.$fhcAlert.confirmDelete()) return;

			this.$api
				.call(ApiFragebogen.deleteZuordnung(zuordnung.lvevaluierung_fragebogen_zuordnung_id))
				.then(() => {
					this.zuordnungen.splice(this.zuordnungen.indexOf(zuordnung), 1);
					this.$fhcAlert.alertSuccess('Zuordnung entfernt');
					this.$emit('changed');
				})
				.catch(error => this.$fhcAlert.handleSystemError(error));
		},
		getZuordnungId(zuordnung) {
			return zuordnung.oe_kurzbz || zuordnung.studienplan_id || zuordnung.lehrveranstaltung_id;
		},
		getEbeneLabel(ebene) {
			return this.ebenen.find(e => e.value === ebene).label;
		},
		getLvLabel(lv) {
			return (lv.kurzbzlang ? lv.kurzbzlang : '') +
					(lv.semester ? ' - ' + lv.semester + ':' : '') +
					' ' + lv.bezeichnung +
					(lv.orgform_kurzbz ? ' - ' + lv.orgform_kurzbz : '');
		}
	},
	template: `
	<section class="d-flex flex-column gap-3">
		<div class="row-cols">
			<span class="text-muted">Fragebogenzuordnung speichern. Passen für eine LV mehrere Fragebögen, gilt der genauer zugeordnete: LV vor Studienplan vor OE.</span>
		</div>
		<!-- Auswahl und Eingabe gesperrt, sobald Fragebogen in einer Evaluierung verwendet -->
		<fieldset class="d-flex flex-column gap-3" :disabled="isVerwendet">
			<!-- Auswahl OE, Studienplan, LV -->
			<div>
				<div v-for="e in ebenen" :key="e.value" class="form-check form-check-inline">
					<input type="radio" class="form-check-input" name="fbEbene" :id="'fbEbene-' + e.value" :value="e.value" v-model="selEbene">
					<label class="form-check-label" :for="'fbEbene-' + e.value">{{ e.label }}</label>
				</div>
			</div>
			<!-- Eingabefelder -->
			<div class="row g-2 align-items-end">
			<!-- Studiensemester: nur bei Studienplan und LV -->
			<div v-if="showStudiensemester" class="col-auto">
				<form-input
					type="select"
					class="w-auto"
					label="Studiensemester"
					name="studiensemester_kurzbz"
					v-model="selStudiensemester"
					bs-feedback
				>
					<option
						v-for="studSem in lists.studiensemester"
						:key="studSem.studiensemester_kurzbz"
						:value="studSem.studiensemester_kurzbz"
					>
						{{ studSem.studiensemester_kurzbz }}
					</option>
				</form-input>
			</div>
			<div class="col">
				<form-input
					type="autocomplete"
					v-model="selItem"
					name="zuordnungen"
					:label="selEbeneObj.label"
					option-label="label"
					:suggestions="suggestions"
					@complete="search"
					:dropdown="selEbene !== 'lv'"
					forceSelection
					bs-feedback
				></form-input>
			</div>
			<div class="col-auto">
				<button type="button" class="btn btn-primary" :disabled="!selItem" @click="addZuordnung">
					Zuordnung speichern
				</button>
			</div>
		</div>
		</fieldset>
		<!-- Anzeige der Auswahl (badges) -->
		<div v-if="zuordnungen.length">
			<div class="form-label">Zuordnungen</div>
			<div class="d-flex flex-wrap gap-2">
				<span
					v-for="zuordnung in sortedZuordnungen"
					:key="zuordnung.lvevaluierung_fragebogen_zuordnung_id"
					class="badge text-bg-primary d-inline-flex align-items-center gap-2 fs-6 fw-normal"
				>
					{{ zuordnung.label }}
				<button v-if="!isVerwendet" type="button" class="btn-close btn-close-white" aria-label="Zuordnung entfernen" @click="deleteZuordnung(zuordnung)"></button>
			</div>
		</div>
	</section>
	`
}