CREATE TABLE IF NOT EXISTS extension.tbl_lvevaluierung_antwort (
    lvevaluierung_antwort_id integer,
    lvevaluierung_frage_antwort_id integer,
    lvevaluierung_frage_id integer,
    lvevaluierung_code_id integer,
    antwort text
);

COMMENT ON TABLE extension.tbl_lvevaluierung_antwort IS 'Liste LV Evaluierung Antworten der Studierenden';

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_antwort
    ADD CONSTRAINT pk_tbl_lvevaluierung_antwort_lvevaluierung_antwort_id PRIMARY KEY (lvevaluierung_antwort_id);
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_antwort
    ADD CONSTRAINT fk_tbl_lvevaluierung_antwort_lvevaluierung_frage_antwort_id FOREIGN KEY (lvevaluierung_frage_antwort_id)
        REFERENCES extension.tbl_lvevaluierung_fragebogen_frage_antwort (lvevaluierung_frage_antwort_id)
        ON DELETE CASCADE ON UPDATE CASCADE;
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_antwort
    ADD CONSTRAINT fk_tbl_lvevaluierung_antwort_lvevaluierung_frage_id FOREIGN KEY (lvevaluierung_frage_id)
        REFERENCES extension.tbl_lvevaluierung_fragebogen_frage (lvevaluierung_frage_id)
        ON DELETE CASCADE ON UPDATE CASCADE;
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_antwort
    ADD CONSTRAINT fk_tbl_lvevaluierung_antwort_lvevaluierung_code_id FOREIGN KEY (lvevaluierung_code_id)
        REFERENCES extension.tbl_lvevaluierung_code (lvevaluierung_code_id)
        ON DELETE CASCADE ON UPDATE CASCADE;
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_antwort
    ADD CONSTRAINT uq_frageid_codeid UNIQUE (lvevaluierung_frage_id, lvevaluierung_code_id);
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

CREATE SEQUENCE IF NOT EXISTS extension.seq_tbl_lvevaluierung_antwort_lvevaluierung_antwort_id
	 INCREMENT BY 1
	 NO MAXVALUE
	 NO MINVALUE
	 CACHE 1;
ALTER TABLE extension.tbl_lvevaluierung_antwort
    ALTER COLUMN lvevaluierung_antwort_id SET DEFAULT nextval('extension.seq_tbl_lvevaluierung_antwort_lvevaluierung_antwort_id');

GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE extension.tbl_lvevaluierung_antwort TO vilesci;
GRANT SELECT ON TABLE extension.tbl_lvevaluierung_antwort TO web;
GRANT SELECT, UPDATE ON extension.seq_tbl_lvevaluierung_antwort_lvevaluierung_antwort_id TO vilesci;

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_antwort ADD COLUMN IF NOT EXISTS unangemessen boolean NOT NULL DEFAULT false;
COMMENT ON COLUMN extension.tbl_lvevaluierung_antwort.unangemessen IS 'True if response violates code of conduct (offensive or inappropriate)';
EXCEPTION WHEN OTHERS THEN NULL;
END $$;
