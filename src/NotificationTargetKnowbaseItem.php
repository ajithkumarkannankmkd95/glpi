<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class NotificationTargetKnowbaseItem extends NotificationTarget
{
    public function getEvents()
    {
        return [
            'newknowbase'     => __('New knowbase'),
            'deletingknowbase' => __('Deleting a knowbase'),
            'updateknowbase' => __('Update of a knowbase')
        ];
    }

    public function addNotificationTargets($entity)
    {
        $this->addGroupsToTargets($entity);
    }

    public function addDataForTemplate($event, $options = [])
    {
        $knowbase = $this->obj;

        // Define all language tags
        $this->data['##lang.knowbaseitem.url##']                = __('URL');
        $this->data['##lang.knowbaseitem.subject##']            = __('Name');
        $this->data['##lang.knowbaseitem.content##']            = __('Content');
        $this->data['##lang.knowbaseitem.categories##']         = __('Categories');
        $this->data['##lang.knowbaseitem.is_faq##']             = __('FAQ');
        $this->data['##lang.knowbaseitem.begin_date##']         = __('Begin Date');
        $this->data['##lang.knowbaseitem.end_date##']           = __('End Date');
        $this->data['##lang.knowbaseitem.numberofdocuments##']  = __('Number of documents');
        $this->data['##lang.document.name##']                   = __('Document name');
        $this->data['##lang.document.downloadurl##']            = __('Document download URL');
        $this->data['##lang.document.url##']                    = __('Document URL');
        $this->data['##lang.document.filename##']               = __('Document filename');
        $this->data['##lang.document.weblink##']                = __('Document weblink');
        $this->data['##lang.document.id##']                     = __('Document ID');
        $this->data['##lang.document.heading##']                = _n('Document heading', 'Documents headings', 2);
        $this->data['##lang.target.url##']                      = __('URL');
        $this->data['##lang.target.name##']                     = __('Name');
        $this->data['##lang.target.itemtype##']                 = _n('Type', 'Types', 2);
        $this->data['##lang.target.action##']                   = __('Event');

        // Set data
        $this->data['##knowbaseitem.url##']          = $knowbase->getLink();
        $this->data['##knowbaseitem.subject##']      = $knowbase->fields['name'];
        $this->data['##knowbaseitem.content##']      = $knowbase->fields['answer'];

        //Check all possible types of targets
        $typeSearch = [
            new Group_KnowbaseItem(),
            new KnowbaseItem_User(),
            new KnowbaseItem_Profile(),
            new Entity_KnowbaseItem(),
            new KnowbaseItem_KnowbaseItemCategory()
        ];
        $targets = $listofcategories = [];
        foreach ($typeSearch as $type) {
            foreach (
                $type->find([
                    'knowbaseitems_id' => $knowbase->getID()
                ]) as $value
            ) {
                if ($type instanceof Group_KnowbaseItem) {
                    $targets[] = Group::getById($value['groups_id']);
                } elseif ($type instanceof KnowbaseItem_User) {
                    $targets[] = User::getById($value['users_id']);
                } elseif ($type instanceof KnowbaseItem_Profile) {
                    $targets[] = Profile::getById($value['profiles_id']);
                } elseif ($type instanceof Entity_KnowbaseItem) {
                    $targets[] = Entity::getById($value['entities_id']);
                } elseif ($type instanceof KnowbaseItem_KnowbaseItemCategory) {
                    $category = KnowbaseItemCategory::getById($value['knowbaseitemcategories_id']);
                    $listofcategories[]      = $category->fields['name'];
                }
            }
        }
        foreach ($targets as $target) {
            $this->data['targets'][] = [
                '##target.url##'             => $target->getLink(),
                '##target.name##'            => $target->fields['name'],
                '##target.itemtype##'        => $target->getType()
            ];
        }
        if (!empty($listofcategories)) {
            $this->data['##knowbaseitem.categories##']      = implode(', ', $listofcategories);
        } else {
            $this->data['##knowbaseitem.categories##']      = '';
        }
        $this->data['##knowbaseitem.is_faq##']      = Dropdown::getYesNo($knowbase->fields['is_faq']);
        $this->data['##knowbaseitem.begin_date##']      = $knowbase->fields['begin_date'];
        $this->data['##knowbaseitem.end_date##']      = $knowbase->fields['end_date'];

        $documents = new Document_Item();
        $associateddocuments = $documents->find([
            'items_id' => $knowbase->getID(),
            'itemtype' => 'KnowbaseItem'
        ]);
        $this->data['##knowbaseitem.numberofdocuments##']      = count($associateddocuments);
        foreach ($associateddocuments as $docid) {
            $document = Document::getById($docid['documents_id']);
            $this->data['documents'][] = [
                '##document.downloadurl##'             => $document->getDownloadLink(),
                '##document.url##'                     => $document->getLink(),
                '##document.filename##'                => $document->fields['filename'],
                '##document.weblink##'                 => $document->fields['link'],
                '##document.id##'                      => $document->getID(),
                '##document.heading##'                 => $document->fields['name'],
                '##document.name##'                    => $document->fields['name']
            ];
        }
    }

    public function getTags()
    {
        $tags = [
            'knowbaseitem.url'                      => __('URL'),
            'knowbaseitem.categories'               => __('Categories'),
            'knowbaseitem.content'                  => __('Content'),
            'knowbaseitem.subject'                  => __('Subject'),
            'knowbaseitem.begin_date'               => __('Begin Date'),
            'knowbaseitem.end_date'                 => __('End Date'),
            'knowbaseitem.is_faq'                   => __('FAQ'),
            'knowbaseitem.numberofdocuments'        => __('Number of documents'),
            'document.name'                         => __('Document name'),
            'document.downloadurl'                  => __('Document download URL'),
            'document.url'                          => __('Document URL'),
            'document.filename'                     => __('Document filename'),
            'document.weblink'                      => __('Document weblink'),
            'document.id'                           => __('Document ID'),
            'document.heading'                      => _n('Document heading', 'Documents headings', 2),
            'target.url'                            => __('URL'),
            'target.name'                           => __('Name'),
            'target.itemtype'                       => _n('Type', 'Types', 2),
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList([
                'tag'   => $tag,
                'label' => $label,
                'value' => true,
                'events'  => ['newknowbase', 'updateknowbase']
            ]);
        }

        $foreachtags = [
            'documents' => __('Documents'),
            'targets' => __('Targets')
        ];
        foreach ($foreachtags as $tag => $label) {
            $this->addTagToList([
                'tag'     => $tag,
                'label'   => $label,
                'value'   => false,
                'foreach' => true,
                'events'  => ['newknowbase', 'updateknowbase']
            ]);
        }

        asort($this->tag_descriptions);
    }
}
