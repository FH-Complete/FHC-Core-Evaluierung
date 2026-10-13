import FragebogenHelper from "../../helpers/FragebogenHelper.js";

export default {
	name: "FragebogenVorschau",
	props: {
		fragebogen: {type: Object, required: true},
		selGruppeKey: {default: null}
	},
	data() {
		return {
			FRAGE_TYP: FragebogenHelper.FRAGE_TYP,
			GRUPPE_TYP: FragebogenHelper.GRUPPE_TYP,
			lang: 'de'
		}
	},
	computed: {
		isDe() {
			return this.lang === 'de';
		},
		gueltigkeit() {
			const fb = this.fragebogen;
			if (!fb.gueltig_von) return '';

			const von = (this.isDe ? 'Gültig ab ' : 'Valid from ') + this.DateHelper.formatDate(fb.gueltig_von);
			if (!fb.gueltig_bis) return von + (this.isDe ? ' - unbefristet' : ' - open-ended');

			return von + (this.isDe ? ' bis ' : ' to ') + this.DateHelper.formatDate(fb.gueltig_bis);
		}
	},
	methods: {
		// Bezeichnung in Vorschausprache, sonst Hinweis.
		// Item kann Fragebogen, Gruppe, Frage oder Antwort sein.
		getBezeichnung(item) {
			const text = item['bezeichnung_' + this.lang];
			return text ? text : (this.isDe ? '[DE fehlt]' : '[EN missing]');
		}
	},
	template: `
	<div class="d-flex flex-column h-100">
		<div class="d-flex align-items-center gap-2 px-3 py-2 bg-white border-bottom">
			<i class="fa-solid fa-eye text-secondary"></i>
			<strong class="small">Live-Vorschau</strong>
			<div class="btn-group btn-group-sm ms-auto" role="group" aria-label="Sprache der Vorschau">
				<template v-for="l in ['de', 'en']" :key="l">
					<input type="radio" class="btn-check" name="vorschauLang" :id="'vorschauLang-' + l" :value="l" v-model="lang">
					<label class="btn btn-outline-primary text-uppercase" :for="'vorschauLang-' + l">{{ l }}</label>
				</template>
			</div>
		</div>

		<div class="flex-grow-1 overflow-auto p-3 d-flex flex-column gap-3">
			<div class="card">
				<div class="card-body">
					<h2 class="h5 mb-1">{{ getBezeichnung(fragebogen) }}</h2>
					<div class="small text-secondary">{{ gueltigkeit }}</div>
				</div>
			</div>

			<div
				v-for="gruppe in fragebogen.gruppen"
				:key="gruppe._key"
				class="card"
				:class="{ 'border-primary border-2': gruppe._key === selGruppeKey }"
			>
				<div class="card-header d-flex align-items-center fw-bold">
					<span class="flex-grow-1">{{ getBezeichnung(gruppe) }}</span>
					<i v-if="gruppe.typ === GRUPPE_TYP.GROUP" class="fa-solid fa-chevron-down text-secondary" title="Aufklappbar"></i>
				</div>
				<div v-for="(frage, index) in gruppe.fragen" :key="frage._key" class="card-body border-top">
					<div class="fw-medium mb-2">
						{{ index + 1 }}. {{ getBezeichnung(frage) }}<span v-if="frage.verpflichtend" class="text-danger"> *</span>
					</div>
					<textarea
						v-if="frage.typ === FRAGE_TYP.TEXT"
						class="form-control form-control-sm"
						rows="2"
						:placeholder="frage['placeholder_' + lang]"
						disabled
					></textarea>
					<div v-else class="d-flex gap-2 text-center small text-secondary">
						<div v-for="(antwort, aIndex) in frage.antworten" :key="aIndex" class="flex-fill d-flex flex-column align-items-center gap-1">
							<input type="radio" class="form-check-input m-0" disabled>
							{{ getBezeichnung(antwort) }}
						</div>
					</div>
				</div>
			</div>

			<div class="d-flex justify-content-between align-items-center">
				<span class="small text-secondary">{{ isDe ? '* Pflichtfrage' : '* Required' }}</span>
				<button type="button" class="btn btn-primary" disabled>{{ isDe ? 'Absenden' : 'Submit' }}</button>
			</div>
		</div>
	</div>
	`
}