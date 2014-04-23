-- Table: fw_core_intl

CREATE TABLE fw_core_intl
(
  uuid uuid NOT NULL,
  property_name text NOT NULL,
  lang text NOT NULL,
  value text,
  CONSTRAINT "fw_core_intl_pkey" PRIMARY KEY (uuid , property_name , lang )
)
WITH (
  OIDS=FALSE
);
ALTER TABLE fw_core_intl
  OWNER TO gisuser;
COMMENT ON TABLE fw_core_intl
  IS 'Contains the "internationalized" properties of the fw_core database.';
