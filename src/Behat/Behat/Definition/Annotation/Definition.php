<?php

namespace Behat\Behat\Definition\Annotation;

use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

use Behat\Behat\Definition\DefinitionInterface,
    Behat\Behat\Exception\Error,
    Behat\Behat\Context\ContextInterface,
    Behat\Behat\Annotation\Annotation;

/*
 * This file is part of the Behat.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Step definition.
 *
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
abstract class Definition extends Annotation implements DefinitionInterface
{
    /**
     * Definition regex to match.
     *
     * @var     string
     */
    private $regex;
    /**
     * Matched to definition regex text.
     *
     * @var     string
     */
    private $matchedText;
    /**
     * Step parameters for call.
     *
     * @var     array
     */
    private $values = array();

    /**
     * Initializes definition.
     *
     * @param   callback    $callback   definition callback
     * @param   string      $regex      definition regular expression
     */
    public function __construct($callback, $regex)
    {
        parent::__construct($callback);

        $this->regex = $regex;
    }

    /**
     * Returns definition regex to match.
     *
     * @return  string
     */
    public function getRegex()
    {
        return $this->regex;
    }

    /**
     * Saves matched step text to definition.
     *
     * @param   string  $text   step text (description)
     */
    public function setMatchedText($text)
    {
        $this->matchedText = $text;
    }

    /**
     * Returns matched step text.
     *
     * @return  string
     */
    public function getMatchedText()
    {
        return $this->matchedText;
    }

    /**
     * Sets step parameters for step run.
     *
     * @param   array   $values step parameters
     */
    public function setValues(array $values)
    {
        $this->values = $values;
    }

    /**
     * Returns step parameters for step run.
     *
     * @return  array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * Custom error handler.
     *
     * This method used as custom error handler when step is running.
     *
     * @see     set_error_handler()
     *
     * @throws  Behat\Behat\Exception\Error
     */
    public function errorHandler($code, $message, $file, $line)
    {
        throw new Error($code, $message, $file, $line);
    }

    /**
     * @see     Behat\Behat\Definition\DefinitionInterface::run()
     */
    public function run(ContextInterface $context, $tokens = array())
    {
        $oldHandler = set_error_handler(array($this, 'errorHandler'), E_ALL ^ E_WARNING);
        $callback   = $this->getCallback();

        $values = $this->getValues();
        if (count($tokens)) {
            foreach ($values as $i => $value) {
                if ($value instanceof TableNode || $value instanceof PyStringNode) {
                    $values[$i] = clone $value;
                    $values[$i]->replaceTokens($tokens);
                }
            }
        }

        if (is_array($callback)) {
            call_user_func_array(array($context->getContextByClassName($callback[0]), $callback[1]), $values);
        } else {
            array_unshift($values, $context);
            call_user_func_array($callback, $values);
        }

        if (null !== $oldHandler) {
            set_error_handler($oldHandler);
        }
    }
}
