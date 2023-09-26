<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

namespace Improntus\Uber\Model;

use Exception;
use Improntus\Uber\Api\Data\TokenInterface;
use Improntus\Uber\Helper\Data;
use Improntus\Uber\Model\ResourceModel\Token\Collection as TokenCollection;
use Improntus\Uber\Model\ResourceModel\TokenFactory as TokenModelFactory;
use Improntus\Uber\Model\Rest\Webservice;
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
     * @param Data $helper
     * @param Webservice $ws
     * @param Resolver $storeInterface
     * @param TokenCollection $tokenCollection
     * @param TokenFactory $tokenFactory
     * @param TimezoneInterface $timezoneInterface
     * @param DateTime $dateTime
     * @param TokenModelFactory $tokenModelFactory
     */
    public function __construct(
        Data $helper,
        Webservice $ws,
        Resolver $storeInterface,
        TokenCollection $tokenCollection,
        TokenFactory $tokenFactory,
        timezoneInterface $timezoneInterface,
        DateTime $dateTime,
        TokenModelFactory $tokenModelFactory
    ) {
        $this->ws = $ws;
        $this->helper = $helper;
        $this->dateTime = $dateTime;
        $this->store = $storeInterface;
        $this->tokenFactory = $tokenFactory;
        $this->tokenCollection = $tokenCollection;
        $this->tokenModelFactory = $tokenModelFactory;
        $this->timezoneInterface = $timezoneInterface;
    }

    /**
     * createOrganization
     * @param $userData
     */
    public function createOrganization($userData)
    {
        // Get Access Token
        $token = $this->getAccessToken(null, self::SCOPE_TOKEN_ORGANIZATION);

        // Has data?
        if (is_null($token)) {
            throw new \Exception(__("An error occurred while validating/generating token"));
        }

        // Prepare Request
        $requestData = [];

        // Add Header Credentials
        $requestData['headers'] = [
            "Content-Type" => "application/json",
            "Authorization" => "Bearer {$token->getToken()}"
        ];

        // Populate Info
        $body['info'] = [
            'name' => $userData['organization_name'],
            'billing_type' => $userData['billing_type'],
            'merchant_type' => $userData['merchant_type']
        ];

        // Populate Address ONLY billing_type is BILLING_TYPE_DECENTRALIZED
        if ($userData['billing_type'] === 'BILLING_TYPE_DECENTRALIZED') {
            $body['info']['address'] = [
                'street1' => $userData['street'],
                'street2' => $userData['street2'],
                'city' => $userData['city'],
                'state' => $userData['state'],
                'zipcode' => $userData['postcode'],
                'country_iso2' => $userData['country']
            ];
        }

        // Populate Point of Contact Info
        $body['info']['point_of_contact'] = [
            'email' => $userData['email'],
            'phone_details' => [
                'phone_number' => $userData['phone_number'],
                'country_code' => $userData['phone_country_code'],
                'subscriber_number' => $userData['phone_number']
            ]
        ];

        // Populate Hierarchy Info
        $parentOrganizationId = $this->helper->getCustomerId();
        $body['hierarchy_info'] = [
            'parent_organization_id' => $parentOrganizationId
        ];

        // Populate Options
        $onBoardingLocale = str_replace('_', '-', $this->store->getDefaultLocale()) ?? 'en-us';
        $body['options'] = [
            'onboarding_invite_type' => $userData['onboarding_type'],
            'locale' => $onBoardingLocale
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
            // TODO: Log message
            $this->helper->log(__("ERROR: Generate Uber Organization: %1", json_encode($responseBody)));
        }

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
        if (is_null($token)) {
            throw new Exception(__("An error occurred while validating/generating token"));
        }

        // Prepare Request
        $requestData = [];

        // Add Header Credentials
        $requestData['headers'] = [
            "Content-Type" => "application/json",
            "Authorization" => "Bearer {$token->getToken()}"
        ];

        // Add Body to RequestData
        $requestData['body'] = json_encode($shippingData);

        // Get endpoint URL
        $createShippingEndpoint = $this->helper->buildRequestURL("customers/{$organizationId}/deliveries", $storeId);

        // Send Request
        $uberRequest = $this->ws->doRequest($createShippingEndpoint, $requestData, "POST");

        // Get Body / Content
        $responseBody = json_decode($uberRequest->getBody()->getContents(), true);

        // Opps...
        if ($uberRequest->getStatusCode() !== 200) {
            // TODO: Log message
            $this->helper->log(__("ERROR: Uber Shipping Create Request %1", json_encode($requestData)));
            $this->helper->log(__("ERROR: Uber Shipping Create Response %1", json_encode($responseBody)));
        }

        // Return Data
        return $responseBody;
    }

    /**
     * getEstimateShipping
     * @param array $shippingData
     * @param string $organizationId
     * @return mixed
     * @throws Exception
     */
    public function getEstimateShipping(array $shippingData, string $organizationId, int $storeId): mixed
    {
        // Get Access Token
        $token = $this->getAccessToken($storeId);

        // Has data?
        if (is_null($token)) {
            throw new Exception(__("An error occurred while validating/generating token"));
        }

        // Prepare Request
        $requestData = [];

        // Add Header Credentials
        $requestData['headers'] = [
            "Content-Type" => "application/json",
            "Authorization" => "Bearer {$token->getToken()}"
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
            // TODO: Log message
            $this->helper->log(__("ERROR: Uber Delivery Quote Request %1", json_encode($requestData)));
            $this->helper->log(__("ERROR: Uber Delivery Quote Response %1", json_encode($responseBody)));
        }

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
        // Get Token
        $tokenCollection = $this->tokenCollection->addFieldToFilter(TokenInterface::SCOPE, $scope);

        // Filter by StoreId?
        if (!is_null($storeId)) {
            $tokenCollection->addFieldToFilter(TokenInterface::STORE_ID, $storeId);
        }

        // Get Token & Validate
        $tokenData = $tokenCollection->getFirstItem();
        if (is_null($tokenData->getId()) || !$this->validateToken($tokenData)) {
            // Delete Current Access Token
            if ($tokenData->getId()) {
                try {
                    $tokenResourceModel = $this->tokenModelFactory->create();
                    $tokenResourceModel->delete($tokenData);
                } catch (\Exception $e) {
                    // TODO: Log message
                    $this->helper->log(__("Uber Delete Expired Token ERROR: %1", $e->getMessage()));
                }
            }

            // Generate new Access Token
            $tokenData = $this->generateAccessToken($storeId, $scope);
        }

        // Return
        return $tokenData;
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
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'form_params' => [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'client_credentials',
                'scope' => $scope
            ]
        ];

        // Send Request
        $uberRequest = $this->ws->doRequest(self::UBER_AUTH_ENDPOINT, $requestData, "POST");

        // Get Body / Content
        $responseBody = json_decode($uberRequest->getBody()->getContents(), true);

        // Request ERROR
        if ($uberRequest->getStatusCode() !== 200) {
            // TODO: Log message
            $this->helper->log(__("ERROR: Get AccessToken: %1", json_encode($responseBody)));
            return null;
        }

        try {
            // Generate Expiration Date
            $storeCurrentDateTime = $this->timezoneInterface->date()->format('Y-m-d H:i:s');

            // Add Token Expiration Days to Current Date
            $tokenExpiration = $this->dateTime->date('Y-m-d H:i:s', strtotime($storeCurrentDateTime . " +" . self::TOKEN_EXPIRATION_DAYS . " days"));

            // Save Token
            $tokenModel = $this->tokenFactory->create();
            $tokenModel->setStoreId($storeId ?? 1)
                ->setExpirationDate($tokenExpiration)
                ->setToken($responseBody['access_token'])
                ->setScope($scope);

            //$tokenModel-> TODO que paso aca
            $tokenResourceModel = $this->tokenModelFactory->create();
            $tokenResourceModel->save($tokenModel);

            // Return
            return $tokenModel;
        } catch (\Exception $e) {
            // TODO: Log message
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
