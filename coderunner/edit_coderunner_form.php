<?php

// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the editing form for the coderunner question type.
 *
 * @package 	questionbank
 * @subpackage 	questiontypes
 * @copyright 	&copy; 2013 Richard Lobb
 * @author 		Richard Lobb richard.lobb@canterbury.ac.nz
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
define("NUM_TESTCASES_START", 5); // Num empty test cases with new questions
define("NUM_TESTCASES_ADD", 3);   // Extra empty test cases to add

/**
 * coderunner editing form definition.
 */
class qtype_coderunner_edit_form extends question_edit_form {

    /**testcode
     * Add question-type specific form fields.
     *
     * @param MoodleQuickForm $mform the form being built.
     */
    var $_textarea_or_htmleditor_generalfb;   //addElement type for general feedback
    var $_editor_options_generalfb;           //in dependence of editor type set a different array for its options

    function qtype() {
        return 'coderunner';
    }


    protected function definition() {
        // Override to add my coderunner_type selector at the top.
        // Need to support both JavaScript enabled and JavaScript disabled
        // sites, so there's a normal select item, containing all languages
        // and question types, for a default and a YUI3 hierarchical menu
        // that the JavaScript switches on if it's enabled.
        global $PAGE;
        $jsmodule = array(
            'name'      => 'qtype_coderunner',
            'fullpath'  => '/question/type/coderunner/module.js',
            'requires'  => array('base', 'widget', 'io', 'node-menunav')
        );

        $mform = $this->_form;
        $question = $this->question;

        list($languages, $types) = $this->get_languages_and_types();

        $mform->addElement('header', 'questiontypeheader', get_string('type_header','qtype_coderunner'));

        $expandedTypes = array_merge(array('Undefined' => 'Undefined'), $types);
        $mform->addElement('select', 'coderunner_type', get_string('coderunner_type', 'qtype_coderunner'), $expandedTypes);

        // Fancy YUI type menu disabled for now as it looked ugly. To re-enable
        // uncomment the following statement and add the line 'this.useYuiTypesMenu(Y);'
        // to the init() function in module.js.
        // $mform->addElement('html', $this->makeYuiMenu(array_keys($languages), array_keys($types)));

        $mform->addRule('coderunner_type', 'You must select the question type', 'required');
        $mform->addHelpButton('coderunner_type', 'coderunner_type', 'qtype_coderunner');

        $mform->addElement('advcheckbox', 'customise', get_string('customise', 'qtype_coderunner'));
        $mform->addHelpButton('customise', 'customise', 'qtype_coderunner');

        // Template is hidden by default in css but displayed by JavaScript if 'customise' checked
        $mform->addElement('textarea', 'custom_template', get_string("template", "qtype_coderunner"),
                '" rows="8" cols="80" class="template edit_code"');

        $mform->addElement('advcheckbox', 'all_or_nothing', get_string('all_or_nothing', 'qtype_coderunner'));
        $mform->addElement('advcheckbox', 'show_source', get_string('show_source', 'qtype_coderunner'));

        $mform->setDefault('all_or_nothing', True);
        $mform->setDefault('show_source', False);
        $mform->addHelpButton('all_or_nothing', 'all_or_nothing', 'qtype_coderunner');
        $mform->addHelpButton('show_source', 'show_source', 'qtype_coderunner');

        $PAGE->requires->js_init_call('M.qtype_coderunner.setupAllTAs',  array(), false, $jsmodule);
        $PAGE->requires->js_init_call('M.qtype_coderunner.initEditForm', array(), false, $jsmodule);
        parent::definition($mform);
    }


    public function definition_inner($mform) {

        // TODO: what was the purpose of the next 2 lines?
        //$mform->addElement('static', 'answersinstruct');
        //$mform->closeHeaderBefore('answersinstruct');

        $gradeoptions = array(); // Unused
        if (isset($this->question->testcases)) {
            $numTestcases = count($this->question->testcases) + NUM_TESTCASES_ADD;
        }
        else {
            $numTestcases = NUM_TESTCASES_START;
        }

        $markDefaults = array();
        for ($i = 0; $i < $numTestcases; $i++) {
            $markDefaults[] = '1.0';
            $mform->disabledIf("mark[$i]", 'all_or_nothing', 'checked');
        }

        // Confusion alert! A call to $mform->setDefault("mark[$i]", '1.0') looks
        // plausible and works to set the empty-form default, but it then
        // overrides (rather than is overridden by) the actual value.
        // I don't understand this (but see 'Evil hack alert' in the baseclass).
        $mform->setDefault('mark', $markDefaults);

        $this->add_per_answer_fields($mform, get_string('testcase', 'qtype_coderunner'),
                $gradeoptions, $numTestcases);
        $this->add_interactive_settings();
    }


    /*
     *  Overridden so each 'answer' is a test case containing a ProgramCode testcode to be evaluated
     *  and a ProgramCode expected output value.
     */
    public function get_per_answer_fields($mform, $label, $gradeoptions,
            &$repeatedoptions, &$answersoption) {
        $repeated = array();
        $repeated[] = & $mform->createElement('header', 'answerhdr', $label);
        $repeated[] = & $mform->createElement('textarea', 'testcode',
                get_string('testcode', 'qtype_coderunner'),
                array('cols' => 80, 'rows' => 4, 'class' => 'testcaseexpression edit_code'));
        $repeated[] = & $mform->createElement('textarea', 'stdin',
                get_string('stdin', 'qtype_coderunner'),
                array('cols' => 80, 'rows' => 4, 'class' => 'testcasestdin edit_code'));
        $repeated[] = & $mform->createElement('textarea', 'output',
                get_string('output', 'qtype_coderunner'),
                array('cols' => 80, 'rows' => 4, 'class' => 'testcaseresult edit_code'));
        $repeated[] = & $mform->createElement('checkbox', 'useasexample', get_string('useasexample', 'qtype_coderunner'), false);
        $repeated[] = & $mform->createElement('text', 'mark', get_string('mark', 'qtype_coderunner'),
                array('size' => 5));
        $options = array();
        foreach ($this->displayOptions() as $opt) {
            $options[$opt] = get_string($opt, 'qtype_coderunner');
        }
        $repeated[] = & $mform->createElement('select', 'display', get_string('display', 'qtype_coderunner'), $options);
        $repeated[] = & $mform->createElement('checkbox', 'hiderestiffail', get_string('hiderestiffail', 'qtype_coderunner'), false);
        $repeatedoptions['output']['type'] = PARAM_RAW;

        // Lastly, a bit of hacking to keep add_per_answer_fields function happy
        if (isset($this->question->options)) {
            $this->question->options->answers = array();
        }
        $answersoption = 'answers';

        return $repeated;
    }



    // A list of the allowed values of the DB 'display' field for each testcase.
    protected function displayOptions() {
        return array('SHOW', 'HIDE', 'HIDE_IF_FAIL', 'HIDE_IF_SUCCEED');
    }


    public function data_preprocessing($question) {
        // Although it's not wildly obvious from the documentation, this method
        // needs to set up fields of the current question whose names match those
        // specified in get_per_answer_fields. These are used to load the
        // data into the form.
        if (isset($question->testcases)) { // Reloading a saved question?
            $question->testcode = array();
            $question->output = array();
            $question->useasexample = array();
            $question->display = array();
            $question->hiderestifail = array();
            foreach ($question->testcases as $tc) {
                $question->testcode[] = $tc->testcode;
                $question->stdin[] = $tc->stdin;
                $question->output[] = $tc->output;
                $question->useasexample[] = $tc->useasexample;
                $question->display[] = $tc->display;
                $question->hiderestiffail[] = $tc->hiderestiffail;
                $question->mark[] = sprintf("%.3f", $tc->mark);
            }
        }
        return $question;
    }


    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if ($data['coderunner_type'] == 'Undefined') {
            $errors['coderunner_type'] = get_string('questiontype_required', 'qtype_coderunner');
        }
        $testcodes = $data['testcode'];
        $stdins = $data['stdin'];
        $outputs = $data['output'];
        $marks = $data['mark'];
        $count = 0;
        $cntNonemptyTests = 0;
        $num = max(count($testcodes), count($stdins), count($outputs));
        for ($i = 0; $i < $num; $i++) {
            $testcode = trim($testcodes[$i]);
            if ($testcode != '') {
                $cntNonemptyTests++;
            }
            $stdin = trim($stdins[$i]);
            $output = trim($outputs[$i]);
            if ($testcode !== '' || $stdin != '' || $output !== '') {
                $count++;
            }
            $mark = trim($marks[$i]);
            if ($mark != '') {
                if (!is_numeric($mark)) {
                    $errors["mark[$i]"] = get_string('nonnumericmark', 'qtype_coderunner');
                }
                else if (floatval($mark) <= 0) {
                    $errors["mark[$i]"] = get_string('negativeorzeromark', 'qtype_coderunner');
                }
            }
        }

        if ($count == 0) {
            $errors["testcode[0]"] = get_string('atleastonetest', 'qtype_coderunner');
        }
        else if ($cntNonemptyTests != 0 && $cntNonemptyTests != $count) {
            $errors["testcode[0]"] = get_string('allornothing', 'qtype_coderunner');
        }
        return $errors;
    }



    private function get_languages_and_types() {
        // Return two arrays (language => language) and (type => subtype) of
        // all the non-custom coderunner question types in the DB,
        // where the subtype is the suffix of the type in the database,
        // e.g. for java_method it is 'method'. For question types without a
        // subtype the word 'Default' is used.
        global $DB;
        $records = $DB->get_records('quest_coderunner_types', array(),
                'coderunner_type', 'coderunner_type');
        $types = array();
        foreach ($records as $row) {
            if (($pos = strpos($row->coderunner_type, '_')) !== FALSE) {
                $subtype = substr($row->coderunner_type, $pos + 1);
                $language = substr($row->coderunner_type, 0, $pos);
            }
            else {
                $subtype = 'Default';
                $language = $row->coderunner_type;
            }
            $types[$row->coderunner_type] = $row->coderunner_type;
            $languages[$language] = ucwords($language);
        }
        asort($types);
        asort($languages);
        return array($languages, $types);
    }

    // Construct the HTML for a YUI3 hierarchical menu of languages/types.
    // Initially hidden and turned on by JavaScript. See module.js.
    private function makeYuiMenu($languages, $types) {
        $s = '<div id="question_types" class="yui3-menu" style="display:none"><div class="yui3-menu-content"><ul>';
        $s .= '<li class="yui3-menuitem"><a class="yui3-menu-label" href="#question-types">Choose type...</a>';
        $s .= '<div id="languages" class="yui3-menu"><div class="yui3-menu-content"><ul>';
        foreach ($languages as $lang) {
            $subtypes = array();
            foreach ($types as $type) {
                if (strpos($type, $lang) === 0) {
                    if ($type != $lang) {
                        $subtypes[$type] = substr($type, strlen($lang) + 1);
                    }
                }
            }

            $s .= '<li class="yui3-menuitem">';
            if (count($subtypes) == 0) {
                $s .= "<a class=\"yui3-menuitem-content\" href=\"#$lang\">$lang</a>";
            } else {
                $s .= '<a class="yui3-menu-label" href="#' . $lang . '">' . $lang . '</a>';
                $s .= "<div id=\"$lang\" class=\"yui3-menu\"><div class=\"yui3-menu-content\"><ul>";
                foreach ($subtypes as $type=>$subtype) {
                    $s .= "<li class=\"yui3-menuitem\"><a class=\"yui3-menuitem-content\" href=\"#$type\">$subtype</a></li>";
                }
                $s .= '</ul></div></div>';
            }
            $s .= '</li>';
        }
        $s .= '</ul></div></div>';
        $s .= '</ul></div></div>';
        return $s;

    }

}
