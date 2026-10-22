import ApiAdminFragebogen from "../../api/admin/fragebogen.js";
import FragebogenHelper from "../../helpers/FragebogenHelper.js";
import FragebogenGruppeModal from "./FragebogenGruppeModal.js";
import FragebogenFrageModal from "./FragebogenFrageModal.js";

export default {
	name: "FragebogenAufbau",
	components: {
		FragebogenGruppeModal,
		FragebogenFrageModal
	},
	props: {
		fragebogenId: {required: true},
		gruppen: {type: Array, required: true},
		isLocked: Boolean
	},
	data() {
		return {
			FRAGE_TYP: FragebogenHelper.FRAGE_TYP,
			GRUPPE_TYP: FragebogenHelper.GRUPPE_TYP,
			selGruppeKey: null,		// Aufgeklappte Gruppe, initial alle zu
			dragGruppeIndex: null,
			overGruppeIndex: null,
			dragFrageIndex: null,
			overFrageIndex: null
		}
	},
	methods: {
		// Gruppe auf- oder zuklappen
		toggleGruppe(gruppe) {
			this.selGruppeKey = this.selGruppeKey === gruppe._key ? null : gruppe._key;
		},
		// Nach Speichern im Modal: Gruppe aktualisieren oder neu anhängen und aufklappen
		onSavedGruppe(saved) {
			const gruppe = this.gruppen.find(g => g.lvevaluierung_fragebogen_gruppe_id === saved.lvevaluierung_fragebogen_gruppe_id);

			if (gruppe) {
				Object.assign(gruppe, saved);
				return;
			}

			const neu = Object.assign(FragebogenHelper.newGruppe(this.gruppen.length + 1), saved);
			this.gruppen.push(neu);
			this.selGruppeKey = neu._key;
		},
		async deleteGruppe(index) {
			const gruppe = this.gruppen[index];

			if (!await this.$fhcAlert.confirm({
				header: 'Bitte bestätigen Sie:',
				message: 'Gruppe „' + gruppe.bezeichnung_de + '“ inkl. Fragen entfernen?'
			})) return;

			this.$api
				.call(ApiAdminFragebogen.deleteGruppe(gruppe.lvevaluierung_fragebogen_gruppe_id))
				.then(() => {
					this.gruppen.splice(this.gruppen.indexOf(gruppe), 1);
					this.$fhcAlert.alertSuccess('Gruppe gelöscht');
				})
				.catch(error => this.$fhcAlert.handleSystemError(error));
		},
		addFrage(gruppe) {
			const frage = FragebogenHelper.newFrage(gruppe.fragen.length + 1);
			gruppe.fragen.push(frage);
			this.$refs.frageModal.open(frage);
		},
		editFrage(frage) {
			this.$refs.frageModal.open(frage);
		},
		deleteFrage(gruppe, index) {
			gruppe.fragen.splice(index, 1);
		},
		// Drag & Drop
		// ---------------------------------------------------------------------------------
		// Drag & Drop Gruppen (Kopfzeile = Griff)
		onDragStartGruppe(e, index) {
			e.dataTransfer.effectAllowed = 'move';
			e.dataTransfer.setData('text/plain', index);	// Firefox startet Drag nur mit Daten
			setTimeout(() => this.dragGruppeIndex = index);
		},
		onDragOverGruppe(e, index) {
			if (this.dragGruppeIndex === null) return;
			e.preventDefault();
			this.overGruppeIndex = index;
		},
		onDropGruppe(index) {
			const from = this.dragGruppeIndex;
			this.resetDrag();
			if (from === null || from === index) return;

			FragebogenHelper.moveItem(this.gruppen, from, index);

			// Reihenfolge sofort speichern, bei Fehler zurückschieben
			this.$api
				.call(ApiAdminFragebogen.sortGruppen(this.fragebogenId, this.gruppen.map(g => g.lvevaluierung_fragebogen_gruppe_id)))
				.catch(error => {
					FragebogenHelper.moveItem(this.gruppen, index, from);
					this.$fhcAlert.handleSystemError(error);
				});
		},
		// Drag & Drop Fragen innerhalb der aufgeklappten Gruppe
		onDragStartFrage(e, index) {
			e.dataTransfer.effectAllowed = 'move';
			e.dataTransfer.setData('text/plain', index);
			setTimeout(() => this.dragFrageIndex = index);
		},
		onDragOverFrage(e, index) {
			if (this.dragFrageIndex === null) return;
			e.preventDefault();
			this.overFrageIndex = index;
		},
		onDropFrage(gruppe, index) {
			if (this.dragFrageIndex === null) return;
			FragebogenHelper.moveItem(gruppe.fragen, this.dragFrageIndex, index);
			this.resetDrag();
		},
		resetDrag() {
			this.dragGruppeIndex = null;
			this.overGruppeIndex = null;
			this.dragFrageIndex = null;
			this.overFrageIndex = null;
		}
	},
	template: `
	<section class="d-flex flex-column gap-3">
	<!--	<div v-if="isLocked" class="alert alert-warning mb-0">
			Bereits beantwortet – Aufbau nur lesbar. Für Änderungen <strong>Kopieren</strong>.
		</div>-->

		<!-- Neue Gruppe: oberhalb der ersten Gruppe -->
		<div>
			<button type="button" class="btn btn-primary" :disabled="isLocked" @click="$refs.gruppeModal.open(null)">
				<i class="fa-solid fa-plus me-2"></i>Gruppe
			</button>
		</div>

		<!-- Fragengruppen -->
		<div
			v-for="(gruppe, gIndex) in gruppen"
			:key="gruppe._key"
			class="card"
			:class="{
				'border-primary shadow-sm': gruppe._key === selGruppeKey,
				'bg-primary-subtle': gIndex === overGruppeIndex && gIndex !== dragGruppeIndex,
				'opacity-50': gIndex === dragGruppeIndex
			}"
			@dragover="onDragOverGruppe($event, gIndex)"
			@drop.prevent="onDropGruppe(gIndex)"
		>
			<!-- Fragengruppe Kopfzeile: Griff zum Sortieren -->
			<div
				class="card-header d-flex align-items-center gap-2 bg-white"
				:draggable="!isLocked"
				@dragstart="onDragStartGruppe($event, gIndex)"
				@dragend="resetDrag"
			>
				<i v-if="!isLocked" class="fa-solid fa-grip-vertical text-secondary" title="Ziehen zum Sortieren"></i>
				<button
					type="button"
					class="btn btn-link text-body text-decoration-none text-start fw-semibold flex-grow-1 p-0"
					:aria-expanded="gruppe._key === selGruppeKey"
					@click="toggleGruppe(gruppe)"
				>
					<i class="fa-solid fa-fw me-1" :class="gruppe._key === selGruppeKey ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
					{{ gruppe.bezeichnung_de || '[Gruppenbezeichnung fehlt]' }}
				</button>
				<span v-if="gruppe.typ !== GRUPPE_TYP.LABEL" class="badge text-bg-light border">{{ gruppe.fragen.length }} Fragen</span>
				<button type="button" class="btn btn-sm btn-outline-secondary" :disabled="isLocked" title="Gruppe bearbeiten" @click="$refs.gruppeModal.open(gruppe)">
					<i class="fa-solid fa-pen"></i>
				</button>
				<button type="button" class="btn btn-sm btn-outline-secondary" :disabled="isLocked" title="Gruppe löschen" @click="deleteGruppe(gIndex)">
					<i class="fa-solid fa-xmark"></i>
				</button>
			</div>

			<!-- Aufgeklappte Gruppe: Fragen (Überschrift hat keine) -->
			<ul v-if="gruppe._key === selGruppeKey && gruppe.typ !== GRUPPE_TYP.LABEL" class="list-group list-group-flush">
				<!-- Neue Frage: oberhalb der ersten Frage -->
				<li class="list-group-item">
					<button type="button" class="btn btn-sm btn-primary" :disabled="isLocked" @click="addFrage(gruppe)">
						<i class="fa-solid fa-plus me-1"></i>Frage
					</button>
				</li>
				<li
					v-for="(frage, fIndex) in gruppe.fragen"
					:key="frage._key"
					class="list-group-item d-flex align-items-center gap-2"
					:class="{
						'list-group-item-primary': fIndex === overFrageIndex && fIndex !== dragFrageIndex,
						'opacity-50': fIndex === dragFrageIndex
					}"
					:draggable="!isLocked"
					@dragstart="onDragStartFrage($event, fIndex)"
					@dragover="onDragOverFrage($event, fIndex)"
					@drop.prevent="onDropFrage(gruppe, fIndex)"
					@dragend="resetDrag"
				>
					<i v-if="!isLocked" class="fa-solid fa-grip-vertical text-secondary" title="Ziehen zum Sortieren"></i>
					<span class="flex-grow-1">{{ frage.bezeichnung_de || '[ohne Text]' }}</span>
					<span class="badge bg-primary-subtle text-primary-emphasis">
						{{ frage.typ === FRAGE_TYP.TEXT ? 'Freitext' : 'Single Response' }}
					</span>
					<span v-if="frage.verpflichtend" class="badge bg-warning-subtle text-warning-emphasis">Pflicht</span>
					<button type="button" class="btn btn-sm btn-outline-secondary" @click="editFrage(frage)">
						<i class="fa-solid" :class="isLocked ? 'fa-eye' : 'fa-pen'"></i>
					</button>
					<button type="button" class="btn btn-sm btn-outline-secondary" :disabled="isLocked" title="Frage entfernen" @click="deleteFrage(gruppe, fIndex)">
						<i class="fa-solid fa-xmark"></i>
					</button>
				</li>
			</ul>
		</div>

		<fragebogen-gruppe-modal ref="gruppeModal" :fragebogen-id="fragebogenId" @saved="onSavedGruppe"></fragebogen-gruppe-modal>
		<fragebogen-frage-modal ref="frageModal" :is-locked="isLocked"></fragebogen-frage-modal>
	</section>
	`
}