INSERT INTO system.tbl_berechtigung (berechtigung_kurzbz, beschreibung) VALUES
    ('extension/lvevaluierung_kf', 'LV-Evaluierung Ansichten für Kompetenzfeldleitung')
    ON CONFLICT (berechtigung_kurzbz) DO NOTHING;