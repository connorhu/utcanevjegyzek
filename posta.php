<?php

if (!isset($_SERVER['argv'][1])) {
    die('usage: php '. $_SERVER['argv'][0] .' posta.xls' . PHP_EOL);
    exit;
}

require('vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

$outputDir = 'data';

$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($_SERVER['argv'][1]);

/*
// ezen a sheeten iranyitoszamok es telepules nevek vannak
// oszlopok: IRSZ	Település	Településrész
// feltelezi, hogy az elso sor a fejlec a tobbi a tartalom
$sheet = $spreadsheet->getSheet(0);
$highestRow = $sheet->getHighestRow();

for ($row = 1; $row < $highestRow; $row++) { 
    $postCode = $sheet->getCellByColumnAndRow(0, $row)->getValue();
    $city = $sheet->getCellByColumnAndRow(1, $row)->getValue();
    $cityPart = $sheet->getCellByColumnAndRow(2, $row)->getValue();
    
    $output_file = $outputDir .'/'. $city .'.json';
    
    if (!is_file($outputDir .'/'. $city .'.json')) {
        $data = [];
    }
    else {
        $data = json_decode(file_get_contents($output_file), true);
    }
    
    $data['codes'][$postCode] = [$city, $cityPart];
    
    file_put_contents($output_file, json_encode($data, JSON_PRETTY_PRINT));
} */

// ezen a sheeten budapest iranyitoszamai vannak
/*
Az utcajegyzékek 1.SZÁM - 2.SZÁM mezőjének kódolása	
	
Ha az 1.SZÁM=0, akkor az egész utca irányítószáma	
Ha az 1.SZÁM=pozitív szám, és a	
	2.SZÁM=pozitív szám, akkor az 1.SZÁM-tól a 2.SZÁM-ig intervallum
	2.SZÁM=0, akkor a 1.SZÁM-tól végig
Ha az 1.SZÁM= -1, akkor a páratlan oldal irányítószáma	
Ha az 1.SZÁM= -2, akkor a páros oldal irányítószáma	
Ha az 1.SZÁM= -3, akkor az utca többi részének irányítószáma	
*/

function processCity($sheet, $isBudapest, $outputFile)
{
    $highestRow = $sheet->getHighestRow();

    $data = [];

    for ($row = 2; $row < $highestRow; $row++) { 
        $postCode = (string) $sheet->getCellByColumnAndRow(0, $row)->getValue();
        $street = $sheet->getCellByColumnAndRow(1, $row)->getValue();
        $streetKind = $sheet->getCellByColumnAndRow(2, $row)->getValue();

        $number1 = $sheet->getCellByColumnAndRow(3, $row)->getValue();
        $sign1 = $sheet->getCellByColumnAndRow(4, $row)->getValue();
        $number2 = $sheet->getCellByColumnAndRow(5, $row)->getValue();
        $sign2 = $sheet->getCellByColumnAndRow(6, $row)->getValue();
    
        if ($isBudapest) {
            $district = (int) ($postCode{1} . $postCode{2});
        }
        else {
            $district = false;
        }
        
        
        $streetData = [
            'name' => trim($street),
            'kind' => $streetKind,
            'post_code' => (int) $postCode,
        ];
    
        if ($number1 == 0) {
            $streetData['side'] = 'whole';
        }
        else {
            if ($number1 > 0) {
                $streetData['side'] = 'range';
                $streetData['from'] = $number1;
            }
            elseif ($number1 == -1) {
                $streetData['side'] = 'odd';
            }
            elseif ($number1 == -2) {
                $streetData['side'] = 'even';
            }
            elseif ($number1 == -3) {
                $streetData['side'] = 'mixed';
            }
        
            if ($sign1) {
                $streetData['from_sign'] = $sign1;
            }

            if ($number2 > 0) {
                $streetData['to'] = $number2;
            }

            if ($sign2) {
                $streetData['to_sign'] = $sign2;
            }
        }
    
        if ($district === false) {
            $data['district'][] = $streetData;
        }
        else {
            $data['district'][$district][] = $streetData;
        }
    }

    file_put_contents($outputFile, json_encode($data, JSON_PRETTY_PRINT));
}

processCity($spreadsheet->getSheet(2), true, $outputDir .'/Budapest.json');
processCity($spreadsheet->getSheet(3), false, $outputDir .'/Miskolc.json');
processCity($spreadsheet->getSheet(4), false, $outputDir .'/Debrecen.json');
processCity($spreadsheet->getSheet(5), false, $outputDir .'/Szeged.json');
processCity($spreadsheet->getSheet(6), false, $outputDir .'/Pécs.json');
processCity($spreadsheet->getSheet(7), false, $outputDir .'/Győr.json');

