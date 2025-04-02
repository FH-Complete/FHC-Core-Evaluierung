CREATE TABLE IF NOT EXISTS extension.tbl_lvevaluierung_fragebogen_frage_antwort (
    lvevaluierung_frage_antwort_id integer,
    lvevaluierung_frage_id integer NOT NULL,
    bezeichnung text[],
    sort integer,
    wert integer
);

COMMENT ON TABLE extension.tbl_lvevaluierung_fragebogen_frage_antwort IS 'Liste LV Evaluierung Antwort zu Fragen zu Fragebogen';

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_fragebogen_frage_antwort
    ADD CONSTRAINT pk_tbl_lvevaluierung_fragebogen_frage_antwort_lvevaluierung_frage_antwort_id PRIMARY KEY (lvevaluierung_frage_antwort_id);
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_fragebogen_frage_antwort
    ADD CONSTRAINT fh_tbl_lvevaluierung_fragebogen_frage_antwort_lvevaluierung_frage_id FOREIGN KEY (lvevaluierung_frage_id)
    REFERENCES extension.tbl_lvevaluierung_fragebogen_frage (lvevaluierung_frage_id)
    ON DELETE CASCADE ON UPDATE CASCADE;
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

CREATE SEQUENCE IF NOT EXISTS extension.seq_tbl_lvevaluierung_fragebogen_frage_antwort_lvevaluierung_frage_antwort_id
	 INCREMENT BY 1
	 NO MAXVALUE
	 NO MINVALUE
	 CACHE 1;
ALTER TABLE extension.tbl_lvevaluierung_fragebogen_frage_antwort
    ALTER COLUMN lvevaluierung_frage_antwort_id
    SET DEFAULT nextval('extension.seq_tbl_lvevaluierung_fragebogen_frage_antwort_lvevaluierung_frage_antwort_id');

GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE extension.tbl_lvevaluierung_fragebogen_frage_antwort TO vilesci;
GRANT SELECT ON TABLE extension.tbl_lvevaluierung_fragebogen_frage_antwort TO web;
GRANT SELECT, UPDATE ON extension.seq_tbl_lvevaluierung_fragebogen_frage_antwort_lvevaluierung_frage_antwort_id TO vilesci;

