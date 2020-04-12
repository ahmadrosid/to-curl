<?php

class Parser {
    private $source;
    private $output;
    private $current = 0;
    private static $methods = [
        'GET' => 'GET',
        'POST' => 'POST',
        'PUT' => 'PUT',
        'PATCH' => 'PATCH',
        'DELETE' => "DELETE"
    ];

    /**
     * Parser constructor.
     * @param $source
     */
    public function __construct($source)
    {
        $this->source = $source . PHP_EOL . PHP_EOL;
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

    private function appendOutput(string $str) {
        $this->output .= $str;
    }

    private function peekUntil($end) {
        $value = '';
        $c = $this->peekPrev();
        while ($c != $end) {
            $value .= $c;
            $c = $this->peek();
        }

        return $value;
    }

    private function parseKeyValueHeader() {
        $end = "\n\n";
        $c = $this->peekPrev() . $this->peek();
        $headers = [];
        while ($c != $end) {
            $key = $this->peekUntil(":");
            $this->peek();
            $value = $this->peekUntil("\n");
            $c = $this->peekPrev() . $this->peek();

            $headers[$key] = $value;
        }

        $headerString = '';
        foreach ($headers as $key => $value) {
            $headerString .= '-H \'' . $key . ':' . $value . '\' \\' . PHP_EOL;
        }

        return $headerString;
    }

    private function parseRequestBody() {
        if ($this->isAtEnd()) return '';

        $value = '';
        $value .= '-d \'';
        while (!$this->isAtEnd()) {
            $value .= $this->peek();
        }
        $value = trim($value) .  '\'';

        return $value;
    }

    private function parseRequest() {
        $method = $this->peekUntil(" ");
        if (array_key_exists($method, self::$methods)) {
            // 1. Parse method & url
            $this->appendOutput("curl -X ");
            $url = $this->peekUntil("\n");
            $this->appendOutput($method . $url . " \\" . PHP_EOL);

            // 2. Parse header key, value
            $header = $this->parseKeyValueHeader();
            $body = $this->parseRequestBody();
            if (empty($body)) {
                $header = rtrim($header, "\n\\");
            }

            $this->appendOutput($header);

            // 3. Parse request body
            $this->appendOutput($body);
        }
    }

    public function parse() {
        while (!$this->isAtEnd()) {
            $c = $this->peek();
            switch ($c) {
                case 'P': // POST PUT PATCH
                case 'G': // GET
                case 'D': // Delete
                    $this->parseRequest();
                    break;
            }
        }

    }

    public function getResult() {
        return $this->output;
    }

}

$source = shell_exec("/usr/bin/xclip -o -selection clipboard");
$parser = new Parser($source);
$parser->parse();

echo $parser->getResult();
