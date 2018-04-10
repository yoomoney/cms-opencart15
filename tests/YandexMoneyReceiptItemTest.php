<?php

require_once __DIR__ . '/controller.php';
require_once __DIR__ . '/../src/catalog/controller/payment/yamoney.php';

class YandexMoneyReceiptItemTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param array $options
     * @return YandexMoneyReceiptItem
     */
    public function getTestInstance(array $options)
    {
        if (empty($options['tax'])) {
            return new YandexMoneyReceiptItem(
                $options['title'],
                $options['quantity'],
                $options['price'],
                $options['shipping'],
                null
            );
        } else {
            return new YandexMoneyReceiptItem(
                $options['title'],
                $options['quantity'],
                $options['price'],
                $options['shipping'],
                $options['tax']
            );
        }
    }

    /**
     * @dataProvider validDataProvider
     * @param array $options
     */
    public function testGetPrice($options)
    {
        $instance = $this->getTestInstance($options);
        self::assertSame(round($options['price'], 2), $instance->getPrice());
    }

    /**
     * @dataProvider validDataProvider
     * @param array $options
     */
    public function testGetAmount($options)
    {
        $instance = $this->getTestInstance($options);
        self::assertSame(round(round($options['price'], 2) * $options['quantity'], 2), $instance->getAmount());
    }

    /**
     * @dataProvider validDataProvider
     * @param array $options
     */
    public function testGetTitle($options)
    {
        $instance = $this->getTestInstance($options);
        if (mb_strlen($options['title'], 'utf-8') <= 60) {
            self::assertEquals($options['title'], $instance->getTitle());
        } else {
            self::assertEquals(mb_substr($options['title'], 0, 60, 'utf-8'), $instance->getTitle());
        }

    }

    /**
     * @dataProvider validDataProvider
     * @param array $options
     */
    public function testGetQuantity($options)
    {
        $instance = $this->getTestInstance($options);
        self::assertSame((float)$options['quantity'], $instance->getQuantity());
    }

    /**
     * @dataProvider validDataProvider
     * @param array $options
     */
    public function testGetTaxId($options)
    {
        $instance = $this->getTestInstance($options);
        if (empty($options['tax'])) {
            self::assertFalse($instance->hasTaxId());
            self::assertNull($instance->getTaxId());
        } else {
            self::assertTrue($instance->hasTaxId());
            self::assertSame((int)$options['tax'], $instance->getTaxId());
        }
    }

    /**
     * @dataProvider validDataProvider
     * @param array $options
     */
    public function testIsShipping($options)
    {
        $instance = $this->getTestInstance($options);
        if ($options['shipping']) {
            self::assertTrue($instance->isShipping());
        } else {
            self::assertFalse($instance->isShipping());
        }
    }

    /**
     * @dataProvider validDataProvider
     * @param array $options
     */
    public function testApplyDiscountCoefficient($options)
    {
        $price = round($options['price'], 2);
        foreach (array(0.333333, 0.6666666, 0.9999999, 0.123) as $discount) {
            $instance = $this->getTestInstance($options);
            $instance->applyDiscountCoefficient($discount);
            self::assertSame(round($price * $discount, 2), $instance->getPrice());
        }
    }

    /**
     * @dataProvider validDataProvider
     * @param array $options
     */
    public function testIncreasePrice($options)
    {
        $price = round($options['price'], 2);
        foreach (array(1, 0.333333, 0, -0.3333333, -1) as $diff) {
            $instance = $this->getTestInstance($options);
            $instance->increasePrice($diff);
            self::assertSame(round($price + $diff, 2), $instance->getPrice());
        }
    }

    /**
     * @dataProvider validDataProvider
     * @param array $options
     */
    public function testFetchItem($options)
    {
        $instance = $this->getTestInstance($options);
        $item = $instance->fetchItem(0.001);
        self::assertTrue($item instanceof YandexMoneyReceiptItem);
        self::assertSame($instance->getTitle(), $item->getTitle());
        self::assertSame($instance->getPrice(), $item->getPrice());
        self::assertSame($instance->getTaxId(), $item->getTaxId());

        self::assertSame(0.001, $item->getQuantity());
        self::assertSame($options['quantity'] - 0.001, $instance->getQuantity());
    }

    public function validDataProvider()
    {
        return array(
            array(
                array(
                    'title' => 'title',
                    'quantity' => 1,
                    'price' => 1,
                    'shipping' => false,
                ),
            ),
            array(
                array(
                    'title' => '',
                    'quantity' => 3.3333,
                    'price' => 1.001,
                    'shipping' => true,
                    'tax' => 1,
                ),
            ),
            array(
                array(
                    'title' => 'Long long title here with some " <tags> or closed </tags> да ещё и с русским текстом: у попа была собака он её любил она съела кусок мяса он её убил, в землю закопал на могиле написал',
                    'quantity' => 7.76543,
                    'price' => 1.3333333333333,
                    'shipping' => false,
                    'tax' => 1,
                ),
            ),
            array(
                array(
                    'title' => 'another title',
                    'quantity' => '0.333333',
                    'price' => '9.99999',
                    'shipping' => true,
                    'tax' => 1,
                ),
            ),
        );
    }
}