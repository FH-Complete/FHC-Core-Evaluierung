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
        'public/css/components/primevue.css'
	)
);

$this->load->view('templates/FHC-Header', $includesArray);
?>
<div id="header">
    title + language
</div>
<div id="main">
    <router-view></router-view>
</div>

<?php $this->load->view('templates/FHC-Footer', $includesArray); ?>
