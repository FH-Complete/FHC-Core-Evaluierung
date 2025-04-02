CREATE TABLE IF NOT EXISTS extension.tbl_lvevaluierung_fragebogen_frage (
    lvevaluierung_frage_id integer,
    typ varchar (32) NOT NULL,
    bezeichnung text[],
    sort integer,
    verpflichtend boolean NOT NULL,
    lvevaluierung_fragebogen_gruppe_id integer
);

COMMENT ON TABLE extension.tbl_lvevaluierung_fragebogen_frage IS 'Liste LV Evaluierung Fragen zu Fragebogen';

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_fragebogen_frage
    ADD CONSTRAINT pk_tbl_lvevaluierung_fragebogen_frage_lvevaluierung_frage_id PRIMARY KEY (lvevaluierung_frage_id);
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_fragebogen_frage
    ADD CONSTRAINT fk_tbl_lvevaluierung_fragebogen_frage_lvevaluierung_fragebogen_gruppe_id FOREIGN KEY (lvevaluierung_fragebogen_gruppe_id)
    REFERENCES extension.tbl_lvevaluierung_fragebogen_gruppe (lvevaluierung_fragebogen_gruppe_id)
    ON DELETE CASCADE ON UPDATE CASCADE;
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

CREATE SEQUENCE IF NOT EXISTS extension.seq_tbl_lvevaluierung_fragebogen_frage_lvevaluierung_frage_id
	 INCREMENT BY 1
	 NO MAXVALUE
	 NO MINVALUE
	 CACHE 1;
ALTER TABLE extension.tbl_lvevaluierung_fragebogen_frage
    ALTER COLUMN lvevaluierung_frage_id
    SET DEFAULT nextval('extension.seq_tbl_lvevaluierung_fragebogen_frage_lvevaluierung_frage_id');

GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE extension.tbl_lvevaluierung_fragebogen_frage TO vilesci;
GRANT SELECT ON TABLE extension.tbl_lvevaluierung_fragebogen_frage TO web;
GRANT SELECT, UPDATE ON extension.seq_tbl_lvevaluierung_fragebogen_frage_lvevaluierung_frage_id TO vilesci;

