-- Table: fw_core

CREATE TABLE fw_core
(
  uuid uuid NOT NULL,
  category character varying(64),
  location geography,
  geometry geometry,
  osm_id integer, -- OpenStreetMap ID (for POIs imported from OSM)
  thumbnail text,
  "timestamp" bigint,
  userid uuid,
  source_name text,
  source_website text,
  source_licence text,
  source_id text,
  CONSTRAINT "pkey" PRIMARY KEY (uuid)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE fw_core
  OWNER TO gisuser;
COMMENT ON TABLE fw_core
  IS 'Table containing the core POI data';
COMMENT ON COLUMN fw_core.osm_id IS 'OpenStreetMap ID (for POIs imported from OSM)';


-- Index: fw_core_geog_gix

CREATE INDEX fw_core_geog_gix
  ON fw_core
  USING gist
  (location );

