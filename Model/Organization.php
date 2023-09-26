<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

namespace Improntus\Uber\Model;

use Improntus\Uber\Api\Data\OrganizationInterface;
use Improntus\Uber\Model\ResourceModel\Organization as OrganizationResourceModel;
use Magento\Framework\Model\AbstractModel;

class Organization extends AbstractModel implements OrganizationInterface
{
    /**
     * @var string
     */
    const CACHE_TAG = 'improntus_uber_organization';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * @var string $_eventPrefix
     */
    protected $_eventPrefix = 'improntus_uber_organization';

    /**
     * @var string $_eventObject
     */
    protected $_eventObject = 'organization';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(OrganizationResourceModel::class);
    }

    /**
     * @return array|mixed|null
     */
    public function getOrganizationName()
    {
        return $this->getData(OrganizationInterface::ORGANIZATION_NAME);
    }

    /**
     * @param string $organizationName
     * @return Organization|mixed
     */
    public function setOrganizationName(string $organizationName)
    {
        return $this->setData(OrganizationInterface::ORGANIZATION_NAME, $organizationName);
    }

    /**
     * @return array|mixed|null
     */
    public function getBillingType()
    {
        return $this->getData(OrganizationInterface::BILLING_TYPE);
    }

    /**
     * @param string $billingType
     * @return Organization|mixed
     */
    public function setBillingType(string $billingType)
    {
        return $this->setData(OrganizationInterface::BILLING_TYPE, $billingType);
    }

    /**
     * @return array|mixed|null
     */
    public function getMerchantType()
    {
        return $this->getData(OrganizationInterface::MERCHANT_TYPE);
    }

    /**
     * @param string $merchantType
     * @return Organization|mixed
     */
    public function setMerchantType(string $merchantType)
    {
        return $this->setData(OrganizationInterface::MERCHANT_TYPE, $merchantType);
    }

    /**
     * @return array|mixed|null
     */
    public function getEmail()
    {
        return $this->getData(OrganizationInterface::EMAIL);
    }

    /**
     * @param string $email
     * @return Organization|mixed
     */
    public function setEmail(string $email)
    {
        return $this->setData(OrganizationInterface::EMAIL, $email);
    }

    /**
     * @return array|mixed|null
     */
    public function getPhoneNumber()
    {
        return $this->getData(OrganizationInterface::PHONE_NUMBER);
    }

    /**
     * @param int $phoneNumber
     * @return Organization|mixed
     */
    public function setPhoneNumber(int $phoneNumber)
    {
        return $this->setData(OrganizationInterface::PHONE_NUMBER, $phoneNumber);
    }

    /**
     * @return array|mixed|null
     */
    public function getPhoneCountryCode()
    {
        return $this->getData(OrganizationInterface::PHONE_COUNTRY_CODE);
    }

    /**
     * @param int $phoneCountryCode
     * @return Organization|mixed
     */
    public function setPhoneCountryCode(int $phoneCountryCode)
    {
        return $this->setData(OrganizationInterface::PHONE_COUNTRY_CODE, $phoneCountryCode);
    }

    /**
     * @return array|mixed|null
     */
    public function getStreet()
    {
        return $this->getData(OrganizationInterface::STREET);
    }

    /**
     * @param string $street
     * @return Organization|mixed
     */
    public function setStreet(string $street)
    {
        return $this->setData(OrganizationInterface::STREET, $street);
    }

    /**
     * @return array|mixed|null
     */
    public function getStreet2()
    {
        return $this->getData(OrganizationInterface::STREET2);
    }

    /**
     * @param string $street2
     * @return Organization|mixed
     */
    public function setStreet2(string $street2)
    {
        return $this->setData(OrganizationInterface::STREET2, $street2);
    }

    /**
     * @return array|mixed|null
     */
    public function getState()
    {
        return $this->getData(OrganizationInterface::STATE);
    }

    /**
     * @param string $state
     * @return Organization|mixed
     */
    public function setState(string $state)
    {
        return $this->setData(OrganizationInterface::STATE, $state);
    }

    /**
     * @return array|mixed|null
     */
    public function getCity()
    {
        return $this->getData(OrganizationInterface::CITY);
    }

    /**
     * @param string $city
     * @return Organization|mixed
     */
    public function setCity(string $city)
    {
        return $this->setData(OrganizationInterface::CITY, $city);
    }

    /**
     * @return array|mixed|null
     */
    public function getPostcode()
    {
        return $this->getData(OrganizationInterface::POSTCODE);
    }

    /**
     * @param string $postcode
     * @return Organization|mixed
     */
    public function setPostcode(string $postcode)
    {
        return $this->setData(OrganizationInterface::POSTCODE, $postcode);
    }

    /**
     * @return array|mixed|null
     */
    public function getCountry()
    {
        return $this->getData(OrganizationInterface::COUNTRY);
    }

    /**
     * @param string $country
     * @return Organization|mixed
     */
    public function setCountry(string $country)
    {
        return $this->setData(OrganizationInterface::COUNTRY, $country);
    }

    /**
     * @return mixed
     */
    public function getActive()
    {
        return $this->getData(OrganizationInterface::ACTIVE);
    }

    /**
     * @param int $active
     * @return mixed
     */
    public function setActive(int $active)
    {
        return $this->setData(OrganizationInterface::ACTIVE, $active);
    }

    /**
     * @return mixed
     */
    public function getOnboardingType()
    {
        return $this->getData(OrganizationInterface::ONBOARDING_TYPE);
    }

    /**
     * @param string $onboardingType
     * @return mixed
     */
    public function setOnboardingType(string $onboardingType)
    {
        return $this->setData(OrganizationInterface::ONBOARDING_TYPE, $onboardingType);
    }

    /**
     * getUberOrganizationId
     * @return mixed
     */
    public function getUberOrganizationId()
    {
        return $this->getData(OrganizationInterface::ORGANIZATION_ID);
    }

    /**
     * setUberOrganizationId
     * @param $organizationId
     * @return mixed
     */
    public function setUberOrganizationId($organizationId)
    {
        return $this->setData(OrganizationInterface::ORGANIZATION_ID, $organizationId);
    }
}
