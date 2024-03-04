<?php
namespace Wexo\Budbee\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class DeliveryOptions implements OptionSourceInterface
{
    /**
     * Get options for multiselect field
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 1, 'label' => __('Budbee Home')],
            ['value' => 2, 'label' => __('Budbee Box')],
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            1 => __('Budbee Home'),
            2 => __('Budbee Box'),
        ];
    }
}
