<?php

require_once __DIR__ . '/controller.php';
require_once __DIR__ . '/../src/catalog/controller/payment/yoomoney.php';

class YooMoneyReceiptTest extends \PHPUnit\Framework\TestCase
{
    protected function getTestInstance($taxId = 1, $currency = 'RUB')
    {
        return new YooMoneyReceipt($taxId, $currency);
    }

    public function testAddItem()
    {
        $instance = $this->getTestInstance();

        $json = $instance->jsonSerialize();
        self::assertEquals(array(), $json['items']);

        self::assertSame($instance, $instance->addItem('test', 1.33333));
        $json = $instance->jsonSerialize();
        self::assertEquals(array(
            array(
                'price' => array(
                    'amount' => '1.33',
                    'currency' => 'RUB',
                ),
                'quantity' => '1',
                'tax' => 1,
                'text' => 'test',
            )
        ), $json['items']);
        self::assertEquals(1.33, $instance->getAmount());
        self::assertEquals(1.33, $instance->getAmount(false));

        self::assertSame($instance, $instance->addItem('test2', 0.66666, 1.333333));
        $json = $instance->jsonSerialize();
        self::assertEquals(array(
            array(
                'price' => array(
                    'amount' => '1.33',
                    'currency' => 'RUB',
                ),
                'quantity' => '1',
                'tax' => 1,
                'text' => 'test',
            ),
            array(
                'price' => array(
                    'amount' => '0.67',
                    'currency' => 'RUB',
                ),
                'quantity' => '1.333333',
                'tax' => 1,
                'text' => 'test2',
            )
        ), $json['items']);
        self::assertEquals(2.22, $instance->getAmount());
        self::assertEquals(2.22, $instance->getAmount(false));


        self::assertSame($instance, $instance->addItem('test3', 0.999999, 1.666666, 2));
        $json = $instance->jsonSerialize();
        self::assertEquals(array(
            array(
                'price' => array(
                    'amount' => '1.33',
                    'currency' => 'RUB',
                ),
                'quantity' => '1',
                'tax' => 1,
                'text' => 'test',
            ),
            array(
                'price' => array(
                    'amount' => '0.67',
                    'currency' => 'RUB',
                ),
                'quantity' => '1.333333',
                'tax' => 1,
                'text' => 'test2',
            ),
            array(
                'price' => array(
                    'amount' => '1.00',
                    'currency' => 'RUB',
                ),
                'quantity' => '1.666666',
                'tax' => 2,
                'text' => 'test3',
            )
        ), $json['items']);
        self::assertEquals(3.89, $instance->getAmount());
        self::assertEquals(3.89, $instance->getAmount(false));
    }

    public function testAddShipping()
    {
        $instance = $this->getTestInstance();

        $json = $instance->jsonSerialize();
        self::assertEquals(array(), $json['items']);

        self::assertSame($instance, $instance->addShipping('shipping', 1.33333));
        $json = $instance->jsonSerialize();
        self::assertEquals(array(
            array(
                'price' => array(
                    'amount' => '1.33',
                    'currency' => 'RUB',
                ),
                'quantity' => '1',
                'tax' => 1,
                'text' => 'shipping',
            )
        ), $json['items']);
        self::assertEquals(1.33, $instance->getAmount());
        self::assertEquals(0.00, $instance->getAmount(false));

        self::assertSame($instance, $instance->addShipping('shipping2', 1.66666, 2));
        $json = $instance->jsonSerialize();
        self::assertEquals(array(
            array(
                'price' => array(
                    'amount' => '1.33',
                    'currency' => 'RUB',
                ),
                'quantity' => '1',
                'tax' => 1,
                'text' => 'shipping',
            ),
            array(
                'price' => array(
                    'amount' => '1.67',
                    'currency' => 'RUB',
                ),
                'quantity' => '1',
                'tax' => 2,
                'text' => 'shipping2',
            )
        ), $json['items']);
        self::assertEquals(3.00, $instance->getAmount());
        self::assertEquals(0.00, $instance->getAmount(false));
    }

    public function testSetCustomerContact()
    {
        $instance = $this->getTestInstance();

        $json = $instance->jsonSerialize();
        self::assertEquals('', $json['customerContact']);

        self::assertSame($instance, $instance->setCustomerContact('test@test'));
        $json = $instance->jsonSerialize();
        self::assertEquals('test@test', $json['customerContact']);

        self::assertSame($instance, $instance->setCustomerContact('79364738273'));
        $json = $instance->jsonSerialize();
        self::assertEquals('79364738273', $json['customerContact']);
    }

    public function testGetJson()
    {
        $instance = $this->getTestInstance();

        $json = $instance->getJson();
        self::assertJson('{"items":[],"customerContact":""}', $json);

        $instance->setCustomerContact('test@test');
        $json = $instance->getJson();
        self::assertJson('{"items":[],"customerContact":"test@test"}', $json);

        $instance->addShipping('&quot;shipping&quot;', 1, 2);
        $json = $instance->getJson();
        self::assertJson('{"items":[{"text":"\\"shipping\\"","price":{"amount":"1.00","currency":"RUB"},"quantity":"1","tax":2}],"customerContact":"test@test"}', $json);

        $instance->addItem('item "айтем"', 1.66666, 2, 3);
        $json = $instance->getJson();
        self::assertJson('{"items":[{"text":"\\"shipping\\"","price":{"amount":"1.00","currency":"RUB"},"quantity":"1","tax":2},{"text":"item \\"айтем\\"","price":{"amount":"1.67","currency":"RUB"},"quantity":"2","tax":3}],"customerContact":"test@test"}', $json);

        $instance->setCustomerContact('87635363728');
        $json = $instance->getJson();
        self::assertJson('{"items":[{"text":"\\"shipping\\"","price":{"amount":"1.00","currency":"RUB"},"quantity":"1","tax":2},{"text":"item \\"айтем\\"","price":{"amount":"1.67","currency":"RUB"},"quantity":"2","tax":3}],"customerContact":"87635363728"}', $json);

        $instance->setCustomerContact('87\'"test"');
        $json = $instance->getJson();
        self::assertJson('{"items":[{"text":"\\"shipping\\"","price":{"amount":"1.00","currency":"RUB"},"quantity":"1","tax":2},{"text":"item \\"айтем\\"","price":{"amount":"1.67","currency":"RUB"},"quantity":"2","tax":3}],"customerContact":"87\'\\"test\\""}', $json);
    }

    /**
     * @dataProvider normalizeDataProvider
     * @param array $items
     * @param float $amount
     * @param array $expected
     */
    public function testNormalize($items, $amount, $expected)
    {
        $instance = $this->getTestInstance();
        foreach ($items as $item) {
            if ($item['title'] === 'shipping') {
                $instance->addShipping($item['title'], $item['price']);
            } else {
                $instance->addItem($item['title'], $item['price'], $item['quantity']);
            }
        }
        self::assertSame($instance, $instance->normalize($amount));
        self::assertEquals($expected, $instance->jsonSerialize()['items']);
    }

    public function normalizeDataProvider()
    {
        return array(
            array(
                array(
                    array(
                        'title' => 'test',
                        'price' => 10.0,
                        'quantity' => 1,
                    )
                ),
                10.0,
                array(
                    array(
                        'text' => 'test',
                        'price' => array(
                            'amount' => '10.00',
                            'currency' => 'RUB',
                        ),
                        'quantity' => '1',
                        'tax' => 1,
                    ),
                ),
            ),
            array(
                array(
                    array(
                        'title' => 'test',
                        'price' => 3.33,
                        'quantity' => 3,
                    )
                ),
                10.0,
                array(
                    array(
                        'text' => 'test',
                        'price' => array(
                            'amount' => '3.33',
                            'currency' => 'RUB',
                        ),
                        'quantity' => '2',
                        'tax' => 1,
                    ),
                    array(
                        'text' => 'test',
                        'price' => array(
                            'amount' => '3.34',
                            'currency' => 'RUB',
                        ),
                        'quantity' => '1',
                        'tax' => 1,
                    ),
                ),
            ),
            array(
                array(
                    array(
                        'title' => 'product1',
                        'price' => 66.66,
                        'quantity' => 3,
                    ),
                    array(
                        'title' => 'product2',
                        'price' => 12.12,
                        'quantity' => 8,
                    ),
                    array(
                        'title' => 'product3',
                        'price' => 200,
                        'quantity' => 1,
                    ),
                ),
                400.0,
                array(
                    array(
                        'text' => 'product1',
                        'price' => array(
                            'amount' => '53.66',
                            'currency' => 'RUB',
                        ),
                        'quantity' => '3',
                        'tax' => 1,
                    ),
                    array(
                        'text' => 'product2',
                        'price' => array(
                            'amount' => '9.76',
                            'currency' => 'RUB',
                        ),
                        'quantity' => '8',
                        'tax' => 1,
                    ),
                    array(
                        'text' => 'product3',
                        'price' => array(
                            'amount' => '160.94',
                            'currency' => 'RUB',
                        ),
                        'quantity' => '1',
                        'tax' => 1,
                    ),
                ),
            ),
            array(
                array(
                    array(
                        'title' => 'test',
                        'price' => 10.0,
                        'quantity' => 1,
                    ),
                    array(
                        'title' => 'shipping',
                        'price' => 10
                    )
                ),
                20.0,
                array(
                    array(
                        'text' => 'test',
                        'price' => array(
                            'amount' => '10.00',
                            'currency' => 'RUB',
                        ),
                        'quantity' => '1',
                        'tax' => 1,
                    ),
                    array(
                        'text' => 'shipping',
                        'price' => array(
                            'amount' => '10.00',
                            'currency' => 'RUB',
                        ),
                        'quantity' => '1',
                        'tax' => 1,
                    ),
                ),
            ),
            array(
                array(
                    array(
                        'title' => 'test',
                        'price' => 3.33,
                        'quantity' => 3,
                    ),
                    array(
                        'title' => 'shipping',
                        'price' => 10
                    )
                ),
                20.0,
                array(
                    array(
                        'text' => 'test',
                        'price' => array(
                            'amount' => '3.33',
                            'currency' => 'RUB',
                        ),
                        'quantity' => '2',
                        'tax' => 1,
                    ),
                    array(
                        'text' => 'test',
                        'price' => array(
                            'amount' => '3.34',
                            'currency' => 'RUB',
                        ),
                        'quantity' => '1',
                        'tax' => 1,
                    ),
                    array(
                        'text' => 'shipping',
                        'price' => array(
                            'amount' => '10.00',
                            'currency' => 'RUB',
                        ),
                        'quantity' => '1',
                        'tax' => 1,
                    ),
                ),
            ),
            array(
                array(
                    array(
                        'title' => 'product1',
                        'price' => 66.66,
                        'quantity' => 3,
                    ),
                    array(
                        'title' => 'shipping',
                        'price' => 10
                    ),
                    array(
                        'title' => 'product2',
                        'price' => 12.12,
                        'quantity' => 8,
                    ),
                    array(
                        'title' => 'product3',
                        'price' => 200,
                        'quantity' => 1,
                    ),
                ),
                410.0,
                array(
                    array(
                        'text' => 'product1',
                        'price' => array(
                            'amount' => '53.66',
                            'currency' => 'RUB',
                        ),
                        'quantity' => '3',
                        'tax' => 1,
                    ),
                    array(
                        'text' => 'shipping',
                        'price' => array(
                            'amount' => '10.00',
                            'currency' => 'RUB',
                        ),
                        'quantity' => '1',
                        'tax' => 1,
                    ),
                    array(
                        'text' => 'product2',
                        'price' => array(
                            'amount' => '9.76',
                            'currency' => 'RUB',
                        ),
                        'quantity' => '8',
                        'tax' => 1,
                    ),
                    array(
                        'text' => 'product3',
                        'price' => array(
                            'amount' => '160.94',
                            'currency' => 'RUB',
                        ),
                        'quantity' => '1',
                        'tax' => 1,
                    ),
                ),
            ),
        );
    }
}