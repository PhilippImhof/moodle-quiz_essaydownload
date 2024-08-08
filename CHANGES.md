# Changelog

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
