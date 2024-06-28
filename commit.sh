#!/bin/env bash

LOG="commit.log"
DATE=$(date +"%Y-%m-%d %H:%M:%S")
USER="$1"

cd /etc/nginx 
if [[ "${?}" == "0" ]]; then
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
  echo "Error! Can not get into /etc/nginx folder!" >> "${LOG}"
  echo "Error! Can not get into /etc/nginx folder!"
fi
