<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Controller\Adminhtml\Waypoint;

use Improntus\Uber\Api\Data\StoreInterfaceFactory;
use Improntus\Uber\Api\Data\WaypointInterface;
use Improntus\Uber\Api\Data\WaypointInterfaceFactory;
use Improntus\Uber\Api\WaypointRepositoryInterface;
use Improntus\Uber\Helper\Data;
use Improntus\Uber\Model\OrganizationRepository;
use Improntus\Uber\Model\StoreRepository as uberStoreRepository;
use Improntus\Uber\Model\Uber;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface as Json;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'Improntus_Uber::waypoint_edit';

    /**
     * @var WaypointInterfaceFactory
     */
    protected $waypointFactory;

    /**
     * @var DataObjectProcessor
     */
    protected $dataObjectProcessor;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var WaypointRepositoryInterface
     */
    protected $waypointRepository;

    /**
     * @var Data $helper
     */
    protected $helper;

    /**
     * @var Uber $uber
     */
    protected $uber;

    /**
     * @var Json $json
     */
    protected $json;

    /**
     * @var OrganizationRepository $organizationRepository
     */
    protected $organizationRepository;

    /**
     * @var Encryptor $encryptor
     */
    protected Encryptor $encryptor;

    /**
     * @var uberStoreRepository $uberStore
     */
    protected uberStoreRepository $uberStore;

    /**
     * @var StoreInterfaceFactory $uberStoreInterfaceFactory
     */
    protected StoreInterfaceFactory $uberStoreInterfaceFactory;

    /**
     * @param Context $context
     * @param OrganizationRepository $organizationRepository
     * @param WaypointInterfaceFactory $waypointFactory
     * @param WaypointRepositoryInterface $waypointRepository
     * @param DataObjectProcessor $dataObjectProcessor
     * @param DataObjectHelper $dataObjectHelper
     * @param DataPersistorInterface $dataPersistor
     * @param Registry $registry
     * @param Data $helper
     * @param Uber $uber
     * @param Json $json
     * @param Encryptor $encryptor
     * @param uberStoreRepository $uberStore
     * @param StoreInterfaceFactory $uberStoreInterfaceFactory
     */
    public function __construct(
        Context $context,
        OrganizationRepository $organizationRepository,
        WaypointInterfaceFactory $waypointFactory,
        WaypointRepositoryInterface $waypointRepository,
        DataObjectProcessor $dataObjectProcessor,
        DataObjectHelper $dataObjectHelper,
        DataPersistorInterface $dataPersistor,
        Registry $registry,
        Data $helper,
        Uber $uber,
        Json $json,
        Encryptor $encryptor,
        uberStoreRepository $uberStore,
        StoreInterfaceFactory $uberStoreInterfaceFactory
    ) {
        $this->organizationRepository = $organizationRepository;
        $this->waypointFactory = $waypointFactory;
        $this->waypointRepository = $waypointRepository;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataPersistor = $dataPersistor;
        $this->registry = $registry;
        $this->helper = $helper;
        $this->uber = $uber;
        $this->json = $json;
        $this->encryptor = $encryptor;
        $this->uberStore = $uberStore;
        $this->uberStoreInterfaceFactory = $uberStoreInterfaceFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        /** @var WaypointInterface $waypoint */
        $waypoint = null;
        $postData = $this->getRequest()->getPostValue();
        $data = $postData;
        $id = !empty($data['waypoint_id']) ? $data['waypoint_id'] : null;
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            if ($id) {
                $waypoint = $this->waypointRepository->get((int)$id);
            } else {
                unset($data['waypoint_id']);
                $waypoint = $this->waypointFactory->create();
            }
            $this->dataObjectHelper->populateWithArray($waypoint, $data, WaypointInterface::class);
            $this->waypointRepository->save($waypoint);
            $this->messageManager->addSuccessMessage(__('The Waypoint has been saved.'));

            /**
             * Sync Source
             */
            $syncSource = true;

            /**
             * Generate Hash Validation
             */
            $storeHash = $this->generateStoreHash($waypoint);

            /**
             * Get Store by Waypoint Id
             */
            $uberStore = $this->uberStore->getByWaypoint($waypoint->getId());
            if ($uberStore) {
                // Compare Hash
                $syncSource = $this->compareHash($storeHash, $uberStore->getHash());

                // Delete Current Store
                if ($syncSource) {
                    try {
                        $this->uberStore->delete($uberStore);
                    } catch (CouldNotSaveException|StateException $e) {
                        $this->helper->log('Waypoint Delete ' . $e->getMessage());
                    }
                }
            }

            /**
             * Sync Store with Uber
             */
            if ($syncSource) {
                // Create new Store
                try {
                    $uberStoreModel = $this->uberStoreInterfaceFactory->create();
                    $uberStoreModel->setHash($storeHash);
                    $uberStoreModel->setWaypointId($waypoint->getWaypointId());
                    $this->uberStore->save($uberStoreModel);

                    // Get Entity
                    $externalStoreId = $uberStoreModel->getId();

                    // Create in Uber
                    $addressData = json_encode([
                        'street_address' => [$waypoint->getAddress()],
                        'city' => $waypoint->getCity(),
                        'state' => $waypoint->getRegion(),
                        'zip_code' => $waypoint->getPostcode(),
                        'country' => $waypoint->getCountry(),
                    ], JSON_UNESCAPED_SLASHES);

                    $requestData = [
                        'pickup_address'    => $addressData,
                        'dropoff_address'   => $addressData,
                        'external_store_id' => $externalStoreId
                    ];
                    $organizationId = $this->getOrganization($waypoint->getOrganizationId(), $waypoint->getStoreId());
                    $this->uber->getEstimateShipping($requestData, $organizationId, $waypoint->getStoreId());
                } catch (CouldNotSaveException $e) {
                    $this->helper->log('Save Waypoint CREATE in Uber ' . $e->getMessage());
                }
            }

            $this->dataPersistor->clear('uber_waypoint');
            if ($this->getRequest()->getParam('back')) {
                $resultRedirect->setPath('*/*/edit', ['waypoint_id' => $waypoint->getId()]);
            } else {
                $resultRedirect->setPath('*/*');
            }
        } catch (LocalizedException $e) {
            $this->helper->log(__('UBER Save Waypoint ERROR: %1', $e->getMessage()));
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->dataPersistor->set('uber_waypoint', $postData);
            $resultRedirect->setPath('*/*/edit', ['waypoint_id' => $id]);
        } catch (\Exception $e) {
            $this->helper->log(__('UBER Save Waypoint ERROR: %1', $e->getMessage()));
            $this->messageManager->addErrorMessage(__('There was a problem saving the Waypoint'));
            $this->dataPersistor->set('improntus\uber_waypoint', $postData);
            $resultRedirect->setPath('*/*/edit', ['waypoint_id' => $id]);
        }
        return $resultRedirect;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }

    /**
     * Get Organization
     *
     * @param $organizationId
     * @param $storeId
     * @return mixed|string
     * @throws NoSuchEntityException
     */
    private function getOrganization($organizationId, $storeId = null)
    {
        if (str_contains($organizationId, 'W') !== false) {
            // Use ROOT Organization from Shipping Configuration
            [$letter, $websiteId] = explode('W', $organizationId);
            return $this->helper->getCustomerId($websiteId);
        }
        // Get from Organization
        $organizationModel = $this->organizationRepository->get($organizationId);
        if ($organizationModel->getId() === null) {
            throw new \Exception(__("It was not possible to create the Waypoint in Uber. Try again later"));
        }
        return $organizationModel->getUberOrganizationId();
    }

    /**
     * Compare Hash
     *
     * @param $newHash
     * @param $hash
     * @return bool
     */
    private function compareHash($newHash, $hash): bool
    {
        return ($newHash !== $hash);
    }

    /**
     * Generate Store Hash
     *
     * @param WaypointInterface $waypoint
     * @return string
     */
    private function generateStoreHash(WaypointInterface $waypoint): string
    {
        return $this->encryptor->hash("{$waypoint->getAddress()}-{$waypoint->getCity()}-{$waypoint->getPostcode()}-{$waypoint->getRegion()}-{$waypoint->getCountry()}");
    }
}
