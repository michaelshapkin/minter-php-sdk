<?php

namespace Minter;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Minter\Library\Http;

/**
 * Class MinterAPI
 * @package Minter
 */
class MinterAPI
{
    /**
     * http requests
     */
    use Http;

    /** @var float */
    const HTTP_DEFAULT_CONNECT_TIMEOUT = 15.0;

    /** @var float */
    const HTTP_DEFAULT_TIMEOUT = 30.0;

    /**
     * MinterAPI constructor.
     * @param $node
     */
    public function __construct($node)
    {
        if ($node instanceof Client) {
            $this->setClient($node);
        } else {
            $client = $this->createDefaultHttpClient($node);
            $this->setClient($client);
        }
    }

    /**
     * @param string $baseUri
     * @return Client
     */
    public function createDefaultHttpClient(string $baseUri): Client
    {
        return new Client([
            'base_uri'        => $baseUri,
            'connect_timeout' => self::HTTP_DEFAULT_CONNECT_TIMEOUT,
            'timeout'         => self::HTTP_DEFAULT_TIMEOUT,
        ]);
    }

    /**
     * Get status of node
     *
     * @return \stdClass
     * @throws Exception
     * @throws GuzzleException
     */
    public function getStatus(): \stdClass
    {
        return $this->get('status');
    }

    /**
     * This endpoint shows candidate’s info by provided public_key.
     * It will respond with 404 code if candidate is not found.
     *
     * @param string   $publicKey
     * @param null|int $height
     * @return \stdClass
     * @throws Exception
     * @throws GuzzleException
     */
    public function getCandidate(string $publicKey, ?int $height = null): \stdClass
    {
        $params = ['pub_key' => $publicKey];

        if ($height) {
            $params['height'] = $height;
        }

        return $this->get('candidate', $params);
    }

    /**
     * Returns list of active validators
     *
     * @param null|int $height
     * @return \stdClass
     * @throws Exception
     * @throws GuzzleException
     */
    public function getValidators(?int $height = null): \stdClass
    {
        return $this->get('validators', ($height ? ['height' => $height] : null));
    }

    /**
     * Returns the balance of given account and the number of outgoing transaction.
     *
     * @param string   $address
     * @param null|int $height
     * @return \stdClass
     * @throws Exception
     * @throws GuzzleException
     */
    public function getBalance(string $address, ?int $height = null): \stdClass
    {
        $params = ['address' => $address];

        if ($height) {
            $params['height'] = $height;
        }

        return $this->get('address', $params);
    }

    /**
     * Returns nonce.
     *
     * @param string $address
     * @return int
     * @throws Exception
     * @throws GuzzleException
     */
    public function getNonce(string $address): int
    {
        return $this->getBalance($address)->result->transaction_count + 1;
    }

    /**
     * Sends transaction to the Minter Network.
     *
     * @param string $tx
     * @return \stdClass
     * @throws Exception
     * @throws GuzzleException
     */
    public function send(string $tx): \stdClass
    {
        return $this->get('send_transaction', ['tx' => $tx]);
    }

    /**
     * Returns transaction info.
     *
     * @param string $hash
     * @return \stdClass
     * @throws Exception
     * @throws GuzzleException
     */
    public function getTransaction(string $hash): \stdClass
    {
        return $this->get('transaction', ['hash' => $hash]);
    }

    /**
     * Returns block data at given height.
     *
     * @param int $height
     * @return \stdClass
     * @throws Exception
     * @throws GuzzleException
     */
    public function getBlock(int $height): \stdClass
    {
        return $this->get('block', ['height' => $height]);
    }

    /**
     * Returns events at given height.
     *
     * @param int $height
     * @return \stdClass
     * @throws Exception
     * @throws GuzzleException
     */
    public function getEvents(int $height): \stdClass
    {
        return $this->get('events', ['height' => $height]);
    }

    /**
     * Returns list of candidates.
     *
     * @param null|int  $height
     * @param bool|null $includeStakes
     * @return \stdClass
     * @throws Exception
     * @throws GuzzleException
     */
    public function getCandidates(?int $height = null, ?bool $includeStakes = false): \stdClass
    {
        $params = [];

        if ($includeStakes) {
            $params['include_stakes'] = 'true';
        }

        if ($height) {
            $params['height'] = $height;
        }

        return $this->get('candidates', $params);
    }

    /**
     * Returns information about coin.
     * Note: this method does not return information about base coins (MNT and BIP).
     *
     * @param null|int $height
     * @param string   $symbol
     * @return \stdClass
     * @throws Exception
     * @throws GuzzleException
     */
    public function getCoinInfo(string $symbol, ?int $height = null): \stdClass
    {
        $params = ['symbol' => $symbol];

        if ($height) {
            $params['height'] = $height;
        }

        return $this->get('coin_info', $params);
    }

    /**
     * Return estimate of sell coin transaction.
     *
     * @param string   $coinToSell
     * @param string   $valueToSell
     * @param string   $coinToBuy
     * @param null|int $height
     * @return \stdClass
     * @throws Exception
     * @throws GuzzleException
     */
    public function estimateCoinSell(
        string $coinToSell,
        string $valueToSell,
        string $coinToBuy,
        ?int $height = null
    ): \stdClass {
        $params = [
            'coin_to_sell'  => $coinToSell,
            'value_to_sell' => $valueToSell,
            'coin_to_buy'   => $coinToBuy
        ];

        if ($height) {
            $params['height'] = $height;
        }

        return $this->get('estimate_coin_sell', $params);
    }

    /**
     * Return estimate of buy coin transaction.
     *
     * @param string   $coinToSell
     * @param string   $valueToBuy
     * @param string   $coinToBuy
     * @param null|int $height
     * @return \stdClass
     * @throws Exception
     * @throws GuzzleException
     */
    public function estimateCoinBuy(
        string $coinToSell,
        string $valueToBuy,
        string $coinToBuy,
        ?int $height = null
    ): \stdClass {
        $params = [
            'coin_to_sell' => $coinToSell,
            'value_to_buy' => $valueToBuy,
            'coin_to_buy'  => $coinToBuy
        ];

        if ($height) {
            $params['height'] = $height;
        }

        return $this->get('estimate_coin_buy', $params);
    }

    /**
     * Return estimate of transaction.
     *
     * @param string $tx
     * @return \stdClass
     * @throws Exception
     * @throws GuzzleException
     */
    public function estimateTxCommission(string $tx): \stdClass
    {
        return $this->get('estimate_tx_commission', ['tx' => $tx]);
    }

    /**
     * Get transactions by query.
     *
     * @param string   $query
     * @param int|null $page
     * @param int|null $perPage
     * @return \stdClass
     * @throws Exception
     * @throws GuzzleException
     */
    public function getTransactions(string $query, ?int $page = null, ?int $perPage = null): \stdClass
    {
        $params = ['query' => $query];

        if ($page) {
            $params['page'] = $page;
        }

        if ($perPage) {
            $params['perPage'] = $perPage;
        }


        return $this->get('transactions', $params);
    }

    /**
     * Returns unconfirmed transactions.
     *
     * @param int|null $limit
     * @return \stdClass
     * @throws Exception
     * @throws GuzzleException
     */
    public function getUnconfirmedTxs(?int $limit = null): \stdClass
    {
        return $this->get('unconfirmed_txs', ($limit ? ['limit' => $limit] : null));
    }

    /**
     * Returns current max gas price.
     *
     * @param int|null $height
     * @return \stdClass
     * @throws Exception
     * @throws GuzzleException
     */
    public function getMaxGasPrice(?int $height = null): \stdClass
    {
        return $this->get('max_gas', ($height ? ['height' => $height] : null));
    }

    /**
     * Returns current min gas price.
     *
     * @return \stdClass
     * @throws Exception
     * @throws GuzzleException
     */
    public function getMinGasPrice(): \stdClass
    {
        return $this->get('min_gas_price');
    }

    /**
     * Returns missed blocks by validator public key.
     *
     * @param string   $pubKey
     * @param int|null $height
     * @return \stdClass
     * @throws Exception
     * @throws GuzzleException
     */
    public function getMissedBlocks(string $pubKey, ?int $height = null): \stdClass
    {
        $params = ['pub_key' => $pubKey];
        if ($height) {
            $params['height'] = $height;
        }

        return $this->get('missed_blocks', $params);
    }
}
