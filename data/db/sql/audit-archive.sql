-- Convert audit table to table engine 'archive'
ALTER TABLE audit DROP INDEX uuid;
ALTER TABLE audit ENGINE = ARCHIVE;
