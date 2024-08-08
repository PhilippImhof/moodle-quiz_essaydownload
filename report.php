<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * This file defines the quiz_essaydownload report class.
 *
 * @package   quiz_essaydownload
 * @copyright 2024 Philipp E. Imhof
 * @author    Philipp E. Imhof
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_files\archive_writer;
use core\dml\sql_join;

defined('MOODLE_INTERNAL') || die();

// This work-around is required until Moodle 4.2 is the lowest version we support.
if (class_exists('\mod_quiz\local\reports\attempts_report')) {
    class_alias('\mod_quiz\local\reports\attempts_report', '\quiz_essaydownload_report_parent_alias');
    class_alias('\mod_quiz\quiz_attempt', '\quiz_essaydownload_quiz_attempt_alias');
} else {
    require_once($CFG->dirroot . '/mod/quiz/report/default.php');
    require_once($CFG->dirroot . '/mod/quiz/report/attemptsreport.php');
    require_once($CFG->dirroot . '/mod/quiz/attemptlib.php');
    class_alias('\quiz_attempts_report', '\quiz_essaydownload_report_parent_alias');
    class_alias('\quiz_attempt', '\quiz_essaydownload_quiz_attempt_alias');
}

require_once($CFG->dirroot . '/mod/quiz/report/essaydownload/essaydownload_form.php');
require_once($CFG->dirroot . '/mod/quiz/report/essaydownload/essaydownload_options.php');

/**
 * Quiz report subclass for the quiz_essaydownload report.
 *
 * This report allows you to download text responses and file attachments submitted
 * by students as a response to quiz essay questions.
 *
 * @package   quiz_essaydownload
 * @copyright 2024 Philipp E. Imhof
 * @author    Philipp E. Imhof
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_essaydownload_report extends quiz_essaydownload_report_parent_alias {

    /** @var object course object */
    protected object $course;

    /** @var object course module object */
    protected object $cm;

    /** @var object quiz object */
    protected object $quiz;

    /** @var quiz_essaydownload_options options for the report */
    protected quiz_essaydownload_options $options;

    /** @var array attempt and user data */
    protected array $attempts;

    /** @var int id of the currently selected group */
    protected int $currentgroup;

    /**
     * Override the parent function, because we have some custom stuff to initialise.
     *
     * @param string $mode
     * @param string $formclass
     * @param stdClass $quiz
     * @param stdClass $cm
     * @param stdClass $course
     * @return array with four elements:
     *      0 => integer the current group id (0 for none).
     *      1 => \core\dml\sql_join Contains joins, wheres, params for all the students in this course.
     *      2 => \core\dml\sql_join Contains joins, wheres, params for all the students in the current group.
     *      3 => \core\dml\sql_join Contains joins, wheres, params for all the students to show in the report.
     *              Will be the same as either element 1 or 2.
     */
    public function init($mode, $formclass, $quiz, $cm, $course): array {
        global $DB;

        // First, we call the parent init function...
        list($currentgroup, $allstudentjoins, $groupstudentjoins, $allowedjoins) =
            parent::init($mode, $formclass, $quiz, $cm, $course);

        $this->options = new quiz_essaydownload_options('essaydownload', $quiz, $cm, $course);
        $this->options->states = [\quiz_essaydownload_quiz_attempt_alias::FINISHED];

        if ($fromform = $this->form->get_data()) {
            $this->options->process_settings_from_form($fromform);
        } else {
            $this->options->process_settings_from_params();
        }

        $this->form->set_data($this->options->get_initial_form_data());

        $this->course = $course;
        $this->cm = $cm;
        $this->quiz = $quiz;
        $this->currentgroup = $currentgroup;

        $this->hasgroupstudents = false;
        if (!empty($groupstudentjoins->joins)) {
            $sql = "SELECT DISTINCT u.id
                               FROM {user} u
                                    {$groupstudentjoins->joins}
                              WHERE {$groupstudentjoins->wheres}";
            $this->hasgroupstudents = $DB->record_exists_sql($sql, $groupstudentjoins->params);
        }

        $this->attempts = $this->get_attempts_and_names($groupstudentjoins);

        return [$currentgroup, $allstudentjoins, $groupstudentjoins, $allowedjoins];
    }

    /**
     * Display the form or, if the "Download" button has been pressed, invoke
     * preparation and shipping of the ZIP archive.
     *
     * @param stdClass $quiz this quiz.
     * @param stdClass $cm the course-module for this quiz.
     * @param stdClass $course the coures we are in.
     */
    public function display($quiz, $cm, $course) {
        $this->init('essaydownload', 'quiz_essaydownload_form', $quiz, $cm, $course);

        // If no download has been requested yet, we only display the form.
        $fromform = $this->form->get_data();
        if (!isset($fromform->download)) {
            $this->display_form();
            return true;
        }

        // Before proceeding to the download, make sure the user has the necessary permissions.
        // If they don't, an exception will be thrown at this point.
        $this->context = context_module::instance($this->cm->id);
        require_capability('mod/quiz:grade', $this->context);

        // The function will not return.
        $this->process_and_download();
    }

    /**
     * Display the settings form with the download button. May display an error notification, e. g.
     * if there are no attempts or if we already know that there are no essay questions.
     *
     * @return void
     */
    protected function display_form(): void {
        if (!$this->quiz_has_essay_questions()) {
            $this->notification(get_string('noessayquestion', 'quiz_essaydownload'));
            return;
        }

        // If $hasgroupstudents is false, the header would automatically include a
        // notification, so we pretend to have group students and show our notification instead.
        if (empty($this->attempts)) {
            if (!$this->hasgroupstudents) {
                $this->hasgroupstudents = true;
            }
            $this->notification(get_string('nothingtodownload', 'quiz_essaydownload'));
            return;
        }

        // Printing the standard header. We'll set $hasquestions and $hasstudents to true here,
        // because otherwise the header will include a notification by itself.
        $this->print_standard_header_and_messages(
            $this->cm,
            $this->course,
            $this->quiz,
            $this->options,
            $this->currentgroup,
            true,
            true
        );
        $this->form->display();
    }

    /**
     * Check whether the quiz contains at least one essay question. If the quiz contains 'random' questions,
     * they might become essay questions in at least some attempts, so we will count those questions towards
     * the essay questions, even if we are not sure.
     *
     * @return bool
     */
    public function quiz_has_essay_questions(): bool {
        // We only want real questions, no descriptions. If there are no questions, we can leave early.
        $questions = quiz_report_get_significant_questions($this->quiz);
        if (empty($questions)) {
            return false;
        }

        foreach ($questions as $question) {
            // If we find an essay or random question, we leave early.
            if (in_array($question->qtype, ['essay', 'random'])) {
                return true;
            }
        }

        // Still here? Then there are no essay questions.
        return false;
    }

    /**
     * Fetch the relevant attempts as well as the name (firstname, lastname) of the user they belong to.
     *
     * @param sql_join $joins joins, wheres, params to select the relevant subset of attemps (all or selected group)
     * @return array array with entries of the form attemptid => path name
     */
    public function get_attempts_and_names(sql_join $joins): array {
        global $DB;

        // If there are no WHERE clauses (i. e. because no group has been selected), we add a dummy
        // clause to simplify the syntax of the query.
        if (empty($joins->wheres)) {
            $joins->wheres = '1 = 1';
        }

        $sql = "SELECT DISTINCT a.id attemptid, a.timefinish, u.firstname, u.lastname
                           FROM {quiz_attempts} a
                      LEFT JOIN {user} u ON a.userid = u.id
                                $joins->joins
                          WHERE a.quiz = :quizid
                                AND a.preview = 0
                                AND a.state = 'finished'
                                AND $joins->wheres
                       ORDER BY attemptid";

        $results = $DB->get_records_sql($sql, ['quizid' => $this->quiz->id] + $joins->params);

        $attempts = [];
        foreach ($results as $result) {
            // If the user has requested short filenames, we limit the last and first name to 40
            // characters each.
            if ($this->options->shortennames) {
                $result->lastname = substr($result->lastname, 0, 40);
                $result->firstname = substr($result->firstname, 0, 40);
            }

            // The user can choose whether to start with the first name or the last name.
            if ($this->options->nameordering === 'firstlast') {
                $name = $result->firstname . '_' . $result->lastname;
            } else {
                $name = $result->lastname . '_' . $result->firstname;
            }

            // Build the path for this attempt: <name>_<attemptid>_<date/time finished>.
            $path = $name . '_' . $result->attemptid;
            $path = $path . '_' .  date('Ymd_His', $result->timefinish);
            $path = self::clean_filename($path);

            $attempts[$result->attemptid] = $path;
        }

        return $attempts;
    }

    /**
     * Fetch the relevant question data for the given attempt, i. e. the question summary, the
     * response summary and references to uploaded attachment files, if there are.
     *
     * @param int $attemptid attempt id
     * @return array top-level index of the array will be a unique label for every question containing
     *               the question number and the question title; every entry will then have the keys
     *               'questiontext', 'responsetext' and 'attachments' which contain the plain-text summary
     *               of the question text, the student's response and a possibly empty array with the
     *               uploaded attachments as stored_file objects
     */
    public function get_details_for_attempt(int $attemptid): array {
        $details = [];

        $attemptobj = quiz_create_attempt_handling_errors($attemptid, $this->cm->id);
        $quba = question_engine::load_questions_usage_by_activity($attemptobj->get_uniqueid());

        $slots = $attemptobj->get_slots();
        foreach ($slots as $slot) {
            // If we are not dealing with an essay question, we can skip this slot.
            $qtype = $quba->get_question($slot, false)->get_type_name();
            if ($qtype !== 'essay') {
                continue;
            }

            $questionfolder = 'Question_' . $attemptobj->get_question_number($slot) . '_-_' . $attemptobj->get_question_name($slot);
            $questionfolder = self::clean_filename($questionfolder);

            $details[$questionfolder] = [];
            $details[$questionfolder]['questiontext'] = $quba->get_question_summary($slot) ?? '';
            $details[$questionfolder]['responsetext'] = $quba->get_response_summary($slot) ?? '';

            $qa = $quba->get_question_attempt($slot);
            $details[$questionfolder]['attachments'] = $qa->get_last_qt_files('attachments', $quba->get_owning_context()->id);
        }
        return $details;
    }

    /**
     * Prepare a ZIP file containing the requested data and initiate the download.
     * user and initiate the download.
     *
     * @return void
     */
    protected function process_and_download(): void {
        $quizname = $this->cm->name;
        // If the user requests shorter file names, we will make sure the quiz' name is not more than
        // 15 characters.
        if ($this->options->shortennames) {
            $quizname = substr($quizname, 0, 15);
        }
        // The archive's name will be <short name of course> - <quiz name> - <cmid for the quiz>.zip.
        // This makes sure that the name will be unique per quiz, even if two quizzes have the same
        // title. Also, we will replace spaces by underscores.
        $filename = $this->course->shortname . ' - ' . $quizname . ' - ' . $this->cm->id . '.zip';
        $filename = self::clean_filename($filename);

        // The ZIP will be created on the fly via the stream writer.
        $zipwriter = archive_writer::get_stream_writer($filename, archive_writer::ZIP_WRITER);

        // In the end, we want to know whether the archive is empty or not.
        $emptyarchive = true;

        // Counter in case of errors.
        $errors = 0;

        // Iterate over every attempt and every question.
        foreach ($this->attempts as $attemptid => $attemptpath) {
            $questions = $this->get_details_for_attempt($attemptid);

            foreach ($questions as $questionpath => $questiondetails) {
                // Depending on the user's choice, the files will either be grouped by attempt or by question.
                if ($this->options->groupby === 'byattempt') {
                    $path = $attemptpath . '/' . $questionpath;
                } else {
                    $path = $questionpath . '/' . $attemptpath;
                }

                try {
                    if ($this->options->questiontext) {
                        $zipwriter->add_file_from_string($path . '/' . 'questiontext.txt', $questiondetails['questiontext']);
                        $emptyarchive = false;
                    }

                    if ($this->options->responsetext) {
                        $zipwriter->add_file_from_string($path . '/' . 'response.txt', $questiondetails['responsetext']);
                        $emptyarchive = false;
                    }

                    if (!empty($questiondetails['attachments']) && $this->options->attachments) {
                        $emptyarchive = false;
                        foreach ($questiondetails['attachments'] as $file) {
                            $zipwriter->add_file_from_stored_file($path . '/attachments/' . $file->get_filename(), $file);
                        }
                    }
                } catch (Throwable $e) {
                    $emptyarchive = false;
                    $errors++;
                    $message = get_string('errormessage', 'quiz_essaydownload');
                    $message .= "\n\n" . $e->getMessage();
                    $message .= "\n\n" . $e->getTraceAsString();
                    $zipwriter->add_file_from_string(get_string('errorfilename', 'quiz_essaydownload', $errors), $message);
                }
            }
        }

        // If we have not added any files to the archive, it is better to output a notification than
        // to send the user an empty file.
        if ($emptyarchive) {
            $this->notification(get_string('nothingtodownload', 'quiz_essaydownload'));
        } else {
            $zipwriter->finish();
            exit();
        }
    }

    /**
     * Output a notification, e. g. when a quiz does not contain any essay questions. This is a shorthand,
     * because we always want to show the standard headers before the notification.
     *
     * @param string $message the notification to be displayed
     * @param string $type the notification type, e. g. 'error' or 'info' or 'warn'
     * @return void
     */
    protected function notification(string $message, string $type = 'error') {
        global $OUTPUT;

        // Printing the standard header. We'll set $hasquestions and $hasstudents to true here,
        // because otherwise the header will include a notification by itself.
        $this->print_standard_header_and_messages(
            $this->cm,
            $this->course,
            $this->quiz,
            $this->options,
            $this->currentgroup,
            true,
            true
        );

        echo $OUTPUT->notification($message, $type);
    }

    /**
     * Clean file or path names by applying the corresponding Moodle function and, additionally,
     * replacing spaces by underscores.
     *
     * @param string $filename the file or pathname to be cleaned
     * @return string
     */
    protected static function clean_filename(string $filename): string {
        return clean_filename(str_replace(' ', '_', $filename));
    }

}
