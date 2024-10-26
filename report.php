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
require_once($CFG->libdir . '/pdflib.php');

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
            $attempts[$result->attemptid]['firstname'] = $result->firstname;
            $attempts[$result->attemptid]['lastname'] = $result->lastname;

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

            $attempts[$result->attemptid]['path'] = $path;
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
            $questiondefinition = $quba->get_question($slot, false);
            // If we are not dealing with an essay question, we can skip this slot.
            $qtype = $questiondefinition->get_type_name();
            if ($qtype !== 'essay') {
                continue;
            }

            $qprefix = ($this->options->shortennames ? 'Q_' : 'Question_');
            $questionfolder = $qprefix . $attemptobj->get_question_number($slot) . '_-_' . $attemptobj->get_question_name($slot);
            $questionfolder = self::clean_filename($questionfolder);

            $details[$questionfolder] = [];

            // First, fetch summary for question text and response, because we can easily retrieve it now and use it
            // as a fallback.
            $details[$questionfolder]['questiontext'] = $quba->get_question_summary($slot) ?? '';
            $details[$questionfolder]['responsetext'] = $quba->get_response_summary($slot) ?? '';

            // If the user wants to use formatted text rather than the summary, fetch the true question text
            // and response now. Note that this setting will be overridden, if output is TXT instead of PDF.
            // We use format_text(), because either we currently have the summary (plain-text) or we have
            // formatted text, but it might be in MARKDOWN or other formats. We consider the text as trusted
            // (because it has been filtered before) and disable filtering. Also, we do not put <div> tags
            // around it, as that is done anyway during generation of the PDF.
            $qa = $quba->get_question_attempt($slot);
            if ($this->options->source === 'html') {
                $formattingoptions = [
                    'trusted' => true,
                    'filter' => false,
                    'para' => false,
                ];

                $responsehtml = format_text(
                    strval($qa->get_last_qt_var('answer', '')),
                    $qa->get_last_qt_var('answerformat', FORMAT_PLAIN),
                    $formattingoptions
                );

                $questionhtml = format_text(
                    $questiondefinition->questiontext,
                    $questiondefinition->questiontextformat,
                    $formattingoptions
                );

                $details[$questionfolder]['responsetext'] = $responsehtml;
                $details[$questionfolder]['questiontext'] = $questionhtml;
            }

            // Finally, fetch attachments, if there are.
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
        foreach ($this->attempts as $attemptid => $attemptdata) {
            $questions = $this->get_details_for_attempt($attemptid);

            foreach ($questions as $questionpath => $questiondetails) {
                // Depending on the user's choice, the files will either be grouped by attempt or by question.
                if ($this->options->groupby === 'byattempt') {
                    $path = $attemptdata['path'] . '/' . $questionpath;
                } else {
                    $path = $questionpath . '/' . $attemptdata['path'];
                }

                // Build the full name according to user setting.
                if ($this->options->nameordering === 'firstlast') {
                    $fullname = $attemptdata['firstname'] . ' ' . $attemptdata['lastname'];
                } else {
                    $fullname = $attemptdata['lastname'] . ' ' . $attemptdata['firstname'];
                }

                try {
                    if ($this->options->fileformat === 'pdf') {
                        $zipwriter->add_file_from_string(
                            $path . '/' . 'response.pdf',

                            $this->generate_pdf(
                                $questiondetails['responsetext'],
                                get_string('response', 'quiz_essaydownload'),
                                $fullname,
                                $fullname
                            )
                        );
                    } else {
                        $zipwriter->add_file_from_string($path . '/' . 'response.txt', $questiondetails['responsetext']);
                    }

                    $emptyarchive = false;

                    // Only include question text if instructed to do so.
                    if ($this->options->questiontext) {
                        if ($this->options->fileformat === 'pdf') {
                            $zipwriter->add_file_from_string($path . '/' . 'questiontext.pdf', $this->generate_pdf(
                                $questiondetails['questiontext'],
                                get_string('questiontext', 'question'),
                                get_string('presentedto', 'quiz_essaydownload', $fullname)
                            ));
                        } else {
                            $zipwriter->add_file_from_string($path . '/' . 'questiontext.txt', $questiondetails['questiontext']);
                        }
                    }

                    if ($this->options->attachments && !empty($questiondetails['attachments'])) {
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
    protected function notification(string $message, string $type = 'error'): void {
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

    /**
     * Generate a PDF file from a given HTML code.
     *
     * @param string $text HTML code to be typeset
     * @param string $header upper line of the header, printed in bold face
     * @param string $subheader lower line of the header
     * @param string $author author name to be stored in the document information field
     * @return string PDF code
     */
    protected function generate_pdf(string $text, string $header = '', string $subheader = '', string $author = ''): string {
        // The text might contain \xC2\xA0 for a unicode NON-BREAK SPACE character. This can confuse TCPDF, so we
        // rather remove it here.
        $text = str_replace("\xc2\xa0", "&nbsp;", $text);

        // Work around a bug with Atto, see MDL-82753 and MDL-67630.
        $text = $this->workaround_atto_font_size_issue($text);

        $doc = new pdf('P', 'mm', $this->options->pageformat);

        $doc->SetCreator('quiz_essaydownload plugin for Moodle LMS');
        $doc->SetAuthor($author);
        $doc->SetTitle('');
        $doc->SetKeywords('');
        $doc->SetSubject('');

        // The configured top margin is used for the distance between the page's top border and the start of the header.
        $doc->setHeaderMargin($this->options->margintop);

        // In order for the document's text to be reasonably separated from the header (and its rule), we add some space
        // relative to linespacing and font size.
        $doc->SetMargins(
            $this->options->marginleft,
            $this->options->margintop + $this->options->linespacing * $this->options->fontsize,
            $this->options->marginright
        );
        $doc->setPrintFooter(false);

        if ($this->options->font === 'serif') {
            $fontname = 'freeserif';
        } else if ($this->options->font === 'mono') {
            $fontname = 'freemono';
        } else {
            $fontname = 'freesans';
        }
        $doc->SetFont($fontname, '', $this->options->fontsize);
        $doc->setHeaderFont([$fontname, '', $this->options->fontsize]);

        $doc->setHeaderData('', 0, $header, $subheader);
        $doc->setFooterData();
        $doc->SetAutoPageBreak(true, $this->options->marginbottom);

        $doc->AddPage();
        $linespacebase = 1.25;
        $doc->writeHTML('<div style="line-height: ' . $this->options->linespacing * $linespacebase . ';">' . $text . '</div>');

        return $doc->Output('', 'S');
    }

    /**
     * Atto sometimes adds a <span> tag setting the font size to some rem value, e. g. 0.9375rem. This
     * will cause the text to be extremely small in the resulting PDF. We try our best to convert those
     * rem sizes into the appropriate point size, based on the general font size.
     *
     * @param string $input the HTML content
     * @return string
     */
    public function workaround_atto_font_size_issue(string $input): string {
        $pattern = '|
            (                    # capturing group #1 for the "prefix"
                <span[^>]*style  # opening a <span> tag, any stuff before the style attribute
                \s*=\s*          # equal sign may be surrounded by white space
                ([\'"])          # opening quote may be single or double, capture #2 for closing quote
                [^\2]*font-size  # arbitrary content before the font-size property
                \s*:\s*          # colon may be surrounded by white space
            )                    # end of capturing group for the "prefix"
            ([.0-9]+)            # capture the numeric value, group #3
            \s*rem               # only match for unit rem, other units do not seem to cause trouble
            (                    # capturing group #4 for the "suffix"
                [^\2]*           # any other stuff except the opening quote in the style attribute after the font-size
                \2               # the closing quote of the style attribute
                [^>]*>           # possibly other attributes and stuff plus the end of the <span> tag
            )                    # end of capturing group for the "suffix"
            |xiU';

        $res = preg_replace_callback(
            $pattern,
            function ($matches) {
                $newsize = round(floatval($matches[3]) * 100);
                return $matches[1] . $newsize . '%' . $matches[4];
            },
            $input
        );

        return $res;
    }

}
