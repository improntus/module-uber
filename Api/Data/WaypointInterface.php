<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Api\Data;

interface WaypointInterface
{
    public const WAYPOINT_ID = 'waypoint_id';
    public const ORGANIZATION_ID = 'organization_id';
    public const STORE_ID = 'store_id';
    public const NAME = 'name';
    public const ACTIVE = 'active';
    public const ADDRESS = 'address';
    public const POSTCODE = 'postcode';
    public const REGION = 'region';
    public const CITY = 'city';
    public const COUNTRY = 'country';
    public const TELEPHONE = 'telephone';
    public const LATITUDE = 'latitude';
    public const LONGITUDE = 'longitude';
    public const INSTRUCTIONS = 'instructions';
    public const MONDAY_OPEN = 'monday_open';
    public const MONDAY_CLOSE = 'monday_close';
    public const TUESDAY_OPEN = 'tuesday_open';
    public const TUESDAY_CLOSE = 'tuesday_close';
    public const WEDNESDAY_OPEN = 'wednesday_open';
    public const WEDNESDAY_CLOSE = 'wednesday_close';
    public const THURSDAY_OPEN = 'thursday_open';
    public const THURSDAY_CLOSE = 'thursday_close';
    public const FRIDAY_OPEN = 'friday_open';
    public const FRIDAY_CLOSE = 'friday_close';
    public const SATURDAY_OPEN = 'saturday_open';
    public const SATURDAY_CLOSE = 'saturday_close';
    public const SUNDAY_OPEN = 'sunday_open';
    public const SUNDAY_CLOSE = 'sunday_close';

    /**
     * @param $id
     * @return mixed
     */
    public function setId($id);

    /**
     * @return int
     */
    public function getId();

    /**
     * @param int $waypointId
     * @return int
     */
    public function setWaypointId(int $waypointId);

    /**
     * @return int
     */
    public function getWaypointId();

    /**
     * @param int $storeId
     * @return int
     */
    public function setStoreId(int $storeId);

    /**
     * @return int
     */
    public function getStoreId();

    /**
     * @param int $active
     * @return int
     */
    public function setActive(int $active);

    /**
     * @return int
     */
    public function getActive();

    /**
     * @param string $name
     * @return string
     */
    public function setName(string $name);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $address
     * @return string
     */
    public function setAddress(string $address);

    /**
     * @return string
     */
    public function getAddress();

    /**
     * @param string $postcode
     * @return mixed
     */
    public function setPostcode(string $postcode);

    /**
     * @return mixed
     */
    public function getPostcode();

    /**
     * @param string $telephone
     * @return mixed
     */
    public function setTelephone(string $telephone);

    /**
     * @return string
     */
    public function getTelephone();

    /**
     * @param $latitude
     * @return mixed
     */
    public function setLatitude($latitude);

    /**
     * @return mixed
     */
    public function getLatitude();

    /**
     * @param $longitude
     * @return mixed
     */
    public function setLongitude($longitude);

    public function getLongitude();

    /**
     * @param string $instructions
     * @return mixed
     */
    public function setInstructions(string $instructions);

    /**
     * @return string
     */
    public function getInstructions();

    /**
     * @param int $mondayOpen
     * @return int
     */
    public function setMondayOpen(int $mondayOpen);

    /**
     * @return int
     */
    public function getMondayOpen();

    /**
     * @param int $mondayClose
     * @return int
     */
    public function setMondayClose(int $mondayClose);

    /**
     * @return int
     */
    public function getMondayClose();

    /**
     * @param int $tuesdayOpen
     * @return int
     */
    public function setTuesdayOpen(int $tuesdayOpen);

    /**
     * @return int
     */
    public function getTuesdayOpen();

    /**
     * @param int $tuesdayClose
     * @return int
     */
    public function setTuesdayClose(int $tuesdayClose);

    /**
     * @return int
     */
    public function getTuesdayClose();

    /**
     * @param int $wednesdayOpen
     * @return int
     */
    public function setWednesdayOpen(int $wednesdayOpen);

    /**
     * @return int
     */
    public function getWednesdayOpen();

    /**
     * @param int $wednesdayClose
     * @return int
     */
    public function setWednesdayClose(int $wednesdayClose);

    /**
     * @return int
     */
    public function getWednesdayClose();

    /**
     * @param int $thursdayOpen
     * @return int
     */
    public function setThursdayOpen(int $thursdayOpen);

    /**
     * @return int
     */
    public function getThursdayOpen();

    /**
     * @param int $thursdayClose
     * @return int
     */
    public function setThursdayClose(int $thursdayClose);

    /**
     * @return int
     */
    public function getThursdayClose();

    /**
     * @param int $fridayOpen
     * @return int
     */
    public function setFridayOpen(int $fridayOpen);

    /**
     * @return int
     */
    public function getFridayOpen();

    /**
     * @param int $fridayClose
     * @return int
     */
    public function setFridayClose(int $fridayClose);

    /**
     * @return int
     */
    public function getFridayClose();

    /**
     * @param int $saturdayOpen
     * @return int
     */
    public function setSaturdayOpen(int $saturdayOpen);

    /**
     * @return int
     */
    public function getSaturdayOpen();

    /**
     * @param int $saturdayClose
     * @return int
     */
    public function setSaturdayClose(int $saturdayClose);

    /**
     * @return int
     */
    public function getSaturdayClose();

    /**
     * @param int $sundayOpen
     * @return int
     */
    public function setSundayOpen(int $sundayOpen);

    /**
     * @return int
     */
    public function getSundayOpen();

    /**
     * @param int $sundayClose
     * @return int
     */
    public function setSundayClose(int $sundayClose);

    /**
     * @return int
     */
    public function getSundayClose();

    /**
     * @param string $region
     * @return mixed
     */
    public function setRegion(string $region);

    /**
     * @return string
     */
    public function getRegion();

    /**
     * @param string $city
     * @return mixed
     */
    public function setCity(string $city);

    /**
     * @return string
     */
    public function getCity();

    /**
     * @param int $organizationId
     * @return mixed
     */
    public function setOrganizationId(int $organizationId);

    /**
     * @return mixed
     */
    public function getOrganizationId();

    /**
     * getCountry
     * @return mixed
     */
    public function getCountry();

    /**
     * setCountry
     * @param string $country
     * @return mixed
     */
    public function setCountry(string $country);
}
