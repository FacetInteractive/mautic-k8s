<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($item->hasChildren() && $options['depth'] !== 0 && $item->getDisplayChildren()) {
    foreach ($item->getChildren() as $child) {
        if (!$child->isDisplayed()) {
            continue;
        }

        //builds the class attributes based on options
        $view['menu']->buildClasses($child, $matcher, $options);

        $showChildren = ($child->hasChildren() && $child->getDisplayChildren());
        $liAttributes = $child->getAttributes();
        $isAncestor   = $matcher->isAncestor($child, $options['matchingDepth']);
        $extras       = $child->getExtras();

        if (!empty($extras['divider'])) {
            echo "<li role=\"separator\" class=\"divider\"></li>\n";
        } elseif (!empty($extras['header'])) {
            echo '<li class="dropdown-header">'.$view['translator']->trans($extras['header'])."</li>\n";
        } else {
            echo "<li{$view['menu']->parseAttributes($liAttributes)}>\n";

            $url            = $child->getUri();
            $linkAttributes = $child->getLinkAttributes();
            $url            = (empty($url)) ? 'javascript:void(0);' : $url;
            if (empty($linkAttributes['target'])) {
                $linkAttributes['data-toggle'] = 'ajax';
            }
            echo "<a href=\"$url\"{$view['menu']->parseAttributes($linkAttributes)}>";
            if (!empty($extras['iconClass'])) {
                echo "<span style=\"margin-top: 4px;\" class=\"fs-14 fa {$extras['iconClass']}\"></span>";
            }

            $labelAttributes = $child->getLabelAttributes();
            if (!isset($labelAttributes['class'])) {
                $labelAttributes['class'] = '';
            }
            $labelAttributes['class'] .= ' text';
            echo "<span{$view['menu']->parseAttributes($labelAttributes)}>{$view['translator']->trans($child->getLabel())}</span>";

            echo "</a>\n";
            echo "</li>\n";
        }
    }
}
