<?php

namespace OroCRM\Bundle\MagentoBundle\ImportExport\Writer;

use Oro\Bundle\IntegrationBundle\Exception\TransportException;
use OroCRM\Bundle\MagentoBundle\Entity\Address;
use OroCRM\Bundle\MagentoBundle\Entity\Customer;

class CustomerExportWriter extends AbstractExportWriter
{
    const CUSTOMER_ID_KEY = 'customer_id';
    const FAULT_CODE_NOT_EXISTS = 102;

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        /** @var Customer $entity */
        $entity = $this->getEntity();
        if ($this->getStateManager()->isInState($entity->getSyncState(), Customer::MAGENTO_REMOVED)) {
            return;
        }

        $item = reset($items);

        if (!$item) {
            $this->logger->error('Wrong Customer data', (array)$item);

            return;
        }

        $this->transport->init($this->getChannel()->getTransport());
        if (empty($item[self::CUSTOMER_ID_KEY])) {
            $this->writeNewItem($item);
        } else {
            $this->writeExistingItem($item);
        }

        parent::write([$entity]);
    }

    /**
     * @param array $item
     */
    protected function writeNewItem(array $item)
    {
        /** @var Customer $entity */
        $entity = $this->getEntity();
        try {
            $customerId = $this->transport->createCustomer($item);
            $entity->setOriginId($customerId);
            $this->markSynced($entity);

            $this->logger->info(
                sprintf('Customer with id %s successfully created with data %s', $customerId, json_encode($item))
            );
        } catch (TransportException $e) {
            if ($e->getFaultCode() === self::FAULT_CODE_NOT_EXISTS) {
                $this->markRemoved($entity);
            }

            $this->logger->error($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * @param array $item
     */
    protected function writeExistingItem(array $item)
    {
        /** @var Customer $entity */
        $entity = $this->getEntity();

        $customerId = $item[self::CUSTOMER_ID_KEY];

        try {
            $result = $this->transport->updateCustomer($customerId, $item);

            if ($result) {
                $this->markSynced($entity);

                $this->logger->info(
                    sprintf('Customer with id %s successfully updated with data %s', $customerId, json_encode($item))
                );
            } else {
                $this->logger->error(sprintf('Customer with id %s was not updated', $customerId));
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return;
        }
    }

    /**
     * @param Customer $customer
     */
    protected function markRemoved(Customer $customer)
    {
        $this->getStateManager()->addState($customer, 'syncState', Customer::MAGENTO_REMOVED);
        $this->markAddressesRemoved($customer);
    }

    /**
     * @param Customer $customer
     */
    protected function markSynced(Customer $customer)
    {
        $this->getStateManager()->removeState($customer, 'syncState', Customer::SYNC_TO_MAGENTO);
        $this->markAddressesForSync($customer);
    }

    /**
     * @param Customer $customer
     */
    protected function markAddressesForSync(Customer $customer)
    {
        $stateManager = $this->getStateManager();
        if (!$customer->getAddresses()->isEmpty()) {
            foreach ($customer->getAddresses() as $address) {
                $stateManager->addState($address, 'syncState', Address::SYNC_TO_MAGENTO);
            }
        }
    }

    /**
     * @param Customer $customer
     */
    protected function markAddressesRemoved(Customer $customer)
    {
        $stateManager = $this->getStateManager();
        if (!$customer->getAddresses()->isEmpty()) {
            foreach ($customer->getAddresses() as $address) {
                $stateManager->addState($address, 'syncState', Address::MAGENTO_REMOVED);
            }
        }
    }
}
