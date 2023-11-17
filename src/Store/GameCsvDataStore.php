<?php

namespace App\Store;

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
    public function fetchAll(){
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
    }
}