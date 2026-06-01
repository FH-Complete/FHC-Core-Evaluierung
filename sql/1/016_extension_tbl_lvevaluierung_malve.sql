CREATE TABLE IF NOT EXISTS extension.tbl_lvevaluierung_malve(
    malve_id integer,
    studiensemester_kurzbz varchar(32),
    oe_kurzbz varchar(32),
    insertamum timestamp DEFAULT NOW(),
    insertvon varchar(32)
);

COMMENT ON TABLE extension.tbl_lvevaluierung_malve IS 'Maßnahmenableitungsbericht zu Evaluierungen';

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_malve
    ADD CONSTRAINT pk_tbl_lvevaluierung_malve_malve_id PRIMARY KEY (malve_id);
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

CREATE SEQUENCE IF NOT EXISTS extension.seq_tbl_lvevaluierung_malve_malve_id
	 INCREMENT BY 1
	 NO MAXVALUE
	 NO MINVALUE
	 CACHE 1;
ALTER TABLE extension.tbl_lvevaluierung_malve
    ALTER COLUMN malve_id SET DEFAULT nextval('extension.seq_tbl_lvevaluierung_malve_malve_id');
GRANT SELECT, INSERT, UPDATE ON extension.seq_tbl_lvevaluierung_malve_malve_id TO vilesci;
GRANT SELECT, INSERT, UPDATE ON extension.seq_tbl_lvevaluierung_malve_malve_id TO web;

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_malve
    ADD CONSTRAINT fk_tbl_lvevaluierung_malve_studiensemester_kurzbz FOREIGN KEY (studiensemester_kurzbz)
        REFERENCES public.tbl_studiensemester (studiensemester_kurzbz)
        ON DELETE CASCADE ON UPDATE CASCADE;
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_malve
    ADD CONSTRAINT fk_tbl_lvevaluierung_malve_oe_kurzbz FOREIGN KEY (oe_kurzbz)
        REFERENCES public.tbl_organisationseinheit (oe_kurzbz)
        ON DELETE CASCADE ON UPDATE CASCADE;
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

GRANT SELECT, INSERT ON TABLE extension.tbl_lvevaluierung_malve TO vilesci;
GRANT SELECT, INSERT ON TABLE extension.tbl_lvevaluierung_malve TO web;

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_malve
    ADD CONSTRAINT uq_studiensemester_kurzbz_oe_kurzbz UNIQUE (studiensemester_kurzbz, oe_kurzbz);
EXCEPTION WHEN OTHERS THEN NULL;
END $$;
