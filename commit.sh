#!/bin/env bash

LOG="$(pwd)/commit.log"
DATE=$(date +"%Y-%m-%d %H:%M:%S")
USER="$1"

if [[ -z "${2}" ]] || [[ -z "${3}" ]]; then
  echo "Error! Some of two path variables are not set!"
  echo "Error! Some of two path variables are not set!" >> "${LOG}"
  exit 1
fi

FOLDER="${2}${3}"
cd "${FOLDER}"
if [[ "${?}" == "0" ]]; then
  #test if we have something changed.If not, it is not necessary to make commits - exiting script
  test=$(git status --porcelain)
  if [[ "${?}" == "0" ]]; then
    if [[ -z "${test}" ]]; then
      echo "Error! There is no changes. Nothing to commit!"
      exit
    fi
  else
    echo "Error checking pending changes!"
    echo "Error checking pending changes!" >> "${LOG}"
    echo "${gitResult}" >> "${LOG}"
    exit
  fi
  #main commit function
  testResult=$(sudo nginx -t)
  if [[ "${?}" == "0" ]]; then
    sudo nginx -s reload    
    echo "${DATE} Commit to Nginx completed!" >> "${LOG}"
    echo "${testResult}" >> "${LOG}"
    echo "Commit to web server completed successfully!"
    gitResult=$(sudo git add . && sudo git commit -m "${DATE} - ${USER}")
    if [[ "${?}" == "0" ]]; then
      echo "${DATE} Commit to Git completed!" >> "${LOG}"
      echo "${gitResult}" >> "${LOG}"
      echo "Commit to Git completed successfully!"
    else
      echo "Error commiting file to Git repo!" >> "${LOG}"
      echo "${gitResult}" >> "${LOG}"
    fi
  else
    echo "Error! Nginx config test failed!" >> "${LOG}"
    echo "${testResult}" >> "${LOG}"
    echo "Error! Nginx config test failed! ${testResult}"
  fi
else
  echo "Error! Can not get into ${FOLDER} folder!" >> "${LOG}"
  echo "Error! Can not get into ${FOLDER} folder!"
fi
