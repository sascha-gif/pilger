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
if ! docker compose up -d --build --remove-orphans; then
  echo "Neustart mit belegten Namen fehlgeschlagen — Container abräumen und neu aufbauen."
  # down ohne -v: die benannten Volumes und damit alle Daten bleiben erhalten.
  docker compose down --remove-orphans
  docker compose up -d --build
fi

echo "Aktualisiert auf ${after:0:7}"
