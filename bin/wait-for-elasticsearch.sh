#!/usr/bin/env bash

set -euo pipefail

host="$1"
url="${host}/_cat/health?h=status"
maxAttempt=100
sleep=1

function try {
    echo "$(curl --fail --silent --connect-timeout 1 --max-time 1 ${url} | grep -o "[a-z]*" || true)"
}

attempt=0
health="$(try)"
until [[ "${health}" = 'green' || "${health}" = 'yellow' || ${attempt} -ge ${maxAttempt} ]]; do
    health="$(try)"
    printf '.'
    sleep ${sleep}
    ((attempt+=1))
done

echo

if [[ ${attempt} -ge ${maxAttempt} ]]; then
    exit 1
fi

exit 0
