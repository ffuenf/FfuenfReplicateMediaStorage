<?php
/**
 *
 * class FfuenfReplicateMediaStorage
 *
 * @category   Shopware
 * @package    Shopware\Plugins\FfuenfReplicateMediaStorage
 * @author     Achim Rosenhagen / ffuenf - Pra & Rosenhagen GbR
 * @copyright  Copyright (c) 2020, Achim Rosenhagen / ffuenf - Pra & Rosenhagen GbR (https://www.ffuenf.de)
 *
 */

namespace FfuenfReplicateMediaStorage\Subscriber;

use Enlight\Event\SubscriberInterface;
use FfuenfCommon\Service\AbstractService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Enlight_Event_EventArgs;
use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Replicate\ReplicateAdapter;
use Symfony\Component\OptionsResolver\OptionsResolver;


class AdapterCollectionSubscriber extends AbstractService implements SubscriberInterface
{

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Collect_MediaAdapter_replicate' => 'createReplicateAdapter',
        ];
    }

    /**
     * Creates adapter instance
     *
     * @param Enlight_Event_EventArgs $args
     *
     * @return ReplicateAdapter
     */
    public function createReplicateAdapter(Enlight_Event_EventArgs $args)
    {
        $config = $args->get('config');
        $s3Options = $this->resolveS3Options($config['s3']);
        $client = new S3Client($s3Options);
        $source = new AwsS3Adapter($client, $s3Options['bucket'], $s3Options['root'], $s3Options['metaOptions']);
        $localOptions = $this->resolveLocalOptions($config['localplain']);
        $replica = new Local($localOptions['root'], LOCK_EX, Local::DISALLOW_LINKS, $localOptions);
        $adapter = new ReplicateAdapter($source, $replica);
        return $adapter;
    }
    
    /**
     * @return array
     */
    private function resolveS3Options(array $definition)
    {
        $options = new OptionsResolver();

        $options->setRequired(['root', 'credentials', 'bucket', 'region']);
        $options->setDefined(['file', 'dir', 'type', 'permissions', 'url', 'version', 'root', 'type', 'mediaUrl', 'endpoint', 'use_path_style_endpoint', 'metaOptions']);

        $options->setAllowedTypes('root', 'string');
        $options->setAllowedTypes('file', 'array');
        $options->setAllowedTypes('dir', 'array');
        $options->setAllowedTypes('mediaUrl', 'string');
        $options->setAllowedTypes('type', 'string');
        $options->setAllowedTypes('permissions', 'array');
        $options->setAllowedTypes('credentials', 'array');
        $options->setAllowedTypes('region', 'string');
        $options->setAllowedTypes('version', 'string');
        $options->setAllowedTypes('metaOptions', 'array');

        $options->setDefault('version', 'latest');
        $options->setDefault('root', '');
        $options->setDefault('endpoint', null);
        $options->setDefault('metaOptions', []);

        $config = $options->resolve($definition);
        $config['credentials'] = $this->resolveCredentialsOptions($config['credentials']);

        return $config;
    }

    /**
     * @return array
     */
    private function resolveLocalOptions(array $config)
    {
        $options = new OptionsResolver();

        $options->setRequired(['root']);
        $options->setDefined(['strategy', 'file', 'dir', 'mediaUrl', 'type', 'permissions', 'url']);

        $options->setAllowedTypes('root', 'string');
        $options->setAllowedTypes('file', 'array');
        $options->setAllowedTypes('dir', 'array');
        $options->setAllowedTypes('strategy', 'string');
        $options->setAllowedTypes('mediaUrl', 'string');
        $options->setAllowedTypes('type', 'string');
        $options->setAllowedTypes('permissions', 'array');

        $options->setDefault('file', []);
        $options->setDefault('dir', []);

        $config = $options->resolve($config);
        $config['file'] = $this->resolveFilePermissions($config['file']);
        $config['dir'] = $this->resolveDirectoryPermissions($config['dir']);

        return $config;
    }

    /**
     * @return array
     */
    private function resolveCredentialsOptions(array $credentials)
    {
        $options = new OptionsResolver();

        $options->setRequired(['key', 'secret']);

        $options->setAllowedTypes('key', 'string');
        $options->setAllowedTypes('secret', 'string');

        return $options->resolve($credentials);
    }
    

    /**
     * @return array
     */
    private function resolveFilePermissions(array $permissions)
    {
        $options = new OptionsResolver();

        $options->setDefined(['public', 'private']);

        $options->setAllowedTypes('public', 'int');
        $options->setAllowedTypes('private', 'int');

        $options->setDefault('public', 0666 & ~umask());
        $options->setDefault('private', 0600 & ~umask());

        return $options->resolve($permissions);
    }

    /**
     * @return array
     */
    private function resolveDirectoryPermissions(array $permissions)
    {
        $options = new OptionsResolver();

        $options->setDefined(['public', 'private']);

        $options->setAllowedTypes('public', 'int');
        $options->setAllowedTypes('private', 'int');

        $options->setDefault('public', 0777 & ~umask());
        $options->setDefault('private', 0700 & ~umask());

        return $options->resolve($permissions);
    }
}
