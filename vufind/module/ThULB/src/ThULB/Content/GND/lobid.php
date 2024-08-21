<?php

namespace ThULB\Content\GND;

use VuFind\Content\AbstractBase;
use VuFindCode\ISBN;

class lobid extends AbstractBase
{
    public function loadByIsbn($key, ISBN $isbnObj) {
        throw new \Exception("Not supported");
    }

    public function loadByGND(string $gnd) : array {
        try {
            $client = $this->getHttpClient('https://lobid.org/gnd/' . $gnd . '.json');
            $response = $client->send();
            if($response->getStatusCode() == 200) {
                return json_decode($response->getBody(), true);
            }
        }
        catch (\Exception $e) {
            $this->logError($e->getMessage());
        }

        return [];
    }

    public function getSameAs(string $gnd, array $sources = ['DNB', 'dewiki', 'enwiki', 'DDB']) : array {
        $data = $this->loadByGND($gnd);

        $result = [];
        foreach($data['sameAs'] ?? [] as $sameAs) {
            $abbr = $sameAs['collection']['abbr'] ?? false;
            if(in_array($abbr, $sources)) {
                $result[$abbr] = [
                    'name' => $this->getName($abbr, $sameAs['collection']['name']),
                    'icon' => $sameAs['collection']['icon'],
                    'url' => $sameAs['id'],
                ];
            }
        }

        return $result;
    }

    protected function getName(string $abbr, string $default) : string {
        $names = [
            'DNB' => 'Deutsche Nationalbibliothek'
        ];

        return $names[$abbr] ?? $default;
    }
}