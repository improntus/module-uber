<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

namespace Improntus\Uber\Api\Data;

interface OrganizationInterface
{
    const ENTITY_ID = 'entity_id';

    const ACTIVE = 'active';

    const ORGANIZATION_ID = 'organization_id';

    const ORGANIZATION_NAME = 'organization_name';

    const BILLING_TYPE = 'billing_type';

    const MERCHANT_TYPE = 'merchant_type';

    const ONBOARDING_TYPE = 'onboarding_type';

    const EMAIL = 'email';

    const PHONE_NUMBER = 'phone_number';

    const PHONE_COUNTRY_CODE = 'phone_country_code';

    const STREET = 'street';

    const STREET2 = 'street2';

    const CITY = 'city';

    const STATE = 'state';

    const POSTCODE = 'postcode';

    const COUNTRY = 'country';

    /**
     * @return mixed
     */
    public function getEntityId();

    /**
     * @param int $entityId
     * @return mixed
     */
    public function setEntityId(int $entityId);

    /**
     * @return mixed
     */
    public function getOrganizationId();

    /**
     * @param $organizationId
     * @return mixed
     */
    public function setOrganizationId($organizationId);

    /**
     * @return mixed
     */
    public function getOrganizationName();

    /**
     * @param string $organizationName
     * @return mixed
     */
    public function setOrganizationName(string $organizationName);

    /**
     * @return mixed
     */
    public function getBillingType();

    /**
     * @param string $billingType
     * @return mixed
     */
    public function setBillingType(string $billingType);

    /**
     * @return mixed
     */
    public function getMerchantType();

    /**
     * @param string $merchanType
     * @return mixed
     */
    public function setMerchantType(string $merchanType);

    /**
     * @return mixed
     */
    public function getEmail();

    /**
     * @param string $email
     * @return mixed
     */
    public function setEmail(string $email);

    /**
     * @return mixed
     */
    public function getPhoneNumber();

    /**
     * @param int $phoneNumber
     * @return mixed
     */
    public function setPhoneNumber(int $phoneNumber);

    /**
     * @return mixed
     */
    public function getPhoneCountryCode();

    /**
     * @param int $phoneCountryCode
     * @return mixed
     */
    public function setPhoneCountryCode(int $phoneCountryCode);

    /**
     * @return mixed
     */
    public function getStreet();

    /**
     * @param string $street
     * @return mixed
     */
    public function setStreet(string $street);

    /**
     * @return mixed
     */
    public function getStreet2();

    /**
     * @param string $street2
     * @return mixed
     */
    public function setStreet2(string $street2);

    /**
     * @return mixed
     */
    public function getCity();

    /**
     * @param string $city
     * @return mixed
     */
    public function setCity(string $city);

    /**
     * @return mixed
     */
    public function getState();

    /**
     * @param string $state
     * @return mixed
     */
    public function setState(string $state);

    /**
     * @return mixed
     */
    public function getPostcode();

    /**
     * @param string $postcode
     * @return mixed
     */
    public function setPostcode(string $postcode);

    /**
     * @return mixed
     */
    public function getCountry();

    /**
     * @param string $country
     * @return mixed
     */
    public function setCountry(string $country);

    /**
     * @return mixed
     */
    public function getActive();

    /**
     * @param int $active
     * @return mixed
     */
    public function setActive(int $active);

    /**
     * @return mixed
     */
    public function getOnboardingType();

    /**
     * @param string $onboardingType
     * @return mixed
     */
    public function setOnboardingType(string $onboardingType);
}
