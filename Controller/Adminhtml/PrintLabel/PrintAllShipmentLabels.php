<?php

namespace Wexo\Budbee\Controller\Adminhtml\PrintLabel;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Throwable;
use Wexo\Budbee\Model\Api;

class PrintAllShipmentLabels extends Action
{
    public function __construct(
        Context $context,
        private readonly Api $api,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly FileFactory $fileFactory,
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        if ($orderId) {
            $order = $this->orderRepository->get($orderId);
            $shipmentCollection = $order->getShipmentsCollection();

            $shipmentIds = [];
            $finalPdf = new \Zend_Pdf();
            try {
                foreach ($shipmentCollection as $shipment) {
                    if ($labelUrl = $this->api->getShipmentLabel($shipment)) {
                        $client = new \Zend_Http_Client($labelUrl);
                        $pdfContent = $client->request()->getBody();
                        $parsed = \Zend_Pdf::parse($pdfContent);

                        foreach ($parsed->pages as $page) {
                            $clonedPage = clone $page;
                            $finalPdf->pages[] = $clonedPage;
                        }
                    } else {
                        throw new LocalizedException(
                            __(
                                'Could not retrieve the shipment label for shipment: ' .
                                $shipment->getIncrementId()
                            )
                        );
                    }
                    $shipmentIds[] = $shipment->getId();
                }
                $this->fileFactory->create(
                    implode('-', $shipmentIds) . '-shipping-labels.pdf',
                    $finalPdf->render(),
                    DirectoryList::TMP,
                    'application/pdf'
                );
            } catch (Throwable) {
                $this->_redirect($this->getUrl('sales/order/view/order_id/' . $orderId));
            }
        }
    }
}
