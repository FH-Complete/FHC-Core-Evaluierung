export default {
	/**
	 * Calculate Mittelwert (mean) from ratings and frequencies
	 *
	 * @param array ratings
	 * @param array frequencies
	 * @returns number
	 */
	getMittelwert(ratings, frequencies) {
		if (!Array.isArray(ratings) || !Array.isArray(frequencies)) return null;
		if (ratings.length !== frequencies.length) return null;

		const total = frequencies.reduce((a, b) => a + b, 0);
		if (total === 0) return 0;

		return ratings.reduce((sum, val, i) => sum + val * frequencies[i], 0) / total;
	},
	/**
	 * Calculate Median from ratings and frequencies
	 *
	 * @param array ratings
	 * @param array frequencies
	 * @returns number
	 */
	getMedian(ratings, frequencies) {
		if (!Array.isArray(ratings) || !Array.isArray(frequencies)) return null;
		if (ratings.length !== frequencies.length) return null;

		const total = frequencies.reduce((a, b) => a + b, 0);
		if (total === 0) return 0;

		let cumFreq = 0;
		const medianPos = total / 2;
		let medianIndex = 0;

		for (let i = 0; i < frequencies.length; i++) {
			cumFreq += frequencies[i];
			if (cumFreq >= medianPos) {
				medianIndex = i;
				break;
			}
		}

		return ratings[medianIndex];
	},
	/**
	 * Calculate interpolated Median from ratings and frequencies
	 *
	 * @param array ratings
	 * @param array frequencies
	 * @returns number
	 */
	getInterpolMedian(ratings, frequencies) {
		if (!Array.isArray(ratings) || !Array.isArray(frequencies)) return null;
		if (ratings.length !== frequencies.length) return null;

		const total = frequencies.reduce((a, b) => a + b, 0);
		if (total === 0) return 0;

		let cumFreq = 0;
		const medianPos = total / 2;
		let medianIndex = 0;

		for (let i = 0; i < frequencies.length; i++) {
			cumFreq += frequencies[i];
			if (cumFreq >= medianPos) {
				medianIndex = i;
				break;
			}
		}

		const F = frequencies.slice(0, medianIndex).reduce((a, b) => a + b, 0);
		const f = frequencies[medianIndex];
		const L = ratings[medianIndex] - 1;
		const w = 1;

		return L + ((medianPos - F) / f) * w;
	},
	/**
	 * Return font awsome icon string representing the antwortWert. (1 = Smiley,...)
	 *
	 * @param integer antwortWert
	 * @returns string
	 */
	getIcon(antwortWert) {
		const icons = [
			'circle-question',	// 0
			'face-laugh',		// 1
			'face-smile',		// 2
			'face-meh',			// 3
			'face-frown',		// 4
			'face-tired'		// 5
		];

		return Number.isInteger(antwortWert) && antwortWert >= 0 && antwortWert <= 5
				? icons[antwortWert]
				: 'circle-question';
	},
	/**
	 * Return icon class string representing the antwortWert. (1 = green,...)
	 *
	 * @param integer antwortWert
	 * @returns string
	 */
	getIconClass(antwortWert) {
		return `antwort-checked wert-${antwortWert}`;
	},
	/**
	 * Get dynamic Highcharts plotLines for each Fragengruppe
	 *
	 * @param frageGruppen
	 * @returns Highcharts plotLines property
	 */
	getFbGruppenPlotlines(fbGruppen){
		const plotLines = [];
		let offset = 0;
		fbGruppen.forEach((g, i) => {
			offset += g.fbFragen.length;
			if (i < fbGruppen.length - 1) {
				plotLines.push({
					color: 'lightgrey',
					width: 1,
					dashStyle: 'Dash',
					value: offset - 0.8,
					zIndex: 5
				});
			}
		});

		return plotLines;
	}
};
