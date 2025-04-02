CREATE TABLE IF NOT EXISTS extension.tbl_lvevaluierung_lehrveranstaltung (
    lvevaluierung_lehrveranstaltung_id integer,
    lehrveranstaltung_id integer,
    studiensemester_kurzbz varchar(32),
    verpflichtend boolean,
    lv_aufgeteilt boolean
);

COMMENT ON TABLE extension.tbl_lvevaluierung_lehrveranstaltung IS 'Liste LV Evaluierung Lehrveranstaltungen';

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_lehrveranstaltung
    ADD CONSTRAINT pk_tbl_lvevaluierung_lehrveranstaltung_lvevaluierung_lehrveranstaltung_id PRIMARY KEY (lvevaluierung_lehrveranstaltung_id);
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

CREATE SEQUENCE IF NOT EXISTS extension.seq_tbl_lvevaluierung_lehrveranstaltung_lvevaluierung_lehrveranstaltung_id
	 INCREMENT BY 1
	 NO MAXVALUE
	 NO MINVALUE
	 CACHE 1;
ALTER TABLE extension.tbl_lvevaluierung_lehrveranstaltung
    ALTER COLUMN lvevaluierung_lehrveranstaltung_id SET DEFAULT nextval('extension.seq_tbl_lvevaluierung_lehrveranstaltung_lvevaluierung_lehrveranstaltung_id');

GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE extension.tbl_lvevaluierung_lehrveranstaltung TO vilesci;
GRANT SELECT ON TABLE extension.tbl_lvevaluierung_lehrveranstaltung TO web;
GRANT SELECT, UPDATE ON extension.seq_tbl_lvevaluierung_lehrveranstaltung_lvevaluierung_lehrveranstaltung_id TO vilesci;