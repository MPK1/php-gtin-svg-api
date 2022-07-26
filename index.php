<?php

class GtinCode {

    public function __construct(
        $code,
        $height = 60,
        $params = array()
    ) {
        $this->code = intval($code);
        $this->error = null;
        $this->sequence = null;
        $this->height = min(max(200, intval($height) * 10), 1000);
        $this->params = $params;
        $this->checkCode();
    }

    protected $charset = array(
        'A' => array(
            '0' => '0001101',
            '1' => '0011001',
            '2' => '0010011',
            '3' => '0111101',
            '4' => '0100011',
            '5' => '0110001',
            '6' => '0101111',
            '7' => '0111011',
            '8' => '0110111',
            '9' => '0001011'
        ),
        'B' => array(
            '0' => '0100111',
            '1' => '0110011',
            '2' => '0011011',
            '3' => '0100001',
            '4' => '0011101',
            '5' => '0111001',
            '6' => '0000101',
            '7' => '0010001',
            '8' => '0001001',
            '9' => '0010111'
        ),
        'C' => array(
            '0' => '1110010',
            '1' => '1100110',
            '2' => '1101100',
            '3' => '1000010',
            '4' => '1011100',
            '5' => '1001110',
            '6' => '1010000',
            '7' => '1000100',
            '8' => '1001000',
            '9' => '1110100'
        )
    );

    protected $parities = array(
        '0' => 'AAAAAA',
        '1' => 'AABABB',
        '2' => 'AABBAB',
        '3' => 'AABBBA',
        '4' => 'ABAABB',
        '5' => 'ABBAAB',
        '6' => 'ABBBAA',
        '7' => 'ABABAB',
        '8' => 'ABABBA',
        '9' => 'ABBABA'
    );

    protected function checkCode()
    {
        if (strlen($this->code) <> 13) {
            $this->error = "Length wrong, should be 13 digits";
            return;
        }
        $code = strval($this->code);
        $sum_a = 0;
        for ($pos = 1; $pos < 12; $pos += 2) {
            $sum_a += $code[$pos];
        }
        $sum_a *= 3;
        $sum_b = 0;
        for ($pos = 0; $pos < 12; $pos += 2) {
            $sum_b += ($code[$pos]);
        }
        $check = ($sum_a + $sum_b) % 10;
        if ($check > 0) {
            $check = (10 - $check);
        }
        if ($check !== intval($code[12])) {
            $this->error = "Check digit wrong";
        }        
    }

    protected function setSequence()
    {
        $code = strval($this->code);
        $seq = '00000000000'; // left padding
        $seq .= '101'; // left guard
        $parity = $this->parities[$code[0]];
        for ($pos = 1; $pos < 7; ++$pos) {
            $seq .= $this->charset[$parity[($pos - 1)]][$code[$pos]];
        }
        $seq .= '01010'; // center guard
        for ($pos = 7; $pos < 13; ++$pos) {
            $seq .= $this->charset['C'][$code[$pos]];
        }
        $seq .= '101'; // right guard
        $seq .= '0000000'; // right padding
        $this->sequence = $seq;
    }

    public function getSvgCode()
    {
        $width = 1130;
        $height = $this->height;
        $svg = '<?xml version="1.0" standalone="no"?>'."\n"
            .'<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">'."\n"
            .'<svg'
            .' width="'.$width.'"'
            .' height="'.$height.'"'
            .' viewBox="0 0 '.$width.' '.$height.'"'
            .' version="1.1"'
            .' xmlns="http://www.w3.org/2000/svg"'
            .'>'."\n"
            .'<title>'.htmlspecialchars($this->code).'</title>'."\n"
            .'<desc>'.htmlspecialchars($this->code).'</desc>'."\n"
            .'<style>'
            .'.code { font: 80px monospace; }'
            .'</style>'."\n";
        $svg .= "\t".'<g'
            .' id="bars"'
            .' fill="#000"'
            .' stroke="none"'
            .' stroke-width="0"'
            .' stroke-linecap="square"'
            .'>'."\n";
        $bars = mb_str_split($this->sequence);
        $i = 0;
        $sum = 0;
        foreach ($bars as $rect) {
            $h = $height - 100;
            $long = array(11, 13, 57, 59, 103, 105);
            if (in_array($i - 1, $long)) {
                $h = $height - 50;
            }

            if ($rect == 0) {
                $svg .= "\t\t".'<rect'
                    .' x="'.sprintf('%F', ($i - $sum) * 10).'"'
                    .' y="'.sprintf('%F', 0).'"'
                    .' width="'.sprintf('%F', $sum * 10).'"'
                    .' height="'.sprintf('%F', $h).'"'
                    .' />'."\n";
                $sum = 0;
            } else {
                $sum += 1;
            }
            $i += 1;
        }
        $text_y = $height - 20;
        $svg .= "\t".'</g>'."\n";
        $svg .= "\n\t".'<text x="50" y="'.$text_y.'" textLength="100" class="code">'.substr($this->code, 0, 1).'</text>'."\n";
        $svg .= "\t".'<text x="155" y="'.$text_y.'" textLength="400" class="code">'.substr($this->code, 1, 6).'</text>'."\n";
        $svg .= "\t".'<text x="615" y="'.$text_y.'" textLength="400" class="code">'.substr($this->code, 7, 6).'</text>'."\n\n";
        $svg .= '</svg>'."\n";
        return $svg;
    }

    public function get($dl=false)
    {
        if ($this->error == null) {
            $this->setSequence();
            $this->getSvgResponse($dl);
        }else{
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            $content = new ArrayObject();
            $content['error'] = $this->error;
            echo json_encode($content);
        }
    }

    public function getSvgResponse($dl=false)
    {
        header('Content-Type: '.($dl ? 'document/svg+xml' : 'image/svg+xml'));
        header('Cache-Control: max-age=0, must-revalidate');
        header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
        header('Content-Disposition: inline; filename="'.$this->code.'.svg";');
        echo $this->getSvgCode();
    }

}

$code = isset($_GET["code"]) ? intval($_GET["code"]) : null;
$height = isset($_GET["height"]) ? intval($_GET["height"]) : null;
$dl = isset($_GET["dl"]) ? filter_var($_GET['dl'], FILTER_VALIDATE_BOOLEAN) : false;

if ($code != null){
    if ($height != null) {
        $barcode = new GtinCode($code, $height);
    } else {
        $barcode = new GtinCode($code);
    }
    $barcode->get($dl);
} else {
    $example_url = $_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
    echo <<< EOT
    <!doctype html>
    <html lang="en" class="h-100">
      <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>GTIN SVG API</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
        <style>
          body {
            text-shadow: 0 .05rem .1rem rgba(0, 0, 0, .5);
            box-shadow: inset 0 0 5rem rgba(0, 0, 0, .5);
          }
          .cover-container {
            max-width: 50em;
          }
          .btn-secondary,
          .btn-secondary:focus {
            color: var(--bs-code-color) !important;
            transition-duration: 500ms;
            text-shadow: none; /* Prevent inheritance from `body` */
          }
          .btn-secondary:hover{
            color: #333 !important;
            background-color: #fff !important;
            transition-duration: 500ms;
            text-shadow: none; /* Prevent inheritance from `body` */
          }
          ul{
            list-style-type: none;
            text-align: left;
            display: inline-block;
            padding: 1em;
          }
          li{
            display: flex;
          }
          span.badge{
            margin: auto 5px auto 0;
          }
        </style>
      </head>
      <body class="d-flex h-100 text-center text-bg-dark">
        
      <div class="cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">

      <a href="https://github.com/MPK1/php-gtin-svg-api" class="github-corner" aria-label="View source on GitHub"><svg width="80" height="80" viewBox="0 0 250 250" style="fill:#fff; color:#151513; position: absolute; top: 0; border: 0; right: 0;" aria-hidden="true"><path d="M0,0 L115,115 L130,115 L142,142 L250,250 L250,0 Z"></path><path d="M128.3,109.0 C113.8,99.7 119.0,89.6 119.0,89.6 C122.0,82.7 120.5,78.6 120.5,78.6 C119.2,72.0 123.4,76.3 123.4,76.3 C127.3,80.9 125.5,87.3 125.5,87.3 C122.9,97.6 130.6,101.9 134.4,103.2" fill="currentColor" style="transform-origin: 130px 106px;" class="octo-arm"></path><path d="M115.0,115.0 C114.9,115.1 118.7,116.5 119.8,115.4 L133.7,101.6 C136.9,99.2 139.9,98.4 142.2,98.6 C133.8,88.0 127.5,74.4 143.8,58.0 C148.5,53.4 154.0,51.2 159.7,51.0 C160.3,49.4 163.2,43.6 171.4,40.1 C171.4,40.1 176.1,42.5 178.8,56.2 C183.1,58.6 187.2,61.8 190.9,65.4 C194.5,69.0 197.7,73.2 200.1,77.6 C213.8,80.2 216.3,84.9 216.3,84.9 C212.7,93.1 206.9,96.0 205.4,96.6 C205.1,102.4 203.0,107.8 198.3,112.5 C181.9,128.9 168.3,122.5 157.7,114.1 C157.9,116.9 156.7,120.9 152.7,124.9 L141.0,136.5 C139.8,137.7 141.6,141.9 141.8,141.8 Z" fill="currentColor" class="octo-body"></path></svg></a><style>.github-corner:hover .octo-arm{animation:octocat-wave 560ms ease-in-out}@keyframes octocat-wave{0%,100%{transform:rotate(0)}20%,60%{transform:rotate(-25deg)}40%,80%{transform:rotate(10deg)}}@media (max-width:500px){.github-corner:hover .octo-arm{animation:none}.github-corner .octo-arm{animation:octocat-wave 560ms ease-in-out}}</style>

      <main class="px-3 my-auto">
          <h1 class="mb-4">GTIN SVG API</h1>
          <p class="lead">
            This is a very simple API to get a barcode as SVG for a given GTIN code.
          </p>
          <div class="mb-4 p-2" style="display: inline-block; border: 1px solid white; border-radius: 0.5rem;">
            <p class="lead">The following query parameters are supported:</p>
            <ul>
                <li><span class="badge bg-secondary">code</span><span>GTIN code (integer with 13 digits)</span></li>
                <li><span class="badge bg-secondary">height</span><span>Integer between 20 and 100 (default=60, width=113)</span></li>
                <li><span class="badge bg-secondary">dl</span><span>Set to 1 to download the svg directly (default=0)</span></li>
            </ul>
          </div>
          <p class="lead">
          <a href="$example_url?code=9099999543217&height=50" class="btn btn-lg btn-secondary fw-bold border-white bg-dark"><code>$example_url?code=9099999543217&height=50</code></a>
          </p>
      </main>

      <footer class="mt-auto text-white-50">
          <p>Made with <span class="text-white">❤️</span> in Munich by <a href="https://github.com/MPK1" class="text-white">MPK1</a></p>
      </footer>
      </div>
      </body>
    </html>
    EOT;
}
