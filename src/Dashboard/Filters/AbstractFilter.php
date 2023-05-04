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

namespace Glpi\Dashboard\Filters;

use Html;
use Search;
use DBmysql;

abstract class AbstractFilter
{
    /**
     * Get the filter name
     *
     * @return string
     */
    abstract public static function getName(): string;

    /**
     * Get the html for the filter
     *
     * @return string
     */
    abstract public static function getHtml(): string;

     /**
     * Get the filter id
     *
     * @return string
     */
    abstract public static function getId(): string;

    /**
     * Get the filter criteria
     *
     * @return array
     */
    abstract public static function getCriteria(DBmysql $DB, string $table = "", array $apply_filters = []): array;


    /**
     * Get the search filter criteria
     *
     * example :
     * [
     * 'link'       => 'AND',
     * 'field'      => self::getSearchOptionID($table, 'itilcategories_id', 'glpi_itilcategories'), // itilcategory
     * 'searchtype' => 'under',
     * 'value'      => (int) $apply_filters[ItilCategoryFilter::getId()]
     * ]
     *
     * @return array
     */
    abstract public static function getSearchCriteria(DBmysql $DB, string $table = "", array $apply_filters = []): array;

    protected static function getSearchOptionID(string $table, string $name, string $tableToSearch): int
    {
        $data = Search::getOptions(getItemTypeForTable($table), true);
        $sort = [];
        foreach ($data as $ref => $opt) {
            if (isset($opt['field'])) {
                $sort[$ref] = $opt['linkfield'] . "-" . $opt['table'];
            }
        }
        return array_search($name . "-" . $tableToSearch, $sort);
    }

    /**
     * Get generic HTML for a filter
     *
     * @param string $id system name of the filter (ex "dates")
     * @param string $field html of the filter
     * @param string $label displayed label for the filter
     * @param bool   $filled
     *
     * @return string the html for the complete field
     */
    public static function field(
        string $id = "",
        string $field = "",
        string $label = "",
        bool $filled = false
    ): string {

         $rand  = mt_rand();
         $class = $filled ? "filled" : "";

         $js = <<<JAVASCRIPT
         $(function () {
            $('#filter-{$rand} input')
               .on('input', function() {
                  var str_len = $(this).val().length;
                  if (str_len > 0) {
                     $('#filter-{$rand}').addClass('filled');
                  } else {
                     $('#filter-{$rand}').removeClass('filled');
                  }

                  $(this).width((str_len + 1) * 8 );
               });

            $('#filter-{$rand}')
               .hover(function() {
                  $('.dashboard .card.filter-{$id}').addClass('filter-impacted');
               }, function() {
                  $('.dashboard .card.filter-{$id}').removeClass('filter-impacted');
               });
         });
         JAVASCRIPT;
         $js = Html::scriptBlock($js);

         $html  = <<<HTML
         <fieldset id='filter-{$rand}' class='filter $class' data-filter-id='{$id}'>
            $field
            <legend>$label</legend>
            <i class='btn btn-sm btn-icon btn-ghost-secondary ti ti-trash delete-filter'></i>
            {$js}
         </fieldset>
         HTML;

        return $html;
    }

    public static function displayList(
        string $label = "",
        string $value = "",
        string $fieldname = "",
        string $itemtype = "",
        array $add_params = []
    ): string {
        $value     = !empty($value) ? $value : null;
        $rand      = mt_rand();
        $label     = $label ?? $fieldname;
        $field     = $itemtype::dropdown([
            'name'                => $fieldname,
            'value'               => $value,
            'rand'                => $rand,
            'display'             => false,
            'display_emptychoice' => false,
            'emptylabel'          => '',
            'placeholder'         => $label,
            'on_change'           => "on_change_{$rand}()",
            'allowClear'          => true,
            'width'               => ''
        ] + $add_params);

        $js = <<<JAVASCRIPT
      var on_change_{$rand} = function() {
         var dom_elem    = $('#dropdown_{$fieldname}{$rand}');
         var selected    = dom_elem.find(':selected').val();

         Dashboard.getActiveDashboard().saveFilter('{$fieldname}', selected);

         $(dom_elem).closest("fieldset").toggleClass("filled", selected !== null)
      };

      JAVASCRIPT;
        $field .= Html::scriptBlock($js);

        return self::field($fieldname, $field, $label, $value !== null);
    }
}
