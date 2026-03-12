CREATE TABLE IF NOT EXISTS extension.tbl_lvevaluierung_reflexion(
	lvevaluierung_reflexion_id bigint NOT NULL, 
	lvevaluierung_id integer NOT NULL, 
	mitarbeiter_uid varchar(32) NOT NULL, 
	
	praesenz_kurzbz varchar(32), 
	nachvollziehbar_kurzbz varchar(32),
	anmerkung_nachvollziehbarkeit text,
	massnahmennoetig boolean,
	
	insertamum timestamp,
	insertvon varchar(32),
	updateamum timestamp,
	updatevon varchar(32)

);

COMMENT ON TABLE extension.tbl_lvevaluierung_reflexion IS 'Reflexionen zu Evaluierungen';

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_reflexion
    ADD CONSTRAINT pk_tbl_lvevaluierung_reflexion_lvevaluierung_reflexion_id PRIMARY KEY (lvevaluierung_reflexion_id);
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

CREATE SEQUENCE IF NOT EXISTS extension.seq_tbl_lvevaluierung_reflexion_lvevaluierung_reflexion_id
	 INCREMENT BY 1
	 NO MAXVALUE
	 NO MINVALUE
	 CACHE 1;
ALTER TABLE extension.tbl_lvevaluierung_reflexion
    ALTER COLUMN lvevaluierung_reflexion_id SET DEFAULT nextval('extension.seq_tbl_lvevaluierung_reflexion_lvevaluierung_reflexion_id');
GRANT SELECT, INSERT, UPDATE ON extension.seq_tbl_lvevaluierung_reflexion_lvevaluierung_reflexion_id TO vilesci;

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_reflexion
    ADD CONSTRAINT fk_tbl_lvevaluierung_reflexion_mitarbeiter_uid FOREIGN KEY (mitarbeiter_uid)
        REFERENCES public.tbl_mitarbeiter (mitarbeiter_uid)
        ON DELETE CASCADE ON UPDATE CASCADE;
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_reflexion
    ADD CONSTRAINT fk_tbl_lvevaluierung_reflexion_lvevaluierung_id FOREIGN KEY (lvevaluierung_id)
        REFERENCES extension.tbl_lvevaluierung (lvevaluierung_id)
        ON DELETE CASCADE ON UPDATE CASCADE;
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE extension.tbl_lvevaluierung_reflexion TO vilesci;
GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE extension.tbl_lvevaluierung_reflexion TO web;

CREATE TABLE IF NOT EXISTS extension.tbl_lvevaluierung_reflexion_antwort_praesenz(
	praesenz_kurzbz varchar(32) NOT NULL,
	bezeichnung_mehrsprachig text[]
);

COMMENT ON TABLE extension.tbl_lvevaluierung_reflexion_antwort_praesenz IS 'Reflexionsantwortmöglichkeiten zu Evaluierungen Praesenz';

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_reflexion_antwort_praesenz
    ADD CONSTRAINT tbl_lvevaluierung_reflexion_antwort_praesenz_praesenz_kurzbz PRIMARY KEY (praesenz_kurzbz);
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_reflexion
    ADD CONSTRAINT fk_tbl_lvevaluierung_reflexion_praesenz FOREIGN KEY (praesenz_kurzbz)
        REFERENCES extension.tbl_lvevaluierung_reflexion_antwort_praesenz (praesenz_kurzbz)
        ON DELETE CASCADE ON UPDATE CASCADE;
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE extension.tbl_lvevaluierung_reflexion_antwort_praesenz TO vilesci;
GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE extension.tbl_lvevaluierung_reflexion_antwort_praesenz TO web;


CREATE TABLE IF NOT EXISTS extension.tbl_lvevaluierung_reflexion_antwort_nachvollziehbar(
	nachvollziehbar_kurzbz varchar(32) NOT NULL,
	bezeichnung_mehrsprachig text[]
);

COMMENT ON TABLE extension.tbl_lvevaluierung_reflexion_antwort_nachvollziehbar IS 'Reflexionsantwortmöglichkeiten zu Evaluierungen Nachvollziehbarkeit';

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_reflexion_antwort_nachvollziehbar
    ADD CONSTRAINT tbl_lvevaluierung_reflexion_antwort_nachvollziehbar_nachvollziehbar_kurzbz PRIMARY KEY (nachvollziehbar_kurzbz);
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO $$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_reflexion
    ADD CONSTRAINT fk_tbl_lvevaluierung_reflexion_nachvollziehbar FOREIGN KEY (nachvollziehbar_kurzbz)
        REFERENCES extension.tbl_lvevaluierung_reflexion_antwort_nachvollziehbar (nachvollziehbar_kurzbz)
        ON DELETE CASCADE ON UPDATE CASCADE;
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE extension.tbl_lvevaluierung_reflexion_antwort_nachvollziehbar TO vilesci;
GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE extension.tbl_lvevaluierung_reflexion_antwort_nachvollziehbar TO web;

INSERT INTO "extension".tbl_lvevaluierung_reflexion_antwort_praesenz(praesenz_kurzbz, bezeichnung_mehrsprachig) VALUES
('ja', '{Ja,Yes}'),
('nein', '{Nein,No}'),
('unknown', '{"Ich weiß nicht","I dont know"}')
ON CONFLICT (praesenz_kurzbz) DO NOTHING;

INSERT INTO "extension".tbl_lvevaluierung_reflexion_antwort_nachvollziehbar (nachvollziehbar_kurzbz, bezeichnung_mehrsprachig) VALUES
('ja', '{"Ja, überwiegend nachvollziehbar","Ja, überwiegend nachvollziehbar"}'),
('nein', '{"Nein, wenig nachvollziehbar","Nein, wenig nachvollziehbar"}'),
('unknown', '{"Kann ich nicht beurteilen (z.B. weil nicht genügend N)","Kann ich nicht beurteilen (z.B. weil nicht genügend N)"}')
ON CONFLICT (nachvollziehbar_kurzbz) DO NOTHING;

ALTER TABLE extension.tbl_lvevaluierung_reflexion
    ALTER COLUMN insertamum SET DEFAULT NOW();
