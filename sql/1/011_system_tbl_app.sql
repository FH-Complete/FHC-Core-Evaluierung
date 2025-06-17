INSERT INTO system.tbl_app (app) VALUES
    ('lvevaluierung')
    ON CONFLICT (app) DO NOTHING;