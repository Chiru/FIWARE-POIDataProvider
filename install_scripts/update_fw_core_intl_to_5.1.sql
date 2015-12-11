-- Table: fw_core_intl
-- This converts the empty langugage key for non-language-specific text to "__"
-- as required by R5.1
UPDATE fw_core_intl
SET lang='__'
WHERE lang='';
