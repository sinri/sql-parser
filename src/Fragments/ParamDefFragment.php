<?php

namespace SqlParser\Fragments;

use SqlParser\Context;
use SqlParser\Fragment;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * The definition of a parameter of a function or procedure.
 *
 * @category   Fragments
 * @package    SqlParser
 * @subpackage Fragments
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class ParamDefFragment extends Fragment
{

    /**
     * The name of the new column.
     *
     * @var string
     */
    public $name;

    /**
     * Parameter's direction (IN, OUT or INOUT).
     *
     * @var string
     */
    public $inOut;

    /**
     * The data type of thew new column.
     *
     * @var DataTypeFragment
     */
    public $type;

    /**
     * @param Parser     $parser  The parser that serves as context.
     * @param TokensList $list    The list of tokens that are being parsed.
     * @param array      $options Parameters for parsing.
     *
     * @return ParamDefFragment[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = array();

        $expr = new ParamDefFragment();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 -----------------------[ ( ]------------------------> 1
         *
         *      1 ----------------[ IN / OUT / INOUT ]----------------> 1
         *      1 ----------------------[ name ]----------------------> 2
         *
         *      2 -------------------[ data type ]--------------------> 3
         *
         *      3 ------------------------[ , ]-----------------------> 1
         *      3 ------------------------[ ) ]-----------------------> -1
         *
         * @var int
         */
        $state = 0;

        for (; $list->idx < $list->count; ++$list->idx) {

            /**
             * Token parsed at this moment.
             * @var Token
             */
            $token = $list->tokens[$list->idx];

            // End of statement.
            if ($token->type === Token::TYPE_DELIMITER) {
                break;
            }

            // Skipping whitespaces and comments.
            if (($token->type === Token::TYPE_WHITESPACE) || ($token->type === Token::TYPE_COMMENT)) {
                continue;
            }

            if ($state === 0) {
                if (($token->type === Token::TYPE_OPERATOR) && ($token->value === '(')) {
                    $state = 1;
                }
                continue;
            } elseif ($state === 1) {
                if (($token->value === 'IN') || ($token->value === 'OUT') || ($token->value === 'INOUT')) {
                    $expr->inOut = $token->value;
                    ++$list->idx;
                } elseif ($token->value === ')') {
                    ++$list->idx;
                    break;
                } else {
                    $expr->name = $token->value;
                    $state = 2;
                }
            } elseif ($state === 2) {
                $expr->type = DataTypeFragment::parse($parser, $list);
                $state = 3;
            } elseif ($state === 3) {
                $ret[] = $expr;
                $expr = new ParamDefFragment();
                if ($token->value === ',') {
                    $state = 1;
                    continue;
                } elseif ($token->value === ')') {
                    ++$list->idx;
                    break;
                }
            }
        }

        // Last iteration was not saved.
        if (!empty($expr->name)) {
            $ret[] = $expr;
        }

        --$list->idx;
        return $ret;

    }
}
