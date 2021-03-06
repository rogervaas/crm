<?php

namespace OroCRM\Bundle\MagentoBundle\Migrations\Schema\v1_34;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddCustomerIndices implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orocrm_magento_customer');
        $table->addIndex(['email'], 'magecustomer_email_guest_idx', []);
    }
}
