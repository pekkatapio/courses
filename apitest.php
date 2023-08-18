<?php

# Tuodaan kurssitietojen hakufunktiot.
require_once("courses.php");

# Haetaan kurssitiedot ja tulostetaan ne ulos JSON-muodossa.
# Huomaa, että PHP-koodissa käsittely kannattaa tehdä assosiatiivisena taulukkona.
echo json_encode(getCourses(), JSON_UNESCAPED_UNICODE);

?>
