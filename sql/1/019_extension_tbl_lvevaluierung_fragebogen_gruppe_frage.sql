CREATE TABLE IF NOT EXISTS extension.tbl_lvevaluierung_fragebogen_gruppe_frage(
    lvevaluierung_fragebogen_gruppe_id integer NOT NULL,
    lvevaluierung_frage_id bigint NOT NULL
);

COMMENT ON TABLE extension.tbl_lvevaluierung_fragebogen_gruppe_frage IS 'Zuordnung Fragen zu Fragebogen';

DO
$$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_fragebogen_gruppe_frage
    ADD CONSTRAINT pk_tbl_lvevaluierung_fragebogen_gruppe_frage
        PRIMARY KEY (lvevaluierung_fragebogen_gruppe_id, lvevaluierung_frage_id);
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO
$$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_fragebogen_gruppe_frage
    ADD CONSTRAINT fk_tbl_lve_fb_gruppe_frage_lvevaluierung_fragebogen_gruppe_id
        FOREIGN KEY (lvevaluierung_fragebogen_gruppe_id)
            REFERENCES extension.tbl_lvevaluierung_fragebogen_gruppe (lvevaluierung_fragebogen_gruppe_id)
            ON DELETE CASCADE ON UPDATE CASCADE;
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO
$$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_fragebogen_gruppe_frage
    ADD CONSTRAINT fk_tbl_lve_fb_gruppe_frage_lvevaluierung_frage_id
        FOREIGN KEY (lvevaluierung_frage_id)
            REFERENCES extension.tbl_lvevaluierung_fragebogen_frage (lvevaluierung_frage_id)
            ON DELETE CASCADE ON UPDATE CASCADE;
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO
$$
BEGIN
ALTER TABLE extension.tbl_lvevaluierung_fragebogen_gruppe_frage
    ADD CONSTRAINT uq_lvevaluierung_fragebogen_gruppe_id_lvevaluierung_frage_id UNIQUE (
        lvevaluierung_fragebogen_gruppe_id,
        lvevaluierung_frage_id
    );
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

GRANT SELECT, INSERT ON TABLE extension.tbl_lvevaluierung_fragebogen_gruppe_frage TO vilesci;
GRANT SELECT, INSERT ON TABLE extension.tbl_lvevaluierung_fragebogen_gruppe_frage TO web;

-- Nach Einführung neuer Zwischentabelle tbl_lvevaluierung_fragebogen_gruppe_frage:
------------------------------------------------------------------------------------------------------------------------
DO
$$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = 'extension'
          AND table_name = 'tbl_lvevaluierung_fragebogen_frage'
          AND column_name = 'lvevaluierung_fragebogen_gruppe_id'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM extension.tbl_lvevaluierung_fragebogen_gruppe_frage
    )
    THEN
        -- Übernahme der bestehenden Zuordnungen in neue Tabelle
        INSERT INTO extension.tbl_lvevaluierung_fragebogen_gruppe_frage(
            lvevaluierung_fragebogen_gruppe_id,
            lvevaluierung_frage_id
        )
        SELECT
            lvevaluierung_fragebogen_gruppe_id,
            lvevaluierung_frage_id
        FROM
            extension.tbl_lvevaluierung_fragebogen_frage
        WHERE
            lvevaluierung_fragebogen_gruppe_id IS NOT NULL
            ON CONFLICT (lvevaluierung_fragebogen_gruppe_id, lvevaluierung_frage_id) DO NOTHING;

        -- Alten Foreign key entfernen, da nicht mehr benötigt
        ALTER TABLE extension.tbl_lvevaluierung_fragebogen_frage
            DROP CONSTRAINT IF EXISTS fk_tbl_lvevaluierung_fragebogen_frage_lvevaluierung_fragebogen_gruppe_id;

        -- Alte Spalte entfernen
        ALTER TABLE extension.tbl_lvevaluierung_fragebogen_frage
            DROP COLUMN IF EXISTS lvevaluierung_fragebogen_gruppe_id;
    END IF;
END $$;
------------------------------------------------------------------------------------------------------------------------
