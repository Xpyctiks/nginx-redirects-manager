#!/bin/env bash

LOG="$(pwd)/rollback.log"
DATE=$(date +"%Y-%m-%d %H:%M:%S")

if [[ -z "${1}" ]] || [[ -z "${2}" ]]; then
  echo "Error! Some of two path variables are not set!"
  echo "Error! Some of two path variables are not set!" >> "${LOG}"
  exit 1
fi

FOLDER="${1}${2}"
cd "${FOLDER}"
if [[ "${?}" == "0" ]]; then
  echo "$(pwd)" >> "${LOG}"
  git checkout $(git checkout | grep "M" | cut -d$'\t' -f 2)
  if [[ "${?}" == "0" ]]; then
    echo "Rollback done!" >> "${LOG}"
    echo "Rollback done!"
  else
    echo "Error! Can not do Git checkout!" >> "${LOG}"
    echo "Error! Can not do Git checkout!"
  fi
else
  echo "Error! Can not get into ${FOLDER} folder!" >> "${LOG}"
  echo "Error! Can not get into ${FOLDER} folder!"
fi
