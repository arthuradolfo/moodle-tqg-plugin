# Moodle TQG Plugin
Plugin to generate adaptative questionnaires in moodle

## Prerequisites
In order to run Moodle TQG Plugin, you need to have installed Docker and Docker Compose.

## How ro run

- Inside project folder, run ``docker-compose up`` and wait until the containers are running
- Run ``docker cp blocks/tqg_plugin moodle-tqg-plugin:/opt/bitnami/moodle/blocks``
- Run ``docker cp mod/tqg moodle-tqg-plugin:/opt/bitnami/moodle/mod``
- You will be able to access Moodle using ``localhost:8081``
- You can add the TQG block to your course and create TQG activities
