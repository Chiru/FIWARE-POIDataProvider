INSERT INTO fw_core_intl(
            uuid, property_name, lang, value)
    SELECT fw_core.uuid, 'name', '__', areaname_osm_point.name
    FROM fw_core INNER JOIN areaname_osm_point
    ON fw_core.osm_id=areaname_osm_point.osm_id
    WHERE   NOT EXISTS 
        (   SELECT  1
            FROM    fw_core_intl 
            WHERE   uuid = fw_core.uuid
            AND     property_name = 'name'
            AND     lang = '__'
        );

INSERT INTO fw_core_intl(
            uuid, property_name, lang, value)
    SELECT fw_core.uuid, 'name', 'en', areaname_osm_point."name:en"
    FROM fw_core INNER JOIN areaname_osm_point
    ON fw_core.osm_id=areaname_osm_point.osm_id
    WHERE "name:en" is not NULL
    AND NOT EXISTS 
        (   SELECT  1
            FROM    fw_core_intl 
            WHERE   uuid = fw_core.uuid 
            AND     property_name = 'name'
            AND     lang = 'en'
        );

INSERT INTO fw_core_intl(
            uuid, property_name, lang, value)
    SELECT fw_core.uuid, 'name', 'de', areaname_osm_point."name:de"
    FROM fw_core INNER JOIN areaname_osm_point
    ON fw_core.osm_id=areaname_osm_point.osm_id
    WHERE "name:de" is not NULL
    AND NOT EXISTS 
        (   SELECT  1
            FROM    fw_core_intl 
            WHERE   uuid = fw_core.uuid 
            AND     property_name = 'name'
            AND     lang = 'de'
        );

INSERT INTO fw_core_intl(
            uuid, property_name, lang, value)
    SELECT fw_core.uuid, 'name', 'fi', areaname_osm_point."name:fi"
    FROM fw_core INNER JOIN areaname_osm_point
    ON fw_core.osm_id=areaname_osm_point.osm_id
    WHERE "name:fi" is not NULL
    AND NOT EXISTS 
        (   SELECT  1
            FROM    fw_core_intl 
            WHERE   uuid = fw_core.uuid 
            AND     property_name = 'name'
            AND     lang = 'fi'
        );

INSERT INTO fw_core_intl(
            uuid, property_name, lang, value)
    SELECT fw_core.uuid, 'name', 'es', areaname_osm_point."name:es"
    FROM fw_core INNER JOIN areaname_osm_point
    ON fw_core.osm_id=areaname_osm_point.osm_id
    WHERE "name:es" is not NULL
    AND NOT EXISTS 
        (   SELECT  1
            FROM    fw_core_intl 
            WHERE   uuid = fw_core.uuid 
            AND     property_name = 'name'
            AND     lang = 'es'
        );

INSERT INTO fw_core_intl(
            uuid, property_name, lang, value)
    SELECT fw_core.uuid, 'description', '__', areaname_osm_point."description"
    FROM fw_core INNER JOIN areaname_osm_point
    ON fw_core.osm_id=areaname_osm_point.osm_id
    WHERE "description" is not NULL
    AND NOT EXISTS 
        (   SELECT  1
            FROM    fw_core_intl 
            WHERE   uuid = fw_core.uuid 
            AND     property_name = 'description'
            AND     lang = '__'
        );

INSERT INTO fw_core_intl(
            uuid, property_name, lang, value)
    SELECT fw_core.uuid, 'description', 'en', areaname_osm_point."description:en"
    FROM fw_core INNER JOIN areaname_osm_point
    ON fw_core.osm_id=areaname_osm_point.osm_id
    WHERE "description:en" is not NULL
    AND NOT EXISTS 
        (   SELECT  1
            FROM    fw_core_intl 
            WHERE   uuid = fw_core.uuid 
            AND     property_name = 'description'
            AND     lang = 'en'
        );

INSERT INTO fw_core_intl(
            uuid, property_name, lang, value)
    SELECT fw_core.uuid, 'description', 'de', areaname_osm_point."description:de"
    FROM fw_core INNER JOIN areaname_osm_point
    ON fw_core.osm_id=areaname_osm_point.osm_id
    WHERE "description:de" is not NULL
    AND NOT EXISTS 
        (   SELECT  1
            FROM    fw_core_intl 
            WHERE   uuid = fw_core.uuid 
            AND     property_name = 'description'
            AND     lang = 'de'
        );

INSERT INTO fw_core_intl(
            uuid, property_name, lang, value)
    SELECT fw_core.uuid, 'description', 'fi', areaname_osm_point."description:fi"
    FROM fw_core INNER JOIN areaname_osm_point
    ON fw_core.osm_id=areaname_osm_point.osm_id
    WHERE "description:fi" is not NULL
    AND NOT EXISTS 
        (   SELECT  1
            FROM    fw_core_intl 
            WHERE   uuid = fw_core.uuid 
            AND     property_name = 'description'
            AND     lang = 'fi'
        );

INSERT INTO fw_core_intl(
            uuid, property_name, lang, value)
    SELECT fw_core.uuid, 'description', 'es', areaname_osm_point."description:es"
    FROM fw_core INNER JOIN areaname_osm_point
    ON fw_core.osm_id=areaname_osm_point.osm_id
    WHERE "description:es" is not NULL
    AND NOT EXISTS 
        (   SELECT  1
            FROM    fw_core_intl 
            WHERE   uuid = fw_core.uuid 
            AND     property_name = 'description'
            AND     lang = 'es'
        );

INSERT INTO fw_core_intl(
            uuid, property_name, lang, value)
    SELECT fw_core.uuid, 'url', '__', areaname_osm_point.website
    FROM fw_core INNER JOIN areaname_osm_point
    ON fw_core.osm_id=areaname_osm_point.osm_id
    WHERE website is not NULL
    AND NOT EXISTS 
        (   SELECT  1
            FROM    fw_core_intl 
            WHERE   uuid = fw_core.uuid 
            AND     property_name = 'url'
            AND     lang = '__'
        );

INSERT INTO fw_core_intl(
            uuid, property_name, lang, value)
    SELECT fw_core.uuid, 'url', '__', areaname_osm_point.url as website
    FROM fw_core INNER JOIN areaname_osm_point
    ON fw_core.osm_id=areaname_osm_point.osm_id
    WHERE website is not NULL
    AND NOT EXISTS 
        (   SELECT  1
            FROM    fw_core_intl 
            WHERE   uuid = fw_core.uuid 
            AND     property_name = 'url'
            AND     lang = '__'
        );
