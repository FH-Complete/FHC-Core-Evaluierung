<?php

// Define Lehrformen, that should not be evaluated
$config['excludedLehrformen'] = ['EXAM', 'BE', 'FL'];

// Define if LV-Leitung is required for initializing Lvevaluierung
$config['lvLeitungRequired'] = true;

// Define filter to get only Lehreinheiten with unique 'Gruppenzusammensetzung'+Lector
$config['filterLehreinheitenByUniqueLectorAndGruppen'] = true;

// Define time window in which the lector is allowed to process the LV-Reflexion
$config['reflexionZeitfensterDauer'] = '2 weeks';