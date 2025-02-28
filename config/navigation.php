<?php

// Add Side-Menu-Entry to Main Page
$config['navigation_header']['*']['Administration']['children']['Evaluierung'] = array(
		'link' => site_url('extensions/FHC-Core-Evaluierung/Administration'),
		'description' => 'LV Evaluierung',
		'expand' => true,
		'requiredPermissions' => 'admin:rw'
);
