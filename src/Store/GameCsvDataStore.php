<?php

namespace App\Store;

use phpDocumentor\Reflection\Types\Boolean;

class GameCsvDataStore
{
    /**
     * @param string $gameCvsDataStorePath
     * @author Solofo RAKOTONDRAE
     */
    public function __construct(
        private string $gameCvsDataStorePath
    ){
        if(!is_readable($this->gameCvsDataStorePath)){
            throw new \InvalidArgumentException(sprintf(
                'Given data store file is unreadable, "%s" given',
                $this->gameCvsDataStorePath
            ));
        }
    }

    /**
     * @return \Generator
     */
    public function fetchAll() {
        $handle = fopen($this->gameCvsDataStorePath, 'r');
        if ($handle === false){
            throw new \RuntimeException(sprintf(
                'Couldnt open data store file at path "%s"' ,
                $this->gameCvsDataStorePath
            ));
        }
        try{
            //Reads first line as it contains data header
            $header = fgetcsv($handle);

            while(($data = fgetcsv($handle)) !== false){
                /*yield [
                    'id'            => 0,       //non renseigné dans le csv
                    'name'          => $data[0],//Game Title
                    'released'      => $data[1],//Release Date
                    'publisher'     => $data[2],//Publisher ?
                    'ageMinimum'    => $data[3],//Release Date
                    'soloMode'      => $data[4],//Release Date
                ];*/
                yield array_combine(
                    $header,
                    $data
                );
            }
        } catch (\Exception $e){
            throw  $e;
        } finally {
            fclose($handle);
        }

        return true;
    }
}