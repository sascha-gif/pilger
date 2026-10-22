#!/usr/bin/env bash
#
# Holt neue Fassungen aus dem öffentlichen Repository und baut die Container
# nur dann neu, wenn sich wirklich etwas geändert hat. Wird vom Zeitgeber
# pilger-update.timer alle fünf Minuten aufgerufen.
#
# Der Server zieht den Code selbst — es kommt also niemand von außen herein.
# Das Repository ist öffentlich, deshalb braucht dieser Abruf keine Anmeldung.

set -euo pipefail

APP_DIR="/opt/pilger-milsh"

# Aus einer Kopie weiterlaufen: gleich folgt ein `git reset --hard`, das diese
# Datei mitten im Lauf austauschen kann — und bash liest Skripte häppchenweise
# nach, statt sie einmal komplett einzulesen. Ohne die Kopie führte eine
# Änderung an genau dieser Datei zu unvorhersehbarem Verhalten.
if [ "${PILGER_SELFCOPY:-}" != "1" ]; then
  copy="$(mktemp /tmp/pilger-update.XXXXXX)"
  cp "$0" "$copy"
  chmod +x "$copy"
  PILGER_SELFCOPY=1 exec "$copy" "$@"
fi
trap 'rm -f "$0"' EXIT

cd "$APP_DIR"

git fetch --quiet origin main

before="$(git rev-parse HEAD)"
after="$(git rev-parse origin/main)"

if [ "$before" = "$after" ]; then
  exit 0
fi

echo "Neue Fassung: ${before:0:7} → ${after:0:7}"
git reset --hard --quiet origin/main

# --build, weil die Änderungen im Image stecken; ohne Änderung am Dockerfile
# nutzt Docker seinen Zwischenspeicher und ist in Sekunden durch.
# --remove-orphans räumt Container weg, deren Dienst es nicht mehr gibt —
# sonst blockiert ein umbenannter Dienst den Namen, den der neue haben will.
# Stand und Bauzeit ins Image geben, damit die Seite selbst sagen kann,
# was gerade laeuft.
export GIT_COMMIT="$(git rev-parse --short HEAD)"
export BUILD_TIME="$(date -u +%Y-%m-%dT%H:%M:%SZ)"

# --pull: das Basis-Image frisch holen. Ohne das nimmt Docker beim Bauen die
# Fassung aus seinem Zwischenspeicher — ein gepatchtes php:8.3.x läge dann auf
# der Registry, und der Deploy würde weiter die alte Fassung ausliefern, ohne
# dass es irgendwo auffiele. Genau das war bei CVE-2026-109618/109619 die
# Gefahr. Kostet nichts: gebaut wird ohnehin nur, wenn ein neuer Stand da ist.
docker compose build --pull

if ! docker compose up -d --remove-orphans; then
  echo "Start fehlgeschlagen — Container abräumen und neu aufbauen."
  # down ohne -v: die benannten Volumes und damit alle Daten bleiben erhalten.
  docker compose down --remove-orphans
  docker compose up -d
fi

echo "Aktualisiert auf ${after:0:7}"
