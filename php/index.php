<?php // index.php v.5.4.2.1 ariokkon 2016-08-03
  $site_info_s = file_get_contents("./site_info.json");
  $site_info = json_decode($site_info_s, true);
  $server_info_s = file_get_contents("./server_info.json");
  $server_info = json_decode($server_info_s, true);
  $owner = $site_info['owner'];
  $owner_website = $owner['website'];
  $support = $site_info['support'];
  $support_contact = $support['contact'];
?>
<html>
<head>
<title><?php print $site_info['title']; ?></title>
</head>
<body>
<h1><?php print $site_info['name']; ?></h1>
<p>This is an installation of 
<a href="http://catalogue.fiware.org/enablers/poi-data-provider">
FIWARE POI Data Provider</a>. The software is available at 
<a href="https://github.com/Chiru/FIWARE-POIDataProvider">GitHub</a>. 
Follow the 
<a href="http://fiware-poidataprovider.readthedocs.io/en/latest/POI_Data_Provider__Installation_and_Administration_Guide/">
Installation Instructions</a> to install the software to your server.
</p>
<p><address>
By <?php print ($owner['name'] . ' <a href="' .
  $owner_website['url'] . '">' . $owner_website['linkname'] . '</a>'); ?>
<br/>
Contact <?php print '<a href="mailto:' . $support_contact['email'] . '">' .
  $support_contact['name'];  ?></a> for support.
</address></p>
</body>
</html>
