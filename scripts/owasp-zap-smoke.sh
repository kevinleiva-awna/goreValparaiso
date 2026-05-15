#!/usr/bin/env bash
#
# Smoke test OWASP ZAP baseline contra el portal publico GORE Valparaiso.
#
# Requisitos: Docker corriendo localmente.
# Uso:
#   BASE_URL=http://host.docker.internal:8000 ./scripts/owasp-zap-smoke.sh
#
# Salida:
#   docs/etapa-5/zap-baseline-report.html
#   docs/etapa-5/zap-baseline-report.json
#
# Exit codes:
#   0  - sin vulnerabilidades High
#   2+ - hay High/Med/Info segun zap-baseline.py
#
# Falla el pipeline si hay vulnerabilidades High. Las Medium se reportan pero
# no bloquean el flujo en esta iteracion.

set -euo pipefail

BASE_URL="${BASE_URL:-http://host.docker.internal:8000}"
OUTPUT_DIR="$(pwd)/docs/etapa-5"
mkdir -p "$OUTPUT_DIR"

echo "==> Ejecutando ZAP baseline contra $BASE_URL"
echo "==> Reportes en: $OUTPUT_DIR"

docker run --rm \
    -v "$OUTPUT_DIR:/zap/wrk/:rw" \
    --user "$(id -u):$(id -g)" \
    -t ghcr.io/zaproxy/zaproxy:stable zap-baseline.py \
    -t "$BASE_URL" \
    -r zap-baseline-report.html \
    -J zap-baseline-report.json \
    -I  # ignorar warnings, solo fallar en High

echo "==> Reporte HTML: $OUTPUT_DIR/zap-baseline-report.html"
echo "==> Reporte JSON: $OUTPUT_DIR/zap-baseline-report.json"
