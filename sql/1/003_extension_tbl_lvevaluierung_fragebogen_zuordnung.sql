CREATE TABLE IF NOT EXISTS extension.tbl_lvevaluierung_fragebogen_zuordnung (
    lvevaluierung_fragebogen_zuordnung_id integer,
    fragebogen_id integer,
    oe_kurzbz varchar (32),
    studienplan_id integer
);

COMMENT ON TABLE extension.tbl_lvevaluierung_fragebogen_zuordnung IS 'Zuordnung Fragebogen zu Studienplan und OE';

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_fragebogen_zuordnung
    ADD CONSTRAINT pk_tbl_lvevaluierung_fragebogen_zuordnung_lvevaluierung_fragebogen_zuordnung_id PRIMARY KEY (lvevaluierung_fragebogen_zuordnung_id);
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_fragebogen_zuordnung
    ADD CONSTRAINT fk_tbl_lvevaluierung_fragebogen_zuordnung_fragebogen_id FOREIGN KEY (fragebogen_id)
    REFERENCES extension.tbl_lvevaluierung_fragebogen (fragebogen_id)
    ON DELETE CASCADE ON UPDATE CASCADE;
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

CREATE SEQUENCE IF NOT EXISTS extension.seq_tbl_lvevaluierung_fragebogen_zuordnung_lvevaluierung_fragebogen_zuordnung_id
	 INCREMENT BY 1
	 NO MAXVALUE
	 NO MINVALUE
	 CACHE 1;
ALTER TABLE extension.tbl_lvevaluierung_fragebogen_zuordnung
    ALTER COLUMN lvevaluierung_fragebogen_zuordnung_id
    SET DEFAULT nextval('extension.seq_tbl_lvevaluierung_fragebogen_zuordnung_lvevaluierung_fragebogen_zuordnung_id');

GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE extension.tbl_lvevaluierung_fragebogen_zuordnung TO vilesci;
GRANT SELECT ON TABLE extension.tbl_lvevaluierung_fragebogen_zuordnung TO web;
GRANT SELECT, UPDATE ON extension.seq_tbl_lvevaluierung_fragebogen_zuordnung_lvevaluierung_fragebogen_zuordnung_id TO vilesci;

