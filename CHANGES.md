# Changelog

### 1.2.0 (2024-10-07)

- assure compatibility with freshly released Moodle 4.5 LTS
- improvement: add possibility to export formatted responses to PDF
- internal: added tests
- internal: remove temporary CI change after bug in moodle-plugin-ci was fixed

### 1.1.0 (2024-08-23)

- improvement: add setting to choose between ordering by first/last or last/first name
- improvement: add setting to use shorter names and thus avoid problem with Windows' unzipper
- internal: temporary change CI to work around problem with moodle-plugin-ci

### 1.0.2 (2024-08-08)

- bugfix: avoid corruption of ZIP file in case of errors
- improvement: in case of an error, include detailed message in ZIP file
- internal: add test with empty response to essay question

### 1.0.1 (2024-04-26)

- bugfix: download button was not working properly
- testing: add behat test for download button
- internal: simplified CI

### 1.0.0 (2024-04-19)

Initial release, inspired by the moodle-quiz_downloadsubmissions plugin. Main features:

- compatible with all current versions of Moodle, i. e. 4.1 and higher
- support for course groups
- support for attachments
