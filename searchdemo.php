<?php

# Tuodaan kurssitietojen hakufunktiot.
require_once("courses.php");

# Tarkistetaan, onko kutsun yhteydessä määritelty hakusana.
if (isset($_GET["hakusana"])) {
  $hakusana = $_GET["hakusana"];
} else {
  $hakusana = "";
}

?>

<h1>Kurssihaku-demo</h1>

<p>Tämä skripti demoaa, miten getCoursesWithSearchData-funktion palauttamista
   tiedoista voidaan tehdä haku hakusanalla. Syötä hakusana alla olevaan kenttään,
   paina HAE ja odota maltilla, että taustaskripti saa tiedot käsiteltyä.</p>

<p>Huomaa, että getCourses ja getCoursesWithSearchData-funktiot ovat suunniteltu
   suoritettavan tausta-ajona, ei välitöntä vastausta vaativissa tapauksissa.</p>

<form method='GET'>
  Hakusana: <input type='text' name='hakusana' value='<?php echo $hakusana?>'>
  <input type='submit' value='HAE'>
</form>

<?php

# Tehdään haku, jos hakusana on määritelty
if (strlen($hakusana) > 0) {

  # Haetaan kurssien tiedot koostetiedoilla täydennettyinä.
  $data = getCoursesWithSearchData();

  # Alustetaan tulostaulukko.
  $results = array();

  # Läpikäydään kurssitaulukko oppilaitoksittain.
  foreach($data as $school) {

    # Läpikäydään oppilaitoksen kurssit.
    foreach($school["Courses"] as $course) {

      # Tarkistetaan löytyykö hakusana tekstistä. Tarkistus tehdään niin, että
      # isoilla ja pienillä kirjaimilla ei ole merkitystä.
      $pos = stripos($course["text"], $hakusana);
      if ($pos !== false) {

        # Hakusana löytyi kurssin tiedoista, lisätään kurssi tulosjoukkoon.
        $match = array("SchoolId" => $school["Id"],
                       "SchoolName" => $school["Caption"],
                       "CourseId" => $course["Id"],
                       "Course" => $course["Koulutus"]);
        array_push($results, $match);

      }

    }

  }

  # Haku on valmis, tulostetaan haun tulokset taulukkona.
  if (count($results) > 0) {

    echo "<h2>Haun tulokset</h2>\n";
    echo "<p>Hakusanalla $hakusana löytyi " . count($results) . " tulosta.</p>\n";
    echo "<table>\n";
    echo "  <tr>\n";
    echo "    <th>SchoolId</th>\n";
    echo "    <th>SchoolName</th>\n";
    echo "    <th>CourseId</th>\n";
    echo "    <th>Course</th>\n";
    echo "  </tr>\n";
    foreach ($results as $result) {
      echo "  <tr>\n";
      echo "    <td>" . $result["SchoolId"] . "</td>\n";
      echo "    <td>" . $result["SchoolName"] . "</td>\n";
      echo "    <td>" . $result["CourseId"] . "</td>\n";
      echo "    <td>" . $result["Course"] . "</td>\n";
      echo "  </tr>\n";
    }
    echo "</table>\n";

  }

}
?>
