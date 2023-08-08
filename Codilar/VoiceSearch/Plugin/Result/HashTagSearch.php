<?php
/*******************************************************************************
 * Codilar Hackathon 2023
 * Team Innovation Squad
 *
 * Copyright 2023 Codilar
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Codilar and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Codilar
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Codilar permits you to use and modify this file with few restriction
 * If you have received this file from a source other than Codilar,
 * then your use, modification, or distribution of it
 * requires the prior written permission from Codilar.
 ******************************************************************************/
declare(strict_types=1);

namespace Codilar\VoiceSearch\Plugin\Result;

use Magento\CatalogSearch\Controller\Result\Index as Subject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Search\Model\QueryFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepository;
use Codilar\VoiceSearch\Logger\Logger;

class HashTagSearch
{
    /**
     * @param PageFactory $pageFactory
     * @param QueryFactory $queryFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ProductRepository $productRepository
     * @param Logger $logger
     */
    public function __construct(
        private readonly PageFactory $pageFactory,
        private readonly QueryFactory $queryFactory,
        Private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly ProductRepository $productRepository,
        private readonly Logger $logger
    ) {
    }

    /**
     * @param Subject $subject
     * @return void $result
     * @throws NoSuchEntityException
     */
    public function beforeExecute(
        Subject $subject
    ): void
    {
        try {
            $queryArgument = $subject->getRequest()->getParam('q');
            $resultPage = $this->pageFactory->create();
            $query = $this->queryFactory->get();
            // Set cacheable property to false for layout
            $subject->getRequest()->setParam('cacheable', false);

            $queryValues = explode(' ', $queryArgument);

            //Removing white spaces from query
            $queryText = array_filter($queryValues, 'strlen');
            $productNames = [];
            $queryWords = [];
            if ($query->getNumResults() == 0) {
                $productCollectionFactory = $this->productCollectionFactory->create();
                $productCollection = $productCollectionFactory->addAttributeToSelect('hash_tag_attribute');
                foreach ($productCollection as $product) {
                    $hasTagValue = $product->getCustomAttribute('hash_tag_attribute')?->getValue();
                    if ($hasTagValue) {
                        // Remove special characters
                        $cleanedHashString = preg_replace('/[^a-zA-Z\s]/', '', $hasTagValue);
                        $shouldReturnProduct = $this->soundAiOccurenceChecking($queryText, $cleanedHashString);
                        if ($shouldReturnProduct === true) {
                            $productNames[] = $this->productRepository->get($product->getSku())->getName();
                        }
                    }
                }
                if ($productNames) {
                    foreach ($productNames as $productName) {
                        if (str_word_count($productName) > 1) {
                            $words = explode(" ", $productName);
                            for ($i = 0; $i < 2; $i++) {
                                $queryWords[] = $words[$i];
                            }
                        }
                    }
                    $this->logger->info('started to add products in search after getting no products with default search');
                    $queryString = implode('+', $queryWords);
                    $query->setQueryText($queryString);
                }
            }
        } catch (\Exception $exception) {
            $this->logger->info($exception->getMessage());
        }
    }

    /**
     * @param $queryText
     * @param $hashTagValue
     * @return bool
     */
    protected function soundAiOccurenceChecking($queryText , $hashTagValue): bool
    {
        $showProductStatus = false;
        $hashTagValue = explode(' ' , $hashTagValue);
        //for each string of Query Text
        foreach ($queryText as $query) {
                foreach ($hashTagValue as $hashTag) {
                    $a = $hashTag;//hash tag val
                    $b = $query;//mic input

                    $micCharactersCount = [];
                    $hashCharactersCount = [];
                    $occurenceCheckingFor = '';
                    $firstCharsOfMic = substr($b, 0, 1);
                    $firstCharOfHash = substr($a, 0, 1);
                    $lengthGap = 0;
                    if(strlen($b) > strlen($a)){
                        $lengthGap = strlen($b)- strlen($a);
                    }else{
                        $lengthGap = strlen($a)-strlen($b);
                    }
                    // Count the occurrences of each character in string of mic
                    if (strtolower($firstCharsOfMic) == strtolower($firstCharOfHash)) {
                        if( $lengthGap < 4 &&  strlen($b) > 2 && strlen($a) > 1){
                            for ($i = 0; $i < strlen($b); $i++) {
                                $char = $b[$i];
                                if (!isset($micCharactersCount[$char])) {
                                    $micCharactersCount[$char] = 1;
                                } else {
                                    $micCharactersCount[$char]++;
                                }
                            }

                            // Count the occurrences of each character in string hash
                            for ($i = 0; $i < strlen($a); $i++) {
                                $char = $a[$i];
                                if (!isset($hashCharactersCount[$char])) {
                                    $hashCharactersCount[$char] = 1;
                                } else {
                                    $hashCharactersCount[$char]++;
                                }
                            }

                            foreach($micCharactersCount as $mickey => $miccharacter){
                                foreach($hashCharactersCount as $hashkey => $hashcharacter){
                                    if(count($micCharactersCount) < count($hashCharactersCount)){
                                        $occurenceCheckingFor = 'hash';
                                        if($hashkey == $mickey && $miccharacter < 3){
                                            $micCharactersCount[$mickey] = $hashcharacter - $miccharacter;
                                            // $micCharactersCount[$mickey]--;
                                        }
                                    }elseif(count($micCharactersCount) > count($hashCharactersCount)){
                                        $occurenceCheckingFor = 'mic';
                                        if($hashkey == $mickey && $miccharacter < 3){
                                            $hashCharactersCount[$hashkey] = $miccharacter - $hashcharacter;
                                        }
                                    }
                                }
                            }

                            //find the occurences after comparing with hash value
                            $finalOccurance = 0;
                            if($occurenceCheckingFor == 'hash'){
                                foreach($micCharactersCount as $micSoundCalculate){
                                    $finalOccurance += $micSoundCalculate;
                                }
                            }else{
                                foreach($hashCharactersCount as $hashSoundCalculate){
                                    $finalOccurance += $hashSoundCalculate;
                                }
                            }

                            if($finalOccurance < 3 ){
                                return true;
                            }elseif ($finalOccurance > -3){
                                return true;
                            }
                        }
                    }
            }
        }
      return false;
    }
}







