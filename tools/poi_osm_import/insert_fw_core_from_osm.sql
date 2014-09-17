INSERT INTO fw_core(
            uuid, osm_id, timestamp, category, location, source_name, source_website, source_licence)
    SELECT uuid_generate_v4(), osm_id, 1410777181, amenity, Geography(ST_Transform(way,4326)), 'OpenStreetMap', 'http://www.openstreetmap.org', 'http://www.openstreetmap.org/copyright'
    FROM bayern_osm_point
    WHERE amenity is not NULL and name is not NULL and length(name) < 65;

INSERT INTO fw_core_intl(
            uuid, property_name, lang, value)
    SELECT fw_core.uuid, 'name', '', bayern_osm_point.name
    FROM fw_core INNER JOIN bayern_osm_point
    ON fw_core.osm_id=bayern_osm_point.osm_id;
    
INSERT INTO fw_core_intl(
            uuid, property_name, lang, value)
    SELECT fw_core.uuid, 'name', 'en', bayern_osm_point."name:en"
    FROM fw_core INNER JOIN bayern_osm_point
    ON fw_core.osm_id=bayern_osm_point.osm_id
    WHERE "name:en" is not NULL;
    
INSERT INTO fw_core_intl(
            uuid, property_name, lang, value)
    SELECT fw_core.uuid, 'name', 'de', bayern_osm_point."name:de"
    FROM fw_core INNER JOIN bayern_osm_point
    ON fw_core.osm_id=bayern_osm_point.osm_id
    WHERE "name:de" is not NULL;
    
INSERT INTO fw_core_intl(
            uuid, property_name, lang, value)
    SELECT fw_core.uuid, 'name', 'fi', bayern_osm_point."name:fi"
    FROM fw_core INNER JOIN bayern_osm_point
    ON fw_core.osm_id=bayern_osm_point.osm_id
    WHERE "name:fi" is not NULL;
    
INSERT INTO fw_core_intl(
            uuid, property_name, lang, value)
    SELECT fw_core.uuid, 'name', 'es', bayern_osm_point."name:es"
    FROM fw_core INNER JOIN bayern_osm_point
    ON fw_core.osm_id=bayern_osm_point.osm_id
    WHERE "name:es" is not NULL;
    
INSERT INTO fw_core_intl(
            uuid, property_name, lang, value)
    SELECT fw_core.uuid, 'description', '', bayern_osm_point."description"
    FROM fw_core INNER JOIN bayern_osm_point
    ON fw_core.osm_id=bayern_osm_point.osm_id
    WHERE "description" is not NULL;
    
INSERT INTO fw_core_intl(
            uuid, property_name, lang, value)
    SELECT fw_core.uuid, 'description', 'en', bayern_osm_point."description:en"
    FROM fw_core INNER JOIN bayern_osm_point
    ON fw_core.osm_id=bayern_osm_point.osm_id
    WHERE "description:en" is not NULL;
    
INSERT INTO fw_core_intl(
            uuid, property_name, lang, value)
    SELECT fw_core.uuid, 'description', 'de', bayern_osm_point."description:de"
    FROM fw_core INNER JOIN bayern_osm_point
    ON fw_core.osm_id=bayern_osm_point.osm_id
    WHERE "description:de" is not NULL;
    
INSERT INTO fw_core_intl(
            uuid, property_name, lang, value)
    SELECT fw_core.uuid, 'description', 'fi', bayern_osm_point."description:fi"
    FROM fw_core INNER JOIN bayern_osm_point
    ON fw_core.osm_id=bayern_osm_point.osm_id
    WHERE "description:fi" is not NULL;
    
INSERT INTO fw_core_intl(
            uuid, property_name, lang, value)
    SELECT fw_core.uuid, 'description', 'es', bayern_osm_point."description:es"
    FROM fw_core INNER JOIN bayern_osm_point
    ON fw_core.osm_id=bayern_osm_point.osm_id
    WHERE "description:es" is not NULL;
    
INSERT INTO fw_core_intl(
            uuid, property_name, lang, value)
    SELECT fw_core.uuid, 'url', '', bayern_osm_point.website
    FROM fw_core INNER JOIN bayern_osm_point
    ON fw_core.osm_id=bayern_osm_point.osm_id
    WHERE website is not NULL;

INSERT INTO fw_core_intl(
            uuid, property_name, lang, value)
    SELECT fw_core.uuid, 'url', '', bayern_osm_point.url as website
    FROM fw_core INNER JOIN bayern_osm_point
    ON fw_core.osm_id=bayern_osm_point.osm_id
    WHERE website is not NULL;
    