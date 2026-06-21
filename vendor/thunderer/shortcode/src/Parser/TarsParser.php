<?php
namespace Thunder\Shortcode\Parser;

use Thunder\Shortcode\Shortcode\ParsedShortcode;
use Thunder\Shortcode\Shortcode\Shortcode;
use Thunder\Shortcode\Syntax\CommonSyntax;
use Thunder\Shortcode\Syntax\SyntaxInterface;

/**
 * TarsParser - a fast, robust shortcode parser.
 *
 * Strategy: a single PCRE pass lexes every individual shortcode tag (both
 * opening and closing) in C, then a linear stack-based pass resolves nesting
 * in PHP. This combines RegexParser's raw scanning speed with RegularParser's
 * robustness: the lexer regex understands quoted values and escapes, so an
 * unterminated quote like `[a k="v]` correctly fails to lex as a tag instead
 * of inventing a bogus parameter. Nesting, mismatched closing tags and
 * open-only shortcodes are then resolved exactly like the default parser.
 *
 * @author Andy Miller
 *
 * @psalm-type TarsNode = array{
 *     0: string, 1: string, 2: string|null, 3: int, 4: int, 5: int,
 *     6: int|null, 7: bool, 8: int|null, 9: int|null, 10: bool
 * }
 */
final class TarsParser implements ParserInterface
{
    /** @var non-empty-string */
    private $tagRegex;
    /** @var non-empty-string */
    private $paramRegex;
    /** @var non-empty-string */
    private $delimiter;
    /** @var positive-int */
    private $delimiterLength;

    /** @param SyntaxInterface|null $syntax */
    public function __construct($syntax = null)
    {
        if(null !== $syntax && false === $syntax instanceof SyntaxInterface) {
            throw new \LogicException('Parameter $syntax must be an instance of SyntaxInterface.');
        }

        $syntax = $syntax ?: new CommonSyntax();
        $this->delimiter = $syntax->getParameterValueDelimiter();
        $this->delimiterLength = strlen($this->delimiter);

        $o = preg_quote($syntax->getOpeningTag(), '~');
        $c = preg_quote($syntax->getClosingTag(), '~');
        $m = preg_quote($syntax->getClosingTagMarker(), '~');
        $e = preg_quote($syntax->getParameterValueSeparator(), '~');
        $d = preg_quote($this->delimiter, '~');

        $ws = '\s*';
        $special = $o.'|'.$c.'|'.$m.'|'.$e.'|'.$d;
        $notSpecial = '(?!'.$special.')';
        // a single "string token": one escape sequence, or one maximal run of
        // non-special, non-whitespace characters (possessive so it never gives back)
        $stringTok = '(?:\\\\.|(?:'.$notSpecial.'[^\s\\\\])++)';
        // a value globs consecutive string tokens; atomic so the lexer commits like
        // RegularParser instead of backtracking into a different tokenization
        $stringRun = '(?>'.$stringTok.'+)';
        // a delimited value; the body is possessive so an escape sequence is never
        // given back to let the value re-close at an earlier (escaped) delimiter
        $quoted = $d.'(?:\\\\.|(?!'.$d.').)*+'.$d;
        $value = '(?>'.$quoted.'|'.$stringRun.')';
        // shortcode name; must end on a token boundary so `[foo.bar]` is rejected wholesale
        $name = '[a-zA-Z0-9_*-]+';
        $boundary = '(?=\s|'.$special.'|$)';
        // a parameter name is a single string token, not a glued run
        $params = '(?<params>(?:'.$ws.$stringTok.'(?:'.$ws.$e.$ws.$value.')?)*+)';
        $bbCode = '(?:'.$e.$ws.'(?<bbCode>'.$value.')'.$ws.')?+';

        $close = $o.$ws.$m.$ws.'(?<cname>'.$name.')'.$ws.$c;
        $open = $o.$ws.'(?<name>'.$name.')'.$boundary.$ws.$bbCode.$params.$ws.'(?<self>'.$m.')?'.$ws.$c;

        $this->tagRegex = '~(?:'.$close.'|'.$open.')~us';
        $this->paramRegex = '~'.$ws.'(?<pn>'.$stringTok.')(?:'.$ws.$e.$ws.'(?<pv>'.$value.'))?~us';
    }

    /**
     * @param string $text
     *
     * @return ParsedShortcode[]
     */
    public function parse($text)
    {
        $count = preg_match_all($this->tagRegex, $text, $matches, PREG_OFFSET_CAPTURE);
        if(false === $count || preg_last_error() !== PREG_NO_ERROR) {
            throw new \RuntimeException(sprintf('PCRE failure `%s`.', preg_last_error()));
        }
        if(0 === $count) {
            return array();
        }

        // pure-ASCII text lets us treat byte offsets as character offsets directly
        $ascii = !preg_match('~[\x80-\xff]~', $text);
        $lastByte = 0;
        $lastChar = 0;

        /** @psalm-var list<TarsNode> $nodes */
        $nodes = array();
        /** @psalm-var list<int> $stack */
        $stack = array();
        $depth = 0;
        $cnames = $matches['cname'];
        $names = $matches['name'];
        $selfs = $matches['self'];
        $bbCodes = $matches['bbCode'];
        $params = $matches['params'];

        foreach($matches[0] as $i => $whole) {
            $byteStart = $whole[1];
            $byteEnd = $byteStart + strlen($whole[0]);

            if($cnames[$i][1] !== -1) {
                // closing tag: match the innermost open node of the same name.
                // RegularParser rejects a closing name that is falsy in PHP (`'0'`)
                // via `if(!$closingName = ...)`, so we faithfully ignore it too.
                $cname = $cnames[$i][0];
                if('0' === $cname) {
                    continue;
                }
                for($s = $depth - 1; $s >= 0; $s--) {
                    $node = $stack[$s];
                    if($nodes[$node][0] === $cname) {
                        $nodes[$node][7] = true;        // closed
                        $nodes[$node][8] = $byteStart;  // closeStart
                        $nodes[$node][9] = $byteEnd;    // closeEnd
                        $stack = array_slice($stack, 0, $s);
                        $depth = $s;
                        break;
                    }
                }
                continue;
            }

            // opening tag — char offset (byte offset is fine for pure-ASCII text)
            if($ascii) {
                $offset = $byteStart;
            } else {
                if($byteStart > $lastByte) {
                    /** @psalm-suppress PossiblyFalseArgument */
                    $lastChar += mb_strlen(substr($text, $lastByte, $byteStart - $lastByte), 'utf-8');
                    $lastByte = $byteStart;
                }
                $offset = $lastChar;
            }

            $self = $selfs[$i][1] !== -1;

            // node tuple: [0]name [1]paramsRaw [2]bbCodeRaw [3]offset [4]start
            //   [5]openEnd [6]parent [7]closed [8]closeStart [9]closeEnd [10]selfClosing
            // parameter/bbCode parsing is deferred to build() so absorbed nodes never pay for it
            $nodes[] = array(
                $names[$i][0],
                $params[$i][1] !== -1 ? $params[$i][0] : '',
                $bbCodes[$i][1] !== -1 ? $bbCodes[$i][0] : null,
                $offset,
                $byteStart,
                $byteEnd,
                $depth ? $stack[$depth - 1] : null,
                $self,
                $self ? $byteEnd : null,
                $self ? $byteEnd : null,
                $self,
            );

            if(false === $self) {
                $stack[$depth++] = count($nodes) - 1;
            }
        }

        return $this->build($nodes, $text);
    }

    /**
     * @psalm-param array<int, TarsNode> $nodes
     * @param string $text
     *
     * @return ParsedShortcode[]
     */
    private function build(array $nodes, $text)
    {
        $shortcodes = array();
        // A node is absorbed (part of a closed ancestor's content) iff its parent is
        // closed or itself absorbed. Parents always precede children, so a single
        // forward pass resolves this in O(1) per node instead of walking ancestors.
        /** @psalm-var array<int,bool> $absorbed */
        $absorbed = array();
        foreach($nodes as $id => $node) {
            $parent = $node[6];
            if(null !== $parent && ($nodes[$parent][7] || $absorbed[$parent])) {
                $absorbed[$id] = true;
                continue;
            }
            $absorbed[$id] = false;

            if($node[7]) {
                // a closed node always has integer close offsets (set on close or self-close)
                /** @psalm-suppress PossiblyNullOperand */
                $content = $node[10] ? null : substr($text, $node[5], $node[8] - $node[5]);
                /** @psalm-suppress PossiblyNullOperand */
                $string = substr($text, $node[4], $node[9] - $node[4]);
            } else {
                $content = null;
                $string = substr($text, $node[4], $node[5] - $node[4]);
            }

            $parameters = '' === $node[1] ? array() : $this->parseParameters($node[1]);
            $bbCode = null === $node[2] ? null : $this->extractValue($node[2]);

            /** @psalm-suppress PossiblyFalseArgument */
            $shortcode = new Shortcode($node[0], $parameters, $content, $bbCode);
            /** @psalm-suppress PossiblyFalseArgument */
            $shortcodes[] = new ParsedShortcode($shortcode, $string, $node[3]);
        }

        return $shortcodes;
    }

    /**
     * @param string $text
     *
     * @psalm-return array<string,string|null>
     */
    private function parseParameters($text)
    {
        if('' === $text || false === preg_match_all($this->paramRegex, $text, $matches, PREG_SET_ORDER)) {
            return array();
        }

        $parameters = array();
        foreach($matches as $match) {
            if(!isset($match['pn']) || '' === $match['pn']) {
                continue;
            }
            $hasValue = isset($match['pv']) && '' !== $match['pv'];
            $parameters[$match['pn']] = $hasValue ? $this->extractValue($match['pv']) : null;
        }

        return $parameters;
    }

    /**
     * @param string $value
     *
     * @return string
     * @psalm-suppress InvalidFalsableReturnType
     */
    private function extractValue($value)
    {
        $dl = $this->delimiterLength;
        if(strlen($value) >= 2 * $dl
            && strncmp($value, $this->delimiter, $dl) === 0
            && substr($value, -$dl) === $this->delimiter) {
            /** @psalm-suppress FalsableReturnStatement */
            return substr($value, $dl, -$dl);
        }

        return $value;
    }
}
