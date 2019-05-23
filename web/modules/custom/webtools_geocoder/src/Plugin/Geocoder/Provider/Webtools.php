<?php

namespace Drupal\webtools_geocoder\Plugin\Geocoder\Provider;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\geocoder\ProviderBase;
use Geocoder\Exception\UnsupportedOperation;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Geocoder\Model\AddressFactory;

/**
 * Provides a File geocoder provider plugin.
 *
 * @GeocoderProvider(
 *   id = "webtools",
 *   name = "Webtools Geocoding Service"
 * )
 */
class Webtools extends ProviderBase {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The logger channel for Webtools geocoder.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The Geocoder AddressFactory needed to create AddressCollection object.
   *
   * @var \Geocoder\Model\AddressFactory
   */
  private $addressFactory;

  /**
   * Constructs Webtools geocoder provider plugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend used to cache geocoding data.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, CacheBackendInterface $cache_backend, ClientInterface $http_client, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $config_factory, $cache_backend);
    $this->httpClient = $http_client;
    $this->logger = $logger;
    $this->addressFactory = new AddressFactory();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('cache.geocoder'),
      $container->get('http_client'),
      $container->get('logger.factory')->get('webtools_geocoder')
    );
  }

  /**
   * Performs the geocoding.
   *
   * @param string $source
   *   The data to be geocoded.
   *
   * @return \Geocoder\Model\AddressCollection|\Geometry|mixed|void|null
   *   The coordinates of given address.
   *
   * @throws \Exception
   */
  public function doGeocode($source) {
    try {
      $chars = [" ", ","];
      $query = trim(str_replace($chars, '+', $source));
      $url = "http://europa.eu/webtools/rest/geocoding/?mode=1&address=" . $query;
      $request = $this->httpClient->get($url, ['headers' => ['Accept' => 'application/json']]);
      $response = json_decode($request->getBody());
      $data = $response->geocodingRequestsCollection[0];
      if ($data->responseCode != 200) {
        $args = [
          '@code' => $data->responseCode,
          '@error' => $data->responseMessage,
        ];
        $message = $this->t('HTTP request to Webtools Geocoder API failed.\nCode: @code\nError: @error', $args);
        $this->logger->error($message);
      }
      if ($data->foundCount == 0) {
        $args = ['@status' => $data->responseMessage, '@address' => $source];
        $message = $this->t('Webtools Geocoder API returned zero results on @address status.\nStatus: @status', $args);
        $this->logger->notice($message);
      }
      elseif (isset($data->responseMessage) && $data->responseMessage != 'OK') {
        $args = ['@status' => $data->responseMessage];
        $message = $this->t('Webtools Geocoder API returned bad status.\nStatus: @status', $args);
        $this->logger->warning($message);
      }

      if (isset($data->result->features[0]->geometry->coordinates)) {
        $coordinates[] = [
          'latitude' => $data->result->features[0]->geometry->coordinates[1],
          'longitude' => $data->result->features[0]->geometry->coordinates[0],
        ];
        return $this->addressFactory->createFromArray($coordinates);
      }

    }
    catch (\Exception $e) {
      // Just rethrow the exception, the geocoder widget handles it.
      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function doReverse($latitude, $longitude) {
    throw new UnsupportedOperation('The File plugin is not able to do reverse geocoding.');
  }

}
