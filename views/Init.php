<?php
$includesArray = array(
	'title' => 'Evaluierung starten',
	'vue3' => true,
	'axios027' => true,
	'bootstrap5' => true,
	'fontawesome6' => true,
	'primevue3' => true,
	'navigationcomponent' => true,
	'customJSs' => array('vendor/vuejs/vuedatepicker_js/vue-datepicker.iife.js'),
	'customJSModules' => array(
        'public/extensions/FHC-Core-Evaluierung/js/apps/Init.js'
    ),
	'customCSSs' => array(
		'public/extensions/FHC-Core-Evaluierung/css/Evaluierung.css',
        'public/css/components/primevue.css',
		'vendor/vuejs/vuedatepicker_css/main.css',
	)
);

$this->load->view('templates/FHC-Header', $includesArray);
?>

<div id="lve-starten-main"></div>

<?php $this->load->view('templates/FHC-Footer', $includesArray); ?>
