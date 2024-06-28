#!/bin/env bash

LOG="rollback.log"
DATE=$(date +"%Y-%m-%d %H:%M:%S")

cd /etc/nginx
if [[ "${?}" == "0" ]]; then
  git checkout $(git checkout | cut -d$'\t' -f 2)
  if [[ "${?}" == "0" ]]; then
    echo "Rollback done!" >> "${LOG}"
    echo "Rollback done!"
  else
    echo "Error! Can not do Git checkout!" >> "${LOG}"
    echo "Error! Can not do Git checkout!"
  fi
else
  echo "Error! Can not get into /etc/nginx folder!" >> "${LOG}"
  echo "Error! Can not get into /etc/nginx folder!"
fi
