# Botswana map data

District geometry: geoBoundaries gbOpen BWA ADM1, boundary ID BWA-ADM1-57670837, representing 2017. Original source OpenStreetMap and Wambacher. Simplified geometry from commit 9469f09, reprojected to local SVG coordinates with rounded precision. Licensed Creative Commons Attribution ShareAlike 2.0. Derived district paths retain that licence.

https://www.geoboundaries.org/api/current/gbOpen/BWA/ADM1/
https://creativecommons.org/licenses/by-sa/2.0/

Settlement coordinates and names: GeoNames Botswana country dump, downloaded 18 November 2026, Creative Commons Attribution 4.0. Populated places are locally matched against district geometry; duplicate names within a district retain the most populous entry. Coordinates represent settlements, not applicant addresses.

https://download.geonames.org/export/dump/BW.zip
https://download.geonames.org/export/dump/readme.txt
https://creativecommons.org/licenses/by/4.0/

Rebuild using tools/build_botswana_map.py. Unknown or ambiguous applicant locations must remain unmapped; no live geocoding service receives application data. The district layer is a reporting reference, not a claim about current council jurisdictions or surveyed boundaries.
