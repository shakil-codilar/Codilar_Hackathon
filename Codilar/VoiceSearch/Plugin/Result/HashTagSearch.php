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

namespace Codilar\VoiceSearch\Plugin\Result;

use Magento\CatalogSearch\Controller\Result\Index as Subject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Search\Model\QueryFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepository;
use Psr\Log\LoggerInterface as Logger;

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
                        $shouldReturnProduct = $this->soundAiChecking($queryText, $hasTagValue);
                        if ($shouldReturnProduct === true) {
                            $productNames[] = $this->productRepository->get($product->getSku())->getName();
                        }
                    }
                }

                if (!$productNames) {
                    foreach ($productCollection as $product) {
                        $hasTagValue = $product->getCustomAttribute('hash_tag_attribute')?->getValue();
                        if ($hasTagValue) {

                            $shouldReturnProduct = $this->soundAiOccurenceChecking($queryText, $hasTagValue);
                            if ($shouldReturnProduct === true) {
                                $productNames[] = $this->productRepository->get($product->getSku())->getName();
                            }
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
    protected function soundAiChecking($queryText , $hashTagValue): bool
    {
        $hasTagStrings = explode(' ', $hashTagValue);
        // Remove the hash symbol from each hash tag
        $cleanedProductHashTags[] = array_map(function ($tag) {
            return str_replace('#', '', trim($tag));
        }, $hasTagStrings);
        //for Combined Query Text
        $combinedQueryText = implode('', preg_replace('/\s+/', '', $queryText));
        foreach ($cleanedProductHashTags as $cleanedProductHashTag) {
            foreach ($cleanedProductHashTag as $cleanedHash) {
                $pattern = "/\b" . preg_quote($combinedQueryText, '/') . "\b/i"; // /i for case-insensitive search
                if (preg_match($pattern, $cleanedHash)) {
                    return true;
                }
            }
        }

        //for each string of Query Text
        foreach ($queryText as $query) {
            foreach ($cleanedProductHashTags as $cleanedProductHashTag) {
                foreach ($cleanedProductHashTag as $cleanedHash) {
                    //removing white spaces from $query
                    //checking the HashTag contains the query
                    // Use regular expression with word boundaries (\b) to match the whole word
                    $pattern = "/\b" . preg_quote($query, '/') . "\b/i"; // /i for case-insensitive search
                    if (preg_match($pattern, $cleanedHash)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * @param $queryText
     * @param $hashTagValue
     * @return bool
     */
    protected function soundAiOccurenceChecking($queryText , $hashTagValue): bool
    {
        $hashTagValue = preg_replace('/[^a-zA-Z0-9]+/', ' ', $hashTagValue);
        $hasTagStrings = explode(' ', $hashTagValue);
        // Remove the hash symbol from each hash tag
        $cleanedProductHashTags[] = array_map(function ($tag) {
            return str_replace('#', '', trim($tag));
        }, $hasTagStrings);
        //does not matches any loginc then checking for occurences of Query
        //for each string of Query Text
        $occurenceCheckingFor = '';
        foreach ($queryText as $query) {
            foreach ($cleanedProductHashTags as $cleanedProductHashTag) {
                foreach ($cleanedProductHashTag as $cleanedHash) {
                    if (strlen($query) > 3) {

                        $allCharactersExist = true;
                        $micCharactersCount = [];
                        $hashCharactersCount = [];
                        $occurenceCheckingFor = '';
                        // Count the occurrences of each character in string of mic
                        for ($i = 0; $i < strlen($query); $i++) {
                            $char = $query[$i];
                            if (!isset($micCharactersCount[$char])) {
                                $micCharactersCount[$char] = 1;
                            } else {
                                $micCharactersCount[$char]++;
                            }
                        }

                        // Count the occurrences of each character in string hash
                        for ($i = 0; $i < strlen($cleanedHash); $i++) {
                            $char = $cleanedHash[$i];
                            if (!isset($hashCharactersCount[$char])) {
                                $hashCharactersCount[$char] = 1;
                            } else {
                                $hashCharactersCount[$char]++;
                            }
                        }

                        if(count($micCharactersCount) > count($hashCharactersCount)){
                            $occurenceCheckingFor = 'mic';
                            foreach($micCharactersCount as $mickey => $miccharacter){
                                foreach($hashCharactersCount as $hashkey => $hashcharacter){
                                    if($micCharactersCount[$mickey]){
                                        if($hashkey == $mickey){
                                            $micCharactersCount[$mickey] = $miccharacter - $hashcharacter;
                                            // $micCharactersCount[$mickey]--;
                                        }
                                    }
                                }
                            }
                        }else{
                            $occurenceCheckingFor = 'hash';
                            foreach($micCharactersCount as $mickey => $miccharacter){
                                foreach($hashCharactersCount as $hashkey => $hashcharacter){
                                    if($micCharactersCount[$mickey]){
                                        if($hashkey == $mickey){
                                            $hashCharactersCount[$hashkey] = $hashcharacter-$miccharacter;
                                            // $micCharactersCount[$mickey]--;
                                        }
                                    }
                                }
                            }
                        }

                        //find the occurences after comparing with hash value
                        $finalOccurance = 0;
                        if($occurenceCheckingFor == 'mic'){
                            foreach($micCharactersCount as $micSoundCalculate){
                                $finalOccurance += $micSoundCalculate;
                            }
                        }else{
                            foreach($hashCharactersCount as $hashSoundCalculate){
                                $finalOccurance += $hashSoundCalculate;
                            }
                        }
                        if($finalOccurance <2 && $finalOccurance >-2){
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }
}







