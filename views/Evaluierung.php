<?php
$includesArray = array(
	'title' => 'Evaluierung',
	'vue3' => true,
	'axios027' => true,
	'bootstrap5' => true,
	'fontawesome6' => true,
	'primevue3' => true,
	'navigationcomponent' => true,
	'customJSModules' => array(
        'public/extensions/FHC-Core-Evaluierung/js/apps/Evaluierung.js'
    ),
	'customCSSs' => array(
		'public/extensions/FHC-Core-Evaluierung/css/Evaluierung.css',
        'public/css/components/primevue.css'
	)
);

$this->load->view('templates/FHC-Header', $includesArray);
?>
<div id="header" class="fixed-top">
	<div class="row fhc-bgc-blue py-2 px-3">
		<div class="col text-start text-light">LV-Evaluierung</div>
		<div class="col text-end">Dropdown Language</div>
	</div>
</div>
<div id="main" class="container-fluid">
    <router-view></router-view>
</div>

<?php $this->load->view('templates/FHC-Footer', $includesArray); ?>
