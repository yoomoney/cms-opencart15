<?php

use YooKassa\Client;
use YooKassa\Common\Exceptions\ApiException;
use YooKassa\Common\Exceptions\BadApiRequestException;
use YooKassa\Common\Exceptions\ExtensionNotFoundException;
use YooKassa\Common\Exceptions\ForbiddenException;
use YooKassa\Common\Exceptions\InternalServerError;
use YooKassa\Common\Exceptions\NotFoundException;
use YooKassa\Common\Exceptions\ResponseProcessingException;
use YooKassa\Common\Exceptions\TooManyRequestsException;
use YooKassa\Common\Exceptions\UnauthorizedException;
use YooKassa\Model\PaymentInterface;
use YooKassa\Model\PaymentStatus;
use YooKassa\Model\Receipt;
use YooKassa\Request\Payments\Payment\CreateCaptureRequest;
use YooKassa\Request\Payments\Payment\CreateCaptureRequestBuilder;
use YooMoney\Updater\Archive\BackupZip;
use YooMoney\Updater\Archive\RestoreZip;
use YooMoney\Updater\ProjectStructure\ProjectStructureReader;

require_once dirname(__FILE__) . '/../../../catalog/model/payment/yoomoney/autoload.php';

class ModelPaymentYoomoney extends Model
{
    private $paymentMethods;

    /**
     * @var Config
     */
    private $config;

    private $backupDirectory = 'yoomoney-cms-opencart15/backup';
    private $versionDirectory = 'yoomoney-cms-opencart15/updates';
    private $downloadDirectory = 'yoomoney-cms-opencart15';
    private $repository = 'yoomoney/cms-opencart15';

    private $client;

    public function init($config)
    {
        $this->config = $config;

        return $this;
    }

    public function install()
    {
        $this->preventDirectories();

        $this->log('info', 'install yoomoney module');
        $this->db->query('
            CREATE TABLE IF NOT EXISTS `'.DB_PREFIX.'yoomoney_payment` (
                `order_id`          INTEGER  NOT NULL,
                `payment_id`        CHAR(36) NOT NULL,
                `status`            ENUM(\'pending\', \'waiting_for_capture\', \'succeeded\', \'canceled\') NOT NULL,
                `amount`            DECIMAL(11, 2) NOT NULL,
                `currency`          CHAR(3) NOT NULL,
                `payment_method_id` CHAR(36) NOT NULL,
                `paid`              ENUM(\'Y\', \'N\') NOT NULL,
                `created_at`        DATETIME NOT NULL,
                `captured_at`       DATETIME NOT NULL DEFAULT \'0000-00-00 00:00:00\',

                CONSTRAINT `'.DB_PREFIX.'yoomoney_payment_pk` PRIMARY KEY (`order_id`),
                CONSTRAINT `'.DB_PREFIX.'yoomoney_payment_unq_payment_id` UNIQUE (`payment_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=UTF8 COLLATE=utf8_general_ci;
        ');

        $this->db->query('
            CREATE TABLE IF NOT EXISTS `'.DB_PREFIX.'yoomoney_product_properties` (
                `product_id`        INTEGER  NOT NULL,
                `payment_subject`   VARCHAR(256),
                `payment_mode`      VARCHAR(256),
                                
                CONSTRAINT `'.DB_PREFIX.'yoomoney_payment_pk` PRIMARY KEY (`product_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=UTF8 COLLATE=utf8_general_ci;
        ');
    }

    public function uninstall()
    {
        $this->log('info', 'uninstall yoomoney module');
        $this->db->query('DROP TABLE IF EXISTS `'.DB_PREFIX.'yoomoney_payment`;');
    }

    public function log($level, $message, $context = null)
    {
        if ($this->config === null || $this->config->get('yoomoney_kassa_debug_mode') || $this->config->get('yoomoney_wallet_debug_mode')) {
            $log     = new Log('yoomoney.log');
            $search  = array();
            $replace = array();
            if (!empty($context)) {
                foreach ($context as $key => $value) {
                    $search[]  = '{'.$key.'}';
                    $replace[] = (is_array($value)||is_object($value)) ? json_encode($value, JSON_PRETTY_PRINT) : $value;
                }
            }
            if (empty($search)) {
                $log->write('['.$level.'] - '.$message);
            } else {
                foreach ($search as $object) {
                    if (stripos($message, $object) === false) {
                        $label = trim($object, "{}");
                        $message .= " \n{$label}: {$object}";
                    }
                }
                $log->write('['.$level.'] - '.str_replace($search, $replace, $message));
            }
        }
    }

    /**
     * @param $orderId
     * @param $status
     */
    public function hookOrderStatusChange($orderId, $status)
    {
        require_once YOOMONEY_MODULE_PATH . '/YooMoneySecondReceipt.php';

        $this->load->model('sale/order');
        $orderInfo = $this->model_sale_order->getOrder($orderId);

        $secondReceipt = new YooMoneySecondReceipt($this);
        $secondReceipt->sendSecondReceipt($orderInfo, $status);
    }

    /**
     * @return YooMoneyPaymentMethod[]
     */
    public function getPaymentMethods()
    {
        if ($this->paymentMethods === null) {
            require_once YOOMONEY_MODULE_PATH . '/YooMoneyPaymentMethod.php';
            require_once YOOMONEY_MODULE_PATH . '/YooMoneyPaymentKassa.php';
            require_once YOOMONEY_MODULE_PATH . '/YooMoneyPaymentMoney.php';
            $this->paymentMethods = array(
                YooMoneyPaymentMethod::MODE_NONE    => new YooMoneyPaymentMethod($this->config),
                YooMoneyPaymentMethod::MODE_KASSA   => new YooMoneyPaymentKassa($this->config, $this->language),
                YooMoneyPaymentMethod::MODE_MONEY   => new YooMoneyPaymentMoney($this->config),
            );
        }

        return $this->paymentMethods;
    }

    /**
     * @param int $type
     *
     * @return YooMoneyPaymentMethod
     */
    public function getPaymentMethod($type)
    {
        $methods = $this->getPaymentMethods();
        if (array_key_exists($type, $methods)) {
            return $methods[$type];
        }
        echo 'Get mayment method#'.$type.PHP_EOL;

        return $methods[0];
    }

    public function getBackupList()
    {
        $result = array();

        $this->preventDirectories();
        $dir = DIR_DOWNLOAD.'/'.$this->backupDirectory;

        $handle = opendir($dir);
        while (($entry = readdir($handle)) !== false) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $ext = pathinfo($entry, PATHINFO_EXTENSION);
            if ($ext === 'zip') {
                $backup            = array(
                    'name' => pathinfo($entry, PATHINFO_FILENAME).'.zip',
                    'size' => $this->formatSize(filesize($dir.'/'.$entry)),
                );
                $parts             = explode('-', $backup['name'], 3);
                $backup['version'] = $parts[0];
                $backup['time']    = $parts[1];
                $backup['date']    = date('d.m.Y H:i:s', $parts[1]);
                $backup['hash']    = $parts[2];
                $result[]          = $backup;
            }
        }

        return $result;
    }

    public function createBackup($version)
    {
        $this->loadClasses();
        $this->preventDirectories();

        $sourceDirectory = dirname(realpath(DIR_CATALOG));
        $reader          = new ProjectStructureReader();
        $root            = $reader->readFile(dirname(__FILE__).'/yoomoney/opencart.map', $sourceDirectory);

        $rootDir  = $version.'-'.time();
        $fileName = $rootDir.'-'.uniqid('', true).'.zip';

        $dir = DIR_DOWNLOAD.'/'.$this->backupDirectory;
        if (!file_exists($dir)) {
            if (!mkdir($dir)) {
                $this->log('error', 'Failed to create backup directory: '.$dir);

                return false;
            }
        }

        try {
            $fileName = $dir.'/'.$fileName;
            $archive  = new BackupZip($fileName, $rootDir);
            $archive->backup($root);
        } catch (Exception $e) {
            $this->log('error', 'Failed to create backup: '.$e->getMessage());

            return false;
        }

        return true;
    }

    public function restoreBackup($fileName)
    {
        $this->loadClasses();
        $this->preventDirectories();

        $fileName = DIR_DOWNLOAD.'/'.$this->backupDirectory.'/'.$fileName;
        if (!file_exists($fileName)) {
            $this->log('error', 'File "'.$fileName.'" not exists');

            return false;
        }

        try {
            $sourceDirectory = dirname(realpath(DIR_CATALOG));
            $archive         = new RestoreZip($fileName);
            $archive->restore('file_map.map', $sourceDirectory);
        } catch (Exception $e) {
            $this->log('error', $e->getMessage());
            if ($e->getPrevious() !== null) {
                $this->log('error', $e->getPrevious()->getMessage());
            }

            return false;
        }

        return true;
    }

    public function removeBackup($fileName)
    {
        $this->preventDirectories();

        $fileName = DIR_DOWNLOAD.'/'.$this->backupDirectory.'/'.str_replace(array('/', '\\'), array('', ''), $fileName);
        if (!file_exists($fileName)) {
            $this->log('error', 'File "'.$fileName.'" not exists');

            return false;
        }

        if (!unlink($fileName) || file_exists($fileName)) {
            $this->log('error', 'Failed to unlink file "'.$fileName.'"');

            return false;
        }

        return true;
    }

    public function checkModuleVersion($useCache = true)
    {
        $this->loadClasses();
        $this->preventDirectories();

        $file = DIR_DOWNLOAD.'/'.$this->downloadDirectory.'/version_log.txt';

        if ($useCache) {
            if (file_exists($file)) {
                $content = preg_replace('/\s+/', '', file_get_contents($file));
                if (!empty($content)) {
                    $parts = explode(':', $content);
                    if (count($parts) === 2) {
                        if (time() - $parts[1] < 3600 * 8) {
                            return array(
                                'tag'     => $parts[0],
                                'version' => preg_replace('/[^\d\.]+/', '', $parts[0]),
                                'time'    => $parts[1],
                                'date'    => $this->dateDiffToString($parts[1]),
                            );
                        }
                    }
                }
            }
        }

        $connector = new GitHubConnector();
        $version   = $connector->getLatestRelease($this->repository);
        if (empty($version)) {
            return array();
        }

        $cache = $version.':'.time();
        file_put_contents($file, $cache);

        return array(
            'tag'     => $version,
            'version' => preg_replace('/[^\d\.]+/', '', $version),
            'time'    => time(),
            'date'    => $this->dateDiffToString(time()),
        );
    }

    public function downloadLastVersion($tag, $useCache = true)
    {
        $this->loadClasses();
        $this->preventDirectories();

        $dir = DIR_DOWNLOAD.'/'.$this->versionDirectory;
        if (!file_exists($dir)) {
            if (!mkdir($dir)) {
                $this->log('error', 'Не удалось создать директорию '.$dir);

                return false;
            }
        } elseif ($useCache) {
            $fileName = $dir.'/'.$tag.'.zip';
            if (file_exists($fileName)) {
                return $fileName;
            }
        }

        $connector = new GitHubConnector();
        $fileName  = $connector->downloadRelease($this->repository, $tag, $dir);
        if (empty($fileName)) {
            $this->log('error', $this->language->get('updater_log_text_load_failed'));

            return false;
        }

        return $fileName;
    }

    public function unpackLastVersion($fileName)
    {
        if (!file_exists($fileName)) {
            $this->log('error', 'File "'.$fileName.'" not exists');

            return false;
        }

        try {
            $sourceDirectory = dirname(realpath(DIR_CATALOG));
            $archive         = new RestoreZip($fileName);
            $archive->restore('opencart.map', $sourceDirectory);
        } catch (Exception $e) {
            $this->log('error', $e->getMessage());
            if ($e->getPrevious() !== null) {
                $this->log('error', $e->getPrevious()->getMessage());
            }

            return false;
        }

        return true;
    }

    public function getChangeLog($currentVersion, $newVersion)
    {
        $connector = new GitHubConnector();

        $dir          = DIR_DOWNLOAD.'/'.$this->downloadDirectory;
        $newChangeLog = $dir.'/CHANGELOG-'.$newVersion.'.md';
        if (!file_exists($newChangeLog)) {
            $fileName = $connector->downloadLatestChangeLog($this->repository, $dir);
            if (!empty($fileName)) {
                rename($dir.'/'.$fileName, $newChangeLog);
            }
        }

        $oldChangeLog = $dir.'/CHANGELOG-'.$currentVersion.'.md';
        if (!file_exists($oldChangeLog)) {
            $fileName = $connector->downloadLatestChangeLog($this->repository, $dir);
            if (!empty($fileName)) {
                rename($dir.'/'.$fileName, $oldChangeLog);
            }
        }

        $result = '';
        if (file_exists($newChangeLog)) {
            $result = $connector->diffChangeLog($oldChangeLog, $newChangeLog);
        }

        return $result;
    }

    private function formatSize($size)
    {
        static $sizes = array(
            'B',
            'kB',
            'MB',
            'GB',
            'TB',
        );

        $i = 0;
        while ($size > 1024) {
            $size /= 1024.0;
            $i++;
        }

        return number_format($size, 2, '.', ',').'&nbsp;'.$sizes[$i];
    }

    private function loadClasses()
    {
        if (!class_exists('GitHubConnector')) {
            $path = dirname(__FILE__).'/yoomoney/Updater/';
            require_once $path.'GitHubConnector.php';
            require_once $path.'ProjectStructure/EntryInterface.php';
            require_once $path.'ProjectStructure/DirectoryEntryInterface.php';
            require_once $path.'ProjectStructure/FileEntryInterface.php';
            require_once $path.'ProjectStructure/AbstractEntry.php';
            require_once $path.'ProjectStructure/DirectoryEntry.php';
            require_once $path.'ProjectStructure/FileEntry.php';
            require_once $path.'ProjectStructure/ProjectStructureReader.php';
            require_once $path.'ProjectStructure/ProjectStructureWriter.php';
            require_once $path.'ProjectStructure/RootDirectory.php';
            require_once $path.'Archive/BackupZip.php';
            require_once $path.'Archive/RestoreZip.php';
        }
    }

    private function dateDiffToString($timestamp)
    {
        return date('d.m.Y H:i', $timestamp);
    }

    private function preventDirectories()
    {
        $this->checkDirectory(DIR_DOWNLOAD.'/'.$this->downloadDirectory);
        $this->checkDirectory(DIR_DOWNLOAD.'/'.$this->backupDirectory);
        $this->checkDirectory(DIR_DOWNLOAD.'/'.$this->versionDirectory);
    }

    private function checkDirectory($directoryName)
    {
        if (!file_exists($directoryName)) {
            mkdir($directoryName);
        }
        if (!is_dir($directoryName)) {
            throw new RuntimeException('Invalid configuration: "'.$directoryName.'" is not directory');
        }
        $this->checkFile($directoryName, 'index.php');
        $this->checkFile($directoryName, '.htaccess');
    }

    private function checkFile($directoryName, $fileName)
    {
        $testFile = $directoryName.'/'.$fileName;
        if (!file_exists($testFile)) {
            copy(dirname(__FILE__).'/yoomoney/'.$fileName, $testFile);
        }
    }

    public function getPayments($offset = 0, $limit = 20)
    {
        $res = $this->db->query('SELECT * FROM `'.DB_PREFIX.'yoomoney_payment` ORDER BY `order_id` DESC LIMIT '.(int)$offset.', '.(int)$limit);
        if ($res->num_rows) {
            return $res->rows;
        }

        return array();
    }

    public function countPayments()
    {
        $res = $this->db->query('SELECT COUNT(*) AS `count` FROM `'.DB_PREFIX.'yoomoney_payment`');
        if ($res->num_rows) {
            return $res->row['count'];
        }

        return 0;
    }

    /**
     * @param YooMoneyPaymentKassa $paymentMethod
     * @param $payments
     *
     * @return PaymentInterface[]
     */
    public function updatePaymentsStatuses(YooMoneyPaymentKassa $paymentMethod, $payments)
    {
        $result = array();

        $this->getPaymentMethods();
        $client   = $this->getClient($paymentMethod);
        $statuses = array(
            PaymentStatus::PENDING,
            PaymentStatus::WAITING_FOR_CAPTURE,
        );
        foreach ($payments as $payment) {
            if (in_array($payment['status'], $statuses)) {
                try {
                    $paymentObject = $client->getPaymentInfo($payment['payment_id']);
                    if ($paymentObject === null) {
                        $this->updatePaymentStatus($payment['payment_id'],
                            PaymentStatus::CANCELED);
                    } else {
                        $result[] = $paymentObject;
                        if ($paymentObject->getStatus() !== $payment['status']) {
                            $this->updatePaymentStatus($payment['payment_id'], $paymentObject->getStatus(),
                                $paymentObject->getCapturedAt());
                        }
                    }
                } catch (\Exception $e) {
                    // nothing to do
                }
            }
        }

        return $result;
    }

    /**
     * @param int $orderId
     * @param array $orderInfo
     * @param PaymentInterface $payment
     * @param int $statusId
     */
    public function confirmOrderPayment($orderId, $orderInfo, $payment, $statusId)
    {
        $this->hookOrderStatusChange($orderId, $statusId);

        $sql     = 'UPDATE `'.DB_PREFIX.'order_history` SET `comment` = \'Платёж подтверждён\' WHERE `order_id` = '
                   .(int)$orderId.' AND `order_status_id` <= 1';
        $comment = 'Номер транзакции: '.$payment->getId().'. Сумма: '.$payment->getAmount()->getValue()
                   .' '.$payment->getAmount()->getCurrency();
        $this->db->query($sql);

        $orderInfo['order_status_id'] = $statusId;
        $this->updateOrderStatus($orderId, $orderInfo);
        $this->updateOrderHistory($orderId, $orderInfo['order_status_id'], $comment);
    }

    /**
     * @param $order_id
     * @param $order_info
     *
     * @return bool
     */
    public function updateOrderStatus($order_id, $order_info)
    {
        if ($order_info && !empty($order_info['order_status_id'])) {
            $sql = "UPDATE `".DB_PREFIX."order` SET order_status_id = '".(int)$order_info['order_status_id']
                   ."', date_modified = NOW() WHERE order_id = '".(int)$order_id."'";

            try {
                $data = $this->db->query($sql);
                return !empty($data);
            } catch (Exception $e) {
                $this->log('error', $e->getMessage());
            }

            return false;
        }
    }

    /**
     * @param $orderId
     * @param $status
     * @param $comment
     *
     * @return bool
     */
    public function updateOrderHistory($orderId, $status, $comment)
    {
        $sql = "INSERT INTO ".DB_PREFIX."order_history SET order_id = '".(int)$orderId
            ."', order_status_id = '".(int)$status."', notify = 0, comment = '"
            .$this->db->escape($comment)."', date_added = NOW()";

        try {
            $data = $this->db->query($sql);
            return !empty($data);
        } catch (Exception $e) {
            $this->log('error', $e->getMessage());
        }

        return false;
    }

    /**
     * @param YooMoneyPaymentKassa $paymentMethod
     * @param PaymentInterface $payment
     * @param $order
     *
     * @return bool
     */
    public function capturePayment(YooMoneyPaymentKassa $paymentMethod, $payment, $order)
    {
        $client = $this->getClient($paymentMethod);
        try {
            $builder = CreateCaptureRequest::builder();
            $amount  = $this->currency->format($order['total'], 'RUB', '', false);
            $builder->setAmount($amount);
            $this->setReceiptItems($builder, $order);
            $request = $builder->build();
            $receipt = $request->getReceipt();
            if ($receipt instanceof Receipt) {
                $receipt->normalize($request->getAmount());
            }
            $result = $client->capturePayment($request, $payment->getId());
            if ($result === null) {
                throw new RuntimeException('Failed to capture payment after 3 retries');
            }
            if ($result->getStatus() !== $payment->getStatus()) {
                $this->updatePaymentStatus($payment->getId(), $result->getStatus(), $result->getCapturedAt());
            }
        } catch (Exception $e) {
            $this->log('error', 'Failed to capture payment: '.$e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * @param YooMoneyPaymentKassa $paymentMethod
     * @param PaymentInterface $payment
     *
     * @return bool
     */
    public function cancelPayment(YooMoneyPaymentKassa $paymentMethod, $payment)
    {
        $client = $this->getClient($paymentMethod);
        try {
            $result = $client->cancelPayment($payment->getId());
            if ($result === null) {
                throw new RuntimeException('Failed to capture payment after 3 retries');
            }
            if ($result->getStatus() !== $payment->getStatus()) {
                $this->updatePaymentStatus($payment->getId(), $result->getStatus(), $result->getCapturedAt());
            }
        } catch (Exception $e) {
            $this->log('error', 'Failed to cancel payment: '.$e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * @param YooMoneyPaymentKassa $paymentMethod
     * @param $orderId
     * @return PaymentInterface|null
     * @throws ApiException
     * @throws BadApiRequestException
     * @throws ForbiddenException
     * @throws InternalServerError
     * @throws NotFoundException
     * @throws ResponseProcessingException
     * @throws TooManyRequestsException
     * @throws UnauthorizedException|ExtensionNotFoundException
     */
    public function getPaymentByOrderId(YooMoneyPaymentKassa $paymentMethod, $orderId)
    {
        $sql     = 'SELECT * FROM `'.DB_PREFIX.'yoomoney_payment` WHERE `order_id` = '.(int)$orderId;
        $dataSet = $this->db->query($sql);
        if (empty($dataSet->num_rows)) {
            return null;
        }
        $paymentId = $dataSet->row['payment_id'];

        $client = $this->getClient($paymentMethod);

        return $client->getPaymentInfo($paymentId);
    }

    /**
     * @param YooMoneyPaymentKassa $paymentMethod
     *
     * @return Client
     */
    public function getClient(YooMoneyPaymentKassa $paymentMethod)
    {
        if ($this->client === null) {
            $this->client = new Client();
            $this->client->setAuth($paymentMethod->getShopId(), $paymentMethod->getPassword());
            $this->client->setLogger($this);
            $userAgent = $this->client->getApiClient()->getUserAgent();
            $userAgent->setCms('OpenCart', VERSION);
            $userAgent->setModule('PaymentGateway', YooMoneyPaymentMethod::MODULE_VERSION);
        }

        return $this->client;
    }

    /**
     * @param $paymentId
     * @param $status
     * @param null $capturedAt
     */
    private function updatePaymentStatus($paymentId, $status, $capturedAt = null)
    {
        $sql = 'UPDATE `'.DB_PREFIX.'yoomoney_payment` SET `status` = \''.$status.'\'';
        if ($capturedAt !== null) {
            $sql .= ', `captured_at`=\''.$capturedAt->format('Y-m-d H:i:s').'\'';
        }
        if ($status !== PaymentStatus::CANCELED && $status !== PaymentStatus::PENDING) {
            $sql .= ', `paid`=\'Y\'';
        }
        $sql .= ' WHERE `payment_id`=\''.$paymentId.'\'';
        $this->db->query($sql);
    }


    /**
     * @param CreateCaptureRequestBuilder $builder
     * @param $order
     */
    private function setReceiptItems(CreateCaptureRequestBuilder $builder, $order)
    {
        $this->load->model('catalog/product');

        if (isset($order['email'])) {
            $builder->setReceiptEmail($order['email']);
        } elseif (isset($order['phone'])) {
            $builder->setReceiptPhone($order['phone']);
        }
        $taxRates = $this->config->get('yoomoney_kassa_receipt_tax_id');

        $orderProducts = $this->model_sale_order->getOrderProducts($order['order_id']);
        foreach ($orderProducts as $prod) {
            $productInfo = $this->model_catalog_product->getProduct($prod['product_id']);
            $price       = $this->currency->format($prod['price'], 'RUB', '', false);
            if (isset($productInfo['tax_class_id'])) {
                $taxId = $productInfo['tax_class_id'];
                if (isset($taxRates[$taxId])) {
                    $builder->addReceiptItem($prod['name'], $price, $prod['quantity'], $taxRates[$taxId]);
                } else {
                    $builder->addReceiptItem($prod['name'], $price, $prod['quantity'], $taxRates['default']);
                }
            } else {
                $builder->addReceiptItem($prod['name'], $price, $prod['quantity'], $taxRates['default']);
            }
        }

        $order_totals = $this->model_sale_order->getOrderTotals($order['order_id']);
        foreach ($order_totals as $total) {
            if ($total['value'] == 0.0) {
                continue;
            }
            if (isset($total['code']) && $total['code'] === 'shipping') {
                $price = $this->currency->format($total['value'], 'RUB', '', false);
                if (isset($total['tax_class_id'])) {
                    $taxId = $total['tax_class_id'];
                    if (isset($taxRates[$taxId])) {
                        $builder->addReceiptShipping($total['title'], $price, $taxRates[$taxId]);
                    } else {
                        $builder->addReceiptShipping($total['title'], $price, $taxRates['default']);
                    }
                } else {
                    $builder->addReceiptShipping($total['title'], $price, $taxRates['default']);
                }
            }
        }
    }
}
