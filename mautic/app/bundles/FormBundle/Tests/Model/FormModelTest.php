<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Tests\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Tests\FormTestAbstract;
use Mautic\LeadBundle\Entity\LeadField;

class FormModelTest extends FormTestAbstract
{
    public function testSetFields()
    {
        $form      = new Form();
        $fields    = $this->getTestFormFields();
        $formModel = $this->getFormModel();
        $formModel->setFields($form, $fields);
        $entityFields = $form->getFields()->toArray();
        $this->assertInstanceOf(Field::class, $entityFields[array_keys($fields)[0]]);
    }

    public function testGetComponentsFields()
    {
        $formModel  = $this->getFormModel();
        $components = $formModel->getCustomComponents();
        $this->assertArrayHasKey('fields', $components);
    }

    public function testGetComponentsActions()
    {
        $formModel  = $this->getFormModel();
        $components = $formModel->getCustomComponents();
        $this->assertArrayHasKey('actions', $components);
    }

    public function testGetComponentsValidators()
    {
        $formModel  = $this->getFormModel();
        $components = $formModel->getCustomComponents();
        $this->assertArrayHasKey('validators', $components);
    }

    public function testGetEntityForNotFoundContactField()
    {
        $formModel  = $this->getFormModel();
        $formEntity = $this->createMock(Form::class);
        $fields     = new ArrayCollection();

        $formField = new Field();
        $formField->setLeadField('contactselect');
        $formField->setProperties(['syncList' => true]);

        $fields->add($formField);

        $formEntity->expects($this->exactly(2))
            ->method('getFields')
            ->willReturn($fields);

        $this->formRepository->expects($this->once())
            ->method('getEntity')
            ->with(5)
            ->willReturn($formEntity);

        $this->leadFieldModel->expects($this->once())
            ->method('getEntityByAlias')
            ->willReturn(null);

        $formModel->getEntity(5);

        $this->assertSame(['syncList' => true], $formField->getProperties());
    }

    public function testGetEntityForNotLinkedSelectField()
    {
        $formModel  = $this->getFormModel();
        $formEntity = $this->createMock(Form::class);
        $fields     = new ArrayCollection();

        $formField = new Field();
        $formField->setProperties(['syncList' => true]);

        $fields->add($formField);

        $formEntity->expects($this->exactly(2))
            ->method('getFields')
            ->willReturn($fields);

        $this->formRepository->expects($this->once())
            ->method('getEntity')
            ->with(5)
            ->willReturn($formEntity);

        $this->leadFieldModel->expects($this->never())
            ->method('getEntityByAlias');

        $formModel->getEntity(5);
    }

    public function testGetEntityForNotSyncedSelectField()
    {
        $formModel  = $this->getFormModel();
        $formEntity = $this->createMock(Form::class);
        $fields     = new ArrayCollection();

        $formField = new Field();
        $formField->setLeadField('contactselect');
        $formField->setProperties(['syncList' => false]);

        $fields->add($formField);

        $formEntity->expects($this->exactly(2))
            ->method('getFields')
            ->willReturn($fields);

        $this->formRepository->expects($this->once())
            ->method('getEntity')
            ->with(5)
            ->willReturn($formEntity);

        $this->leadFieldModel->expects($this->never())
            ->method('getEntityByAlias');

        $formModel->getEntity(5);
    }

    public function testGetEntityForSyncedBooleanField()
    {
        $formModel  = $this->getFormModel();
        $formEntity = $this->createMock(Form::class);
        $fields     = new ArrayCollection();
        $options    = ['no' => 'lunch?', 'yes' => 'dinner?'];

        $formField = new Field();
        $formField->setLeadField('contactbool');
        $formField->setProperties(['syncList' => true]);

        $fields->add($formField);

        $contactField = new LeadField();
        $contactField->setType('boolean');
        $contactField->setProperties($options);

        $formEntity->expects($this->exactly(2))
            ->method('getFields')
            ->willReturn($fields);

        $this->formRepository->expects($this->once())
            ->method('getEntity')
            ->with(5)
            ->willReturn($formEntity);

        $this->leadFieldModel->expects($this->once())
            ->method('getEntityByAlias')
            ->with('contactbool')
            ->willReturn($contactField);

        $formModel->getEntity(5);

        $this->assertSame(['lunch?', 'dinner?'], $formField->getProperties()['list']['list']);
    }

    public function testGetEntityForSyncedCountryField()
    {
        $formField = $this->standardSyncListStaticFieldTest('country');

        $this->assertArrayHasKey('Czech Republic', $formField->getProperties()['list']['list']);
    }

    public function testGetEntityForSyncedRegionField()
    {
        $formField = $this->standardSyncListStaticFieldTest('region');

        $this->assertArrayHasKey('Canada', $formField->getProperties()['list']['list']);
    }

    public function testGetEntityForSyncedTimezoneField()
    {
        $formField = $this->standardSyncListStaticFieldTest('timezone');

        $this->assertArrayHasKey('Africa', $formField->getProperties()['list']['list']);
    }

    public function testGetEntityForSyncedLocaleField()
    {
        $formField = $this->standardSyncListStaticFieldTest('locale');

        $this->assertArrayHasKey('cs_CZ', $formField->getProperties()['list']['list']);
    }

    public function testGetEntityForLinkedSyncListFields()
    {
        $this->standardSyncListFieldTest('select');
        $this->standardSyncListFieldTest('multiselect');
        $this->standardSyncListFieldTest('lookup');
    }

    private function standardSyncListFieldTest($type)
    {
        $formModel  = $this->getFormModel();
        $formEntity = $this->createMock(Form::class);
        $fields     = new ArrayCollection();
        $options    = [
            ['label' => 'label1', 'value' => 'value1'],
            ['label' => 'label2', 'value' => 'value2'],
        ];

        $formField = new Field();
        $formField->setLeadField('contactfieldalias');
        $formField->setProperties(['syncList' => true]);

        $contactField = new LeadField();
        $contactField->setType($type);
        $contactField->setProperties(['list' => $options]);

        $fields->add($formField);

        $formEntity->expects($this->exactly(2))
            ->method('getFields')
            ->willReturn($fields);

        $this->formRepository->expects($this->once())
            ->method('getEntity')
            ->with(5)
            ->willReturn($formEntity);

        $this->leadFieldModel->expects($this->once())
            ->method('getEntityByAlias')
            ->with('contactfieldalias')
            ->willReturn($contactField);

        $formModel->getEntity(5);

        $this->assertSame($options, $formField->getProperties()['list']['list']);
    }

    private function standardSyncListStaticFieldTest($type)
    {
        $formModel  = $this->getFormModel();
        $formEntity = $this->createMock(Form::class);
        $fields     = new ArrayCollection();

        $formField = new Field();
        $formField->setLeadField('contactfield');
        $formField->setProperties(['syncList' => true]);

        $fields->add($formField);

        $contactField = new LeadField();
        $contactField->setType($type);

        $formEntity->expects($this->exactly(2))
            ->method('getFields')
            ->willReturn($fields);

        $this->formRepository->expects($this->once())
            ->method('getEntity')
            ->with(5)
            ->willReturn($formEntity);

        $this->leadFieldModel->expects($this->once())
            ->method('getEntityByAlias')
            ->with('contactfield')
            ->willReturn($contactField);

        $formModel->getEntity(5);

        return $formField;
    }
}
