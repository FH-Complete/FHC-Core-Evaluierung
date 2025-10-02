<?php
$includesArray = array(
	'title' => 'Evaluierung Initiierung',
	'vue3' => true,
	'axios027' => true,
	'bootstrap5' => true,
	'fontawesome6' => true,
	'primevue3' => true,
	'navigationcomponent' => true,
	'customJSs' => array('vendor/vuejs/vuedatepicker_js/vue-datepicker.iife.js'),
	'customJSModules' => array(
        'public/extensions/FHC-Core-Evaluierung/js/apps/Initiierung.js'
    ),
	'customCSSs' => array(
		'public/extensions/FHC-Core-Evaluierung/css/Evaluierung.css',
        'public/css/components/primevue.css',
		'vendor/vuejs/vuedatepicker_css/main.css',
	)
);

if (defined("CIS4")) {
	$this->load->view(
		'templates/CISVUE-Header',
		$includesArray
	);
} else {
	$this->load->view(
		'templates/FHC-Header',
		$includesArray
	);
}
?>

<div id="lve-initiierung-main"></div>

<?php
if (defined("CIS4")) {
	$this->load->view(
		'templates/CISVUE-Footer',
		$includesArray
	);
} else {
	$this->load->view(
		'templates/FHC-Footer',
		$includesArray
	);
}
?>

