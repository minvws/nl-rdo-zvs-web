DO $$
    BEGIN
        -- Controleer en hernoem de eerste index
        IF EXISTS (SELECT 1 FROM pg_class WHERE relname = 'case_requests_number_key' AND relkind = 'i') THEN
            EXECUTE 'ALTER INDEX case_requests_number_key RENAME TO petition_number_key';
            RAISE NOTICE 'Index "case_requests_number_key" is hernoemd naar "petition_number_key".';
        ELSE
            RAISE NOTICE 'Index "case_requests_number_key" bestaat niet. Overslaan.';
        END IF;

        -- Controleer en hernoem de tweede index
        IF EXISTS (SELECT 1 FROM pg_class WHERE relname = 'case_requests_pkey' AND relkind = 'i') THEN
            EXECUTE 'ALTER INDEX case_requests_pkey RENAME TO petition_pkey';
            RAISE NOTICE 'Index "case_requests_pkey" is hernoemd naar "petition_pkey".';
        ELSE
            RAISE NOTICE 'Index "case_requests_pkey" bestaat niet. Overslaan.';
        END IF;

    END $$;