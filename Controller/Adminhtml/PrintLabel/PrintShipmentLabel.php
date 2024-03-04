<?php

namespace Wexo\Budbee\Controller\Adminhtml\PrintLabel;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Throwable;
use Wexo\Budbee\Model\Api;

class PrintShipmentLabel extends Action
{
    /**
     * @var Api
     */
    protected Api $api;
    /**
     * @var ShipmentRepositoryInterface
     */
    protected ShipmentRepositoryInterface $shipmentRepository;
    /**
     * @var FileFactory
     */
    protected FileFactory $fileFactory;

    public function __construct(
        Context $context,
        Api $api,
        ShipmentRepositoryInterface $shipmentRepository,
        FileFactory $fileFactory
    ) {
        parent::__construct($context);
        $this->api = $api;
        $this->shipmentRepository = $shipmentRepository;
        $this->fileFactory = $fileFactory;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $redirect = false;
        $shipmentId = $this->getRequest()->getParam('shipment_id');
        if ($shipment = $this->shipmentRepository->get($shipmentId)) {
            try {
                $labelUrl = $this->api->getShipmentLabel($shipment);
                $this->fileFactory->create(
                    $shipmentId . '-shippingLabel.pdf',
                    file_get_contents($labelUrl),
                    DirectoryList::TMP,
                    'application/pdf'
                );
            } catch (Throwable $e) {
                $redirect = 'sales/shipment/view/shipment_id' . $shipmentId;
            }
        }

        if ($redirect) {
            $this->_redirect($this->getUrl($redirect));
        }
    }
}
