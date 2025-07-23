export default {
	/**
	 * Format Date as DD.MM.YYYY.
	 * @param {Date | String} dateValue
	 * */
	formatDateTime(dateValue) {
		const dateObj = _toValidDate(dateValue);
	
		// Exit if date is not validated
		if (!dateObj) return null;
	
		// Return format DD.MM.YYYY
		return new Intl.DateTimeFormat('de-DE', {
			day: '2-digit',
			month: '2-digit',
			year: 'numeric',
			hour: '2-digit',
			minute: '2-digit'
		}).format(dateObj);
	},
	/**
	 * Format Date as DD.MM.YYYY.
	 * @param {Date | String} dateValue
	 * */
	formatDate(dateValue) {
		const dateObj = _toValidDate(dateValue);
	
		// Exit if date is not validated
		if (!dateObj) return null;
	
		// Return format DD.MM.YYYY
		return new Intl.DateTimeFormat('de-DE', {
			day: '2-digit',
			month: '2-digit',
			year: 'numeric'
		}).format(dateObj);
	},
	/**
	 * Format Date as HH:MM.
	 * @param {Date | String} dateValue
	 * */
	formatTime(dateValue) {
		const dateObj = _toValidDate(dateValue);
	
		// Exit if date is not validated
		if (!dateObj) return null;
	
		// Return format HH:MM
		return new Intl.DateTimeFormat('de-DE', {
			hour: '2-digit',
			minute: '2-digit'
		}).format(dateObj);
	},
	/**
	 * Format Date as PGSQL Timestamp DD.MM.YYYY HH:MM:SS
	 * @param {Date | String} dateValue
	 * */
	formatToSqlTimestamp(dateValue) {
		const dateObj = _toValidDate(dateValue);
		const pad = (n) => n.toString().padStart(2, '0');

		return `${dateObj.getFullYear()}-${pad(dateObj.getMonth() + 1)}-${pad(dateObj.getDate())} ` +
				`${pad(dateObj.getHours())}:${pad(dateObj.getMinutes())}:${pad(dateObj.getSeconds())}`;
	}
}

// Private functions
//----------------------------------------------------------------------------------------------------------------------
/**
 * Return Javascript Date Object.
 * @param {Date | String} dateValue
 * return Date Object || null
 */
function _toValidDate(dateValue) {
	// If already is Date
	if (dateValue instanceof Date) {

		// Return after validating
		return isNaN(dateValue) ? null : dateValue;
	}

	// If is String
	if (typeof dateValue === 'string') {
		const safeStr = dateValue.replace(' ', 'T'); // Make "YYYY-MM-DD HH:MM:SS" safe for Date constructor
		const parsed = new Date(safeStr);

		// Return after validating
		return isNaN(parsed) ? null : parsed;
	}

	// Not a date or string => invalid
	return null;
}