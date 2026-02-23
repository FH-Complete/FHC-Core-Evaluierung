CREATE TABLE IF NOT EXISTS extension.tbl_lvevaluierung_zeitfenster (
    lvevaluierung_zeitfenster_id integer NOT NULL,
	typ varchar(32),
	studiensemester_kurzbz varchar(32),
	startdatum timestamp without time zone,
	endedatum timestamp without time zone
);

COMMENT ON TABLE extension.tbl_lvevaluierung_zeitfenster IS 'Zeitliche Freischaltungen fuer LV Evaluierungen';

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_zeitfenster
    ADD CONSTRAINT pk_tbl_lvevaluierung_zeitfenster_lvevaluierung_zeitfenster_id PRIMARY KEY (lvevaluierung_zeitfenster_id);
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_zeitfenster
    ADD CONSTRAINT fk_tbl_lvevaluierung_zeitfenster_studiensemester_kurzbz FOREIGN KEY (studiensemester_kurzbz)
        REFERENCES public.tbl_studiensemester (studiensemester_kurzbz)
        ON DELETE CASCADE ON UPDATE CASCADE;
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

CREATE SEQUENCE IF NOT EXISTS extension.seq_tbl_lvevaluierung_zeitfenster_lvevaluierung_zeitfenster_id
	 INCREMENT BY 1
	 NO MAXVALUE
	 NO MINVALUE
	 CACHE 1;
ALTER TABLE extension.tbl_lvevaluierung_zeitfenster
    ALTER COLUMN lvevaluierung_zeitfenster_id SET DEFAULT nextval('extension.seq_tbl_lvevaluierung_zeitfenster_lvevaluierung_zeitfenster_id');

GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE extension.tbl_lvevaluierung_zeitfenster TO vilesci;
GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE extension.tbl_lvevaluierung_zeitfenster TO web;
GRANT SELECT, INSERT, UPDATE, DELETE ON extension.seq_tbl_lvevaluierung_zeitfenster_lvevaluierung_zeitfenster_id TO vilesci;
GRANT SELECT, INSERT, UPDATE, DELETE ON extension.seq_tbl_lvevaluierung_zeitfenster_lvevaluierung_zeitfenster_id TO web;

--INSERT INTO extension.tbl_lvevaluierung_zeitfenster(typ, studiensemester_kurzbz, startdatum, endedatum) VALUES
--('stgauswahl','SS2026','2026-01-26 00:00:00','2026-02-08 00:00:00'),
--('stgauswahl','WS2026','2026-08-26 00:00:00','2026-09-08 00:00:00'),
--('typswitch','SS2026','2026-02-09 00:00:00','2026-02-22 00:00:00'),
--('typswitch','WS2026','2026-09-09 00:00:00','2026-09-22 00:00:00');
