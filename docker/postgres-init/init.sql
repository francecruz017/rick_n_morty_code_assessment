DO $$ 
BEGIN
  CREATE ROLE rick WITH LOGIN PASSWORD 'p1ckL3r1Ck!' SUPERUSER;
EXCEPTION WHEN duplicate_object THEN
  RAISE NOTICE 'Role rick already exists, skipping.';
END $$;

-- This MUST be outside DO $$ ... END $$ because CREATE DATABASE isn't allowed in a transaction
CREATE DATABASE pickle_jar OWNER rick;