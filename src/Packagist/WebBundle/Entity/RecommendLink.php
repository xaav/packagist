<?php

/*
 * This file is part of Packagist.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *     Nils Adermann <naderman@naderman.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Packagist\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="link_recommend")
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class RecommendLink extends PackageLink
{
    /**
     * @ORM\ManyToOne(targetEntity="Packagist\WebBundle\Entity\Version", inversedBy="recommend")
     */
    protected $version;
}
