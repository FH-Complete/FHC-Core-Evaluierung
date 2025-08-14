CREATE TABLE IF NOT EXISTS extension.tbl_lvevaluierung (
    lvevaluierung_id integer,
    startzeit timestamp,
    endezeit timestamp,
    dauer time,
    codes_ausgegeben integer,
    insertamum timestamp DEFAULT NOW(),
    insertvon varchar(32),
    updateamum timestamp,
    updatevon varchar(32),
    codes_gemailt boolean NOT NULL,
    lehreinheit_id integer,
    lvevaluierung_lehrveranstaltung_id integer,
    fragebogen_id integer
);

COMMENT ON TABLE extension.tbl_lvevaluierung IS 'Liste LV Evaluierungen';

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung
    ADD CONSTRAINT pk_tbl_lvevaluierung_lvevaluierung_id PRIMARY KEY (lvevaluierung_id);
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung
    ADD CONSTRAINT fk_tbl_lvevaluierung_lehreinheit_id FOREIGN KEY (lehreinheit_id)
        REFERENCES lehre.tbl_lehreinheit (lehreinheit_id)
        ON DELETE CASCADE ON UPDATE CASCADE;
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung
    ADD CONSTRAINT fk_tbl_lvevaluierung_lvevaluierung_lehrveranstaltung_id FOREIGN KEY (lvevaluierung_lehrveranstaltung_id)
        REFERENCES extension.tbl_lvevaluierung_lehrveranstaltung (lvevaluierung_lehrveranstaltung_id)
        ON DELETE CASCADE ON UPDATE CASCADE;
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung
    ADD CONSTRAINT fk_tbl_lvevaluierung_fragebogen_id FOREIGN KEY (fragebogen_id)
        REFERENCES extension.tbl_lvevaluierung_fragebogen (fragebogen_id)
        ON DELETE CASCADE ON UPDATE CASCADE;
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

CREATE SEQUENCE IF NOT EXISTS extension.seq_tbl_lvevaluierung_lvevaluierung_id
	 INCREMENT BY 1
	 NO MAXVALUE
	 NO MINVALUE
	 CACHE 1;
ALTER TABLE extension.tbl_lvevaluierung
    ALTER COLUMN lvevaluierung_id SET DEFAULT nextval('extension.seq_tbl_lvevaluierung_lvevaluierung_id');

ALTER TABLE extension.tbl_lvevaluierung
    ALTER COLUMN codes_gemailt SET DEFAULT FALSE;

GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE extension.tbl_lvevaluierung TO vilesci;
GRANT SELECT ON TABLE extension.tbl_lvevaluierung TO web;
GRANT SELECT, UPDATE ON extension.seq_tbl_lvevaluierung_lvevaluierung_id TO vilesci;