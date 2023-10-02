<?php
/*
   Copyright (c) 2020 Sunil Mohan Adapa <sunil at medhas dot org>

   Drop in replacement for native gettext.

   This file is part of PHP-gettext.

   PHP-gettext is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   PHP-gettext is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with PHP-gettext; if not, write to the Free Software
   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

/**
 * Lexical analyzer for gettext plurals expression. Takes a string to parse
 * during construction and returns a single token every time peek() or
 * fetch_token() are called. The special string '__END__' is returned if there
 * are no more tokens to be read. Spaces are ignored during tokenization.
 */
class PluralsLexer {
  private $string;
  private $position;

  /**
   * Constructor
   *
   * @param string string Contains the value gettext plurals expression to
   * analyze.
   */
  public function __construct(string $string) {
    $this->string = $string;
    $this->position = 0;
  }

  /**
   * Return the next token and the length to advance the read position without
   * actually advancing the read position. Tokens for operators and variables
   * are simple strings containing the operator or variable. If there are no
   * more token to provide, the special value ['__END__', 0] is returned. If
   * there was an unexpected input an Exception is raised.
   *
   * @access private
   * @throws Exception If there is unexpected input in the provided string.
   * @return array The next token and length to advance the current position.
   */
  private function _tokenize() {
    $buf = $this->string;

    // Consume all spaces until the next token
    $index = $this->position;
    while ($index < strlen($buf) && $buf[$index] == ' ') {
      $index++;
    }
    $this->position = $index;

    // Return special token if next of the string is reached.
    if (strlen($buf) - $index == 0) {
      return ['__END__', 0];
    }

    // Operators with two characters
    $doubles = ['==', '!=', '>=', '<=', '&&', '||'];
    $next = substr($buf, $index, 2);
    if (in_array($next, $doubles)) {
      return [$next, 2];
    }

    // Operators with single character or variable 'n'.
    $singles = [
      'n', '(', ')', '?', ':', '+', '-', '*', '/', '%', '!', '>', '<'];
    if (in_array($buf[$index], $singles)) {
      return [$buf[$index], 1];
    }

    // Whole number constants, return an integer.
    $digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $pos = $index;
    while ($pos < strlen($buf) && in_array($buf[$pos], $digits)) {
      $pos++;
    }
    if ($pos != $index) {
      $length = $pos - $index;
      return [(int)substr($buf, $index, $length), $length];
    }

    // Throw and exception for all other unexpected input in the string.
    throw new Exception('Lexical analysis failed');
  }

  /**
   * Return the next token without actually advancing the read position.
   * Tokens for operators and variables are simple strings containing the
   * operator or variable. If there are no more tokens to provide, the special
   * value '__END__' is returned. If there was an unexpected input an
   * Exception is raised.
   *
   * @throws Exception If there is unexpected input in the provided string.
   * @return string The next token.
   */
  public function peek() {
    list($token, $length) = $this->_tokenize();
    return $token;
  }

  /**
   * Return the next token after advancing the read position. Tokens for
   * operators and variables are simple strings containing the operator or
   * variable. If there are no more token to provide, the special value
   * '__END__' is returned. If there was an unexpected input an Exception is
   * raised.
   *
   * @throws Exception If there is unexpected input in the provided string.
   * @return string The next token.
   */
  public function fetch_token() {
    list($token, $length) = $this->_tokenize();
    $this->position += $length;
    return $token;
  }
}

/**
 * A parsed representation of the gettext plural expression. This is a tree
 * containing further expressions depending on how nested the given input is.
 * Calling the evaluate() function computes the value of the expression if the
 * variable 'n' is set a certain value. This is used to decide which plural
 * string translation to use based on the number items at hand.
 */
class PluralsExpression {
  private $operator;
  private $operands;

  const BINARY_OPERATORS = [
    '==', '!=', '>=', '<=', '&&', '||', '+', '-', '*', '/', '%', '>', '<'];
  const UNARY_OPERATORS = ['!'];

  /**
   * Constructor
   *
   * @param string Operator for the expression.
   * @param (int|string|PuralsExpression)[] Variable number of operands of the
   * expression. One int operand is expected in case the operator is 'const'.
   * One string operand with value 'n' is expected in case the operator is
   * 'var'. For all other operators, the operands much be objects of type
   * PluralExpression. Unary operators expect one operand, binary operators
   * expect two operands and trinary operators expect three operands.
   */
  public function __construct($operator, ...$operands) {
    $this->operator = $operator;
    $this->operands = $operands;
  }

  /**
   * Return a parenthesized string representation of the expression for
   * debugging purposes.
   *
   * @return string A string representation of the expression.
   */
  public function to_string() {
    if ($this->operator == 'const' || $this->operator == 'var') {
      return $this->operands[0];
    } elseif (in_array($this->operator, self::BINARY_OPERATORS)) {
      return sprintf(
        "(%s %s %s)", $this->operands[0]->to_string(), $this->operator,
        $this->operands[1]->to_string());
    } elseif (in_array($this->operator, self::UNARY_OPERATORS)) {
      return sprintf(
        "(%s %s)", $this->operator, $this->operands[0]->to_string());
    } elseif ($this->operator == '?') {
      return sprintf(
        "(%s ? %s : %s)", $this->operands[0]->to_string(),
        $this->operands[1]->to_string(),
        $this->operands[2]->to_string());
    }
  }

  /**
   * Return the computed value of the expression if the variable 'n' is set to
   * a certain value.
   *
   * @param int The value of the variable n to use when evaluating.
   * @throws Exception If the expression has been constructed incorrectly.
   * @return int The value of the expression after evaluation.
   */
  public function evaluate($n) {
    if (!in_array($this->operator, ['const', 'var'])) {
      $operand1 = $this->operands[0]->evaluate($n);
    }
    if (in_array($this->operator, self::BINARY_OPERATORS) ||
        $this->operator == '?') {
      $operand2 = $this->operands[1]->evaluate($n);
    }
    if ($this->operator == '?') {
      $operand3 = $this->operands[2]->evaluate($n);
    }

    switch ($this->operator) {
      case 'const':
        return $this->operands[0];
      case 'var':
        return $n;
      case '!':
        return !($operand1);
      case '==':
        return $operand1 == $operand2;
      case '!=':
        return $operand1 != $operand2;
      case '>=':
        return $operand1 >= $operand2;
      case '<=':
        return $operand1 <= $operand2;
      case '>':
        return $operand1 > $operand2;
      case '<':
        return $operand1 < $operand2;
      case '&&':
        return $operand1 && $operand2;
      case '||':
        return $operand1 || $operand2;
      case '+':
        return $operand1 + $operand2;
      case '-':
        return $operand1 - $operand2;
      case '*':
        return $operand1 * $operand2;
      case '/':
        return (int)($operand1 / $operand2);
      case '%':
        return $operand1 % $operand2;
      case '?':
        return $operand1 ? $operand2 : $operand3;
      default:
        throw new Exception('Invalid expression');
    }
  }
}

/**
 * A simple operator-precedence parser for gettext plural expressions. Takes a
 * string during construction and returns a PluralsExpression tree when
 * parse() is called.
 */
class PluralsParser {
  private $lexer;

  /*
   * Operator precedence. The parsing only happens with minimum precedence of
   * 0. However, ':' and ')' exist here to make sure that parsing does not
   * proceed beyond them when they are not to be parsed.
   */
  private const PREC = [
    ':' => -1, '?' => 0, '||' => 1, '&&' => 2, '==' => 3, '!=' => 3,
    '>' => 4, '<' => 4, '>=' => 4, '<=' => 4, '+' => 5, '-' => 5, '*' => 6,
    '/' => 6, '%' => 6, '!' => 7, '__END__' => -1, ')' => -1
  ];

  // List of right associative operators
  private const RIGHT_ASSOC = ['?'];

  /**
   * Constructor
   *
   * @param string string the plural expression to be parsed.
   */
  public function __construct(string $string) {
    $this->lexer = new PluralsLexer($string);
  }

  /**
   * Expect a primary next for parsing and return a PluralsExpression or throw
   * and exception otherwise. A primary can be the variable 'n', an whole
   * number constant, a unary operator expression string with '!', or a
   * parenthesis expression.
   *
   * @throws Exception If the next token is not a primary or if parenthesis
   * expression is not closes properly with ')'.
   * @return PluralsExpression That is constructed from the parsed primary.
   */
  private function _parse_primary() {
    $token = $this->lexer->fetch_token();
    if ($token === 'n') {
      return new PluralsExpression('var', 'n');
    } elseif (is_int($token)) {
      return new PluralsExpression('const', (int)$token);
    } elseif ($token === '!') {
      return new PluralsExpression('!', $this->_parse_primary());
    } elseif ($token === '(') {
      $result = $this->_parse($this->_parse_primary(), 0);
      if ($this->lexer->fetch_token() != ')') {
        throw new Exception('Mismatched parenthesis');
      }
      return $result;
    }

    throw new Exception('Primary expected');
  }

  /**
   * Fetch an operator from the lexical analyzer and test for it. Optionally
   * advance the position of the lexical analyzer to next token. Raise
   * exception if the token retrieved is not an operator.
   *
   * @access private
   * @param bool peek A flag to indicate whether the position of the lexical
   * analyzer should *not* be advanced. If false, the lexical analyzer is
   * advanced by one token.
   * @throws Exception If the token read is not an operator.
   * @return string The operator that has been fetched from the lexical
   * analyzer.
   */
  private function _parse_operator($peek) {
    if ($peek) {
      $token = $this->lexer->peek();
    } else {
        $token = $this->lexer->fetch_token();
    }

    if ($token !== NULL && !array_key_exists($token, self::PREC)) {
      throw new Exception('Operator expected');
    }
    return $token;
  }

  /**
   * A parsing method suitable for recursion.
   *
   * @access private
   * @param ParserExpression left_side A pre-parsed left-hand side expression
   * of the file expression to be constructed. This helps with recursion.
   * @param int min_precedence The minimum value of precedence for the
   * operators to be considered for parsing. Parsing will stop and current
   * expression is returned if an operator of a lower precedence is
   * encountered.
   * @throws Exception If the input string does not conform to the grammar of
   * the gettext plural expression.
   * @return ParserExpression A complete expression after parsing.
   */
  private function _parse($left_side, $min_precedence) {
    $next_token = $this->_parse_operator(true);

    while (self::PREC[$next_token] >= $min_precedence) {
      $operator = $this->_parse_operator(false);
      $right_side = $this->_parse_primary();

      $next_token = $this->_parse_operator(true);

      /*
       * Consume (recursively) into right hand side all expressions of higher
       * precedence.
       */
      while ((self::PREC[$operator] < self::PREC[$next_token]) ||
             ((self::PREC[$operator] == self::PREC[$next_token]) &&
              in_array($operator, self::RIGHT_ASSOC))) {
        $right_side = $this->_parse(
            $right_side, self::PREC[$next_token]);
        $next_token = $this->_parse_operator(true);
      }

      if ($operator != '?') {
        /*
         * Handling for all binary operators. Consume into left hand side all
         * expressions of equal precedence.
         */
        $left_side = new PluralsExpression($operator, $left_side, $right_side);
      } else {
        // Special handling for (a ? b : c) expression
        $operator = $this->lexer->fetch_token();
        if ($operator != ':') {
          throw new Exception('Invalid ? expression');
        }

        $right_side2 = $this->_parse(
          $this->_parse_primary(), self::PREC[$operator] + 1);
        $next_token = $this->_parse_operator(true);
        $left_side = new PluralsExpression(
            '?', $left_side, $right_side, $right_side2);
      }
    }
    return $left_side;
  }

 /**
   * A simple implementation of an operator-precedence parser. See:
   * https://en.wikipedia.org/wiki/Operator-precedence_parser for an analysis
   * of the algorithm.
   *
   * @throws Exception If the input string does not conform to the grammar of
   * the gettext plural expression.
   * @return ParserExpression A complete expression after parsing.
   */
  public function parse() {
    $expression = $this->_parse($this->_parse_primary(), 0);
    // Special handling for an extra ')' at the end.
    if ($this->lexer->peek() != '__END__') {
      throw new Exception('Could not parse completely');
    }
    return $expression;
  }
}

/**
 * Provides a class to parse the value of the 'Plural-Forms:' header in the
 * gettext translation files. Holds the expression tree and the number of
 * plurals after parsing. Parsing happens during construction which takes as
 * its only argument the string to parse. Error during parsing are silently
 * suppressed and the fallback behavior is used with the value for Germanic
 * languages as follows: "nplurals=2; plural=n == 1 ? 0 : 1;".
 */
class PluralHeader {
  public $total;
  public $expression;

  /**
   * Constructor
   *
   * @param string The value of the Plural-Forms: header as seen in .po files.
   */
  function __construct($string) {
    try {
      list($total, $expression) = $this->parse($string);
    } catch (Exception $e) {
      $string = "nplurals=2; plural=n == 1 ? 0 : 1;";
      list($total, $expression) = $this->parse($string);
    }
    $this->total = $total;
    $this->expression = $expression;
  }

  /**
   * Return the number of plural forms and the parsed expression tree.
   *
   * @access private
   * @param string string The value of the Plural-Forms: header.
   * @throws Exception If the string could not be parsed.
   * @return array The number of plural forms and parsed expression tree.
   */
  private function parse($string) {
    $regex = "/^\s*nplurals\s*=\s*(\d+)\s*;\s*plural\s*=([^;]+);/i";
    if (preg_match($regex, $string, $matches)) {
      $total = (int)$matches[1];
      $expression_string = $matches[2];
    } else {
      throw new Exception('Invalid header value');
    }

    $parser = new PluralsParser($expression_string);
    $expression = $parser->parse();
    return [$total, $expression];
  }
}
