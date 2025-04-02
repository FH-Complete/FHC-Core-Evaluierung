CREATE TABLE IF NOT EXISTS extension.tbl_lvevaluierung_fragebogen (
    fragebogen_id integer,
    bezeichnung text,
    gueltig_von date,
    gueltig_bis date
);

COMMENT ON TABLE extension.tbl_lvevaluierung_fragebogen IS 'Liste der LV Evaluierung Fragebogen';

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_fragebogen
    ADD CONSTRAINT pk_tbl_lvevaluierung_fragebogen_fragebogen_id PRIMARY KEY (fragebogen_id);
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

CREATE SEQUENCE IF NOT EXISTS extension.seq_tbl_lvevaluierung_fragebogen_fragebogen_id
	 INCREMENT BY 1
	 NO MAXVALUE
	 NO MINVALUE
	 CACHE 1;

ALTER TABLE extension.tbl_lvevaluierung_fragebogen
    ALTER COLUMN fragebogen_id
    SET DEFAULT nextval('extension.seq_tbl_lvevaluierung_fragebogen_fragebogen_id');

GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE extension.tbl_lvevaluierung_fragebogen TO vilesci;
GRANT SELECT ON TABLE extension.tbl_lvevaluierung_fragebogen TO web;
GRANT SELECT, UPDATE ON extension.seq_tbl_lvevaluierung_fragebogen_fragebogen_id TO vilesci;
