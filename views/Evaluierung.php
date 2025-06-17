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
		'public/extensions/FHC-Core-Evaluierung/css/Countdown.css',
        'public/css/components/primevue.css'
	)
);

$this->load->view('templates/FHC-Header', $includesArray);
?>
<div id="lve-evaluierung-main">
	<div id="lve-evaluierung-header" class="fixed-top">
		<div class="row fhc-bgc-blue py-2 px-3 align-items-center">
			<div class="col text-start text-light">{{ $p.t('global/lvevaluierung') }}</div>
			<div class="col text-end">
				<sprache-dropdown></sprache-dropdown>
			</div>
		</div>
	</div>
	<div id="lve-evaluierung-body" class="container-fluid">
		<router-view></router-view>
	</div>
</div>

<?php $this->load->view('templates/FHC-Footer', $includesArray); ?>
