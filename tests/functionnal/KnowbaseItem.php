<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

namespace test\units;

use DbTestCase;
use Glpi\Toolbox\Sanitizer;

/* Test for inc/knowbaseitem.class.php */

class KnowbaseItem extends DbTestCase
{
    public function testGetTypeName()
    {
        $expected = 'Knowledge base';
        $this->string(\KnowbaseItem::getTypeName(1))->isIdenticalTo($expected);

        $expected = 'Knowledge base';
        $this->string(\KnowbaseItem::getTypeName(0))->isIdenticalTo($expected);
        $this->string(\KnowbaseItem::getTypeName(2))->isIdenticalTo($expected);
        $this->string(\KnowbaseItem::getTypeName(10))->isIdenticalTo($expected);
    }

    public function testCleanDBonPurge()
    {
        global $DB;

        $users_id = getItemByTypeName('User', TU_USER, true);

        $kb = new \KnowbaseItem();
        $this->integer(
            (int)$kb->add([
                'name'     => 'Test to remove',
                'answer'   => 'An KB entry to remove',
                'is_faq'   => 0,
                'users_id' => $users_id,
                'date'     => '2017-10-06 12:27:48',
            ])
        )->isGreaterThan(0);

       //add some comments
        $comment = new \KnowbaseItem_Comment();
        $input = [
            'knowbaseitems_id' => $kb->getID(),
            'users_id'         => $users_id
        ];

        $id = 0;
        for ($i = 0; $i < 4; ++$i) {
            $input['comment'] = "Comment $i";
            $this->integer(
                (int)$comment->add($input)
            )->isGreaterThan($id);
            $id = (int)$comment->getID();
        }

       //change KB entry
        $this->boolean(
            $kb->update([
                'id'     => $kb->getID(),
                'answer' => 'Answer has changed'
            ])
        )->isTrue();

       //add an user
        $kbu = new \KnowbaseItem_User();
        $this->integer(
            (int)$kbu->add([
                'knowbaseitems_id'   => $kb->getID(),
                'users_id'           => $users_id
            ])
        )->isGreaterThan(0);

       //add an entity
        $kbe = new \Entity_KnowbaseItem();
        $this->integer(
            (int)$kbe->add([
                'knowbaseitems_id'   => $kb->getID(),
                'entities_id'        => 0
            ])
        )->isGreaterThan(0);

       //add a group
        $group = new \Group();
        $this->integer(
            (int)$group->add([
                'name'   => 'KB group'
            ])
        )->isGreaterThan(0);
        $kbg = new \Group_KnowbaseItem();
        $this->integer(
            (int)$kbg->add([
                'knowbaseitems_id'   => $kb->getID(),
                'groups_id'          => $group->getID()
            ])
        )->isGreaterThan(0);

       //add a profile
        $profiles_id = getItemByTypeName('Profile', 'Admin', true);
        $kbp = new \KnowbaseItem_Profile();
        $this->integer(
            (int)$kbp->add([
                'knowbaseitems_id'   => $kb->getID(),
                'profiles_id'        => $profiles_id
            ])
        )->isGreaterThan(0);

       //add an item
        $kbi = new \KnowbaseItem_Item();
        $tickets_id = getItemByTypeName('Ticket', '_ticket01', true);
        $this->integer(
            (int)$kbi->add([
                'knowbaseitems_id'   => $kb->getID(),
                'itemtype'           => 'Ticket',
                'items_id'           => $tickets_id
            ])
        )->isGreaterThan(0);

        $relations = [
            $comment->getTable(),
            \KnowbaseItem_Revision::getTable(),
            \KnowbaseItem_User::getTable(),
            \Entity_KnowbaseItem::getTable(),
            \Group_KnowbaseItem::getTable(),
            \KnowbaseItem_Profile::getTable(),
            \KnowbaseItem_Item::getTable()
        ];

       //check all relations have been created
        foreach ($relations as $relation) {
            $iterator = $DB->request([
                'FROM'   => $relation,
                'WHERE'  => ['knowbaseitems_id' => $kb->getID()]
            ]);
            $this->integer(count($iterator))->isGreaterThan(0);
        }

       //remove KB entry
        $this->boolean(
            $kb->delete(['id' => $kb->getID()], true)
        )->isTrue();

       //check all relations has been removed
        foreach ($relations as $relation) {
            $iterator = $DB->request([
                'FROM'   => $relation,
                'WHERE'  => ['knowbaseitems_id' => $kb->getID()]
            ]);
            $this->integer(count($iterator))->isIdenticalTo(0);
        }
    }

    public function testScreenshotConvertedIntoDocument()
    {

        $this->login(); // must be logged as Document_Item uses Session::getLoginUserID()

       // Test uploads for item creation
        $base64Image = base64_encode(file_get_contents(__DIR__ . '/../fixtures/uploads/foo.png'));
        $filename = '5e5e92ffd9bd91.11111111image_paste22222222.png';
        $users_id = getItemByTypeName('User', TU_USER, true);
        $instance = new \KnowbaseItem();
        $input = [
            'name'     => 'Test to remove',
            'answer'   => Sanitizer::sanitize(<<<HTML
<p>Test with a ' (add)</p>
<p><img id="3e29dffe-0237ea21-5e5e7034b1d1a1.00000000" src="data:image/png;base64,{$base64Image}" width="12" height="12"></p>
HTML
            ),
            '_filename' => [
                $filename,
            ],
            '_tag_filename' => [
                '3e29dffe-0237ea21-5e5e7034b1d1a1.00000000',
            ],
            '_prefix_filename' => [
                '5e5e92ffd9bd91.11111111',
            ],
            'is_faq'   => 0,
            'users_id' => $users_id,
            'date'     => '2017-10-06 12:27:48',
        ];
        copy(__DIR__ . '/../fixtures/uploads/foo.png', GLPI_TMP_DIR . '/' . $filename);
        $instance->add($input);
        $this->boolean($instance->isNewItem())->isFalse();
        $this->boolean($instance->getFromDB($instance->getId()))->isTrue();
        $expected = 'a href="/front/document.send.php?docid=';
        $this->string($instance->fields['answer'])->contains($expected);

       // Test uploads for item update
        $base64Image = base64_encode(file_get_contents(__DIR__ . '/../fixtures/uploads/bar.png'));
        $filename = '5e5e92ffd9bd91.44444444image_paste55555555.png';
        $tmpFilename = GLPI_TMP_DIR . '/' . $filename;
        file_put_contents($tmpFilename, base64_decode($base64Image));
        $success = $instance->update([
            'id'       => $instance->getID(),
            'answer'   => Sanitizer::sanitize(<<<HTML
<p>Test with a ' (update)</p>
<p><img id="3e29dffe-0237ea21-5e5e7034b1ffff.33333333" src="data:image/png;base64,{$base64Image}" width="12" height="12"></p>
HTML
            ),
            '_filename' => [
                $filename,
            ],
            '_tag_filename' => [
                '3e29dffe-0237ea21-5e5e7034b1ffff.33333333',
            ],
            '_prefix_filename' => [
                '5e5e92ffd9bd91.44444444',
            ],
        ]);
        $this->boolean($success)->isTrue();
        $this->boolean($instance->getFromDB($instance->getId()))->isTrue();
       // Ensure there is an anchor to the uploaded document
        $expected = 'a href="/front/document.send.php?docid=';
        $this->string($instance->fields['answer'])->contains($expected);
    }

    public function testUploadDocuments()
    {

        $this->login(); // must be logged as Document_Item uses Session::getLoginUserID()

       // Test uploads for item creation
        $filename = '5e5e92ffd9bd91.11111111' . 'foo.txt';
        $instance = new \KnowbaseItem();
        $input = [
            'name'    => 'a kb item',
            'answer' => 'testUploadDocuments',
            '_filename' => [
                $filename,
            ],
            '_tag_filename' => [
                '3e29dffe-0237ea21-5e5e7034b1ffff.00000000',
            ],
            '_prefix_filename' => [
                '5e5e92ffd9bd91.11111111',
            ]
        ];
        copy(__DIR__ . '/../fixtures/uploads/foo.txt', GLPI_TMP_DIR . '/' . $filename);
        $instance->add($input);
        $this->boolean($instance->isNewItem())->isFalse();
        $this->string($instance->fields['answer'])->contains('testUploadDocuments');
        $count = (new \DBUtils())->countElementsInTable(\Document_Item::getTable(), [
            'itemtype' => 'KnowbaseItem',
            'items_id' => $instance->getID(),
        ]);
        $this->integer($count)->isEqualTo(1);

       // Test uploads for item update (adds a 2nd document)
        $filename = '5e5e92ffd9bd91.44444444bar.txt';
        copy(__DIR__ . '/../fixtures/uploads/bar.txt', GLPI_TMP_DIR . '/' . $filename);
        $success = $instance->update([
            'id' => $instance->getID(),
            'answer' => 'update testUploadDocuments',
            '_filename' => [
                $filename,
            ],
            '_tag_filename' => [
                '3e29dffe-0237ea21-5e5e7034b1d1a1.33333333',
            ],
            '_prefix_filename' => [
                '5e5e92ffd9bd91.44444444',
            ]
        ]);
        $this->boolean($success)->isTrue();
        $this->string($instance->fields['answer'])->contains('update testUploadDocuments');
        $count = (new \DBUtils())->countElementsInTable(\Document_Item::getTable(), [
            'itemtype' => 'KnowbaseItem',
            'items_id' => $instance->getID(),
        ]);
        $this->integer($count)->isEqualTo(2);
    }

    public function testGetForCategory()
    {
        global $DB;

       // Prepare mocks
        $m_db = new \mock\DB();
        $m_kbi = new \mock\KnowbaseItem();

       // Mocked db request result
        $it = new \ArrayIterator([
            ['id' => '1'],
            ['id' => '2'],
            ['id' => '3'],
        ]);
        $this->calling($m_db)->request = $it;

       // Ignore get fromDB
        $this->calling($m_kbi)->getFromDB = true;

       // True for call 1 & 3, false for call 2 and every following calls
        $this->calling($m_kbi)->canViewItem[0] = false;
        $this->calling($m_kbi)->canViewItem[1] = true;
        $this->calling($m_kbi)->canViewItem[2] = false;
        $this->calling($m_kbi)->canViewItem[3] = true;

       // Replace global DB with mocked DB
        $DB = $m_db;

       // Expected : [1, 3]
        $this->array(\KnowbaseItem::getForCategory(1, $m_kbi))
         ->hasSize(2)
         ->containsValues([1, 3]);

       // Expected : [-1]
        $this->array(\KnowbaseItem::getForCategory(1, $m_kbi))
         ->hasSize(1)
         ->contains(-1);
    }

    protected function testGetListRequestProvider(): array
    {
        return [
            [
                'params' => [
                    'knowbaseitemcategories_id' => 0,
                    'faq' => false,
                    'contains' => "test1 ",
                ],
                'type' => 'search'
            ],
            [
                'params' => [
                    'knowbaseitemcategories_id' => 0,
                    'faq' => false,
                    'contains' => "test1 / test2 ( test3 )",
                ],
                'type' => 'search'
            ]
        ];
    }

    /**
     * @dataprovider testGetListRequestProvider
     */
    public function testGetListRequest(array $params, string $type): void
    {
        global $DB;

       // Build criteria array
        $criteria = \KnowbaseItem::getListRequest($params, $type);
        $this->array($criteria);

       // Check that the request is valid
        $DB->request($criteria);
    }

    public function testGetAnswerAnchors(): void
    {
       // Create test KB with multiple headers
        $kb_name = 'Test testGetAnswerAnchors' . mt_rand();
        $input = [
            'name' => $kb_name,
            'answer' => '<h1>title 1a</h1><h2>title2</h2><h1>title 1b</h1><h1>title 1c</h1>'
        ];
        $this->createItems('KnowbaseItem', [$input]);

       // Load KB
        /** @var \KnowbaseItem */
        $kbi = getItemByTypeName("KnowbaseItem", $kb_name);
        $answer = $kbi->getAnswer();

       // Test anchors, there should be one per header
        $this->string($answer)->contains('<h1 id="title-1a">');
        $this->string($answer)->contains('<a href="#title-1a">');
        $this->string($answer)->contains('<h2 id="title2">');
        $this->string($answer)->contains('<a href="#title2">');
        $this->string($answer)->contains('<h1 id="title-1b">');
        $this->string($answer)->contains('<a href="#title-1b">');
        $this->string($answer)->contains('<h1 id="title-1c">');
        $this->string($answer)->contains('<a href="#title-1c">');
    }

    /**
     * To be deleted after 10.1 release
     */
    public function testCreateWithCategoriesDeprecated()
    {
        $root_entity = getItemByTypeName('Entity', '_test_root_entity', true);

        // Create a KB category
        $category = $this->createItem(\KnowbaseItemCategory::class, [
            'name' => __FUNCTION__ . '_1',
            'comment' => __FUNCTION__ . '_1',
            'entities_id' => $root_entity,
            'is_recursive' => 1,
            'knowbaseitemcategories_id' => 0,
        ]);

        // Create KB item with category
        $kb_item = $this->createItem(\KnowbaseItem::class, [
            'name' => __FUNCTION__ . '_1',
            'answer' => __FUNCTION__ . '_1',
            'knowbaseitemcategories_id' => $category->getID(),
        ], ['knowbaseitemcategories_id']);

        // Get categories linked to our kb_item
        $linked_categories = (new \KnowbaseItem_KnowbaseItemCategory())->find([
            'knowbaseitems_id' => $kb_item->getID(),
        ]);

        // We expect one category
        $this->array($linked_categories)->hasSize(1);

        // Check category id
        $data = array_pop($linked_categories);
        $this->integer($data['knowbaseitemcategories_id'])->isEqualTo($category->getID());
    }

    public function testCreateWithCategories()
    {
        global $DB;

        // Create 2 new KB categories
        $kb_category = new \KnowbaseItemCategory();
        $root_entity = getItemByTypeName('Entity', '_test_root_entity', true);
        $kb_cat_id1 = $kb_category->add([
            'name' => __FUNCTION__ . '_1',
            'comment' => __FUNCTION__ . '_1',
            'entities_id' => $root_entity,
            'is_recursive' => 1,
            'knowbaseitemcategories_id' => 0,
        ]);
        $this->integer($kb_cat_id1)->isGreaterThan(0);

        $kb_cat_id2 = $kb_category->add([
            'name' => __FUNCTION__ . '_2',
            'comment' => __FUNCTION__ . '_2',
            'entities_id' => $root_entity,
            'is_recursive' => 1,
            'knowbaseitemcategories_id' => 0,
        ]);
        $this->integer($kb_cat_id2)->isGreaterThan(0);

        $kbitem = new \KnowbaseItem();
        // Create a new KB item with the first category
        $kbitems_id1 = $kbitem->add([
            'name' => __FUNCTION__ . '_1',
            'answer' => __FUNCTION__ . '_1',
            '_categories' => [$kb_cat_id1],
        ]);
        $this->integer($kbitems_id1)->isGreaterThan(0);

        // Expect the KB item to have the first category
        $iterator = $DB->request([
            'FROM' => \KnowbaseItem_KnowbaseItemCategory::getTable(),
            'WHERE' => [
                'knowbaseitems_id' => $kbitems_id1,
            ],
        ]);
        $this->integer($iterator->count())->isEqualTo(1);
        $this->integer($iterator->current()['knowbaseitemcategories_id'])->isEqualTo($kb_cat_id1);

        // Create a new KB item with both categories
        $kbitems_id2 = $kbitem->add([
            'name' => __FUNCTION__ . '_2',
            'answer' => __FUNCTION__ . '_2',
            '_categories' => [$kb_cat_id1, $kb_cat_id2],
        ]);
        $this->integer($kbitems_id2)->isGreaterThan(0);

        // Expect the KB item to have both categories
        $iterator = $DB->request([
            'FROM' => \KnowbaseItem_KnowbaseItemCategory::getTable(),
            'WHERE' => [
                'knowbaseitems_id' => $kbitems_id2,
            ],
        ]);
        $this->integer($iterator->count())->isEqualTo(2);
        $category_ids = [];
        foreach ($iterator as $row) {
            $category_ids[] = $row['knowbaseitemcategories_id'];
        }
        $this->array($category_ids)->containsValues([$kb_cat_id1, $kb_cat_id2]);
    }
}
