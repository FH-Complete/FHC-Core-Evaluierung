CREATE TABLE IF NOT EXISTS extension.tbl_lvevaluierung_code (
    lvevaluierung_code_id integer,
    code varchar(32) UNIQUE NOT NULL,
    startzeit timestamp,
    endezeit timestamp,
    lvevaluierung_id integer
);

COMMENT ON TABLE extension.tbl_lvevaluierung_code IS 'Liste LV Evaluierung Codes';

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_code
    ADD CONSTRAINT pk_tbl_lvevaluierung_code_lvevaluierung_code_id PRIMARY KEY (lvevaluierung_code_id);
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_code
    ADD CONSTRAINT fk_tbl_lvevaluierung_code_lvevaluierung_id FOREIGN KEY (lvevaluierung_id)
        REFERENCES extension.tbl_lvevaluierung (lvevaluierung_id)
        ON DELETE CASCADE ON UPDATE CASCADE;
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

CREATE SEQUENCE IF NOT EXISTS extension.seq_tbl_lvevaluierung_code_lvevaluierung_code_id
	 INCREMENT BY 1
	 NO MAXVALUE
	 NO MINVALUE
	 CACHE 1;
ALTER TABLE extension.tbl_lvevaluierung_code
    ALTER COLUMN lvevaluierung_code_id SET DEFAULT nextval('extension.seq_tbl_lvevaluierung_code_lvevaluierung_code_id');

GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE extension.tbl_lvevaluierung_code TO vilesci;
GRANT SELECT ON TABLE extension.tbl_lvevaluierung_code TO web;
GRANT SELECT, UPDATE ON extension.seq_tbl_lvevaluierung_code_lvevaluierung_code_id TO vilesci;
