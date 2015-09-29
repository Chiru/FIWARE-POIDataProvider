Representation Format
=====================

The *POI Data Provider RESTFul API* supports JSON data format, where the
data is structured as key-value pairs.

POI data is modular consisting components, which are represented as JSON
objects. The key of each JSON object identifies the type of POI data
component it represents.

The JSON schema and an example JSON structure of the POI data provided
by the API is shown below. The root level JSON object is named “pois”,
which contains all the POIs corresponding to the query. Each POI is
represented as a JSON object, where the UUID of the POI is the key of
the object. Each POI object contains an individual set of data
components.

**JSON schema of query response main structure**

| `` {``
| ``   ``\ “``title``”\ ``: ``\ “``POIS`` ``Query`` ``Response``”\ ``,``
| ``   ``\ “``description``”\ ``: ``\ “``Generic`` ``POIS``
  ``response.``”\ ``,``
| ``   ``\ “``type``”\ ``: ``\ “``object``”\ ``,``
| ``   ``\ “``properties``”\ ``: {``
| ``     "``\ **``pois``**\ ``": {``
| ``       ``\ “``description``”\ ``: ``\ “``Contains`` ``one``
  ``object`` ``per`` ``a`` ``point`` ``of`` ``interest.`` ``The``
  ``key`` ``of`` ``an`` ``object`` ``is`` ``the`` ``UUID`` ``of``
  ``the`` ``POI.``”\ ``,``
| ``       ``\ “``type``”\ ``: ``\ “``object``”\ ``,``
| ``       ``\ “``additionalProperties``”\ ``: {``
| ``         ``\ “``title``”\ ``: ``\ “``POI`` ``data,`` ``key`` ``is``
  ``the`` **``UUID`` ``of`` ``the`` ``POI``**”
| ``         ``\ “``description``”\ ``: ``\ “``The`` ``POI`` ``data``
  ``consists`` ``of`` ``data`` ``components`` ``that`` ``are``
  ``identified`` ``by`` ``their`` ``keys.``”\ ``,``
| ``         ``\ “``type``”\ ``: ``\ “``object``”\ ``,``
| ``         ``\ “``additionalProperties``”\ ``: {``
| ``           ``\ “``title``”\ ``: ``\ “``POI`` ``data`` ``component,``
  ``key`` ``defines`` ``the`` ``structure``”
| ``         }``
| ``       }``
| ``     }``
| ``   }``
| `` }``

**Example of query response main structure** - details hidden

| `` {``
| ``   ``\ “``pois``”\ ``: {``
| ``     ``\ “``8e57d2e6-f98f-4404-b075-112049e72346``”\ ``: {``
| ``       ``\ “``fw_core``”\ ``: {``
| ``         ``\ “``category``”\ ``: ``\ “``library``”\ ``,``
| ``         ``\ “``location``”\ ``: {``
| ``           ``\ “``wgs84``”\ ``: {``
| ``             ``\ “``latitude``”\ ``: 65.0612507,``
| ``             ``\ “``longitude``”\ ``: 25.4667681``
| ``           }``
| ``         },``
| ``         ``\ “``name``”\ ``: {``
| ``           "":``\ “``Tiedekirjasto`` ``Pegasus``”
| ``         },``
| ``         /* ``\ *``more`` ``core`` ``data``*\ `` */``
| ``       }``
| ``     },``
| ``     ``\ “``30ddf703-59f5-4448-8918-0f625a7e1122``”\ ``: {``
| ``       /* ``\ *``POI`` ``data``*\ `` */``
| ``     }``
| ``   }``
| `` }``