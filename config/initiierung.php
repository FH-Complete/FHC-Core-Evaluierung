<?php

// Define Lehrformen, that should not be evaluated
$config['excludedLehrformen'] = ['EXAM', 'BE', 'FL'];

// Define if LV-Leitung is required for initializing Lvevaluierung
$config['lvLeitungRequired'] = true;

// Define filter to get only Lehreinheiten with unique 'Gruppenzusammensetzung'+Lector
$config['filterLehreinheitenByUniqueLectorAndGruppen'] = true;