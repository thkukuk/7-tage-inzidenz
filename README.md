# 7-Tage-Inzidenz Anzeige mit PHP als Container

7-Tage-Inzidenz Anzeige für Deutschland, dessen Bundesländer sowie Landkreise
und Städte mit Daten des RKI. Die Idee und Codeteile basieren auf dem Artikel
[Corona-Ampel](https://ct.de/yw1c) in c’t 9/2021 ab Seite 160.


![Screenshot](Screenshot.png)


Die Beispiele sind alle mit `podman`, was aber 1:1 durch `docker` ersetzt werden kann.

## Container bauen

Um den Container selber zu bauen muss dieses Projekt ausgecheckt werden und
dann in dem Verzeichnis folgender Befehl ausgeführt werden:

```
# podman build -t 7-tage-inzidenz .
```

## Container ausführen

```
# podman run -d --rm --name 7-tage-inzidenz -p 80:80  thkukuk/7-tage-inzidenz:latest
```

Der Container legt einen Cache an. Dieser geht verloren wenn man den Container neu startet. Um nur die aktuellen Tages-Zahlen anzuzeigen ist das in Ordnung, aber für mehrtägige Anzeigen mit verlauf sollten die Daten persistent gespeichert werden:

```
podman run -d --rm -v /srv/7-tage-inzidenz/data:/data --name 7-tage-inzidenz -p 80:80  7-tage-inzidenz:latest
```

Um sich die Seite anzeigen zu lassen, gehe auf `http://localhost`.

## Environment Variablen

Die Ausgabe des Containers ist konfigurierbar:

* **REGIONS** - Komma separatierte Liste von Regionen, die angezeigt werden soll. Die `AdmUnitID` wird dafür verwendet und kann für die Bundesländer, Landkreise und Städte [hier](https://www.arcgis.com/apps/mapviewer/index.html?layers=c093fe0ef8fd4707beeb3dc0c02c3381) gefunden werden. Ein Beispiel für die Anzeige von Deutschland, Bayern und München wäre `REGIONS=0,9,9162`, der Default ist `0`.
* **PAST_DAYS** - Anzahl der vergangenen Tage, die zusätzlich zum Tagesaktuellen Ergebnis angezeigt wird. Der Default is `7`
* **TZ** - Die Zeitzone, unter der der Container laufen soll. Default ist `Europe/Berlin`.
* **DEBUG**=[0|1] - Zeigt an was das entrypoint Skript gerade ausführt. Default ist `0`.
