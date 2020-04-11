
<?php

class Parser {

    /**
     * @var string
     */
    private $output;
    /**
     * @var string
     */
    private $source;
    /**
     * @var int
     */
    private $current;
    /**
     * @var array
     */
    private $headers = [];

    private static $methods = [
        "GET" => "GET",
        "POST" => "POST",
        "PATCH" => "PATCH",
        "DELETE" => "DELETE",
    ];

    /**
     * Parser constructor.
     * @param string $source
     */
    public function __construct(string $source)
    {
        $this->source = $source;
    }

    private function appendOutput($str) {
        $this->output .= $str;
    }

    private function isAtEnd() {
        return $this->current >= strlen($this->source);
    }

    private function peek() {
        $this->current++;
        return $this->source[$this->current - 1];
    }

    private function peekPrev() {
        return $this->source[$this->current - 1];
    }

    private function peekNext() {
        if ($this->current + 1 >= strlen($this->source)) return '\0';
        return $this->source[$this->current + 1];
    }

    private function peekUntil(string $end) {
        $value = '';
        $c = $this->peekPrev();
        while ($c != $end){
            $value .= $c;
            $c = $this->peek();
        }

        return $value;
    }

    private function getHeaderKeyValue() {
        $c = $this->peek();
        while ($c != PHP_EOL) {
            $key = $this->peekUntil(":");
            $this->peek();
            $value = $this->peekUntil(PHP_EOL);
            $c = $this->peek();
            $this->headers[$key] = $value;
        }
    }

    private function parseRequestBody() {
        if ($this->isAtEnd()) return;

        $body = "-d '";
        $c = $this->peek();
        while (!$this->isAtEnd()) {
            $body .= $c;
            $c = $this->peek();
        }
        $body .= "'";

        return $body;
    }

    private function parseHeaders() {
        $token = $this->peekPrev() . $this->peekNext();
        while ($token != "\n\n" && !$this->isAtEnd()) {
            $this->getHeaderKeyValue();
            $token = $this->peekPrev() . $this->peekNext();
        }

        $header = '';
        foreach ($this->headers as $key => $value) {
            $header .= "-H '" . $key . ":" . $value . "' \\\n";
        }

        return $header;
    }

    private function parseUrl() {
        $c = $this->peek();
        $url = '';
        while ($c != PHP_EOL) {
            $url .= $c;
            $c = $this->peek();
        }

        return trim($url) . " \\" . PHP_EOL;
    }

    private function parseRequest() {
        $c = $this->peekPrev();
        $method = '';
        while ($c !== ' ') {
            $method .= $c;
            $c = $this->peek();
        }

        if (array_key_exists($method, self::$methods)) {
            $this->appendOutput("curl -X");
            $this->appendOutput(" " . self::$methods[$method]);
            $this->appendOutput(" " . $this->parseUrl());
            $this->appendOutput($this->parseHeaders());
            $this->appendOutput($this->parseRequestBody());
        }
    }

    public function parse() {
        while (!$this->isAtEnd()){
            $c = $this->peek();
            switch ($c) {
                case 'P': // POST PATCH PUT
                case 'G': // GET
                case 'D': // DELETE
                    $this->parseRequest();
                    break;
            }
        }
    }

    public function getResult() {
        return rtrim($this->output, "\\\n");
    }
}

$input = shell_exec("/usr/bin/xclip -o -selection clipboard");
if (!empty($input)){
    $parser = new Parser($input);
    $parser->parse();

    echo $parser->getResult();
}
