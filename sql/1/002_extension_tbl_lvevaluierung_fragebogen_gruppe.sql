CREATE TABLE IF NOT EXISTS extension.tbl_lvevaluierung_fragebogen_gruppe (
    lvevaluierung_fragebogen_gruppe_id integer,
    fragebogen_id integer,
    typ varchar (32),
    bezeichnung text,
    sort integer,
    style text
);

COMMENT ON TABLE extension.tbl_lvevaluierung_fragebogen_gruppe IS 'Liste LV Evaluierung Fragebogen Gruppen';

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_fragebogen_gruppe
    ADD CONSTRAINT pk_tbl_lvevaluierung_fragebogen_gruppe_lvevaluierung_fragebogen_gruppe_id PRIMARY KEY (lvevaluierung_fragebogen_gruppe_id);
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_fragebogen_gruppe
    ADD CONSTRAINT fk_tbl_lvevaluierung_fragebogen_gruppe_fragebogen_id FOREIGN KEY (fragebogen_id)
    REFERENCES extension.tbl_lvevaluierung_fragebogen (fragebogen_id)
    ON DELETE CASCADE ON UPDATE CASCADE;
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

CREATE SEQUENCE IF NOT EXISTS extension.seq_tbl_lvevaluierung_fragebogen_gruppe_lvevaluierung_fragebogen_gruppe_id
	 INCREMENT BY 1
	 NO MAXVALUE
	 NO MINVALUE
	 CACHE 1;
ALTER TABLE extension.tbl_lvevaluierung_fragebogen_gruppe
    ALTER COLUMN lvevaluierung_fragebogen_gruppe_id
    SET DEFAULT nextval('extension.seq_tbl_lvevaluierung_fragebogen_gruppe_lvevaluierung_fragebogen_gruppe_id');

GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE extension.tbl_lvevaluierung_fragebogen_gruppe TO vilesci;
GRANT SELECT ON TABLE extension.tbl_lvevaluierung_fragebogen_gruppe TO web;
GRANT SELECT, UPDATE ON extension.seq_tbl_lvevaluierung_fragebogen_gruppe_lvevaluierung_fragebogen_gruppe_id TO vilesci;

