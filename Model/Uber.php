<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model;

use Exception;
use Improntus\Uber\Api\Data\OrganizationInterface;
use Improntus\Uber\Api\Data\TokenInterface;
use Improntus\Uber\Api\OrganizationRepositoryInterface;
use Improntus\Uber\Helper\Data;
use Improntus\Uber\Model\ResourceModel\Token\CollectionFactory as TokenCollection;
use Improntus\Uber\Model\ResourceModel\TokenFactory as TokenModelFactory;
use Improntus\Uber\Model\Rest\Webservice;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\Framework\Locale\Resolver;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Uber
{
    protected const SCOPE_TOKEN_ORGANIZATION = 'direct.organizations';

    protected const SCOPE_TOKEN_DELIVERIES = 'eats.deliveries';

    protected const TOKEN_EXPIRATION_DAYS = 30;

    protected const UBER_AUTH_ENDPOINT = 'https://login.uber.com/oauth/v2/token';

    /**
     * @var Data $helper
     */
    protected Data $helper;

    /**
     * @var Resolver $store
     */
    protected Resolver $store;

    /**
     * @var Webservice $ws
     */
    protected Webservice $ws;

    /**
     * @var TokenCollection $tokenCollection
     */
    protected TokenCollection $tokenCollection;

    /**
     * @var TokenFactory $tokenFactory
     */
    protected TokenFactory $tokenFactory;

    /**
     * @var TimezoneInterface $timezone
     */
    protected TimezoneInterface $timezoneInterface;

    /**
     * @var DateTime $dateTime
     */
    protected DateTime $dateTime;

    /**
     * @var TokenModelFactory $tokenModelFactory
     */
    protected TokenModelFactory $tokenModelFactory;

    /**
     * @var SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var OrganizationRepositoryInterface $organizationRepository
     */
    protected OrganizationRepositoryInterface $organizationRepository;

    /**
     * @param Data $helper
     * @param Webservice $ws
     * @param Resolver $storeInterface
     * @param TokenCollection $tokenCollection
     * @param TokenFactory $tokenFactory
     * @param TimezoneInterface $timezoneInterface
     * @param DateTime $dateTime
     * @param TokenModelFactory $tokenModelFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrganizationRepositoryInterface $organizationRepository
     */
    public function __construct(
        Data $helper,
        Webservice $ws,
        Resolver $storeInterface,
        TokenCollection $tokenCollection,
        TokenFactory $tokenFactory,
        timezoneInterface $timezoneInterface,
        DateTime $dateTime,
        TokenModelFactory $tokenModelFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrganizationRepositoryInterface $organizationRepository
    ) {
        $this->ws = $ws;
        $this->helper = $helper;
        $this->dateTime = $dateTime;
        $this->store = $storeInterface;
        $this->tokenFactory = $tokenFactory;
        $this->tokenCollection = $tokenCollection;
        $this->tokenModelFactory = $tokenModelFactory;
        $this->timezoneInterface = $timezoneInterface;
        $this->organizationRepository = $organizationRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * createOrganization
     * @param $userData
     * @param int|null $storeId
     * @return mixed
     * @throws Exception
     */
    public function createOrganization($userData, int|null $storeId)
    {
        // Get Access Token
        $token = $this->getAccessToken($storeId, self::SCOPE_TOKEN_ORGANIZATION);

        // Has data?
        if ($token === null) {
            throw new Exception(__("An error occurred while validating/generating the token"));
        }

        // Prepare Request
        $requestData = [];

        // Add Header Credentials
        $requestData['headers'] = [
            "Content-Type" => "application/json",
            "Authorization" => "Bearer {$token->getToken()}",
        ];

        // Populate Info
        $body['info'] = [
            'name' => $userData['organization_name'],
            'billing_type' => $userData['billing_type'],
            'merchant_type' => $userData['merchant_type'],
        ];

        // Populate Address ONLY billing_type is BILLING_TYPE_DECENTRALIZED
        if ($userData['billing_type'] === 'BILLING_TYPE_DECENTRALIZED') {
            $body['info']['address'] = [
                'street1' => $userData['street'],
                'street2' => $userData['street2'],
                'city' => $userData['city'],
                'state' => $userData['state'],
                'zipcode' => $userData['postcode'],
                'country_iso2' => $userData['country'],
            ];
        }

        // Populate Point of Contact Info
        $body['info']['point_of_contact'] = [
            'email' => $userData['email'],
            'phone_details' => [
                'phone_number' => $userData['phone_number'],
                'country_code' => $userData['phone_country_code'],
                'subscriber_number' => $userData['phone_number'],
            ],
        ];

        // Populate Hierarchy Info
        $parentOrganizationId = $this->helper->getCustomerId($storeId);
        $body['hierarchy_info'] = [
            'parent_organization_id' => $parentOrganizationId,
        ];

        // Populate Options
        $onBoardingLocale = str_replace('_', '-', $this->store->getDefaultLocale()) ?? 'en-us';
        $body['options'] = [
            'onboarding_invite_type' => $userData['onboarding_type'],
            'locale' => $onBoardingLocale,
        ];

        // Add Body to RequestData
        $requestData['body'] = json_encode($body);

        // Get endpoint URL
        $createOrganizationEndpoint = $this->helper->buildRequestURL('direct/organizations');

        // Send Request
        $uberRequest = $this->ws->doRequest($createOrganizationEndpoint, $requestData, "POST");

        // Get Body / Content
        $responseBody = json_decode($uberRequest->getBody()->getContents(), true);

        // Opps...
        if ($uberRequest->getStatusCode() !== 200) {
            $this->helper->log(__("ERROR: Generate Uber Organization: %1", json_encode($responseBody)));
            throw new Exception($responseBody['code']);
        }

        // Log Debug Mode
        $this->helper->logDebug("Uber Create Organization Request: " . json_encode($requestData));
        $this->helper->logDebug("Uber Create Organization Response: " . json_encode($responseBody));

        // Return Data
        return $responseBody;
    }

    /**
     * @param array $shippingData
     * @param string $organizationId
     * @param int $storeId
     * @return mixed
     * @throws Exception
     */
    public function createShipping(array $shippingData, string $organizationId, int $storeId): mixed
    {
        // Get Access Token
        $token = $this->getAccessToken($storeId);

        // Has data?
        if ($token === null) {
            throw new Exception(__("An error occurred while validating/generating the token"));
        }

        // Prepare Request
        $requestData = [];

        // Add Header Credentials
        $requestData['headers'] = [
            "Content-Type" => "application/json",
            "Authorization" => "Bearer {$token->getToken()}",
        ];

        // Add Body to RequestData
        $requestData['body'] = json_encode($shippingData);

        // Get endpoint URL
        $createShippingEndpoint = $this->helper->buildRequestURL("customers/{$organizationId}/deliveries", $storeId);

        // Send Request
        $uberRequest = $this->ws->doRequest($createShippingEndpoint, $requestData, "POST");

        // Get Response
        $responseBody = $uberRequest->getBody()->getContents();

        // Opps...
        if ($uberRequest->getStatusCode() !== 200) {
            $error = json_decode($responseBody, true);

            /**
             * There are errors that return an invalid json and can generate an error.
             * That is why if $error is null I use the 'raw' body
             */
            if ($error === null) {
                $logMsg = $responseBody;
            } else {
                $logMsg = json_encode($error);
            }

            // Write log
            $this->helper->log("ERROR: Uber Shipping Create Request - CustomerID / OrganizationID: $organizationId");
            $this->helper->log("ERROR: Uber Shipping Create Request: " . json_encode($requestData));
            $this->helper->log("ERROR: Uber Shipping Create Response: $logMsg");

            /**
             * If the error is invalid_params and metadata exists, return that message
             */
            if ((isset($error['code']) && $error['code'] === "invalid_params") && isset($error['metadata'])) {
                $metadataValues = array_values($error['metadata']);
                $exceptionMsg = $metadataValues[0] ?? $uberRequest->getReasonPhrase();
            } else {
                $exceptionMsg = $error['message'] ?? $error['code'] ?? $uberRequest->getReasonPhrase();
            }

            // Create Exception
            throw new Exception($exceptionMsg, $uberRequest->getStatusCode());
        }

        // Log Debug Mode
        $this->helper->logDebug("Uber Shipping Create Request - CustomerID / OrganizationID: $organizationId");
        $this->helper->logDebug("Uber Shipping Create Request: " . json_encode($requestData));
        $this->helper->logDebug("Uber Shipping Create Response: $responseBody");

        // Return Data
        return json_decode($responseBody, true);
    }

    /**
     * @param string $shippingId
     * @param string $organizationId
     * @param int $storeId
     * @return mixed
     * @throws Exception
     */
    public function getShipping(string $shippingId, string $organizationId, int $storeId): mixed
    {
        // Get Access Token
        $token = $this->getAccessToken($storeId);

        // Has data?
        if ($token === null) {
            throw new Exception(__("An error occurred while validating/generating the token"));
        }

        // Prepare Request
        $requestData = [];

        // Add Header Credentials
        $requestData['headers'] = [
            "Content-Type" => "application/json",
            "Authorization" => "Bearer {$token->getToken()}",
        ];

        // Get endpoint URL
        $getShippingEndpoint = $this->helper->buildRequestURL("customers/{$organizationId}/deliveries/{$shippingId}", $storeId);

        // Send Request
        $uberRequest = $this->ws->doRequest($getShippingEndpoint, $requestData, "POST");

        // Get Response
        $responseBody = $uberRequest->getBody()->getContents();

        // Opps...
        if ($uberRequest->getStatusCode() !== 200) {
            $error = json_decode($responseBody, true);

            /**
             * There are errors that return an invalid json and can generate an error.
             * That is why if $error is null I use the 'raw' body
             */
            if ($error === null) {
                $logMsg = $responseBody;
            } else {
                $logMsg = json_encode($error);
            }

            // Write log
            $this->helper->log("ERROR: Uber Shipping Get Request - CustomerID / OrganizationID: $organizationId");
            $this->helper->log("ERROR: Uber Shipping Get Request: " . json_encode($requestData));
            $this->helper->log("ERROR: Uber Shipping Get Response: $logMsg");

            /**
             * If the error is invalid_params and metadata exists, return that message
             */
            if ((isset($error['code']) && $error['code'] === "invalid_params") && isset($error['metadata'])) {
                $metadataValues = array_values($error['metadata']);
                $exceptionMsg = $metadataValues[0] ?? $uberRequest->getReasonPhrase();
            } else {
                $exceptionMsg = $error['message'] ?? $error['code'] ?? $uberRequest->getReasonPhrase();
            }

            // Create Exception
            throw new Exception($exceptionMsg, $uberRequest->getStatusCode());
        }

        // Log Debug Mode
        $this->helper->logDebug("Uber Shipping Get Request - CustomerID / OrganizationID: $organizationId");
        $this->helper->logDebug("Uber Shipping Get Request: " . json_encode($requestData));
        $this->helper->logDebug("Uber Shipping Get Response: $responseBody");

        // Return Data
        return json_decode($responseBody, true);
    }

    /**
     * cancelShipping
     * @param string $shippingId
     * @param string $organizationId
     * @param int $storeId
     * @return mixed
     * @throws Exception
     */
    public function cancelShipping(string $shippingId, string $organizationId, int $storeId): mixed
    {
        // Get Access Token
        $token = $this->getAccessToken($storeId);

        // Has data?
        if ($token === null) {
            throw new Exception(__("An error occurred while validating/generating the token"));
        }

        // Prepare Request
        $requestData = [];

        // Add Header Credentials
        $requestData['headers'] = [
            "Authorization" => "Bearer {$token->getToken()}",
        ];

        // Get endpoint URL
        $createShippingEndpoint = $this->helper->buildRequestURL(
            "customers/{$organizationId}/deliveries/{$shippingId}/cancel",
            $storeId
        );

        // Send Request
        $uberRequest = $this->ws->doRequest($createShippingEndpoint, $requestData, "POST");

        // Get Response
        $responseBody = $uberRequest->getBody()->getContents();

        // Opps...
        if ($uberRequest->getStatusCode() !== 200) {
            $error = json_decode($responseBody, true);
            $this->helper->log("ERROR: Uber Shipping Cancel - CustomerID / OrganizationID:  $organizationId");
            $this->helper->log("ERROR: Uber Shipping Cancel Request: " . json_encode($requestData));

            /**
             * There are errors that return an invalid json and can generate an error.
             * That is why if $error is null I use the 'raw' body
             */
            if ($error === null) {
                $logMsg = $responseBody;
            } else {
                $logMsg = json_encode($error);
            }
            $this->helper->log("ERROR: Uber Shipping Cancel Response: $logMsg");
            $exceptionMsg = $error['message'] ?? $error['code'] ?? $error['error'] ?? $uberRequest->getReasonPhrase();
            throw new Exception($exceptionMsg, $uberRequest->getStatusCode());
        }

        // Log Debug Mode
        $this->helper->logDebug("Uber Shipping Cancel - CustomerID / OrganizationID: $organizationId");
        $this->helper->logDebug("Uber Shipping Cancel Request: " . json_encode($requestData));
        $this->helper->logDebug("Uber Shipping Cancel Response: " . $responseBody);

        // Return Data
        return json_decode($responseBody, true);
    }

    /**
     * getEstimateShipping
     * @param array $shippingData
     * @param string $organizationId
     * @param int|null $storeId
     * @return mixed
     * @throws Exception
     */
    public function getEstimateShipping(array $shippingData, string $organizationId, int|null $storeId): mixed
    {
        // Get Access Token
        $token = $this->getAccessToken($storeId);

        // Has data?
        if ($token === null) {
            throw new Exception(__("An error occurred while validating/generating the token"));
        }

        // Prepare Request
        $requestData = [];

        // Add Header Credentials
        $requestData['headers'] = [
            "Content-Type" => "application/json",
            "Authorization" => "Bearer {$token->getToken()}",
        ];

        // Add Body to RequestData
        $requestData['body'] = json_encode($shippingData);

        // Get endpoint URL
        $shippingQuoteEndpoint = $this->helper->buildRequestURL("customers/{$organizationId}/delivery_quotes");

        // Send Request
        $uberRequest = $this->ws->doRequest($shippingQuoteEndpoint, $requestData, "POST");

        // Get Body / Content
        $responseBody = json_decode($uberRequest->getBody()->getContents(), true);

        // Opps...
        if ($uberRequest->getStatusCode() !== 200) {
            $this->helper->log("ERROR: Uber Delivery Quote CustomerID / OrganizationID: " . $organizationId);
            $this->helper->log("ERROR: Uber Delivery Quote Request: " . json_encode($requestData));
            $this->helper->log("ERROR: Uber Delivery Quote Response: " . json_encode($responseBody));
            throw new Exception($responseBody['message']);
        }

        // Log Debug Mode
        $this->helper->logDebug("Uber Delivery Quote CustomerID / OrganizationID: " . $organizationId);
        $this->helper->logDebug("Uber Delivery Quote Request: " . json_encode($requestData));
        $this->helper->logDebug("Uber Delivery Quote Response: " . json_encode($responseBody));

        // Return Data
        return $responseBody;
    }

    /**
     * getProofOfDelivery
     *
     * @param string $shippingId
     * @param string $organizationId
     * @param int $storeId
     * @return mixed
     * @throws Exception
     */
    public function getProofOfDelivery(string $shippingId, string $organizationId, int $storeId): mixed
    {
        // Get Access Token
        $token = $this->getAccessToken($storeId);

        // Has data?
        if ($token === null) {
            throw new Exception(__("An error occurred while validating/generating the token"));
        }

        // Prepare Request
        $requestData = [];

        // Add Header Credentials
        $requestData['headers'] = [
            "Content-Type" => "application/json",
            "Authorization" => "Bearer {$token->getToken()}",
        ];

        // Add Body to RequestData
        $verificationType = $this->helper->getVerificationType($storeId) === 'signature_requirement' ? 'signature' : $this->helper->getVerificationType($storeId);
        $requestData['body'] = json_encode([
            "waypoint" => "dropoff",
            "type" => $verificationType,
        ]);

        // Get endpoint URL
        $shippingPOD = $this->helper->buildRequestURL(
            "customers/{$organizationId}/deliveries/{$shippingId}/proof-of-delivery"
        );

        // Send Request
        $uberRequest = $this->ws->doRequest($shippingPOD, $requestData, "POST");

        // Get Body / Content
        $responseBody = json_decode($uberRequest->getBody()->getContents(), true);

        // Opps...
        if ($uberRequest->getStatusCode() !== 200) {
            $this->helper->log("ERROR: Uber Proof Of Delivery CustomerID / OrganizationID: " . $organizationId);
            $this->helper->log("ERROR: Uber Proof Of Delivery Request: " . json_encode($requestData));
            $this->helper->log("ERROR: Uber Proof Of Delivery Quote Response: " . json_encode($responseBody));
            throw new Exception($responseBody['code'] ?? 'Desconocido');
        }

        // Log Debug Mode
        $this->helper->logDebug("Uber Proof Of Delivery CustomerID / OrganizationID: " . $organizationId);
        $this->helper->logDebug("Uber Proof Of Delivery Request: " . json_encode($requestData));
        $this->helper->logDebug("Uber Proof Of Delivery Quote Response: " . json_encode($responseBody));

        // Return Data
        return $responseBody;
    }

    /**
     * getAccessToken
     * @param int|null $storeId
     * @param string $scope
     * @return Token|DataObject|null
     */
    public function getAccessToken(int|null $storeId = null, string $scope = self::SCOPE_TOKEN_DELIVERIES)
    {
        // Clear Collection
        $tokenCollection = $this->tokenCollection->create();

        // Get Token
        $tokenCollection->addFieldToFilter(TokenInterface::SCOPE, $scope);

        // Filter by StoreId?
        if ($storeId !== null) {
            $tokenCollection->addFieldToFilter(TokenInterface::STORE_ID, $storeId);
        }

        // Get Token & Validate
        $tokenData = $tokenCollection->getFirstItem();
        if ($tokenData->getId() === null || !$this->validateToken($tokenData)) {
            // Delete Current Access Token
            if ($tokenData->getId()) {
                try {
                    $tokenResourceModel = $this->tokenModelFactory->create();
                    $tokenResourceModel->delete($tokenData);
                } catch (\Exception $e) {
                    $this->helper->log("Uber Delete Expired Token ERROR: " . $e->getMessage());
                }
            }

            // Generate new Access Token
            $tokenData = $this->generateAccessToken($storeId, $scope);
        }

        // Return
        return $tokenData;
    }

    /**
     * checkWarehouseClosest
     *
     * @param string $customerAddress
     * @param int|null $storeId
     * @return mixed
     * @throws Exception
     */
    public function checkWarehouseClosest(string $customerAddress, int|null $storeId = null)
    {
        // Get Access Token
        $token = $this->getAccessToken($storeId, self::SCOPE_TOKEN_ORGANIZATION);

        // Has data?
        if ($token === null) {
            throw new Exception(__("An error occurred while validating/generating the token"));
        }

        // Prepare Request
        $requestData = [];

        // Get Store Locale
        $storeLocale = $this->store->getDefaultLocale() ?? 'en_US';

        // Add Header Credentials
        $requestData['headers'] = [
            "Accept-Language" => $storeLocale,
            "Content-Type" => "application/json",
            "Authorization" => "Bearer {$token->getToken()}",
        ];

        // Get all Sub organizations associated to Store
        $suborganizations = $this->getStoreSuborganizations($storeId);

        // Add ROOT Organization
        $suborganizations[] = $this->helper->getCustomerId($storeId);

        /**
         * Now you must check in all sub-organizations if there is coverage for the address in question.
         */
        $storesAllowed = [];
        foreach ($suborganizations as $organizationId) {
            // Check is valid $organizationId
            if (empty($organizationId)) {
                continue;
            }

            // Get endpoint URL
            $getWarehouseClosestEndpoint = $this->helper->buildRequestURL("direct/organizations/$organizationId/stores?address=$customerAddress");

            // Send Request
            $uberRequest = $this->ws->doRequest($getWarehouseClosestEndpoint, $requestData, "GET");

            // Get Body / Content
            $responseBody = json_decode($uberRequest->getBody()->getContents(), true);

            // Request ERROR
            if ($uberRequest->getStatusCode() !== 200) {
                $this->helper->log("Uber Stores Closest Customer Address: $customerAddress");
                $this->helper->log("Uber Stores Closest Request: " . json_encode($requestData));
                $this->helper->log(__("ERROR: Uber Stores Closest: %1", json_encode($responseBody)));
                throw new Exception($responseBody['code'] ?? '');
            }

            // Add Stores
            foreach ($responseBody['stores'] as $store) {
                $storesAllowed['stores'][] = $store;
            }
        }

        // Log Debug Mode
        $this->helper->logDebug("Uber Organization / Suborganizations: " . implode(", ", $suborganizations));
        $this->helper->logDebug("Uber Stores Closest Customer Address: $customerAddress");
        $this->helper->logDebug("Uber Stores Closest Request: " . json_encode($requestData));
        $this->helper->logDebug("Uber Stores Closest Response: " . json_encode($storesAllowed));

        // Return Data
        return $storesAllowed;
    }

    /**
     * Get Store Suborganizations
     *
     * @param int $storeId
     * @return array
     */
    public function getStoreSuborganizations(int $storeId)
    {
        $suborganizationsAllowed = [];
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(OrganizationInterface::STORE_ID, $storeId)
            ->addFilter(OrganizationInterface::ACTIVE, 1)
            ->create();
        $suborganizationsRepository = $this->organizationRepository->getList($searchCriteria);
        if ($suborganizationsRepository->getTotalCount() > 0) {
            foreach ($suborganizationsRepository->getItems() as $suborganization) {
                $suborganizationsAllowed[] = $suborganization->getUberOrganizationId();
            }
        }
        return $suborganizationsAllowed;
    }

    /**
     * generateAccessToken
     *
     * Generate AccessToken Uber API
     * @param int|null $storeId
     * @param string $scope
     * @return Token|null
     */
    private function generateAccessToken(int|null $storeId = null, string $scope = self::SCOPE_TOKEN_DELIVERIES): ?Token
    {
        // Get CustomerId / ClientId
        $clientId = $this->helper->getClientId($storeId);

        // Get Client Secret
        $clientSecret = $this->helper->getClientSecret($storeId);

        // Prepare Request Data
        $requestData = [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'form_params' => [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'client_credentials',
                'scope' => $scope,
            ],
        ];

        // Send Request
        $uberRequest = $this->ws->doRequest(self::UBER_AUTH_ENDPOINT, $requestData, "POST");

        // Get Body / Content
        $responseBody = json_decode($uberRequest->getBody()->getContents(), true);

        // Request ERROR
        if ($uberRequest->getStatusCode() !== 200) {
            $this->helper->log("ERROR: Get AccessToken Request: " . json_encode($requestData));
            $this->helper->log("Response: " . json_encode($responseBody));
            return null;
        }

        try {
            // Generate Expiration Date
            $storeCurrentDateTime = $this->timezoneInterface->date()->format('Y-m-d H:i:s');

            // Add Token Expiration Days to Current Date
            $tokenExpiration = $this->dateTime->date(
                'Y-m-d H:i:s',
                strtotime($storeCurrentDateTime . " +" . self::TOKEN_EXPIRATION_DAYS . " days")
            );

            // Save Token
            $tokenModel = $this->tokenFactory->create();
            $tokenModel->setStoreId($storeId ?? 1)
                ->setExpirationDate($tokenExpiration)
                ->setToken($responseBody['access_token'])
                ->setScope($scope);
            $tokenResourceModel = $this->tokenModelFactory->create();
            $tokenResourceModel->save($tokenModel);

            // Log Debug Mode
            $this->helper->logDebug("Uber Generate Token Request: " . json_encode($requestData));
            $this->helper->logDebug("Uber Generate Token Response: " . json_encode($responseBody));

            // Return
            return $tokenModel;
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
            return null;
        }
    }

    /**
     * validateToken
     * @param $token
     * @return bool
     */
    private function validateToken($token): bool
    {
        // Validate Expiration Date
        $tokenExpirationDate = $token->getExpirationDate();

        // Get Current Date
        $currentDateTime = $this->timezoneInterface->date()->format('Y-m-d H:i:s');

        // Validate Expiration
        if (strtotime($currentDateTime) >= strtotime($tokenExpirationDate)) {
            return false;
        }

        // Valid token
        return true;
    }
}
