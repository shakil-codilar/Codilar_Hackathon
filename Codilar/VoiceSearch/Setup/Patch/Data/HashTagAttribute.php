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

namespace Codilar\VoiceSearch\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Validator\ValidateException;
use Psr\Log\LoggerInterface as Logger;

class HashTagAttribute implements DataPatchInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param Logger $logger
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory $eavSetupFactory,
        private readonly Logger $logger
    ) {
    }

    /**
     * @return void
     * @throws LocalizedException
     * @throws ValidateException
     */
    public function apply()
    {
        try {
            /** @var EavSetup $eavSetup */
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

            $eavSetup->addAttribute(
                Product::ENTITY,
                'hash_tag_attribute',
                [
                    'type' => 'text',
                    'label' => 'Hash Tag Attribute',
                    'input' => 'textarea',
                    'is_visible_in_grid' => true,
                    'is_html_allowed_on_front' => false,
                    'visible_on_front' => true,
                    'visible' => true,
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'source' => '',
                    'searchable' => true,
                    'filterable' => true,
                    'used_in_product_listing' => true,
                    'user_defined' => true,
                    'is_used_in_grid' => true,
                    'required' => false,
                    'is_filterable_in_grid' => true,
                    'sort_order' => 60,
                    'group' => 'General',
                ]
            );
        } catch (\Exception $exception) {
            $this->logger->info($exception->getMessage());
        }
    }

    /**
     * Get array of patches that have to be executed prior to this.
     *
     * Example of implementation:
     *
     * [
     *      \Vendor_Name\Module_Name\Setup\Patch\Patch1::class,
     *      \Vendor_Name\Module_Name\Setup\Patch\Patch2::class
     * ]
     *
     * @return string[]
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Get aliases (previous names) for the patch.
     *
     * @return string[]
     */
    public function getAliases()
    {
        return [];
    }
}
