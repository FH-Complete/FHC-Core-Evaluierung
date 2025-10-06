CREATE TABLE IF NOT EXISTS extension.tbl_lvevaluierung_prestudent (
    lvevaluierung_prestudent_id integer,
    prestudent_id integer,
    lvevaluierung_id integer,
    insertamum timestamp DEFAULT NOW(),
    insertvon varchar(32),
    updateamum timestamp,
    updatevon varchar(32)
);

COMMENT ON TABLE extension.tbl_lvevaluierung_prestudent IS 'Liste LV Evaluierung Prestudenten';

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_prestudent
    ADD CONSTRAINT pk_tbl_lvevaluierung_prestudent_lvevaluierung_prestudent_id PRIMARY KEY (lvevaluierung_prestudent_id);
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_prestudent
    ADD CONSTRAINT fk_tbl_lvevaluierung_prestudent_prestudent_id FOREIGN KEY (prestudent_id)
        REFERENCES public.tbl_prestudent (prestudent_id)
        ON DELETE CASCADE ON UPDATE CASCADE;
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_prestudent
    ADD CONSTRAINT fk_tbl_lvevaluierung_prestudent_lvevaluierung_id FOREIGN KEY (lvevaluierung_id)
        REFERENCES extension.tbl_lvevaluierung (lvevaluierung_id)
        ON DELETE CASCADE ON UPDATE CASCADE;
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

CREATE SEQUENCE IF NOT EXISTS extension.seq_tbl_lvevaluierung_prestudent_lvevaluierung_prestudent_id
	 INCREMENT BY 1
	 NO MAXVALUE
	 NO MINVALUE
	 CACHE 1;
ALTER TABLE extension.tbl_lvevaluierung_prestudent
    ALTER COLUMN lvevaluierung_prestudent_id SET DEFAULT nextval('extension.seq_tbl_lvevaluierung_prestudent_lvevaluierung_prestudent_id');

GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE extension.tbl_lvevaluierung_prestudent TO vilesci;
GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE extension.tbl_lvevaluierung_prestudent TO web;
GRANT SELECT, INSERT, UPDATE, DELETE ON extension.seq_tbl_lvevaluierung_prestudent_lvevaluierung_prestudent_id TO vilesci;
GRANT SELECT, INSERT, UPDATE, DELETE ON extension.seq_tbl_lvevaluierung_prestudent_lvevaluierung_prestudent_id TO web;