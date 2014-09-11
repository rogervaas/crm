<?php

namespace OroCRM\Bundle\CampaignBundle\Transport;

use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use OroCRM\Bundle\CampaignBundle\Entity\EmailCampaign;

class EmailTransport implements TransportInterface
{
    /**
     * @var Processor $processor
     */
    protected $processor;

    /**
     * @var EmailRenderer
     */
    protected $emailRenderer;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var EmailAddressHelper
     */
    protected $emailAddressHelper;

    /**
     * @param Processor          $processor
     * @param EmailRenderer      $emailRenderer
     * @param DoctrineHelper     $doctrineHelper
     * @param EmailAddressHelper $emailAddressHelper
     */
    public function __construct(
        Processor $processor,
        EmailRenderer $emailRenderer,
        DoctrineHelper $doctrineHelper,
        EmailAddressHelper $emailAddressHelper
    ) {
        $this->processor          = $processor;
        $this->emailRenderer      = $emailRenderer;
        $this->doctrineHelper     = $doctrineHelper;
        $this->emailAddressHelper = $emailAddressHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function send(EmailCampaign $campaign, $entity, array $from, array $to)
    {
        $entityId      = $this->doctrineHelper->getSingleEntityIdentifier($entity);
        $marketingList = $campaign->getMarketingList();

        $template = $campaign->getTemplate();
        list ($subjectRendered, $templateRendered) = $this->emailRenderer->compileMessage(
            $template,
            ['entity' => $entity]
        );

        $emailModel = new Email();
        $emailModel
            ->setType($template->getType())
            ->setFrom($this->buildFullEmailAddress($from))
            ->setEntityClass($marketingList->getEntity())
            ->setEntityId($entityId)
            ->setTo($to)
            ->setSubject($subjectRendered)
            ->setBody($templateRendered);

        $this->processor->process($emailModel);
    }

    /**
     * @param array $from
     *
     * @return string
     */
    protected function buildFullEmailAddress(array $from)
    {
        foreach ($from as $email => $name) {
            return $this->emailAddressHelper->buildFullEmailAddress($email, $name);
        }

        throw new \InvalidArgumentException('Sender email and name is empty');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'internal';
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayName()
    {
        return 'Internal';
    }
}
