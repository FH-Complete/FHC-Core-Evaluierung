INSERT INTO system.tbl_berechtigung (berechtigung_kurzbz, beschreibung) VALUES
    ('extension/lvevaluierung_init', 'LV-Evaluierung starten und Codeversand')
    ON CONFLICT (berechtigung_kurzbz) DO NOTHING;