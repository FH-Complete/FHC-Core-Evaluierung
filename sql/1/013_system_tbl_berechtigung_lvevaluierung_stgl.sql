INSERT INTO system.tbl_berechtigung (berechtigung_kurzbz, beschreibung) VALUES
    ('extension/lvevaluierung_stg', 'LV-Evaluierung Ansichten für Studiengaenge')
    ON CONFLICT (berechtigung_kurzbz) DO NOTHING;