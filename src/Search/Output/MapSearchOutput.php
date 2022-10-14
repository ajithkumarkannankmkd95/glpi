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

namespace Glpi\Search\Output;

use Glpi\Toolbox\Sanitizer;

/**
 *
 * @internal Not for use outside {@link Search} class and the "Glpi\Search" namespace.
 */
final class MapSearchOutput extends HTMLSearchOutput
{
    public static function prepareInputParams(string $itemtype, array $params): array
    {
        $params = parent::prepareInputParams($itemtype, $params);

        if ($itemtype === 'Location') {
            $latitude = 21;
            $longitude = 20;
        } else if ($itemtype === 'Entity') {
            $latitude = 67;
            $longitude = 68;
        } else {
            $latitude = 998;
            $longitude = 999;
        }

        $params['criteria'][] = [
            'link'         => 'AND NOT',
            'field'        => $latitude,
            'searchtype'   => 'contains',
            'value'        => 'NULL'
        ];
        $params['criteria'][] = [
            'link'         => 'AND NOT',
            'field'        => $longitude,
            'searchtype'   => 'contains',
            'value'        => 'NULL'
        ];

        return $params;
    }

    public static function displayData(array $data, array $params): void
    {
        global $CFG_GLPI;

        $itemtype = $data['itemtype'];
        if ($data['data']['totalcount'] > 0) {
            $target = $data['search']['target'];
            $criteria = $data['search']['criteria'];
            array_pop($criteria);
            array_pop($criteria);
            $criteria[] = [
                'link'         => 'AND',
                'field'        => ($itemtype === 'Location' || $itemtype === 'Entity') ? 1 : (($itemtype === 'Ticket') ? 83 : 3),
                'searchtype'   => 'equals',
                'value'        => 'CURLOCATION'
            ];
            $globallinkto = \Toolbox::append_params(
                [
                    'criteria'     => Sanitizer::unsanitize($criteria),
                    'metacriteria' => Sanitizer::unsanitize($data['search']['metacriteria'])
                ],
                '&amp;'
            );
            $sort_params = \Toolbox::append_params([
                'sort'   => $data['search']['sort'],
                'order'  => $data['search']['order']
            ], '&amp;');
            $parameters = "as_map=0&amp;" . $sort_params . '&amp;' .
                $globallinkto;

            if (strpos($target, '?') == false) {
                $fulltarget = $target . "?" . $parameters;
            } else {
                $fulltarget = $target . "&" . $parameters;
            }
            $typename = class_exists($itemtype) ? $itemtype::getTypeName($data['data']['totalcount']) : $itemtype;

            echo "<div class='card border-top-0 rounded-0 search-as-map'>";
            echo "<div class='card-body px-0' id='map_container'>";
            echo "<small class='text-muted p-1'>" . __('Search results for localized items only') . "</small>";
            $js = "$(function() {
               var map = initMap($('#map_container'), 'map', 'full');
               _loadMap(map, '$itemtype');
            });

         var _loadMap = function(map_elt, itemtype) {
            L.AwesomeMarkers.Icon.prototype.options.prefix = 'far';
            var _micon = 'circle';

            var stdMarker = L.AwesomeMarkers.icon({
               icon: _micon,
               markerColor: 'blue'
            });

            var aMarker = L.AwesomeMarkers.icon({
               icon: _micon,
               markerColor: 'cadetblue'
            });

            var bMarker = L.AwesomeMarkers.icon({
               icon: _micon,
               markerColor: 'purple'
            });

            var cMarker = L.AwesomeMarkers.icon({
               icon: _micon,
               markerColor: 'darkpurple'
            });

            var dMarker = L.AwesomeMarkers.icon({
               icon: _micon,
               markerColor: 'red'
            });

            var eMarker = L.AwesomeMarkers.icon({
               icon: _micon,
               markerColor: 'darkred'
            });


            //retrieve geojson data
            map_elt.spin(true);
            $.ajax({
               dataType: 'json',
               method: 'POST',
               url: '{$CFG_GLPI['root_doc']}/ajax/map.php',
               data: {
                  itemtype: itemtype,
                  params: " . json_encode($params) . "
               }
            }).done(function(data) {
               var _points = data.points;
               var _markers = L.markerClusterGroup({
                  iconCreateFunction: function(cluster) {
                     var childCount = cluster.getChildCount();

                     var markers = cluster.getAllChildMarkers();
                     var n = 0;
                     for (var i = 0; i < markers.length; i++) {
                        n += markers[i].count;
                     }

                     var c = ' marker-cluster-';
                     if (n < 10) {
                        c += 'small';
                     } else if (n < 100) {
                        c += 'medium';
                     } else {
                        c += 'large';
                     }

                     return new L.DivIcon({ html: '<div><span>' + n + '</span></div>', className: 'marker-cluster' + c, iconSize: new L.Point(40, 40) });
                  }
               });

               $.each(_points, function(index, point) {
                  var _title = '<strong>' + point.title + '</strong><br/><a href=\''+'$fulltarget'.replace(/CURLOCATION/, point.loc_id)+'\'>" . sprintf(__('%1$s %2$s'), 'COUNT', $typename) . "'.replace(/COUNT/, point.count)+'</a>';
                  if (point.types) {
                     $.each(point.types, function(tindex, type) {
                        _title += '<br/>" . sprintf(__('%1$s %2$s'), 'COUNT', 'TYPE') . "'.replace(/COUNT/, type.count).replace(/TYPE/, type.name);
                     });
                  }
                  var _icon = stdMarker;
                  if (point.count < 10) {
                     _icon = stdMarker;
                  } else if (point.count < 100) {
                     _icon = aMarker;
                  } else if (point.count < 1000) {
                     _icon = bMarker;
                  } else if (point.count < 5000) {
                     _icon = cMarker;
                  } else if (point.count < 10000) {
                     _icon = dMarker;
                  } else {
                     _icon = eMarker;
                  }
                  var _marker = L.marker([point.lat, point.lng], { icon: _icon, title: point.title });
                  _marker.count = point.count;
                  _marker.bindPopup(_title);
                  _markers.addLayer(_marker);
               });

               map_elt.addLayer(_markers);
               map_elt.fitBounds(
                  _markers.getBounds(), {
                     padding: [50, 50],
                     maxZoom: 12
                  }
               );
            }).fail(function (response) {
               var _data = response.responseJSON;
               var _message = '" . __s('An error occurred loading data :(') . "';
               if (_data.message) {
                  _message = _data.message;
               }
               var fail_info = L.control();
               fail_info.onAdd = function (map) {
                  this._div = L.DomUtil.create('div', 'fail_info');
                  this._div.innerHTML = _message + '<br/><span id=\'reload_data\'><i class=\'fa fa-sync\'></i> " . __s('Reload') . "</span>';
                  return this._div;
               };
               fail_info.addTo(map_elt);
               $('#reload_data').on('click', function() {
                  $('.fail_info').remove();
                  _loadMap(map_elt);
               });
            }).always(function() {
               //hide spinner
               map_elt.spin(false);
            });
         }

         ";
            echo \Html::scriptBlock($js);
            echo "</div>"; // .card-body
            echo "</div>"; // .card
        }
    }
}
