<?php

namespace SwagProductAssoc;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Tools\SchemaTool;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use SwagProductAssoc\Models\Attribute;
use SwagProductAssoc\Models\Product;
use SwagProductAssoc\Models\Variant;

class SwagProductAssoc extends Plugin
{
    /**
     * {@inheritdoc}
     */
    public function install(InstallContext $installContext)
    {
        $this->createDatabase();

        $this->addDemoData();
    }

    /**
     * {@inheritdoc}
     */
    public function activate(ActivateContext $activateContext)
    {
        $activateContext->scheduleClearCache(ActivateContext::CACHE_LIST_DEFAULT);
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(UninstallContext $uninstallContext)
    {
        if (!$uninstallContext->keepUserData()) {
            $this->removeDatabase();
        }
    }

    private function createDatabase()
    {
        $modelManager = $this->container->get('models');
        $tool = new SchemaTool($modelManager);

        $classes = [
            $modelManager->getClassMetadata(Product::class),
            $modelManager->getClassMetadata(Variant::class),
            $modelManager->getClassMetadata(Attribute::class)
        ];

        $tool->updateSchema($classes, true); // make sure use the save mode
    }

    private function removeDatabase()
    {
        $modelManager = $this->container->get('models');
        $tool = new SchemaTool($modelManager);

        $classes = [
            $modelManager->getClassMetadata(Product::class),
            $modelManager->getClassMetadata(Variant::class),
            $modelManager->getClassMetadata(Attribute::class)
        ];

        $tool->dropSchema($classes);
    }

    private function addDemoData()
    {
        $connection = $this->container->get('dbal_connection');

        $this->createProductDemoData($connection);
        $this->createProductVariantDemoData($connection);
        $this->createProductAttributeDemoData($connection);

        $sql = "
            SET FOREIGN_KEY_CHECKS = 0;
            INSERT IGNORE INTO s_product_categories (product_id, category_id)
            SELECT
              a.articleID as product_id,
              a.categoryID as category_id
            FROM s_articles_categories a
        ";

        $connection->exec($sql);
    }

    private function createProductDemoData(Connection $connection)
    {
        $sql = 'INSERT IGNORE INTO s_product (id, name, active, description, descriptionLong, lastStock, createDate, tax_id)
            SELECT
                a.id,
                a.name,
                a.active,
                a.description,
                a.description_long as descriptionLong,
                a.laststock as lastStock,
                a.datum as createDate,
                a.taxID as tax_id
            FROM s_articles a
        ';

        $connection->exec($sql);
    }

    private function createProductVariantDemoData(Connection $connection)
    {
        $sql = 'SET FOREIGN_KEY_CHECKS = 0;
            INSERT IGNORE INTO s_product_variant (id, product_id, number, additionalText, active, inStock, stockMin, weight)
            SELECT
              a.id,
              a.articleID,
              a.ordernumber,
              a.additionaltext,
              a.active,
              a.instock,
              a.stockmin,
              a.weight
            FROM s_articles_details a
        ';

        $connection->exec($sql);
    }

    private function createProductAttributeDemoData(Connection $connection)
    {
        $sql = 'SET FOREIGN_KEY_CHECKS = 0;
            INSERT IGNORE INTO s_product_attribute
            SELECT
              a.id,
              a.articleID as product_id,
              a.attr1,
              a.attr2,
              a.attr3,
              a.attr4,
              a.attr5
            FROM s_articles_attributes a
        ';

        $connection->exec($sql);
    }
}