<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DynamicContentBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\TranslationEntityInterface;
use Mautic\CoreBundle\Entity\TranslationEntityTrait;
use Mautic\CoreBundle\Entity\VariantEntityInterface;
use Mautic\CoreBundle\Entity\VariantEntityTrait;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class DynamicContent extends FormEntity implements VariantEntityInterface, TranslationEntityInterface
{
    use TranslationEntityTrait;
    use VariantEntityTrait;

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var \Mautic\CategoryBundle\Entity\Category
     **/
    private $category;

    /**
     * @var \DateTime
     */
    private $publishUp;

    /**
     * @var \DateTime
     */
    private $publishDown;

    /**
     * @var string
     */
    private $content;

    /**
     * @var int
     */
    private $sentCount = 0;

    /**
     * @var ArrayCollection
     */
    private $stats;

    /**
     * DynamicContent constructor.
     */
    public function __construct()
    {
        $this->stats           = new ArrayCollection();
        $this->variantChildren = new ArrayCollection();
    }

    /**
     * Clone method.
     */
    public function __clone()
    {
        $this->id              = null;
        $this->sentCount       = 0;
        $this->stats           = new ArrayCollection();
        $this->variantChildren = new ArrayCollection();

        parent::__clone();
    }

    /**
     * Clear stats.
     */
    public function clearStats()
    {
        $this->stats = new ArrayCollection();
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('dynamic_content')
            ->setCustomRepositoryClass('Mautic\DynamicContentBundle\Entity\DynamicContentRepository');

        $builder->addIdColumns();

        $builder->addCategory();

        $builder->addPublishDates();

        $builder->createField('sentCount', 'integer')
            ->columnName('sent_count')
            ->build();

        $builder->createField('content', 'text')
            ->columnName('content')
            ->nullable()
            ->build();

        $builder->createOneToMany('stats', 'Stat')
            ->setIndexBy('id')
            ->mappedBy('dynamicContent')
            ->cascadePersist()
            ->fetchExtraLazy()
            ->build();

        self::addTranslationMetadata($builder, self::class);
        self::addVariantMetadata($builder, self::class);
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetaData(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('name', new NotBlank(['message' => 'mautic.core.name.required']));
    }

    /**
     * @param ApiMetadataDriver $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('dwc')
            ->addListProperties([
                'id',
                'name',
                'category',
            ])
            ->addProperties([
                'publishUp',
                'publishDown',
                'sentCount',
                'variantParent',
                'variantChildren',
                'content',
            ])
            ->setMaxDepth(1, 'variantParent')
            ->setMaxDepth(1, 'variantChildren')
            ->build();
    }

    /**
     * @param $prop
     * @param $val
     */
    protected function isChanged($prop, $val)
    {
        $getter  = 'get'.ucfirst($prop);
        $current = $this->$getter();

        if ($prop == 'variantParent' || $prop == 'translationParent' || $prop == 'category') {
            $currentId = ($current) ? $current->getId() : '';
            $newId     = ($val) ? $val->getId() : null;
            if ($currentId != $newId) {
                $this->changes[$prop] = [$currentId, $newId];
            }
        } else {
            parent::isChanged($prop, $val);
        }
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return \Mautic\CategoryBundle\Entity\Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param \Mautic\CategoryBundle\Entity\Category $category
     *
     * @return $this
     */
    public function setCategory($category)
    {
        $this->isChanged('category', $category);
        $this->category = $category;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getPublishUp()
    {
        return $this->publishUp;
    }

    /**
     * @param \DateTime $publishUp
     *
     * @return $this
     */
    public function setPublishUp($publishUp)
    {
        $this->isChanged('publishUp', $publishUp);
        $this->publishUp = $publishUp;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getPublishDown()
    {
        return $this->publishDown;
    }

    /**
     * @param \DateTime $publishDown
     *
     * @return $this
     */
    public function setPublishDown($publishDown)
    {
        $this->isChanged('publishDown', $publishDown);
        $this->publishDown = $publishDown;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     *
     * @return $this
     */
    public function setContent($content)
    {
        $this->isChanged('content', $content);
        $this->content = $content;

        return $this;
    }

    /**
     * @param bool $includeVariants
     *
     * @return mixed
     */
    public function getSentCount($includeVariants = false)
    {
        return $includeVariants ? $this->getAccumulativeTranslationCount('getSentCount') : $this->sentCount;
    }

    /**
     * @param $sentCount
     *
     * @return $this
     */
    public function setSentCount($sentCount)
    {
        $this->sentCount = $sentCount;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getStats()
    {
        return $this->stats;
    }
}
