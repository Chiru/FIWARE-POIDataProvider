INSERT INTO fw_core (uuid, categories, location, timestamp) 
    VALUES('ae01d34a-d0c1-4134-9107-71814b4805af', '{"test_poi"}', ST_GeogFromText('POINT(1.0 1.0)'), 1390985438);

INSERT INTO fw_core_intl (uuid, property_name, lang, value)
    VALUES('ae01d34a-d0c1-4134-9107-71814b4805af', 'name', '__', 'Test POI 1');
    
INSERT INTO fw_core (uuid, categories, location, timestamp) 
    VALUES('8e57d2e6-f98f-4404-b075-112049e72346', '{"test_poi"}', ST_GeogFromText('POINT(2.0 2.0)'), 1390985438);    
    
INSERT INTO fw_core_intl (uuid, property_name, lang, value)
    VALUES('8e57d2e6-f98f-4404-b075-112049e72346', 'name', '__', 'Test POI 2');
    
INSERT INTO fw_core (uuid, categories, location, timestamp) 
    VALUES('12ebff8a-d36d-4eba-8841-d5176bbba707', '{"test_poi"}', ST_GeogFromText('POINT(3.0 3.0)'), 1390985438);
    
INSERT INTO fw_core_intl (uuid, property_name, lang, value)
    VALUES('12ebff8a-d36d-4eba-8841-d5176bbba707', 'name', '__', 'Test POI 3');
    
INSERT INTO fw_core (uuid, categories, location, timestamp) 
    VALUES('45fe3060-6d47-46a8-9184-d2b93b9f2268', '{"test_poi"}', ST_GeogFromText('POINT(-1.0 -1.0)'), 1390985438);
    
INSERT INTO fw_core_intl (uuid, property_name, lang, value)
    VALUES('45fe3060-6d47-46a8-9184-d2b93b9f2268', 'name', 'de', 'Multilingual test POI');
    
INSERT INTO fw_core_intl (uuid, property_name, lang, value)
    VALUES('45fe3060-6d47-46a8-9184-d2b93b9f2268', 'name', 'fi', 'Monikielinen testi POI');

INSERT INTO fw_core_intl (uuid, property_name, lang, value)
    VALUES('45fe3060-6d47-46a8-9184-d2b93b9f2268', 'description', 'en', 'Test POI containing attributes in multiple languages');
    
INSERT INTO fw_core_intl (uuid, property_name, lang, value)
    VALUES('45fe3060-6d47-46a8-9184-d2b93b9f2268', 'description', 'fi', 'Testi POI, joka sisältää monikielisiä attribuutteja');