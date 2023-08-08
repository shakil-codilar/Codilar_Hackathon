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

namespace Codilar\VoiceSearch\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Codilar\VoiceSearch\Logger\Logger;
use Magento\Framework\Filesystem\Io\File as IoFIle;

class SetHashTag implements ObserverInterface
{
    /**
     * Quantity of words of Api
     */
    const RELATED_WORDS_QUANTITY = 1000;

    /**
     * @param CategoryRepositoryInterface $categoryRepository
     * @param IoFIle $ioFile
     * @param Logger $logger
     */
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly IoFIle $ioFile,
        private readonly Logger                      $logger
    )
    {
    }

    /**
     * @param Observer $observer
     * @return $this|void
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        try {
            $product = $observer->getProduct();
            if ($product) {
                $productName = $product->getName();
                $productSku = $product->getSku();
                $productDescription = $product->getShortDescription() ? strip_tags($product->getShortDescription()) : '';

                //removing spaces to create hash tag with whole name
                $productNameWords = $this->removeSpaces($productName);
                $categoryIds = $product->getCategoryIds();
                $categoryNames = $this->getCategoryNames($categoryIds);
                //removing spaces to create hash tag with whole sku
                $productSkuWords = $this->removeSpaces($productSku);
                $combinedCategoriesName = $this->removeSpaces($categoryNames);
                $categoryRelatedWords = $this->getCategoryRelatedWords($categoryNames);


                if ($productDescription) {
                    //only Selecting Words with Uppercases and converting that array to string with lowercase
                    preg_match_all('/\b[A-Z]+\b/', $productDescription, $matches);
                    $selectedDescriptionWord = strtolower(implode(' ', $matches[0]));

                    //removing spaces to create hash tag with whole sku
                    $selectedCombinedDescription = $this->removeSpaces($selectedDescriptionWord);
                    //Separate all the texts and convert to hashtags for unique words
                    $separatedText = $productName . ' ' . $productSku . ' ' . $selectedDescriptionWord . ' ' . $categoryNames . ' ' . $categoryRelatedWords;
                    //Combined Name, Sku and Desc and convert into hashtag
                    $combinedText = $productNameWords . ' ' . $productSkuWords . ' ' .
                        $selectedCombinedDescription . ' ' . $combinedCategoriesName . ' ' . $categoryRelatedWords;
                } else {
                    //Separate all the texts and convert to hashtags for unique words
                    $separatedText = $productName . ' ' . $productSku . ' ' . $categoryNames . ' ' . $categoryRelatedWords;
                    //Combined Name and Sku and convert into hashtag
                    $combinedText = $productNameWords . ' ' . $productSkuWords . ' ' . $combinedCategoriesName . ' ' . $categoryRelatedWords;
                }

                $hashtagSepartedText = $this->convertWordsToHashtags($separatedText);
                $hashtagCombinedText = $this->convertWordsToHashtags($combinedText);

                $hasTagText = array_unique(array_merge($hashtagSepartedText, $hashtagCombinedText));
                $hasTagString = implode(' ', $hasTagText);
                $this->logger->info('Started Saving the hashtags related product words');
                $product->setHashTagAttribute($hasTagString);
            }
            return $this;
        } catch (\Exception $exception) {
            $this->logger->info($exception->getMessage());
        }
    }

    /**
     * @param $text
     * @return string[]
     */
    private function convertWordsToHashtags($text): array
    {
        $words = explode(' ', $text);
        //prevent of getting same hash text
        $uniqueWords = array_unique($words);

        // Remove empty and null values at the end text (If we save product with space after product name)
        $cleanedWords = array_filter($uniqueWords, function ($value) {
            return $value !== null && $value !== '';
        });
        return array_map(function ($cleanedWords) {
            return '#' . $cleanedWords;
        }, $cleanedWords);
    }

    /**
     * @param $word
     * @return array|string[]
     */
    protected function relatedWordsOfCategoryName($word): array
    {
        $wordsArray = [];
        // datamuse api json request
        $request = $this->ioFile->read('https://api.datamuse.com/words?ml=' . $word . '&max=' . self::RELATED_WORDS_QUANTITY);
        if($request) {
            $response = json_decode($request);

            // getting the words from array
            $i = 0;
            while ($i < count($response)) {
                if ($response[$i]->word != "") {
                    $wordsArray[] = $response[$i]->word;
                }
                $i++;
            }
        }

            //getting only related words which contains category name
            $relatedWords = array_filter($wordsArray, function ($text) use ($word) {
                // Use stripos for case-insensitive search
                return stripos($text, $word) !== false;
            });

            if (!$relatedWords) {
                return array_slice($wordsArray, 0, 5, true);
            }
            return $relatedWords;
    }

    /**
     * @param $name
     * @return array|string|null
     */
    public function removeSpaces($name): array|string|null
    {
        return preg_replace('/\s+/', '', $name);
    }

    /**
     * @param $categoryIds
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCategoryNames($categoryIds): string
    {
        $categoryNames = [];
        foreach ($categoryIds as $categoryId) {
            $category = $this->categoryRepository->get($categoryId);
            if ($category->getName() != 'Default Category') {
                $categoryNames[] = $category->getName();
            }
        }
        return implode(' ', $categoryNames);
    }

    /**
     * @param $categoryString
     * @return string
     */
    protected function getCategoryRelatedWords($categoryString): string
    {
        $relatedWordsFromAPi = [];
        $categoryNames = explode(' ', $categoryString);
        foreach ($categoryNames as $categoryName) {
            $relatedWordsFromAPi = array_merge($relatedWordsFromAPi, $this->relatedWordsOfCategoryName($categoryName));
        }

        return implode(' ', $relatedWordsFromAPi);
    }
}
