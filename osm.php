<?php

#jtart table tr td:first-child a

require('vendor/autoload.php');

function loadLinkAndQuery($link, $query)
{
    $client = new GuzzleHttp\Client();
    $res = $client->request('GET', $link);
    
    if ($res->getStatusCode() != 200) {
        throw new \Exception('invalid response status code');
    }
    
    $doc = new DOMDocument();
    @$doc->loadHTML($res->getBody());

    $xpath = new DOMXpath($doc);
    return $xpath->query($query);
}

function splitStreetName($street) {
    $lastWord = strrpos($street, ' ');
    if ($lastWord === false) {
        return [$street, ''];
    }
    
    $kind = substr($street, $lastWord+1);
    
    $listOfKinds = array_map('trim', file('kozterulet.txt'));
    
    if (!in_array($kind, $listOfKinds)) {
        var_dump($street .' '. $kind);
        return [$street, ''];
    }
    
    return [trim(str_replace($kind, '', $street)), $kind];
}

$outputDir = 'data';
$nodes = loadLinkAndQuery('https://data2.openstreetmap.hu/utcastat.php', '//*[@id=\'jtart\']//table//tr/td[1]/a');
$alreadyFinished = ['Budapest', 'Miskolc', 'Debrecen', 'Szeged', 'Pécs', 'Győr'];

sleep(1);

foreach ($nodes as $node) {
    $city = trim($node->textContent);
    $listOfStreets = $node->getAttribute('href');
    
    if (in_array($city, $alreadyFinished)) {
        continue;
    }
    
    echo $city . PHP_EOL;
    
    $streetNodes = loadLinkAndQuery($listOfStreets, '//*[@id=\'jtart\']//table//tr/td[1]/a');
    $outputFile = $outputDir .'/'. $city .'.json';
    
    if (!is_file($outputFile)) {
        $data = [];
    }
    else {
        $data = json_decode(file_get_contents($outputFile), true);
    }
    
    $data['streets'] = [];
    
    foreach ($streetNodes as $streetNode) {
        $street = $streetNode->textContent;

        if (strlen($street) == 0) {
            continue;
        }
        
        list($streetName, $kind) = splitStreetName($street);

        $streetData = [
            'name' => $streetName,
            'kind' => $kind,
            'post_code' => 0,
        ];
        
        $data['streets'][] = $streetData;
    }
    
    file_put_contents($outputFile, json_encode($data, JSON_PRETTY_PRINT));
    sleep(1);
}
