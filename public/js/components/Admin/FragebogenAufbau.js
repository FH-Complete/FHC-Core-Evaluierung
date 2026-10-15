import FragebogenHelper from "../../helpers/FragebogenHelper.js";
import FragebogenFrageModal from "./FragebogenFrageModal.js";
import FormInput from "../../../../../js/components/Form/Input.js";

export default {
	name: "FragebogenAufbau",
	components: {
		FormInput,
		FragebogenFrageModal
	},
	props: {
		gruppen: {type: Array, required: true},
		isLocked: Boolean,
		selGruppeKey: {default: null}
	},
	emits: ['update:selGruppeKey'],
	data() {
		return {
			FRAGE_TYP: FragebogenHelper.FRAGE_TYP,
			GRUPPE_TYP: FragebogenHelper.GRUPPE_TYP,
			dragGruppeIndex: null,
			overGruppeIndex: null,
			dragFrageIndex: null,
			overFrageIndex: null
		}
	},
	methods: {
		selectGruppe(gruppe) {
			this.$emit('update:selGruppeKey', gruppe._key);
		},
		addGruppe() {
			const gruppe = FragebogenHelper.newGruppe(this.gruppen.length + 1);
			this.gruppen.push(gruppe);
			this.selectGruppe(gruppe);
		},
		async deleteGruppe(index) {
			if (!await this.$fhcAlert.confirm({
				header: 'Bitte bestätigen Sie:',
				message: 'Gruppe „' + this.gruppen[index].bezeichnung_de + '“ inkl. Fragen entfernen?'
			})) return;

			this.gruppen.splice(index, 1);
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
		// Überschrift hat keine Fragen: vorhandene Fragen nach Bestätigung entfernen
		async changeTyp(gruppe, e) {
			const typ = e.target.value;

			if (typ === this.GRUPPE_TYP.LABEL && gruppe.fragen.length) {
				if (!await this.$fhcAlert.confirm({
					header: 'Bitte bestätigen Sie:',
					message: 'Eine Überschrift hat keine Fragen. ' + gruppe.fragen.length + ' Fragen entfernen?'
				})) {
					e.target.value = gruppe.typ;
					return;
				}
				gruppe.fragen = [];
			}

			gruppe.typ = typ;
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
			if (this.dragGruppeIndex === null) return;
			FragebogenHelper.moveItem(this.gruppen, this.dragGruppeIndex, index);
			this.resetDrag();
		},
		// Drag & Drop Fragen innerhalb der ausgewählten Gruppe
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
			console.log('resetDrag');
			this.dragGruppeIndex = null;
			this.overGruppeIndex = null;
			this.dragFrageIndex = null;
			this.overFrageIndex = null;
		}
	},
	template: `
	<section class="d-flex flex-column gap-3">
		<div class="d-flex flex-wrap align-items-center gap-3">
			<div class="flex-grow-1">
				<h2 class="h4 mb-1">Aufbau</h2>
		<!--		<p class="small text-secondary mb-0">Gruppen und Fragen per Drag &amp; Drop sortieren, Details im Dialog.</p>-->
			</div>
			<button type="button" class="btn btn-primary" :disabled="isLocked" @click="addGruppe">
				<i class="fa-solid fa-plus me-2"></i>Gruppe
			</button>
		</div>

		<div v-if="isLocked" class="alert alert-warning mb-0">
			Bereits beantwortet – Aufbau nur lesbar. Für Änderungen <strong>Bestehenden kopieren</strong>.
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
					@click="selectGruppe(gruppe)"
				>
					{{ gruppe.bezeichnung_de || '[Gruppenbezeichnung fehlt]' }}
				<!--	<span class="small fw-normal text-secondary">· {{ gruppe.bezeichnung_en || '—' }}</span>-->
				</button>
				<span  v-if="gruppe.typ !== GRUPPE_TYP.LABEL" class="badge text-bg-light border">{{ gruppe.fragen.length }} Fragen</span>
				<button type="button" class="btn btn-sm btn-outline-secondary" :disabled="isLocked" title="Gruppe löschen" @click="deleteGruppe(gIndex)">
					<i class="fa-solid fa-xmark"></i>
				</button>
			</div>

			<!-- Ausgewählte Gruppe: Felder und Fragen -->
			<template v-if="gruppe._key === selGruppeKey">
				<fieldset class="card-body bg-body-tertiary row g-2 m-0" :disabled="isLocked">
					<div class="col-md-4">
						<form-input label="Bezeichnung DE" name="gruppe_bezeichnung_de" v-model.trim="gruppe.bezeichnung_de" bs-feedback></form-input>
					</div>
					<div class="col-md-4">
						<form-input label="Bezeichnung EN" name="gruppe_bezeichnung_en" v-model.trim="gruppe.bezeichnung_en" bs-feedback></form-input>
					</div>
					<div class="col-md-4">
						<!-- Eigenes Label wegen Info-Icon -->
						<span class="form-label d-block">
							Typ
							<i
								class="fa-solid fa-circle-info text-secondary ms-1"
								v-tooltip
								title="Fragengruppe (einklappbar): Fragen in einer Card, einklappbar. Fragengruppe (fix): Fragen in einer Card, nicht einklappbar - immer angezeigt. Nur Überschrift: wird nur als Überschrift angezeigt, ohne Fragen."
							></i>
						</span>
						<form-input type="select" name="gruppe_typ" aria-label="Typ" v-model="gruppe.typ" @change="changeTyp(gruppe, $event)" bs-feedback>
							<option :value="GRUPPE_TYP.GROUP">Fragengruppe (einklappbar)</option>
							<option :value="GRUPPE_TYP.CARD">Fragengruppe (fix)</option>
							<option :value="GRUPPE_TYP.LABEL">Nur Überschrift</option>
						</form-input>
					</div>
				<!--	<div class="col-md-3">
						<form-input label="Style" name="gruppe_style" v-model.trim="gruppe.style" placeholder="optional" bs-feedback></form-input>
					</div>-->
				</fieldset>
				<!-- Fragen -->
				<ul v-if="gruppe.typ !== GRUPPE_TYP.LABEL" class="list-group list-group-flush">
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
					<li class="list-group-item">
						<button type="button" class="btn btn-sm btn-primary" :disabled="isLocked" @click="addFrage(gruppe)">
							<i class="fa-solid fa-plus me-1"></i>Frage
						</button>
					</li>
				</ul>
			</template>
		</div>

		<fragebogen-frage-modal ref="frageModal" :is-locked="isLocked"></fragebogen-frage-modal>
	</section>
	`
}