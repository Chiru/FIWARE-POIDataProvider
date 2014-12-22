poi_clients

This package contains three HTML pages:
    poi.xhtml      displays vehicle POIs from vehicle_poi_server
    poi_cb.xhtml   displays POIs from orion_enhanced_poi_server
    edit_poi.html  allows editing of POI data (except vehicles)

The first two of the above mentioned files contain a line like
      BACKEND_ADDRESS_POI: "../vehicle_poi_server/",
where the string is server's address. Replace the .. if necessary
to point to correct web address of the server. The last part is
the server's name. Note that there must be a / at the end.

The principal content of the page is the map where POIs are shown.
In addition there are controls for selection of POIs for display,
choosing location to show on the map, choosing display language, and
displaying additional information. These additional elements can be
hidden and restored with the angle-shaped control next to the top left
corner of the map.

POIs are organized into categories. One or more categories can be
selected from the panel on the left. Some names are preceded by a
+ sign. Clicking this + sign opens a list of subcategories. At the same
time the + sign is changed to a - sign, which can be clicked to close
the subcategory list. A simple click selects one category and its
subcategories. Multiple categories can be selected with the second
mouse button or holding control key when clicking. Holding shift key
selects a block of categories. On touch screen tapping a category
selects or deselects it. The category list can be magnified on a touch
screen with a stretch gesture (i.e., touch with two fingers moving
apart).

Display language is selected in the lower right corner. Two languages
can be selected as some information may not be available in the
preferred language.

If the device can determine its position, the Locate me button under
the map centers the map to location of the device, which is shown on
the map with a yellow marker. Alternatively, the map can be centered at
a selected place by writing an address or a pair of coordinates in the
Position field and then clicking the Go to position button. Map
position can also be adjusted with mouse (or touch) dragging. Map scale
can be adjusted with scale controls (+ and - on the map) or mouse wheel
or stretch and pinch touch gestures.

Clicking a POI on the map opens an information window for the POI.

** The above is valid for both versions. The following applies to
   edit_poi.html only.

In order to add a POI, right-click the point on the map to open a menu,
then select Add POI. A window opens where the properties of the POI can
be specified. Sections and fields can be added by clicking the big +
after the name. Existing sections and fields can be deleted by clicking
the red cross after the name. Sections can be opened for editing by
clicking the right pointing white triangle if front of the section
name. At least category, name, and location are needed. The location is
set automatically. In order to set the category, first open fw_core and
categories if they are not already open. There is already one category
proposed but you probably want something else. Click the down pointing
wedge to open a drop-down list and select the category.
Through-stricken categories are deprecated and should not be used.
If the POI needs to be in several categories, the big + sign can be
clicked and then another category selected. The name is a section of
several fields for different languages. Enter a name for at least one
of the languages or to the nameless field for a language-independent
name (or both, in which case the language-independent name is only used
when there is no translation to the desired language). Instead of the
language independent name one of the translations can be declared the
default by entering its code to the _def field. The same structure is
used for other fields that may need translations. When enough fields
are filled click the OK button at the bottom of the window. The new POI
will be visible on the map even if its category is not selected.

In order to modify a POI, first select its category to make it visible
on the map. Right-click the POI to open a menu. Select Edit this POI. A
window opens where the properties of can be examined and edited. It is
the same as described above for a new POI except that the head line is
different and fields already have values. You may change, add, and
remove values as described above. You may also adjust the position of
the POI marker: drag it with mouse to the desired position.

Note that the editing window may sometimes be hidden behind the
main window (or other windows you may have).

In order to delete a POI, right-click the POI to open a menu and select
DELETE this POI. If and only if you are sure that the POI should be
deleted for good click the OK button, otherwise click the other button.
