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

/**
 * Unit tests for the coderunner question definition class.
 *
 * @package    qtype
 * @subpackage coderunner
 * @copyright  2011 Richard Lobb, University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/coderunner/question.php');
require_once($CFG->dirroot . '/local/Twig/Autoloader.php');


/**
 * Unit tests for the coderunner question definition class.
 */
class qtype_coderunner_python_question_test extends basic_testcase {
    protected function setUp() {
        $this->qtype = new qtype_coderunner_question();
        $this->goodcode = "def sqr(n): return n * n";
    }


    protected function tearDown() {
        $this->qtype = null;
    }


    public function test_get_question_summary() {
        $q = test_question_maker::make_question('coderunner', 'sqr');
        $this->assertEquals('Write a function sqr(n) that returns n squared',
                $q->get_question_summary());
    }


    public function test_summarise_response() {
        $s = $this->goodcode;
        $q = test_question_maker::make_question('coderunner', 'sqr');
        $this->assertEquals($s,
               $q->summarise_response(array('answer' => $s)));
    }


    public function test_grade_response_right() {
        $q = test_question_maker::make_question('coderunner', 'sqr');
        $response = array('answer' => $this->goodcode);
        list($mark, $grade, $cache) = $q->grade_response($response);
        $this->assertEquals($mark, 1);
        $this->assertEquals($grade, question_state::$gradedright);
        $this->assertTrue(isset($cache['_testoutcome']));
        $testOutcome = unserialize($cache['_testoutcome']);
        $this->assertFalse($testOutcome->hasSyntaxError());
        foreach ($testOutcome->testResults as $tr) {
            $this->assertTrue($tr->isCorrect);
        }
    }


    public function test_grade_response_wrong_ans() {
        $q = test_question_maker::make_question('coderunner', 'sqr');
        $code = "def sqr(x): return x * x * x / abs(x)";
        $response = array('answer' => $code);
        list($mark, $grade, $cache) = $q->grade_response($response);
        $this->assertEquals($mark, 0);
        $this->assertEquals($grade, question_state::$gradedwrong);
        $this->assertTrue(isset($cache['_testoutcome']));
    }


    public function test_grade_syntax_error() {
        $q = test_question_maker::make_question('coderunner', 'sqr');
        $code = "def sqr(x): return x  x";
        $response = array('answer' => $code);
        list($mark, $grade, $cache) =  $q->grade_response($response);
        $this->assertEquals($mark, 0);
        $this->assertEquals($grade, question_state::$gradedwrong);
        $this->assertTrue(isset($cache['_testoutcome']));
        $testOutcome = unserialize($cache['_testoutcome']);
        $this->assertTrue($testOutcome->hasSyntaxError());
        $this->assertEquals(count($testOutcome->testResults), 0);
    }


    public function test_grade_runtime_error() {
        $q = test_question_maker::make_question('coderunner', 'sqr');
        $code = "def sqr(x): return x * y";
        $response = array('answer' => $code);
        $result = $q->grade_response($response);
        list($mark, $grade, $cache) = $result;
        $this->assertEquals($mark, 0);
        $this->assertEquals($grade, question_state::$gradedwrong);
        $this->assertTrue(isset($cache['_testoutcome']));
        $testOutcome = unserialize($cache['_testoutcome']);
        $this->assertEquals(count($testOutcome->testResults), 1);
        $this->assertFalse($testOutcome->testResults[0]->isCorrect);
    }


    public function test_student_answer_variable() {
        $q = test_question_maker::make_question('coderunner', 'studentanswervar');
        $code = "\"\"\"Line1\n\"Line2\"\n'Line3'\nLine4\n\"\"\"";
        $response = array('answer' => $code);
        $result = $q->grade_response($response);
        list($mark, $grade, $cache) = $result;
        $this->assertEquals($mark, 1);
        $this->assertEquals($grade, question_state::$gradedright);
    }


    public function test_illegal_open_error() {
        $q = test_question_maker::make_question('coderunner', 'sqr');
        $code = "def sqr(x):\n    f = open('/tmp/xxx');\n    return x * x";
        $response = array('answer' => $code);
        $result = $q->grade_response($response);
        list($mark, $grade, $cache) = $result;
        $this->assertEquals($mark, 0);
        $this->assertEquals($grade, question_state::$gradedwrong);
        $this->assertTrue(isset($cache['_testoutcome']));
        $testOutcome = unserialize($cache['_testoutcome']);
        $this->assertEquals(count($testOutcome->testResults), 1);
        $this->assertFalse($testOutcome->testResults[0]->isCorrect);
    }


    public function test_grade_delayed_runtime_error() {
        $q = test_question_maker::make_question('coderunner', 'sqr');
        $code = "def sqr(x):\n  if x != 11:\n    return x * x\n  else:\n    return y";
        $response = array('answer' => $code);
        $result = $q->grade_response($response);
        list($mark, $grade, $cache) = $result;
        $this->assertEquals($mark, 0);
        $this->assertEquals($grade, question_state::$gradedwrong);
        $this->assertTrue(isset($cache['_testoutcome']));
        $testOutcome = unserialize($cache['_testoutcome']);
        $this->assertEquals(count($testOutcome->testResults), 3);
        $this->assertTrue($testOutcome->testResults[0]->isCorrect);
        $this->assertFalse($testOutcome->testResults[2]->isCorrect);
    }


    public function test_triple_quotes() {
        $q = test_question_maker::make_question('coderunner', 'sqr');
        $code = <<<EOCODE
def sqr(x):
    """This is a function
       that squares its parameter"""
    return x * x
EOCODE;
        $response = array('answer' => $code);
        $result = $q->grade_response($response);
        list($mark, $grade, $cache) = $result;
        $this->assertEquals($mark, 1);
        $this->assertEquals($grade, question_state::$gradedright);
        $this->assertTrue(isset($cache['_testoutcome']));
        $testOutcome = unserialize($cache['_testoutcome']);
        $this->assertEquals(count($testOutcome->testResults), 5);
        foreach ($testOutcome->testResults as $tr) {
            $this->assertTrue($tr->isCorrect);
        }
    }


    public function test_helloFunc() {
        // Check a question type with a function that prints output
        $q = test_question_maker::make_question('coderunner', 'helloFunc');
        $code = "def sayHello(name):\n  print('Hello ' + name)";
        $response = array('answer' => $code);
        $result = $q->grade_response($response);
        list($mark, $grade, $cache) = $result;
        $this->assertEquals($mark, 1);
        $this->assertEquals($grade, question_state::$gradedright);
        $this->assertTrue(isset($cache['_testoutcome']));
        $testOutcome = unserialize($cache['_testoutcome']);
        $this->assertEquals(count($testOutcome->testResults), 4);
        foreach ($testOutcome->testResults as $tr) {
            $this->assertTrue($tr->isCorrect);
        }
    }


    public function test_copyStdin() {
        // Check a question that reads stdin and writes to stdout
        $q = test_question_maker::make_question('coderunner', 'copyStdin');
        $code = <<<EOCODE
def copyStdin(n):
  for i in range(n):
    line = input()
    print(line)
EOCODE;
        $response = array('answer' => $code);
        $result = $q->grade_response($response);
        list($mark, $grade, $cache) = $result;
        $this->assertEquals($mark, 0);
        $this->assertEquals($grade, question_state::$gradedwrong);
        $this->assertTrue(isset($cache['_testoutcome']));
        $testOutcome = unserialize($cache['_testoutcome']);
        $this->assertEquals(count($testOutcome->testResults), 4);
        $this->assertTrue($testOutcome->testResults[0]->isCorrect);
        $this->assertTrue($testOutcome->testResults[1]->isCorrect);
        $this->assertTrue($testOutcome->testResults[2]->isCorrect);
        $this->assertFalse($testOutcome->testResults[3]->isCorrect);
        $this->assertTrue(strpos($testOutcome->testResults[3]->got, 'EOFError') !== FALSE);
     }


     public function test_timeout() {
         // Check a question that loops forever. Should cause sandbox timeout
        $q = test_question_maker::make_question('coderunner', 'timeout');
        $code = "def timeout():\n  while (1):\n    pass";
        $response = array('answer' => $code);
        $result = $q->grade_response($response);
        list($mark, $grade, $cache) = $result;
        $this->assertEquals($mark, 0);
        $this->assertEquals($grade, question_state::$gradedwrong);
        $this->assertTrue(isset($cache['_testoutcome']));
        $testOutcome = unserialize($cache['_testoutcome']);
        $this->assertEquals(count($testOutcome->testResults), 1);
        $this->assertFalse($testOutcome->testResults[0]->isCorrect);
        $this->assertTrue(strpos($testOutcome->testResults[0]->got, 'Time limit exceeded') !== FALSE);
     }


     public function test_exceptions() {
         // Check a function that conditionally throws exceptions
        $q = test_question_maker::make_question('coderunner', 'exceptions');
        $code = "def checkOdd(n):\n  if n & 1:\n    raise ValueError()";
        $response = array('answer' => $code);
        $result = $q->grade_response($response);
        list($mark, $grade, $cache) = $result;
        $this->assertEquals($mark, 1);
        $this->assertEquals($grade, question_state::$gradedright);
        $this->assertTrue(isset($cache['_testoutcome']));
        $testOutcome = unserialize($cache['_testoutcome']);
        $this->assertEquals(count($testOutcome->testResults), 2);
        $this->assertEquals($testOutcome->testResults[0]->got, "Exception\n");
        $this->assertEquals($testOutcome->testResults[1]->got, "Yes\nYes\nNo\nNo\nYes\nNo\n");
     }

     public function test_partial_mark_question() {
         // Test a question that isn't of the usual all_or_nothing variety
        $q = test_question_maker::make_question('coderunner', 'sqrPartMarks');
        $code = "def sqr(n):\n  return -17.995";
        $response = array('answer' => $code);
        $result = $q->grade_response($response);
        list($mark, $grade, $cache) = $result;
        $this->assertEquals($grade, question_state::$gradedpartial);
        $this->assertEquals($mark, 0);

        $code = "def sqr(n):\n  return 0";  // Passes first test only
        $response = array('answer' => $code);
        $result = $q->grade_response($response);
        list($mark, $grade, $cache) = $result;
        $this->assertEquals($grade, question_state::$gradedpartial);
        $this->assertTrue(abs($mark - 0.5/7.5) < 0.00001);

        $code = "def sqr(n):\n  return n * n if n <= 0 else -17.995";  // Passes first test and last two only
        $response = array('answer' => $code);
        $result = $q->grade_response($response);
        list($mark, $grade, $cache) = $result;
        $this->assertEquals($grade, question_state::$gradedpartial);
        $this->assertTrue(abs($mark - 5.0/7.5) < 0.00001);
     }


     public function test_template_engine() {
         // Check if the template engine is installed and working OK
         Twig_Autoloader::register();
         $loader = new Twig_Loader_String();
         $twig = new Twig_Environment($loader, array(
             'debug' => true,
             'autoescape' => false,
             'strict_variables' => true,
             'optimizations' => 0
         ));
         $this->assertEquals($twig->render('Hello {{ name }}!', array('name' => 'Fabien')), 'Hello Fabien!');
     }
}

