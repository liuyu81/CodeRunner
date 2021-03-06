<?php
/** The base class for the coderunner Validator classes.
 *  A Validator is called after running all testcases in a sandbox
 *  to confirm the correctness of the results.
 *  In the simplest subclass, BasicValidator, results are correct if
 *  the exactly equal the expected results after trailing white space
 *  has been removed. More complicated subclasses can, for example, do
 *  things like regular expression testing.
 */

/**
 * @package    qtype
 * @subpackage coderunner
 * @copyright  Richard Lobb, 2012, The University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/question/type/coderunner/testingoutcome.php');

abstract class Validator {
    /** Check all outputs, returning an array of TestResult objects.
     * A TestResult is an object with expected, got and isCorrect fields.
     * 'got' and 'expected' fields are sanitised by replacing embedded
     * control characters with hex equivalents and by limiting their
     * lengths to MAX_STRING_LENGTH.
     */

    const MAX_STRING_LENGTH = 8000;

    /** Called to validate the output generated by a student's code for
     *  a given testcase. Returns a single TestResult object.
     */
    abstract function validate($output, $testCase);

    protected function clean($s) {
        // A copy of $s with trailing lines removed and trailing white space
        // from each line removed. Used e.g. by BasicValidator subclass.
        $bits = explode("\n", $s);
        while (count($bits) > 0 && trim($bits[count($bits)-1]) == '') {
            array_pop($bits);
        }
        $new_s = '';
        foreach ($bits as $bit) {
            while (strlen($bit) > 0 && $bit[strlen($bit) - 1] == ' ') {
                $bit = substr($bit, 0, strlen($bit) - 1);
            }
            $new_s .= $bit . "\n";
        }

        return $new_s;
    }



    protected function sanitise($progOutput) {
        // Return given $progOutput (e.g. from a C program), sanitised by replacing
        // all non-printable standard ascii chars except newline with hex
        // equivalents.
        $s = '';
        for ($i = 0, $len = strlen($progOutput); $i < $len; $i++) {
            $c = $progOutput[$i];
            if (($c < " " && $c != "\n") || $c > "\x7E") {
                $c = '\\x' . sprintf("%02x", ord($c));
            }
            $s .= $c;
        }
        return $s;
    }


    protected function snip($s) {
        // Limit the length of the given string to MAX_STRING_LENGTH by
        // removing the centre of the string, inserting the substring
        // [... snip ... ] in its place
        $snipInsert = ' ...snip... ';
        $len = strlen($s);
        if ($len > Validator::MAX_STRING_LENGTH) {
            $lenToRemove = $len - Validator::MAX_STRING_LENGTH + strlen($snipInsert);
            $partLength = ($len - $lenToRemove) / 2;
            $firstBit = substr($s, 0, $partLength);
            $lastBit = substr($s, $len - $partLength, $partLength);
            $s = $firstBit . $snipInsert . $lastBit;
        }
        return $s;
    }
}