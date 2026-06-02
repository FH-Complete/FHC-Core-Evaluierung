INSERT INTO system.tbl_berechtigung (berechtigung_kurzbz, beschreibung) VALUES
    ('extension/lvevaluierung_admin', 'LV-Evaluierung Ansichten für Admins')
    ON CONFLICT (berechtigung_kurzbz) DO NOTHING;