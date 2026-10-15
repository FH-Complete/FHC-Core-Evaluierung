let keyCounter = 0;

export default {
	// Werte für fragebogen_gruppe.typ
	GRUPPE_TYP: {
		GROUP: 'group',
		LABEL: 'label',
		CARD: 'card'
	},
	// Werte für fragebogen_frage.typ
	FRAGE_TYP: {
		SINGLERESPONSE: 'singleresponse',
		TEXT: 'text'
	},
	// Eindeutiger, fortlaufender Key für Gruppen und Fragen für v-for, auch für ungespeicherte Einträge
	newKey() {
		return 'k' + (++keyCounter); // erst erhöhen und neuen Wert gleich zuweisen
	},
	// Default Fragebogen mit 3 default Start-Gruppen. Kann danach angepasst werden.
	newFragebogen() {
		return {
			fragebogen_id: null,
			bezeichnung_de: 'Neuer Fragebogen',
			bezeichnung_en: 'New questionnaire',
			gueltig_von: '',
			gueltig_bis: '',
			hasAntworten: false,
			zuordnungen: [],
			gruppen: [
				this.newGruppe(1, 2),
				this.newGruppe(2, 1),
				this.newGruppe(3, 2, this.FRAGE_TYP.TEXT)
			]
		};
	},
	// Leer, bis bestehender Fragebogen gewählt ist
	emptyFragebogen() {
		return {...this.newFragebogen(), bezeichnung_de: '', bezeichnung_en: '', gruppen: []};
	},
	newGruppe(nr, anzahlFragen = 0, frageTyp = this.FRAGE_TYP.SINGLERESPONSE) {
		return {
			_key: this.newKey(),
			lvevaluierung_fragebogen_gruppe_id: null,
			typ: this.GRUPPE_TYP.GROUP,
			bezeichnung_de: 'Fragengruppe ' + nr,
			bezeichnung_en: 'Question group ' + nr,
			style: '',
			fragen: Array.from({
				length: anzahlFragen}, (_, i) => this.newFrage(i + 1, frageTyp)
			)
		};
	},
	newFrage(nr, typ = this.FRAGE_TYP.SINGLERESPONSE) {
		const isSingle = typ === this.FRAGE_TYP.SINGLERESPONSE;

		return {
			_key: this.newKey(),
			lvevaluierung_frage_id: null,
			typ,
			bezeichnung_de: 'Frage ' + nr,
			bezeichnung_en: 'Question ' + nr,
			placeholder_de: '',
			placeholder_en: '',
			verpflichtend: isSingle,
			gesperrt: false,
			anzahlAndere: 0,
			antworten: isSingle ? this.defaultAntworten() : []
		};
	},
	defaultAntworten() {
		return [
			{bezeichnung_de: 'Trifft voll zu', bezeichnung_en: 'Strongly agree', wert: 1},
			{bezeichnung_de: 'Trifft eher zu', bezeichnung_en: 'Agree', wert: 2},
			{bezeichnung_de: 'Teils/teils', bezeichnung_en: 'Neutral', wert: 3},
			{bezeichnung_de: 'Trifft eher nicht zu', bezeichnung_en: 'Disagree', wert: 4},
			{bezeichnung_de: 'Trifft nicht zu', bezeichnung_en: 'Strongly disagree', wert: 5}
		];
	},
	// Geladenen Fragebogen vorbereiten (Kopie: neue IDs, Fragen bleiben verknüpft)
	prepareLoaded(fragebogen, isCopy) {
		fragebogen.gruppen.forEach(gruppe => {
			gruppe._key = this.newKey();
			if (isCopy) gruppe.lvevaluierung_fragebogen_gruppe_id = null;

			gruppe.fragen.forEach(frage => {
				frage._key = this.newKey();
				// Andere Fragebögen mit dieser Frage: Beim Kopieren zählt das Original mit, beim Bearbeiten nicht der eigene
				frage.anzahlAndere = isCopy ? frage.anzahlFrageboegen : frage.anzahlFrageboegen - 1;
			});
		});

		if (isCopy) {
			fragebogen.fragebogen_id = null;
			fragebogen.bezeichnung_de = '';
			fragebogen.bezeichnung_en = '';
			fragebogen.hasAntworten = false;
		}

		return fragebogen;
	},
	moveItem(list, from, to) {
		if (from === to || to < 0 || to >= list.length) return;
		list.splice(to, 0, list.splice(from, 1)[0]);
	}
};