<?php

# Määritellään skriptissä käytettävät vakiot.
define("URL_BASE", "https://sasky.inschool.fi/browsecourses/");
define("URL_COURSES", URL_BASE . "index_json");
define("URL_COURSEDATA", URL_BASE . "popup");

/**
 * Noutaa yksittäisen kurssin tiedot Wilman avoimesta rajapinnasta.
 *
 * @author Pekka Tapio Aalto <pekka.aalto@sasky.fi>
 *
 * @param  string $shcoolid Oppilaitoksen tunniste.
 * @param  string $term     Haetaanko lyhyt- vai pitkäkestoista kurssia (short/long).
 * @param  string $id       Kurssin tunniste.
 *
 * @return array            Kurssin tiedot assosiatiivisessa taulukossa.
 *
 * @see https://help.inschool.fi/old/index86bf.html?q=node/14496 Rajapinnan dokumentaatio.
 */
function getCourseData($schoolid, $term, $id) {

  # Muodostetaan URL-osoite annetuista tiedoista. Luotava osoite noudattaa seuraavaa mallia:
  #   https://sasky.inschool.fi/browsecourses/popup?long-term=4006&school-id=18&format=json
  #   https://sasky.inschool.fi/browsecourses/popup?short-term=4006&school-id=18&format=json
  $url = URL_COURSEDATA . "?$term-term=$id&school-id=$schoolid&format=json";

  # Ladataan kurssin tarkemmat tiedot.
  $coursedata = file_get_contents($url);

  # Palautetaan haetut tiedot assosiatiivisena taulukkona.
  return json_decode($coursedata, true);

}

/**
 * Noutaa kurssien tiedot Wilman avoimesta rajapinnasta.
 *
 * Haetaan ensin kaikki haettavissa olevat kurssit rajapinnan kautta ja
 * käydään ne oppilaitoksittain lävitse. Jokaisesta kurssista noudetaan
 * tarkemmat tiedot, joista koostetaan oppilaitoksen alle tarjolla olevan
 * kurssin tiedokokonaisuus.
 *
 * Koostetaulukko on rakenteeltaan seuraavanlainen:
 *
 *   [
 *     { "Id": "1",
 *       "Caption": "Oppilaitoksen nimi",
 *       "Courses": [
 *         { "Id": "3200",
 *           "PaymentMethod": "0",
 *           "Koulutus": "Prosessiteollisuuden perustutkinto",
 *           "Oppilaitos": "Oppilaitoksen nimi",
 *           "Paikkakunta": "Hämeenkyrö",
 *           "Tutkintotyyppi": "Ammatillinen perustutkinto",
 *           "Koulutusala": "Tekniikan alat",
 *           ...
 *         },
 *         { "Id": "3195",
 *           ...
 *         }
 *       ]
 *     },
 *     { "Id": "2",
 *       ...
 *     }
 *   ]
 *
 * @author Pekka Tapio Aalto <pekka.aalto@sasky.fi>
 *
 * @return array Kurssien tiedot assosiatiivisessa taulukossa.
 */
function getCourses() {

  # Ladataan kurssien tiedot JSON-muodossa.
  $url_content = file_get_contents(URL_COURSES);

  # Puretaan JSON-rakenne assosiatiiviseksi taulukoksi.
  $data = json_decode($url_content, true);

  # Alustetaan tulostaulukko.
  $result = array();

  # Käydään lävitse vuorotellen jokainen tulosjoukon oppilaitos.
  foreach ($data["Schools"] as $school) {

    # Alustetaan taulukko, johon kerätään tarjolla olevat oppilaitoksen koulutukset.
    $item_courses = array();

    # Käydään lävitse jokainen oppilaitoksen kategoria.
    foreach ($school["Categories"] as $category) {

      # Käydään lävitse kategorian alla olevat kurssit ja kerätään ne taulukkoon.
      foreach ($category["Courses"] as $course) {

        # Alustetaan kurssitiedot sisältävä taulukko.
        $item_course = array();

        # Lisätään yleiset kurssitiedot.
        $item_course["Id"] = $course["Id"];
        $item_course["PaymentMethod"] = $course["PaymentMethod"];
        If (isset($coursedata["ClosedMsg"])) {
          $item_course["ClosedMsg"] = $course["ClosedMsg"];
        }

        # Haetaan kurssin tarkemmat tiedot.
        $coursedata = getCourseData($school["Id"],$course["Type"],$course["Id"]);

        # Koostetaan kurssin tiedot tietueiden otsikoista ja sisällöstä.
        foreach ($coursedata["Courses"][0] as $cellindex => $cellvalue) {

          # Lisätään tietue kurssin tietoihin.
          $item_course[$cellindex] = $cellvalue;

        }

        # Lisätään kurssi taulukon jatkoksi.
        array_push($item_courses, $item_course);

      }

    }

    # Koostetaan rivi, joka sisältää oppilaitoksen tiedot sekä sen tarjoamat kurssit.
    $item = array("Id" => $school["Id"],
                  "Caption" => $school["Caption"],
                  "Courses" => $item_courses);

    # Lisätään oppitoksen tiedot tulosjoukon jatkoksi
    array_push($result, $item);

  }

  # Palautetaan muodostettu koostetaulukko.
  return $result;

}

/**
 * Noutaa kurssien tiedot Wilman avoimesta rajapinnasta ja muodostaa kurssien
 * tiedoista tekstikoosteen.
 *
 * Hakee kurssien tiedot avoimesta rajapinnasta getCourses-funktion avulla
 * ja koostaa kurssitiedoista yhtenäisen tekstikentän ja siitä lasketun
 * MD5-hajautustunnisteen. Koostetusta tekstikentästä voidaan tehdä haku
 * hakusanalla.
 *
 * Koostetaulukko on rakenteeltaan seuraavanlainen:
 *
 *   [
 *     { "Id": "1",
 *       "Caption": "Oppilaitoksen nimi",
 *       "Courses": [
 *         { "Id": "3200",
 *           "PaymentMethod": "0",
 *           "Koulutus": "Prosessiteollisuuden perustutkinto",
 *           "Oppilaitos": "Oppilaitoksen nimi",
 *           "Paikkakunta": "Hämeenkyrö",
 *           "Tutkintotyyppi": "Ammatillinen perustutkinto",
 *           "Koulutusala": "Tekniikan alat",
 *           ...
 *           "text": "3200\n0\nProsessiteollisuuden perustutkinto\n...",
 *           "hash": "e18bb67a87e764930d70a81b1e4fd87f"
 *         },
 *         { "Id": "3195",
 *           ...
 *         }
 *       ]
 *     },
 *     { "Id": "2",
 *       ...
 *     }
 *   ]
 *
 * @author Pekka Tapio Aalto <pekka.aalto@sasky.fi>
 *
 * @return array Kurssien tiedot ja koosteteksti assosiatiivisessa taulukossa.
 */
function getCoursesWithSearchData() {

  # Noudetaan kaikki kurssien tiedot taulukkona käsittelyn pohjaksi.
  $array = getCourses();

  # Käydään lävitse yksitellen tulosjoukon jokainen oppilaitos.
  foreach ($array as $schoolindex => $schooldata) {

    # Käydään lävitse yksitellen oppilaitoksen jokainen kurssi.
    foreach ($schooldata["Courses"] as $courseindex => $coursedata) {

      # Alustetaan muuttuja, johon koostetaan teksti, josta haku suoritetaan.
      $text = "";

      # Käydään kurssin tiedot yksitellen lävitse.
      foreach ($coursedata as $key => $value) {

        # Lisätään kentän arvo koostemuuttujan loppuun rivinvaihdon kanssa.
        $text = $text . "$value\n";

      }

      # Lasketaan koosteesta MD5-hajautustunniste.
      $hash = md5($text);

      # Lisätään kooste ja hajautustunniste kurssin tietoihin.
      $array[$schoolindex]["Courses"][$courseindex]["text"] = $text;
      $array[$schoolindex]["Courses"][$courseindex]["hash"] = $hash;

    }

  }

  # Palautetaan käsitelty taulukko.
  return $array;

}

?>
