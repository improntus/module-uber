<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

namespace Improntus\Uber\Model;

use Improntus\Uber\Api\Data\WaypointInterface;
use Improntus\Uber\Model\ResourceModel\Waypoint as WaypointResourceModel;
use Magento\Framework\Model\AbstractModel;

class Waypoint extends AbstractModel implements WaypointInterface
{
    /**
     * @var string
     */
    const CACHE_TAG = 'improntus_uber_waypoint';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * @var string $_eventPrefix
     */
    protected $_eventPrefix = 'improntus_uber_waypoint';

    /**
     * @var string $_eventObject
     */
    protected $_eventObject = 'waypoint';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(WaypointResourceModel::class);
    }

    /**
     * @param int $waypointId
     */
    public function setWaypointId(int $waypointId)
    {
        return $this->setData(WaypointInterface::WAYPOINT_ID, $waypointId);
    }

    /**
     * @return array|int|mixed|null
     */
    public function getWaypointId()
    {
        return $this->getData(WaypointInterface::WAYPOINT_ID);
    }

    /**
     * @param int $storeId
     */
    public function setStoreId(int $storeId)
    {
        return $this->setData(WaypointInterface::STORE_ID, $storeId);
    }

    /**
     * @return array|int|mixed|null
     */
    public function getStoreId()
    {
        return $this->getData(WaypointInterface::STORE_ID);
    }

    /**
     * @param int $active
     * @return Waypoint|int
     */
    public function setActive(int $active)
    {
        return $this->setData(WaypointInterface::ACTIVE, $active);
    }

    /**
     * @return array|int|mixed|null
     */
    public function getActive()
    {
        return $this->getData(WaypointInterface::ACTIVE);
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        return $this->setData(WaypointInterface::NAME, $name);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getData(WaypointInterface::NAME);
    }

    /**
     * @param string $address
     */
    public function setAddress(string $address)
    {
        return $this->setData(WaypointInterface::ADDRESS, $address);
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->getData(WaypointInterface::ADDRESS);
    }

    /**
     * @param string $postcode
     * @return mixed
     */
    public function setPostcode(string $postcode)
    {
        return $this->setData(WaypointInterface::POSTCODE, $postcode);
    }

    /**
     * @return array|mixed|null
     */
    public function getPostcode()
    {
        return $this->getData(WaypointInterface::POSTCODE);
    }

    /**
     * @param string $telephone
     * @return mixed
     */
    public function setTelephone(string $telephone)
    {
        return $this->setData(WaypointInterface::TELEPHONE, $telephone);
    }

    /**
     * @return string
     */
    public function getTelephone()
    {
        return $this->getData(WaypointInterface::TELEPHONE);
    }

    /**
     * @param $latitude
     * @return mixed
     */
    public function setLatitude($latitude)
    {
        return $this->setData(WaypointInterface::LATITUDE, $latitude);
    }

    /**
     * @return array|mixed|null
     */
    public function getLatitude()
    {
        return $this->getData(WaypointInterface::LATITUDE);
    }

    /**
     * @param $longitude
     * @return mixed
     */
    public function setLongitude($longitude)
    {
        return $this->setData(WaypointInterface::LONGITUDE, $longitude);
    }

    /**
     * @return array|mixed|null
     */
    public function getLongitude()
    {
        return $this->getData(WaypointInterface::LONGITUDE);
    }

    /**
     * @param string $instructions
     * @return mixed
     */
    public function setInstructions(string $instructions)
    {
        return $this->setData(WaypointInterface::INSTRUCTIONS, $instructions);
    }

    /**
     * @return string
     */
    public function getInstructions()
    {
        return $this->getData(WaypointInterface::INSTRUCTIONS);
    }

    /**
     * @param int $mondayOpen
     */
    public function setMondayOpen(int $mondayOpen)
    {
        return $this->setData(WaypointInterface::MONDAY_OPEN, $mondayOpen);
    }

    /**
     * @return array|int|mixed|null
     */
    public function getMondayOpen()
    {
        return $this->getData(WaypointInterface::MONDAY_OPEN);
    }

    /**
     * @param int $mondayClose
     */
    public function setMondayClose(int $mondayClose)
    {
        return $this->setData(WaypointInterface::MONDAY_CLOSE, $mondayClose);
    }

    /**
     * @return array|int|mixed|null
     */
    public function getMondayClose()
    {
        return $this->getData(WaypointInterface::MONDAY_CLOSE);
    }

    /**
     * @param int $tuesdayOpen
     */
    public function setTuesdayOpen(int $tuesdayOpen)
    {
        return $this->setData(WaypointInterface::TUESDAY_OPEN, $tuesdayOpen);
    }

    /**
     * @return array|int|mixed|null
     */
    public function getTuesdayOpen()
    {
        return $this->getData(WaypointInterface::TUESDAY_OPEN);
    }

    /**
     * @param int $tuesdayClose
     */
    public function setTuesdayClose(int $tuesdayClose)
    {
        return $this->setData(WaypointInterface::TUESDAY_CLOSE, $tuesdayClose);
    }

    /**
     * @return array|int|mixed|null
     */
    public function getTuesdayClose()
    {
        return $this->getData(WaypointInterface::TUESDAY_CLOSE);
    }

    /**
     * @param int $wednesdayOpen
     */
    public function setWednesdayOpen(int $wednesdayOpen)
    {
        return $this->setData(WaypointInterface::WEDNESDAY_OPEN, $wednesdayOpen);
    }

    /**
     * @return array|int|mixed|null
     */
    public function getWednesdayOpen()
    {
        return $this->getData(WaypointInterface::WEDNESDAY_OPEN);
    }

    /**
     * @param int $wednesdayClose
     */
    public function setWednesdayClose(int $wednesdayClose)
    {
        return $this->setData(WaypointInterface::WEDNESDAY_CLOSE, $wednesdayClose);
    }

    /**
     * @return array|int|mixed|null
     */
    public function getWednesdayClose()
    {
        return $this->getData(WaypointInterface::WEDNESDAY_CLOSE);
    }

    /**
     * @param int $thursdayOpen
     */
    public function setThursdayOpen(int $thursdayOpen)
    {
        return $this->setData(WaypointInterface::THURSDAY_OPEN, $thursdayOpen);
    }

    /**
     * @return array|int|mixed|null
     */
    public function getThursdayOpen()
    {
        return $this->getData(WaypointInterface::THURSDAY_OPEN);
    }

    /**
     * @param int $thursdayClose
     */
    public function setThursdayClose(int $thursdayClose)
    {
        return $this->setData(WaypointInterface::THURSDAY_CLOSE, $thursdayClose);
    }

    /**
     * @return array|int|mixed|null
     */
    public function getThursdayClose()
    {
        return $this->getData(WaypointInterface::THURSDAY_CLOSE);
    }

    /**
     * @param int $fridayOpen
     */
    public function setFridayOpen(int $fridayOpen)
    {
        return $this->setData(WaypointInterface::FRIDAY_OPEN, $fridayOpen);
    }

    /**
     * @return array|int|mixed|null
     */
    public function getFridayOpen()
    {
        return $this->getData(WaypointInterface::FRIDAY_OPEN);
    }

    /**
     * @param int $fridayClose
     */
    public function setFridayClose(int $fridayClose)
    {
        return $this->setData(WaypointInterface::FRIDAY_CLOSE, $fridayClose);
    }

    /**
     * @return array|int|mixed|null
     */
    public function getFridayClose()
    {
        return $this->getData(WaypointInterface::FRIDAY_CLOSE);
    }

    /**
     * @param int $saturdayOpen
     */
    public function setSaturdayOpen(int $saturdayOpen)
    {
        return $this->setData(WaypointInterface::SATURDAY_OPEN, $saturdayOpen);
    }

    /**
     * @return array|int|mixed|null
     */
    public function getSaturdayOpen()
    {
        return $this->getData(WaypointInterface::SATURDAY_OPEN);
    }

    /**
     * @param int $saturdayClose
     */
    public function setSaturdayClose(int $saturdayClose)
    {
        return $this->setData(WaypointInterface::SATURDAY_CLOSE, $saturdayClose);
    }

    /**
     * @return array|int|mixed|null
     */
    public function getSaturdayClose()
    {
        return $this->getData(WaypointInterface::SATURDAY_CLOSE);
    }

    /**
     * @param int $sundayOpen
     */
    public function setSundayOpen(int $sundayOpen)
    {
        return $this->setData(WaypointInterface::SUNDAY_OPEN, $sundayOpen);
    }

    /**
     * @return array|int|mixed|null
     */
    public function getSundayOpen()
    {
        return $this->getData(WaypointInterface::SUNDAY_OPEN);
    }

    /**
     * @param int $sundayClose
     */
    public function setSundayClose(int $sundayClose)
    {
        return $this->setData(WaypointInterface::SUNDAY_CLOSE, $sundayClose);
    }

    /**
     * @return array|int|mixed|null
     */
    public function getSundayClose()
    {
        return $this->getData(WaypointInterface::SUNDAY_CLOSE);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getCity()
    {
        return $this->getData(WaypointInterface::CITY);
    }

    /**
     * @param string $city
     * @return Waypoint|mixed
     */
    public function setCity(string $city)
    {
        return $this->setData(WaypointInterface::CITY, $city);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getRegion()
    {
        return $this->getData(WaypointInterface::REGION);
    }

    /**
     * @param string $region
     * @return Waypoint|mixed
     */
    public function setRegion(string $region)
    {
        return $this->setData(WaypointInterface::REGION, $region);
    }

    /**
     * @param int $organizationId
     * @return mixed
     */
    public function setOrganizationId(int $organizationId)
    {
        return $this->setData(WaypointInterface::ORGANIZATION_ID, $organizationId);
    }

    /**
     * @return mixed
     */
    public function getOrganizationId()
    {
        return $this->getData(WaypointInterface::ORGANIZATION_ID);
    }
}
