<?php
$includesArray = array(
	'title' => 'Evaluierung',
	'vue3' => true,
	'axios027' => true,
	'bootstrap5' => true,
	'tabulator5' => true,
	'fontawesome6' => true,
	'primevue3' => true,
	'navigationcomponent' => true,
	'filtercomponent' => true,
	'customJSModules' => array(
		'public/extensions/FHC-Core-Evaluierung/js/apps/Administration.js'
	),
	'customCSSs' => array(
	    'public/css/components/vue-datepicker.css',
        'public/css/components/primevue.css',
		'public/css/components/verticalsplit.css',
	)
);

$this->load->view('templates/FHC-Header', $includesArray);
?>

<div id="lve-evaluierung-admin"></div>

<?php $this->load->view('templates/FHC-Footer', $includesArray); ?>
