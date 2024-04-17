<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Api\Data;

interface TokenInterface
{
    public const TOKEN_ID = 'entity_id';
    public const STORE_ID = 'store_id';
    public const EXPIRATION_DATE = 'expiration_date';
    public const TOKEN = 'token';
    public const SCOPE = 'scope';

    /**
     * @return mixed
     */
    public function getTokenId();

    /**
     * @param $tokenId
     * @return mixed
     */
    public function setTokenId($tokenId);

    /**
     * @return mixed
     */
    public function getStoreId();

    /**
     * @param int $storeId
     * @return mixed
     */
    public function setStoreId(int $storeId);

    /**
     * @return mixed
     */
    public function getExpirationDate();

    /**
     * @param string $expirationDate
     * @return mixed
     */
    public function setExpirationDate(string $expirationDate);

    /**
     * @return mixed
     */
    public function getToken();

    /**
     * @param string $token
     * @return mixed
     */
    public function setToken(string $token);

    /**
     * @return mixed
     */
    public function getScope();

    /**
     * @param string $scope
     * @return mixed
     */
    public function setScope(string $scope);
}
