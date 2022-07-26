# PHP GTIN SVG API

## Introduction

After searching too long for a php library that can create proper GTIN/EAN13 barcodes according to ISO/IEC 15420, I decided to write my own.
It is lightweight and creates SVG's for your GTIN codes (if they are valid).
I got some good ideas from [tc-lib-barcode](https://github.com/tecnickcom/tc-lib-barcode), a nice PHP barcode library that unfortunately only creates ugly GTIN/EAN13 codes, that are not suitable for serious applications.

## Usage

The library only consists of one single `index.php` file.\
The following GET parameters are available:
* `code`
  * format: integer (exactly 13 digits)
  * the 13-digit GTIN/EAN13 code with valid check digit
* `height`
  * format: integer (between 20 and 100)
  * default: 60
  * defines the height of the returned image. The width is always 113, so height=57 would generate an image with almost 2:1 ratio.
  * a height of 60 or more is required for omni-directional barcode scanning.
* `dl`
  * format: boolean
  * possible values: [1, 0, true, false, on, off]
  * default: false
  * set to true/on/1 to download the SVG instead of viewing it
  
## Example
  
Go to [https://gtin.mpk1.de](https://gtin.mpk1.de) for a demo (do not use this demo page for other purposes than testing this library).
