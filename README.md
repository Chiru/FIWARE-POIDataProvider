FIWARE-POIDataProvider
======================
[![License badge](https://img.shields.io/hexpm/l/plug.svg)](http://www.apache.org/licenses/LICENSE-2.0)
[![Documentation badge](https://readthedocs.org/projects/fiware-poidataprovider/badge/?version=latest)](http://fiware-poidataprovider.readthedocs.org/en/latest/?badge=latest)
[![Docker badge](https://img.shields.io/docker/pulls/ariokkon/fiware_poi_dataprovider.svg)](https://hub.docker.com/r/ariokkon/fiware_poi_dataprovider/)
[![Support badge]( https://img.shields.io/badge/support-sof-yellowgreen.svg)](mailto:ari.okkonen@gmail.com)

POI Data Provider is a web service for provisioning Points of Interest (POI) 
data. It is a RESTful web service implemented with PHP.

* [Introduction](#introduction)
* [GEi overall description](#gei-overall-description)
    * [POI data provider in general](#poi-data-provider-in-general)
    * [FIWARE - POI Data Provider](#fiware-poi-data-provider)
* [Build and Install](#build-and-install)
* [Running](#running)
* [API Overview](#api-overview)
* [API Walkthrough](#api-walkthrough)
* [API Reference Documentation](#api-reference-documentation)
* [Testing](#testing)
    * [End-to-end tests](#end-to-end-tests)
    * [Unit Tests](#unit-tests)
* [Advanced topics](#advanced-topics)
* [License](#license)
* [Support](#support)
* [Releases](#releases)

## <a name="introduction"></a>Introduction

This is the code repository for the FIWARE-POIDataProvider, the reference implementation of the FIWARE POI Data Provider GE.

This project is part of [FIWARE](http://www.fiware.org). Check also the [FIWARE Catalogue entry for POI Data Provider](http://catalogue.fiware.org/enablers/poi-data-provider)

Any feedback on this documentation is highly welcome, including bugs, typos
or things you think should be included but aren't. You can use [github issues](https://github.com/Chiru/FIWARE-POIDataProvider/issues) to provide feedback.

You can find the User & Programmer's Manual and the Installation & Administration Manual on [readthedocs.org](http://fiware-poidataprovider.readthedocs.org/)

## <a name="gei-overall-description"></a>GEi overall description

### <a name="poi-data-provider-in-general"></a>POI data provider in general

A POI data provider stores and provides information based on location

* Searches based on area e.g. circle or bounding box
* Filtering of results using other data values e.g. category, tags, time, ...

### <a name="fiware-poi-data-provider"></a>FIWARE - POI Data Provider

FIWARE POI (Points of interest) Generic Enabler is a web server kit that supports

* storing information related to locations
* serving queries by location and other criteria
* can be configured to meet your data needs

FIWARE POI Generic Enabler makes it relatively easy to

* Relate any information to places, e.g.
** Tourist attractions / services
** Photos, videos, 3D content
** Special location data of your business
** Imaginary items of an outdoor game
** ...

* Search information by location and other criteria
* Store information by location</li>
* Develop an application that utilizes those capabilities

*Specially* FIWARE POI Generic Enabler allows you to

* **combine** your own data with public POI data
* **speed up** mobile operation by fetching only the data your  application needs
* define and use own data structures, if need for **extra flexibility**
* **distribute** your service and data to several separately managed servers
* store texts and links in **several languages**

## <a name="build-and-install"></a>Build and Install

Build and Install documentation for POI Data Provider can be found at [the corresponding section of the Admin Manual](docs/POI_Data_Provider__Installation_and_Administration_Guide.md).

## <a name="running"></a>Running

The POI Data Provider is run as PHP scripts within a web service. It is enabled upon installation. The scripts are run upon external queries. No special action is needed for running. 

## <a name="api-overview"></a>API Overview

The POI Data Provider uses REST API. The structured POI data is presented in JSON format. E.g.:

    {
      "fw_core": {
        "categories": ["cafe"], 
        "location": {
          "wgs84" { 
            "latitude": 65.059334, 
            "longitude": 25.4664775
          }
        }, 
        "name": {
          "__": "Aulakahvila"
        }
      }
    }
    
The POIs are identified by UUIDs. E.g.:

    {
      "462c375c-3284-4d80-a8a0-6c09608623a5": {
        "fw_core": {...}
      },
      "5069d6b8-7ded-43c8-b71d-48aec9364ca9": {
        "fw_core": {...}
      },
      "96f9d67e-9177-4d89-9bbb-e13f4cec7308": {
        "fw_core": {...}
      }
    }

Sample query:

    GET http://{poi server}/radial_search?lat=65.059254&lon=25.470997&radius=250&category=cafe,restaurant&begin_time=2014-01-23T11:30&end_time=2014-01-23T13:00&min_minutes=30&max_results=2

This finds such cafes and restaurants within 250 m radius at center of Oulu, Finland that are open for at least 30 minutes within given time interval.

Available queries are

* GET /get_components *Provide the POI data components supported by this server*
* GET /poi_schema.json *Provide the full JSON schema of the data supported by this server*
* GET /poi_categories.json *Provide the POI categories supported by this server* 
Version information
* GET /radial_search *Provide the POIs within given circle*
* GET /bbox_search *Provide the POIs within given bounding box*
* GET /get_pois *Provide the pois listed by the query* 
* POST /add_poi *Add a new POI to the database*
* POST /update_poi *Update existing POI data*
* DELETE /delete_poi *Delete existing POI from database*

## <a name="api-walkthrough"></a>API Walkthrough

* [API presentation for developers](https://docs.google.com/presentation/d/1Z3i_F1BFtzqKNRsmToBwby3c0KLwZzRWusasvBu7n7A/edit#slide=id.p32)
* [Apiary](http://docs.fiwarepoi.apiary.io/#)

## <a name="api-reference-documentation"></a>API Reference Documentation

Thorough treatment of API can be found in [POI Data Provider Open API Specification](http://forge.fiware.org/plugins/mediawiki/wiki/fiware/index.php/POI_Data_Provider_Open_API_Specification).

## <a name="testing"></a>Testing

### <a name="end-to-end-tests"></a>End to End tests

You can do a quick test to see if everything is up and running by accessing the following URL:

    http://your_hostname/poi_dp/radial_search?lat=1&lon=1&category=test_poi

You should get a JSON structure representing a test POI as a response.

### <a name="unit-tests"></a>Unit Tests

No specific unit tests have been needed so far. See the _Sanity check procedures_ and the _Diagnosis Procedures_ sections of [the Admin Manual](docs/POI_Data_Provider__Installation_and_Administration_Guide.md).

Use the &lt;POI\_GIT\_root&gt;/poi\_mapper\_client manually to add, update, and delete some POIs to see it works.

## <a name="advanced-topics"></a>Advanced topics

* [Client Development](http://fiware-poidataprovider.readthedocs.org/en/latest/POI_Data_Provider__User_and_Programmers_Guide/index.html)
* [Installation and Administration](http://fiware-poidataprovider.readthedocs.org/en/latest/POI_Data_Provider__Installation_and_Administration_Guide/index.html)
* [Container-based deployment](https://hub.docker.com/r/ariokkon/fiware_poi_dataprovider/)

## <a name="license"></a>License

POI Data Provider is licensed under [Apache License v.2](http://www.apache.org/licenses/LICENSE-2.0)

## <a name="support"></a>Support

Ask your programming questions using [stackoverflow](http://stackoverflow.com/questions/tagged/fiware-poi). Please, use the tag `fiware-poi`.

## <a name="releases"></a>Releases

### 5.1 2015-12-29 Quality Boost

* Mostly error corrections
* NOTE: Two of interface changes are introduced:
  1. In internationalized texts the language key for the language-independent text is changed from "" (empty string) to "__" (two underscores). The reason is that MongoDB does not accept zero length keys. SQL script to update the database: install_scripts/update_fw_core_intl_to_5.1.sql
  2. Two resources are added to the interface to facilitate automatic configuring of POI-data editor/validator functionality in clients.
     * poi_schema.json - Full JSON schema of POI data supported by the server
     * poi_categories.json - Description of POI categories supported by the server
* Developer: Ari Okkonen / Adminotech http://www.adminotech.com/

### 4.4 2015-12-14 NGSI-10

* NGSI-10 interface included
* Errors corrected
* Developers: Ari Okkonen / Adminotech, Timo Mukari / Adminotech http://www.adminotech.com/

### 4.3 2015-06-30 Dynamic POIs: 

* Some POI data may be fetched from external servers e.g. from special 
sensor data providers or Orion Context Broker using HTTP GET or POST requests.
* Powerful pattern matching mechanism to find the wanted data from 
the response e.g. from a web page or JSON data.
* Developers: 
  * Ari Okkonen, Timo Mukari / Adminotech http://www.adminotech.com/
  * Mikko Levanto / CIE University of Oulu http://www.oulu.fi/english/

### 3.3 2015-05-22 Original release
* Developers: 
  * Ari Okkonen, Mikko Levanto, Arto Heikkinen / CIE University of Oulu http://www.oulu.fi/english/
