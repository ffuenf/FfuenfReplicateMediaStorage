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

namespace FfuenfReplicateMediaStorage;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Shopware\Models\Plugin\Plugin;

class FfuenfReplicateMediaStorage extends \Shopware\Components\Plugin
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        $container->setParameter('ffuenf_replicate_media_storage.plugin_dir', $this->getPath());
        $container->setParameter('ffuenf_replicate_media_storage.view_dir', $this->getPath() . '/Resources/views');
        parent::build($container);
    }
}
