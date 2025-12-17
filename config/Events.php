<?php

use CI3_Events as Events;


Events::on('lvMenuBuild', function ($menu_reference, $params) {

	$menu =& $menu_reference();

	// Show LVE Neu only for Pilot Studiengänge
	// TODO alternative link to 'old' LVE
	if (defined('CIS_EVALUIERUNG_ANZEIGEN_STG')
		&& CIS_EVALUIERUNG_ANZEIGEN_STG && $params['angemeldet']
		&& (!defined('CIS_EVALUIERUNG_ANZEIGEN_STG') || in_array($params['studiengang_kz'], unserialize(CIS_EVALUIERUNG_ANZEIGEN_STG)))
		&& ($params['permissionLib']->isBerechtigt('extension/lvevaluierung_init')))
	{
		$lehrveranstaltung_id = $params['lvid'];
		$studiensemester_kurzbz = $params['angezeigtes_stsem'];

		$text='(Pilotphase)';
		$link= APP_ROOT. 'cis.php/extensions/FHC-Core-Evaluierung/Initiierung?lehrveranstaltung_id='. $lehrveranstaltung_id.'&studiensemester_kurzbz='.$studiensemester_kurzbz;

		$menu[]=array
		(
			'id'=>'extension_lvevaluierung_menu_initiierung',
			'position'=>'140',
			'name'=> $params['phrasesLib']->t('global', 'lvevaluierung'),
			'phrase'=> 'global/lvevaluierung',
			'c4_icon'=> APP_ROOT. "/skin/images/button_lvevaluierung.png",
			'c4_link'=> $link,
			'text'=>$text
		);
	}
});
