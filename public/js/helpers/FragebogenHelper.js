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
	defaultAntworten() {
		return [
			{bezeichnung_de: 'Trifft voll zu', bezeichnung_en: 'Strongly agree', wert: 1},
			{bezeichnung_de: 'Trifft eher zu', bezeichnung_en: 'Agree', wert: 2},
			{bezeichnung_de: 'Teils/teils', bezeichnung_en: 'Neutral', wert: 3},
			{bezeichnung_de: 'Trifft eher nicht zu', bezeichnung_en: 'Disagree', wert: 4},
			{bezeichnung_de: 'Trifft nicht zu', bezeichnung_en: 'Strongly disagree', wert: 5}
		];
	},
};