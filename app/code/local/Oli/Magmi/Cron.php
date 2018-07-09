<?php
/**
 * Created by PhpStorm.
 * User: Oli
 * Date: 02/11/2017
 * Time: 17:05
 */

/* Change importer to the name of your Magmi directory */
require_once(Mage::getBaseDir() . "/importer/plugins/inc/magmi_datasource.php");
require_once(Mage::getBaseDir() . "/importer/integration/inc/productimport_datapump.php");

class Oli_Magmi_Cron {

    const LOG_DIRECTORY = "oli-extensions.log";

    public function runMagmi()
    {


        try {
            $directory = Mage::getBaseDir('var') . '/import/';
            $magmiCsvFile = $directory . 'magmiUploadReady.csv';

            if(!file_exists($magmiCsvFile)) {
                throw new Exception('CSV file for Magmi to upload cant be found.');
            }
        } catch (Exception $e) {
            $message = date('F jS (l) Y h:i:s A') . " === MAGMI (" . __METHOD__ . ") === Could not load todays magmiupload file. It must not have run today. This script will now stop: ".$e->getMessage();
            Mage::log($message, null, self::LOG_DIRECTORY);
            die();
        }

        $productArray = $this->csvToArray($magmiCsvFile);

        $indexes = 'catalog_product_attribute,catalog_product_price,cataloginventory_stock';
        $mode = 'update';
        $profile = 'cronUpdatePrices';
        try {
            $this->import($productArray, $mode, $profile);
        } catch (Exception $e) {
            $message = date('F jS (l) Y h:i:s A') . " === MAGMI (" . __METHOD__ . ") === There was an issue importing the products: ".$e->getMessage();
            Mage::log($message, null, self::LOG_DIRECTORY);
        }
        try {
            $this->reindex($indexes);
        } catch (Exception $e) {
            $message = date('F jS (l) Y h:i:s A') . " === MAGMI (" . __METHOD__ . ") === There is a problem with the reindex function: ".$e->getMessage();
            Mage::log($message, null, self::LOG_DIRECTORY);
        }
        /* log that it actually ran */
        $message = date('F jS (l) Y h:i:s A') . " === MAGMI (" . __METHOD__ . ") === Has finished running";
        Mage::log($message, null, self::LOG_DIRECTORY);

    }



    public function csvToArray($filename)
    {
        $productArray = array_map('str_getcsv', file($filename));
        array_shift($productArray);
        $newProductArray = array();
        /* Customise the item array index's to match the file you are importing */
        foreach ($productArray as $key => $item) {
            $newProductArray[$key]['sku'] = $item[0];
            $newProductArray[$key]['buy_price'] = $item[1];
            $newProductArray[$key]['price'] = $item[2];
            $newProductArray[$key]['store'] = $item[3];
            $newProductArray[$key]['price_updated'] = $item[4];
            $newProductArray[$key]['amazon_euros'] = $item[5];
            $newProductArray[$key]['amazon_dollars'] = $item[6];
            $newProductArray[$key]['qty'] = $item[7];
            $newProductArray[$key]['is_in_stock'] = $item[8];

        }
        return $newProductArray;
    }



    private function import($items, $mode, $profile)
    {
        if (count($items) > 0) {
            try{

                $dp = new Magmi_ProductImport_DataPump();
                $dp->beginImportSession($profile, $mode);
                foreach ($items as $item) {
                    $dp->ingest($item);
                }
                $dp->endImportSession();

            } catch (Exception $e) {
                $message = date('F jS (l) Y h:i:s A') . " === MAGMI (" . __METHOD__ . ") === Seems to be an issue with the import function: ".$e->getMessage();
                Mage::log($message, null, self::LOG_DIRECTORY);
            }
        }
    }

    private function reindex($string)
    {
        /** @var $indexer Mage_Index_Model_Indexer */
        $indexer = Mage::getModel('index/indexer');

        $processes = array();

        if ($string == 'all') {
            $processes = $indexer->getProcessesCollection();
        } else {
            $codes = explode(',', $string);
            foreach ($codes as $code) {
                $process = $indexer->getProcessByCode(trim($code));
                if ($process) {
                    $processes[] = $process;
                }
            }
        }

        /** @var $process Mage_Index_Model_Process */
        foreach ($processes as $process) {
            $process->reindexEverything();
        }
    }
}